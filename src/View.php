<?php
namespace Nullai\Vista;

use Nullai\Vista\Engines\TemplateEngine;

class View
{
    protected array $data = [];
    protected string $folder;
    protected string $ext = 'php';
    protected null|string $file = null;
    protected null|string $engine = null;

    /**
     * View constructor.
     *
     * Take a custom file location or dot notation of view location.
     *
     * @param string $dots dot syntax or specific file path
     * @param array $data
     */
    public function __construct(string $dots, array $data = [])
    {
        if(!str_contains(':', $dots) && str_contains($dots, DIRECTORY_SEPARATOR) ) {
            $this->ext = pathinfo($dots, PATHINFO_EXTENSION);
            $this->file = pathinfo($dots, PATHINFO_FILENAME);
            $this->folder = pathinfo($dots, PATHINFO_DIRNAME);
        } else {
            $this->file = str_replace('.', DIRECTORY_SEPARATOR, $dots);
            $this->folder = str_contains(':', $dots) ? explode(':', $dots)[0] : constant('NULLAI_VISTA_ROOT');
        }

        $this->data ??= $data;
        $this->engine = defined('NULLAI_VISTA_ENGINE') ? constant('NULLAI_VISTA_ENGINE') : TemplateEngine::class;
        $this->init();
    }

    protected function init() {}

    public function data(array $data = []) : static|array
    {
        if($data) {
            $this->data = $data;
            return $this;
        }

        return $this->data;
    }

    public function folder(string $folder = '') : static|string
    {
        if($folder) {
            $this->folder = rtrim($folder, DIRECTORY_SEPARATOR);
            return $this;
        }

        return $this->folder;
    }

    public function file(string $file = '') : static|string
    {
        if($file) {
            $this->file = $file;
            return $this;
        }

        return $this->file;
    }

    public function ext(string $ext = '') : static|string
    {
        if($ext) {
            $this->ext = $ext;
            return $this;
        }

        return $this->ext;
    }

    public function engine(string $engine = '') : static|string
    {
        if($engine) {
            $this->engine = $engine;
            return $this;
        }

        return $this->engine;
    }

    public function fullPath() : string
    {
        return $this->folder . DIRECTORY_SEPARATOR . $this->file . '.' . $this->ext;
    }

    protected function render(): void
    {
        $templateEngine = $this->engine;
        (new $templateEngine($this->fullPath(), $this->data(), $this))->load();
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