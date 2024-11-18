<?php

namespace Nullai\Vista\Engines;

use Nullai\Vista\View;

class ViewRenderEngine
{
    protected View $view;
    protected array $sections = [];
    protected string $currentSection;
    protected string $layout = '';

    /**
     * TemplateEngine constructor.
     *
     * @param View $view
     */
    public function __construct(View $view)
    {
        $this->view = $view;
    }

    public function data() : array
    {
        return $this->view->data;
    }

    public function view() : View
    {
        return $this->view;
    }

    public function sectionIs() : string
    {
        return $this->currentSection;
    }

    public function include(string|View $view, array $data = []) : void
    {
        $_include_view = $view instanceof View ?: new View($view, $data);
        $_parent_view_data = $this->view->data;
        $_view_data = $_include_view->data;

        $cb = \Closure::bind(function() use ($_include_view, $_parent_view_data, $_view_data) {
            if(file_exists($_include_view->fullPath)) {
                extract($_parent_view_data);
                extract($_view_data);

                include $_include_view->fullPath;
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
        $_view_data = $this->view->data;
        extract($_view_data);
        /** @noinspection PhpIncludeInspection */
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