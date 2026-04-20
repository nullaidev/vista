<?php

use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
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

    public function testEngineEndThrowsWhenNoSectionOpened()
    {
        $engine = new ViewRenderEngine(new View('test'));

        $this->expectException(\LogicException::class);
        $engine->end();
    }

    public function testEngineYieldMissingSectionIsSilent()
    {
        $engine = new ViewRenderEngine(new View('test'));

        ob_start();
        $engine->yield('does-not-exist');
        $output = ob_get_clean();

        $this->assertSame('', $output);
    }

    public function testEngineIncludeIfFalseReturnsFalseAndSkipsInclude()
    {
        $engine = new ViewRenderEngine(new View('test'));

        ob_start();
        $result = $engine->includeIf(false, 'test');
        $output = ob_get_clean();

        $this->assertFalse($result);
        $this->assertSame('', $output);
    }

    public function testEngineIncludeIfTrueReturnsTrueAndIncludes()
    {
        $engine = new ViewRenderEngine(new View('test'));

        ob_start();
        $result = $engine->includeIf(true, 'test');
        $output = ob_get_clean();

        $this->assertTrue($result);
        $this->assertStringContainsString('test file &', $output);
    }

    public function testEngineToStringRendersContent()
    {
        $engine = new ViewRenderEngine(new View('test'));

        $this->assertStringContainsString('test file &', (string) $engine);
    }

    public function testLayoutCapturesTrailingMarkupAsDunderMainWhenMainSectionSetExplicitly()
    {
        $view = new View('with-main-section-and-extra');
        $content = $view->content();

        $this->assertStringContainsString('MAIN_CONTENT', $content);
        $this->assertStringContainsString('EXTRA_CONTENT', $content);
        $this->assertStringContainsString('MAIN_CONTENT|EXTRA_CONTENT', $content);
    }

    public function testViewPreservesNonPhpExtensionFromDirectPath()
    {
        $view = new View(__DIR__ . '/views/test.html');

        $this->assertSame('html', $view->ext);
        $this->assertStringEndsWith('tests/views/test.html', $view->fullPath);
        $this->assertStringContainsString('html-ext-content', $view->content());
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testConstructorUsesCustomEngineFromNullaiVistaEngineConstant()
    {
        require_once __DIR__ . '/Support/FakeEngine.php';
        define('NULLAI_VISTA_ENGINE', \Tests\Support\FakeEngine::class);

        $view = new View('test');

        $this->assertSame(\Tests\Support\FakeEngine::class, $view->engine);
        $this->assertSame('FAKE_ENGINE_OUTPUT:test', $view->content());
        $this->assertSame($view, \Tests\Support\FakeEngine::$lastView);
    }
}
