<?php

namespace Tests;

use Nullai\Vista\Engines\ViewRenderEngine;
use Nullai\Vista\View;

class ViewRenderEngineTest extends VistaTestCase
{
    public function testEngineAccessibleAsThisInsideView(): void
    {
        $view = new View('engine-access');

        $this->assertSame($view->fullPath, $view->content());
    }

    public function testLayoutWithContent(): void
    {
        $content = $this->renderView('with-layout', ['content' => 'content body']);

        $this->assertStringStartsWith('<script>', $content);
        $this->assertStringContainsString('content body', $content);
        $this->assertStringEndsWith(PHP_EOL . '<footer>', $content);
    }

    public function testLayoutWithInclude(): void
    {
        $content = $this->renderView('with-layout-and-include', ['content' => 'content body']);

        $this->assertStringStartsWith('content body', $content);
        $this->assertStringContainsString('test file', $content);
        $this->assertStringEndsWith(PHP_EOL . '<footer>', $content);
    }

    public function testLayoutWithIncludeIfAndShortTag(): void
    {
        $content = $this->renderView('with-layout-and-include-if', ['content' => 'content body']);

        $this->assertStringStartsWith('<html', $content);
        $this->assertStringNotContainsString('test file &amp;', $content);
        $this->assertStringContainsString('short tag', $content);
        $this->assertStringContainsString('</html>', $content);
    }

    public function testLayoutWithTitleAndJsonEscape(): void
    {
        $content = $this->renderView('with-layout-and-title', [
            'content' => 'content body',
            'title' => 'test title',
        ]);

        $this->assertStringStartsWith('<html', $content);
        $this->assertStringContainsString('<title>test title</title>', $content);
        $this->assertStringContainsString('short tag', $content);
        $this->assertStringContainsString('console.log(\'test\');', $content);
        $this->assertStringNotContainsString('test file &amp;', $content);
        $this->assertStringContainsString('</html>', $content);
    }

    public function testRelativeIncludeNestWithGlobalAndLocalVars(): void
    {
        $content = $this->renderView('nest.level-two', ['content' => 'nested']);

        $this->assertSame('nested3test file &', $content);
    }

    public function testViewDataDoesNotOverrideEngineLocals(): void
    {
        $content = $this->renderView('with-layout', [
            'content' => 'content body',
            'layout' => 'overridden',
            'sections' => ['main' => 'overridden'],
            'currentSection' => 'overridden',
            '_data' => 'overridden',
        ]);

        $this->assertStringStartsWith('<script>', $content);
        $this->assertStringContainsString('content body', $content);
        $this->assertStringEndsWith(PHP_EOL . '<footer>', $content);
    }

    public function testIncludeAcceptsViewInstanceInsideTemplate(): void
    {
        $content = $this->renderView('with-layout-and-view-include', ['content' => 'content body']);

        $this->assertStringStartsWith('content body', $content);
        $this->assertStringContainsString('test file', $content);
        $this->assertStringEndsWith(PHP_EOL . '<footer>', $content);
    }

    public function testGetClosesItsOwnBufferWhenRenderThrows(): void
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

    public function testEndThrowsWhenNoSectionOpened(): void
    {
        $engine = new ViewRenderEngine(new View('test'));

        $this->expectException(\LogicException::class);
        $engine->end();
    }

    public function testYieldMissingSectionIsSilent(): void
    {
        $engine = new ViewRenderEngine(new View('test'));

        ob_start();
        $engine->yield('does-not-exist');
        $output = ob_get_clean();

        $this->assertSame('', $output);
    }

    public function testIncludeIfFalseReturnsFalseAndSkipsInclude(): void
    {
        $engine = new ViewRenderEngine(new View('test'));

        ob_start();
        $result = $engine->includeIf(false, 'test');
        $output = ob_get_clean();

        $this->assertFalse($result);
        $this->assertSame('', $output);
    }

    public function testIncludeIfTrueReturnsTrueAndIncludes(): void
    {
        $engine = new ViewRenderEngine(new View('test'));

        ob_start();
        $result = $engine->includeIf(true, 'test');
        $output = $this->cleanBuffer();

        $this->assertTrue($result);
        $this->assertStringContainsString('test file &', $output);
    }

    public function testToStringRendersContent(): void
    {
        $engine = new ViewRenderEngine(new View('test'));

        $this->assertStringContainsString('test file &', (string) $engine);
    }

    public function testLayoutCapturesTrailingMarkupAsDunderMainWhenMainSectionSetExplicitly(): void
    {
        $content = $this->renderView('with-main-section-and-extra');

        $this->assertStringContainsString('MAIN_CONTENT', $content);
        $this->assertStringContainsString('EXTRA_CONTENT', $content);
        $this->assertStringContainsString('MAIN_CONTENT|EXTRA_CONTENT', $content);
    }

    public function testSectionsLeakAcrossRepeatedGetCalls(): void
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

    public function testViewContentDoesNotLeakSectionsBecauseEachCallBuildsFreshEngine(): void
    {
        $view = new View('conditional-footer', ['include_footer' => true]);
        $this->assertStringContainsString('LEAKY_FOOTER', $view->content());

        $view->data = ['include_footer' => false];
        $this->assertStringNotContainsString('LEAKY_FOOTER', $view->content());
    }

    public function testIncludeIfAcceptsViewInstance(): void
    {
        $engine = new ViewRenderEngine(new View('test'));

        ob_start();
        $result = $engine->includeIf(true, new View('test'));
        $output = $this->cleanBuffer();

        $this->assertTrue($result);
        $this->assertStringContainsString('test file &', $output);
    }

    public function testIncludeIfForwardsDataToIncludedView(): void
    {
        $engine = new ViewRenderEngine(new View('test'));

        ob_start();
        $engine->includeIf(true, 'echo-var', ['label' => 'forwarded']);
        $output = ob_get_clean();

        $this->assertSame('forwarded', $output);
    }

    public function testRelativeIncludeResolvesWhenParentIsTopLevel(): void
    {
        $content = $this->renderView('relative-include-top');

        $this->assertStringContainsString('test file &', $content);
    }

    public function testIncludeDataDoesNotClobberInternalLocals(): void
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

    public function testOpeningSecondSectionBeforeEndingFirstOrphansEarlierBuffer(): void
    {
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

    public function testReopeningClosedSectionOverwritesPreviousContent(): void
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

    public function testCurrentSectionPersistsAfterEndAllowingStrayEndToEatOuterBuffer(): void
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
