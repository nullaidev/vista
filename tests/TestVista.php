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
        // using dot notation
        $view = new View('test');

        $this->assertStringEndsWith('tests/views/test.php', $view->fullPath());
        $this->assertStringEndsWith('tests/views', $view->folder());
        $this->assertStringEndsWith('test', $view->file());
        $this->assertStringEndsWith('php', $view->ext());

        // using override folder prefix with : delimiter, in dot notation
        $view = new View(__DIR__ . '/views' .':test');

        $this->assertStringEndsWith('tests/views/test.php', $view->fullPath());
        $this->assertStringEndsWith('tests/views', $view->folder());
        $this->assertStringEndsWith('test', $view->file());
        $this->assertStringEndsWith('php', $view->ext());

        // using direct file path
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

    public function testViewEngineLayoutWithContent()
    {
        $view = new View('with-layout', ['content' => 'content body']);
        $content = $view->get();

        $this->assertStringStartsWith('<script>', $content);
        $this->assertStringContainsString('content body', $content);
        $this->assertStringEndsWith(PHP_EOL . '<footer>', $content);
    }

    public function testViewEngineLayoutWithInclude()
    {
        $view = new View('with-layout-and-include', ['content' => 'content body']);
        $content = $view->get();

        $this->assertStringStartsWith('content body', $content);
        $this->assertStringContainsString('test file', $content);
        $this->assertStringEndsWith(PHP_EOL . '<footer>', $content);
    }

    public function testViewEngineLayoutWithIncludeIf()
    {
        $view = new View('with-layout-and-include-if', ['content' => 'content body']);
        $content = (string) $view;

        $this->assertStringStartsWith('<html', $content);
        $this->assertStringNotContainsString('test file', $content);
        $this->assertStringContainsString('short tag', $content);
        $this->assertStringEndsWith('html>', $content);
    }

    public function testViewEngineRelativeIncludeNestWithGlobalAndLocalVars()
    {
        $view = new View('nest.level-two', ['content' => 'nested']);
        $content = $view->get();

        $this->assertEquals('nested3test file', $content);
    }
}
