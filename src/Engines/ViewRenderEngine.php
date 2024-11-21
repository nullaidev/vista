<?php

namespace Nullai\Vista\Engines;

use Nullai\Vista\View;

class ViewRenderEngine implements \Stringable
{
    public const string ENCODING = 'UTF-8';

    protected View $view {
        get => $this->view;
    }

    protected array $sections = [];
    protected string $currentSection;
    protected string $layout = '';

    /**
     * Engine constructor.
     *
     * @param View $view
     */
    public function __construct(View $view)
    {
        $this->view = $view;
    }

    public function include(string|View $view, array $data = []) : void
    {
        if(str_starts_with($view, ':')) {
            $view = $this->view->folder . '/' . pathinfo($this->view->file, PATHINFO_DIRNAME) . $view;
        }

        $_view = $view instanceof View ?: new View($view, $data);
        $_data = $_view->data;
        $_parent_view = $this->view;

        // Use parent view's file extension if none is set
        $_view->ext = $_view->ext ?: $this->view->ext;

        $cb = \Closure::bind(function() use ($_view, $_data, $_parent_view) {
            if(file_exists($_view->fullPath)) {
                $parent = $_parent_view->data;
                extract($_data);

                include $_view->fullPath;
            }
        }, $this);

        $cb();
    }

    public function escHtml($html, $flags = ENT_NOQUOTES) : string
    {
        return htmlspecialchars($html, $flags, static::ENCODING);
    }

    public function escAttr($html, $flags = ENT_QUOTES) : string
    {
        return htmlspecialchars($html, $flags, static::ENCODING);
    }

    /**
     * Filters the given HTML content, allowing only specified tags and attributes.
     *
     * Escapes attribute values and removes unsupported tags and attributes based on the provided allowed tags and attributes configuration.
     *
     * @param string|\Stringable $html The input HTML content as a string.
     * @param array|string $allowedTags A list of tags (with attributes) to allow, either as an array or a comma-separated string (a:href|style,br).
     * @param string|null $selector Optional CSS selector to specify which elements should be filtered.
     * @param int $flags Optional flags for HTML parsing and error handling, defaults to LIBXML_NOERROR | LIBXML_HTML_NOIMPLIED.
     *
     * @return string The sanitized HTML content.
     */
    public function allowTags(string|\Stringable $html, array|string $allowedTags = [], ?string $selector = null, int $flags = LIBXML_NOERROR | LIBXML_HTML_NOIMPLIED) : string
    {
        if(is_string($allowedTags)) {
            $tags = explode(',', $allowedTags);
            $allowedTags = [];

            foreach ($tags as $tag) {
                [$tag, $attributes] = explode(':', $tag, 2);
                if ($attributes) {
                    $attributes = explode('|', $attributes);

                }
                $allowedTags[$tag] = $attributes ?? [];
            }
        }

        $dom = \Dom\HTMLDocument::createFromString((string) $html, $flags);
        $allowedTagsList = array_keys($allowedTags);

        foreach ($dom->querySelectorAll($selector ?? '*') as $node) {
            if (!in_array($node->localName, $allowedTagsList)) {
                $node->parentNode->removeChild($node);
                continue;
            }

            foreach (iterator_to_array($node->attributes) as $attribute) {
                if (!$attribute->localName || !in_array($attribute->localName, $allowedTags[$node->localName] ?? [])) {
                    $node->removeAttribute($attribute->localName);
                    continue;
                }

                // PHP HTMLDocument is double encoding &
                // $attribute->value = htmlspecialchars($attribute->value, ENT_QUOTES, static::ENCODING);
            }
        }

        return $dom->saveHTML();
    }

    /**
     * Escape data for JSON encoding.
     *
     * @param mixed $data The data to be encoded.
     * @param int $flags Optional. Bitmask consisting of JSON encode options.
     * @return string The JSON encoded string.
     */
    public function escJson($data, $flags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) : string
    {
        return json_encode($data, $flags);
    }

    public function includeIf(bool $condition, mixed ...$args) : bool
    {
        if($condition) {
            $this->include(...$args);
        }

        return $condition;
    }

    public function section($name) : void
    {
        $this->currentSection = $name;
        ob_start();
    }

    public function end() : void
    {
        $this->sections[$this->currentSection] = ob_get_clean();
    }

    public function yield($section) : void
    {
        echo $this->sections[$section] ?? null;
    }

    public function layout(string $layout) : void
    {
        $this->layout = $layout;
        ob_start();
    }

    public function render() : void
    {
        $_data = $this->view->data;

        extract($_data);
        include ( $this->view->fullPath );

        if($this->layout) {
            $html = trim(ob_get_clean());

            if(empty($this->sections['main'])) {
                $this->sections['main'] = $html;
            } elseif($html) {
                $this->sections['__main'] = $html;
            }

            $this->include($this->layout);
        }
    }

    public function __toString() : string
    {
        return $this->get();
    }

    public function get() : string
    {
        ob_start();
        $this->render();
        return ob_get_clean();
    }
}