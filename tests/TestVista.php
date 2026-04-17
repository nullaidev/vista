<?php

use PHPUnit\Framework\TestCase;
use \Nullai\Vista\View;
use \Nullai\Vista\Engines\ViewRenderEngine;

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

        $this->assertStringEndsWith('tests/views/test.php', $view->fullPath);
        $this->assertStringEndsWith('tests/views', $view->folder);
        $this->assertStringEndsWith('test', $view->file);
        $this->assertStringEndsWith('php', $view->ext);

        // using override folder prefix with : delimiter, in dot notation
        $view = new View(__DIR__ . '/views' .':test');

        $this->assertStringEndsWith('tests/views/test.php', $view->fullPath);
        $this->assertStringEndsWith('tests/views', $view->folder);
        $this->assertStringEndsWith('test', $view->file);
        $this->assertStringEndsWith('php', $view->ext);

        // using direct file path
        $view = new View(__DIR__ . '/views/test.php');

        $this->assertStringEndsWith('tests/views/test.php', $view->fullPath);
        $this->assertStringEndsWith('tests/views', $view->folder);
        $this->assertStringEndsWith('test', $view->file);
        $this->assertStringEndsWith('php', $view->ext);
    }

    public function testViewContent()
    {
        $view = new View('test');

        $this->assertStringContainsString('test file &', $view->content());
    }

    public function testViewContentRelativeLookup()
    {
        $view = new View(':test');

        $this->assertStringContainsString('test file &', $view->content());
    }

    public function testViewEngineClass()
    {
        $view = new View('engine-access');

        $this->assertEquals(ViewRenderEngine::class, $view->engine);
    }

    public function testViewEngineContent()
    {
        $view = new View('engine-access');

        $this->assertEquals($view->fullPath, $view->content());
    }

    public function testViewEngineLayoutWithContent()
    {
        $view = new View('with-layout', ['content' => 'content body']);
        $content = $view->content();

        $this->assertStringStartsWith('<script>', $content);
        $this->assertStringContainsString('content body', $content);
        $this->assertStringEndsWith(PHP_EOL . '<footer>', $content);
    }

    public function testViewEngineLayoutWithInclude()
    {
        $view = new View('with-layout-and-include', ['content' => 'content body']);
        $content = $view->content();

        $this->assertStringStartsWith('content body', $content);
        $this->assertStringContainsString('test file', $content);
        $this->assertStringEndsWith(PHP_EOL . '<footer>', $content);
    }

    public function testViewEngineLayoutWithIncludeIfAndSlugify()
    {
        $view = new View('with-layout-and-include-if', ['content' => 'content body']);
        $content = (string) $view;

        $this->assertStringStartsWith('<html', $content);
        $this->assertStringNotContainsString('test file &amp;', $content);
        $this->assertStringContainsString('short tag', $content);
        $this->assertStringContainsString('</html>', $content);
    }

    public function testViewEngineLayoutWithTitleAndJsonEscape()
    {
        $view = new View('with-layout-and-title', ['content' => 'content body', 'title' => 'test title']);
        $content = (string) $view;

        $this->assertStringStartsWith('<html', $content);
        $this->assertStringContainsString('<title>test title</title>', $content);
        $this->assertStringContainsString('short tag', $content);
        $this->assertStringContainsString('console.log(\'test\');', $content);
        $this->assertStringNotContainsString('test file &amp;', $content);
        $this->assertStringContainsString('</html>', $content);
    }

    public function testViewEngineRelativeIncludeNestWithGlobalAndLocalVars()
    {
        $view = new View('nest.level-two', ['content' => 'nested']);
        $content = $view->content();

        $this->assertEquals('nested3test file &', $content);
    }

    public function testViewDataDoesNotOverrideEngineLocals()
    {
        $view = new View('with-layout', [
            'content' => 'content body',
            'layout' => 'overridden',
            'sections' => ['main' => 'overridden'],
            'currentSection' => 'overridden',
            '_data' => 'overridden',
        ]);

        $content = $view->content();

        $this->assertStringStartsWith('<script>', $content);
        $this->assertStringContainsString('content body', $content);
        $this->assertStringEndsWith(PHP_EOL . '<footer>', $content);
    }

    public function testViewEngineIncludeAcceptsViewInstance()
    {
        $view = new View('with-layout-and-view-include', ['content' => 'content body']);
        $content = $view->content();

        $this->assertStringStartsWith('content body', $content);
        $this->assertStringContainsString('test file', $content);
        $this->assertStringEndsWith(PHP_EOL . '<footer>', $content);
    }

    public function testViewContentClosesItsOwnBufferWhenRenderThrows()
    {
        $view = new View('missing-view');
        $bufferLevel = ob_get_level();

        try {
            $view->content();
            $this->fail('Expected missing view render to throw.');
        } catch (\Exception $e) {
            $this->assertSame($bufferLevel, ob_get_level());
            $this->assertStringContainsString('missing-view.php not found', $e->getMessage());
        }
    }

    public function testViewEngineGetClosesItsOwnBufferWhenRenderThrows()
    {
        $engine = new ViewRenderEngine(new View('missing-view'));
        $bufferLevel = ob_get_level();

        try {
            $engine->get();
            $this->fail('Expected missing view render to throw.');
        } catch (\Exception $e) {
            $this->assertSame($bufferLevel, ob_get_level());
            $this->assertStringContainsString('missing-view.php not found', $e->getMessage());
        }
    }
}
