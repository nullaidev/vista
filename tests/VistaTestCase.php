<?php

namespace Tests;

use Nullai\Vista\Engines\ViewRenderEngine;
use Nullai\Vista\View;
use PHPUnit\Framework\TestCase;

abstract class VistaTestCase extends TestCase
{
    /** @param array<string, mixed> $data */
    protected function renderView(string $name, array $data = []): string
    {
        return new View($name, $data)->content();
    }

    /** @param array<string, mixed> $data */
    protected function renderEngineFor(string $name, array $data = []): string
    {
        return (string) new ViewRenderEngine(new View($name, $data));
    }

    protected function cleanBuffer(): string
    {
        $output = ob_get_clean();
        if($output === false) {
            $this->fail('Expected ob_get_clean to return a string from an active buffer.');
        }
        return $output;
    }
}
