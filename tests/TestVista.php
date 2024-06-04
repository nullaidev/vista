<?php
use PHPUnit\Framework\TestCase;
use \Nullai\Vista\View;
use \Nullai\Vista\Engines\TemplateEngine;

class TestVista extends TestCase
{
    public function testViewClassConstructor()
    {
        $this->assertInstanceOf(View::class, new View('test'));
    }

    public function testViewPaths()
    {
        $view = new View('test');

        $this->assertStringEndsWith('tests/views/test.php', $view->fullPath());
        $this->assertStringEndsWith('tests/views', $view->folder());
        $this->assertStringEndsWith('test', $view->file());
        $this->assertStringEndsWith('php', $view->ext());

        $view = new View(__DIR__ . '/views' .':test');

        $this->assertStringEndsWith('tests/views/test.php', $view->fullPath());
        $this->assertStringEndsWith('tests/views', $view->folder());
        $this->assertStringEndsWith('test', $view->file());
        $this->assertStringEndsWith('php', $view->ext());

        $view = new View(__DIR__ . '/views/test.php');

        $this->assertStringEndsWith('tests/views/test.php', $view->fullPath());
        $this->assertStringEndsWith('tests/views', $view->folder());
        $this->assertStringEndsWith('test', $view->file());
        $this->assertStringEndsWith('php', $view->ext());
    }

    public function testViewContent()
    {
        $view = new View('test');

        $this->assertStringContainsString('test file', $view->get());
    }

    public function testViewEngineClass()
    {
        $view = new View('engine-access');

        $this->assertEquals(TemplateEngine::class, $view->engine());
    }

    public function testViewEngineContent()
    {
        $view = new View('engine-access');

        $this->assertEquals($view->fullPath(), $view->get());
    }
}
