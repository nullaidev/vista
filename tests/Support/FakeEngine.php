<?php

namespace Tests\Support;

use Nullai\Vista\Engines\RenderEngineInterface;
use Nullai\Vista\View;

class FakeEngine implements RenderEngineInterface
{
    public static ?View $lastView = null;

    public function __construct(public View $view)
    {
        self::$lastView = $view;
    }

    public function render(): void
    {
        echo 'FAKE_ENGINE_OUTPUT:' . $this->view->file;
    }
}
