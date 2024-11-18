<?php
namespace Nullai\Vista;

use Nullai\Vista\Engines\ViewRenderEngine;

class View
{
    public array $data = [] {
        get => $this->data;
        set => $this->data = $value;
    }

    public string $folder {
        get => $this->folder;
        set => $this->folder = $value;
    }

    public string $ext = 'php' {
        get => $this->ext;
        set => $this->ext = $value;
    }

    public string $file {
        get => $this->file;
        set => $this->file = $value;
    }

    public string $fullPath {
        get => $this->folder . DIRECTORY_SEPARATOR . $this->file . '.' . $this->ext;
    }

    public string $engine {
        get => $this->engine;
        set => $this->engine = $value;
    }

    /**
     * View constructor.
     *
     * Take a custom file location or dot notation of view location.
     *
     * @param string $view dot syntax or specific file path
     * @param array $data
     */
    public function __construct(string $view, array $data = [])
    {
        if(str_contains($view, ':')) {
            [$this->folder, $file] = explode(':', $view);
            $this->file = str_replace('.', DIRECTORY_SEPARATOR, $file);
        }
        elseif(str_contains($view, DIRECTORY_SEPARATOR)) {
            $this->ext = pathinfo($view, PATHINFO_EXTENSION);
            $this->file = pathinfo($view, PATHINFO_FILENAME);
            $this->folder = pathinfo($view, PATHINFO_DIRNAME);
        }
        else {
            $this->folder = constant('NULLAI_VISTA_VIEWS_FOLDER');
            $this->file = str_replace('.', DIRECTORY_SEPARATOR, $view);
        }

        $this->data = $data ?: $this->data;
        $this->engine = defined('NULLAI_VISTA_ENGINE') ? constant('NULLAI_VISTA_ENGINE') : ViewRenderEngine::class;
        $this->init();
    }

    protected function init() {}

    public function data(array $data = []) : static|array
    {
        $this->data = $data;
        return $this;
    }

    public function folder(string $folder) : static|string
    {
        $this->folder = rtrim($folder, DIRECTORY_SEPARATOR);
        return $this;
    }

    public function file(string $file) : static|string
    {
        $this->file ??= $file;
        return $this;
    }

    public function ext(string $ext) : static|string
    {
        if($ext) {
            $this->ext = $ext;
        }

        return $this;
    }

    public function engine(string $engine) : static|string
    {
        $this->engine = $engine;
        return $this;
    }

    protected function render(): void
    {
        $templateEngine = $this->engine;
        new $templateEngine($this)->render();
    }

    public function get(): string
    {
        ob_start();
        $this->render();
        return ob_get_clean();
    }

    public function __toString() : string
    {
        return $this->get();
    }
}