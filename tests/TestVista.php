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

    public function testEngineSectionsLeakAcrossRepeatedGetCalls()
    {
        // Documents a likely bug: $sections is not reset between render() calls,
        // so a section populated by the first render survives into the second
        // even when the second render does not write to it.
        $view = new View('conditional-footer', ['include_footer' => true]);
        $engine = new ViewRenderEngine($view);

        $first = $engine->get();
        $this->assertStringContainsString('LEAKY_FOOTER', $first);

        $view->data = ['include_footer' => false];
        $second = $engine->get();

        $this->assertStringContainsString('LEAKY_FOOTER', $second, 'Leak documented: sections persist across renders.');
    }

    public function testViewContentDoesNotLeakSectionsBecauseEachCallBuildsFreshEngine()
    {
        // View::render() instantiates a new engine each call, so the leak above
        // does not reach through View::content(). This pins that guarantee.
        $view = new View('conditional-footer', ['include_footer' => true]);
        $this->assertStringContainsString('LEAKY_FOOTER', $view->content());

        $view->data = ['include_footer' => false];
        $this->assertStringNotContainsString('LEAKY_FOOTER', $view->content());
    }

    public function testIncludeIfAcceptsViewInstance()
    {
        $engine = new ViewRenderEngine(new View('test'));

        ob_start();
        $result = $engine->includeIf(true, new View('test'));
        $output = ob_get_clean();

        $this->assertTrue($result);
        $this->assertStringContainsString('test file &', $output);
    }

    public function testIncludeIfForwardsDataToIncludedView()
    {
        $engine = new ViewRenderEngine(new View('test'));

        ob_start();
        $engine->includeIf(true, 'echo-var', ['label' => 'forwarded']);
        $output = ob_get_clean();

        $this->assertSame('forwarded', $output);
    }

    public function testRelativeIncludeResolvesWhenParentIsTopLevel()
    {
        $view = new View('relative-include-top');

        $this->assertStringContainsString('test file &', $view->content());
    }

    public function testIncludeDataDoesNotClobberEngineInternalLocals()
    {
        $engine = new ViewRenderEngine(new View('test'));

        ob_start();
        $engine->include('reveal-locals', [
            '_view' => 'clobbered',
            '_data' => 'clobbered',
            '_parent_view' => 'clobbered',
            'parent' => 'clobbered',
        ]);
        $output = ob_get_clean();

        $this->assertSame('VIEW_OK|DATA_OK|PARENT_VIEW_OK|PARENT_OK', $output);
    }

    public function testOpeningSecondSectionBeforeEndingFirstOrphansEarlierBuffer()
    {
        // Opening a nested section is not guarded: end() closes only the inner
        // buffer, leaving the outer section() buffer dangling. Documented so a
        // future fix (throw on nested section(), or auto-end) has a regression test.
        $engine = new ViewRenderEngine(new View('test'));
        $baselineLevel = ob_get_level();

        $engine->section('outer');
        echo 'OUTER';
        $engine->section('inner');
        echo 'INNER';
        $engine->end();

        ob_start();
        $engine->yield('inner');
        $this->assertSame('INNER', ob_get_clean());

        ob_start();
        $engine->yield('outer');
        $this->assertSame('', ob_get_clean(), '"outer" was never captured.');

        while(ob_get_level() > $baselineLevel) {
            ob_end_clean();
        }
    }

    public function testReopeningClosedSectionOverwritesPreviousContent()
    {
        $engine = new ViewRenderEngine(new View('test'));

        $engine->section('a');
        echo 'FIRST';
        $engine->end();

        $engine->section('a');
        echo 'SECOND';
        $engine->end();

        ob_start();
        $engine->yield('a');
        $this->assertSame('SECOND', ob_get_clean());
    }

    public function testCurrentSectionPersistsAfterEndAllowingStrayEndToEatOuterBuffer()
    {
        // Likely bug: end() does not unset $currentSection, so a stray extra
        // end() passes the isset() guard and silently ob_get_clean()s whatever
        // buffer is currently topmost — potentially the caller's buffer.
        $engine = new ViewRenderEngine(new View('test'));
        $baselineLevel = ob_get_level();

        $engine->section('a');
        echo 'FIRST';
        $engine->end();

        ob_start();
        echo 'OUTER_BUFFER';
        $engine->end();

        ob_start();
        $engine->yield('a');
        $captured = ob_get_clean();

        $this->assertSame('OUTER_BUFFER', $captured, 'Stray end() swallowed and stored the outer buffer.');

        while(ob_get_level() > $baselineLevel) {
            ob_end_clean();
        }
    }
}
