<?php

use Nullai\Vista\Engines\ViewRenderEngine;
use Nullai\Vista\View;
use PHPUnit\Framework\TestCase;

abstract class VistaTestCase extends TestCase
{
    protected function renderView(string $name, array $data = []): string
    {
        return new View($name, $data)->content();
    }

    protected function renderEngineFor(string $name, array $data = []): string
    {
        return (string) new ViewRenderEngine(new View($name, $data));
    }
}
