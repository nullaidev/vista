<?php

namespace Nullai\Vista\Engines;

use Nullai\Vista\View;

class TemplateEngine
{
    protected string $path;
    protected string $ext;
    protected View $view;
    protected string $folder;
    protected array $data = [];
    protected array $sections = [];
    protected string $currentSection;
    protected string $layout = '';

    /**
     * TemplateEngine constructor.
     *
     * @param string $file
     * @param array $data
     * @param null|View $view
     */
    public function __construct(string $path, array $data, View $view = null)
    {
        $this->path = $path;
        $this->data = $data;
        $this->view = $view;
        $this->ext = $view->ext();
        $this->folder = $view->folder();
    }

    public function data() : array
    {
        return $this->data;
    }

    public function fullPath() : string
    {
        return $this->path;
    }

    public function view() : View
    {
        return $this->view;
    }

    public function sectionIs() : string
    {
        return $this->currentSection;
    }

    public function include(string $dots, array $_data = [], string $ext = '') : void
    {
        // relative path in dot notation
        if(str_starts_with($dots, '&')) {
            $dots = $this->folder . ':' . substr($dots, 1);
        }

        $view = new View($dots, $_data);
        $view->ext($ext);
        $_view_file = $view->fullPath();

        $cb = \Closure::bind(function() use ($_view_file, $_data) {

            extract($this->data());
            extract($_data);
            unset($_data);

            if(file_exists($_view_file)) {
                include $_view_file;
            }

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
        extract( $this->data );
        /** @noinspection PhpIncludeInspection */
        include ( $this->path );

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