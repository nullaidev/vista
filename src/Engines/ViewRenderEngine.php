<?php

namespace Nullai\Vista\Engines;

use Nullai\Vista\View;

class ViewRenderEngine implements \Stringable, RenderEngineInterface
{
    protected View $view {
        get => $this->view;
    }

    /** @var array<string, string|null> */
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

    /**
     * Pulls a template part into the current view.
     *
     * Any data passed in is extracted into the included template's scope so the
     * partial can reference those values directly as variables. The including
     * (parent) view's data remains accessible inside the partial via the
     * `$parent` variable, allowing partials to read parent context without
     * colliding with their own extracted variables.
     *
     * @param string|View $view The view to include, which can be a string path or a View object.
     *                          If the string starts with ":", it is resolved relative to the parent view.
     * @param array<string, mixed> $data An associative array of data to be extracted and made available to the included view.
     *                    Defaults to an empty array.
     *
     * @return void
     */
    public function include(string|View $view, array $data = []) : void
    {
        if(is_string($view) && str_starts_with($view, ':')) {
            $view = $this->view->folder . '/' . pathinfo($this->view->file, PATHINFO_DIRNAME) . $view;
        }

        $_view = $view instanceof View ? $view : new View($view, $data);
        $_data = $_view->data;
        $_parent_view = $this->view;

        // Use parent view's file extension if none is set
        $_view->ext = $_view->ext ?: $this->view->ext;

        $cb = \Closure::bind(function() use ($_view, $_data, $_parent_view) {
            if(!file_exists($_view->fullPath)) {
                throw new \Exception("ViewRenderEngine {$_view->fullPath} not found");
            }

            // Exposes the parent view's data to the included template so
            // partials can reach up for shared context.
            $parent = $_parent_view->data;
            extract($_data, EXTR_SKIP);

            include $_view->fullPath;
        }, $this);

        $cb();
    }

    public function includeIf(bool $condition, mixed ...$args) : bool
    {
        if($condition) {
            $this->include(...$args);
        }

        return $condition;
    }

    public function section(string $name) : void
    {
        $this->currentSection = $name;
        ob_start();
    }

    public function end() : void
    {
        if(!isset($this->currentSection)) {
            throw new \LogicException('Cannot call end() before section() has opened a section.');
        }

        $captured = ob_get_clean();
        if($captured === false) {
            throw new \RuntimeException('Expected an active section output buffer.');
        }

        $this->sections[$this->currentSection] = $captured;
    }

    public function yield(string $section) : void
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

        if(!file_exists($this->view->fullPath)) {
            throw new \Exception("ViewRenderEngine {$this->view->fullPath} not found");
        }

        extract($_data, EXTR_SKIP);
        include ( $this->view->fullPath );

        if($this->layout) {
            $captured = ob_get_clean();
            if($captured === false) {
                throw new \RuntimeException('Expected an active output buffer while applying layout.');
            }
            $html = trim($captured);

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
        $bufferLevel = ob_get_level();
        ob_start();

        try {
            $this->render();
            $output = ob_get_clean();
            if($output === false) {
                throw new \RuntimeException('Expected an active output buffer.');
            }
            return $output;
        } finally {
            if(ob_get_level() > $bufferLevel) {
                ob_end_clean();
            }
        }
    }
}
