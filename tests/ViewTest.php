<?php

use Nullai\Vista\Engines\ViewRenderEngine;
use Nullai\Vista\View;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;

class ViewTest extends VistaTestCase
{
    public function testInstantiation(): void
    {
        $this->assertInstanceOf(View::class, new View('test'));
    }

    public static function provideViewPathInputs(): iterable
    {
        $viewsDir = __DIR__ . '/views';

        yield 'dot notation' => [
            'input' => 'test',
            'expectedFile' => 'test',
            'expectedExt' => 'php',
            'expectedContent' => 'test file &',
        ];
        yield 'colon prefix with explicit folder' => [
            'input' => $viewsDir . ':test',
            'expectedFile' => 'test',
            'expectedExt' => 'php',
            'expectedContent' => 'test file &',
        ];
        yield 'colon prefix with empty folder falls back to NULLAI_VISTA_VIEWS_FOLDER' => [
            'input' => ':test',
            'expectedFile' => 'test',
            'expectedExt' => 'php',
            'expectedContent' => 'test file &',
        ];
        yield 'direct file path with php extension' => [
            'input' => $viewsDir . '/test.php',
            'expectedFile' => 'test',
            'expectedExt' => 'php',
            'expectedContent' => 'test file &',
        ];
        yield 'direct file path with html extension' => [
            'input' => $viewsDir . '/test.html',
            'expectedFile' => 'test',
            'expectedExt' => 'html',
            'expectedContent' => 'html-ext-content',
        ];
    }

    #[DataProvider('provideViewPathInputs')]
    public function testResolvesAndRendersForEachInputForm(
        string $input,
        string $expectedFile,
        string $expectedExt,
        string $expectedContent,
    ): void {
        $view = new View($input);

        $this->assertSame($expectedFile, $view->file);
        $this->assertSame($expectedExt, $view->ext);
        $this->assertStringEndsWith("tests/views/{$expectedFile}.{$expectedExt}", $view->fullPath);
        $this->assertStringEndsWith('tests/views', $view->folder);
        $this->assertStringContainsString($expectedContent, $view->content());
    }

    public function testDefaultEngineClass(): void
    {
        $view = new View('engine-access');

        $this->assertSame(ViewRenderEngine::class, $view->engine);
    }

    public function testContentClosesItsOwnBufferWhenRenderThrows(): void
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

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testConstructorUsesCustomEngineFromNullaiVistaEngineConstant(): void
    {
        require_once __DIR__ . '/Support/FakeEngine.php';
        define('NULLAI_VISTA_ENGINE', \Tests\Support\FakeEngine::class);

        $view = new View('test');

        $this->assertSame(\Tests\Support\FakeEngine::class, $view->engine);
        $this->assertSame('FAKE_ENGINE_OUTPUT:test', $view->content());
        $this->assertSame($view, \Tests\Support\FakeEngine::$lastView);
    }
}
