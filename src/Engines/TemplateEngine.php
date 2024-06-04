<?php

namespace Nullai\Vista\Engines;

use Nullai\Vista\View;

class TemplateEngine
{
    protected string $file;
    protected string $ext;
    protected View $view;
    protected string $folder;
    protected array $data = [];
    protected array $sections = [];
    protected string $currentSection;
    protected string $layout;

    /**
     * TemplateEngine constructor.
     *
     * @param string $file
     * @param array $data
     * @param null|View $view
     */
    public function __construct(string $file, array $data, View $view = null)
    {
        $this->file = $file;
        $this->data = $data;
        $this->view = $view;
        $this->ext = $view->ext();
        $this->folder = $view->folder();
    }

    public function data() : array
    {
        return $this->data;
    }

    public function file() : string
    {
        return $this->file;
    }

    public function view() : View
    {
        return $this->view;
    }

    public function sectionIs() : string
    {
        return $this->currentSection;
    }

    public function include(string $dots, array $_data = [], ?string $ext = null) : void
    {
        if(!str_contains('/', $dots)) {
            $dots = $this->folder . ':' . $dots;
        }

        $_view_file = (new View($dots, $_data))->ext($ext)->fullPath();

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

    public function load() : void
    {
        extract( $this->data );
        /** @noinspection PhpIncludeInspection */
        include ( $this->file );

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
        $this->load();
        return ob_get_clean();
    }
}