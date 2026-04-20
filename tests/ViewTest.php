<?php

namespace Tests;

use Nullai\Vista\Engines\ViewRenderEngine;
use Nullai\Vista\View;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use Tests\Support\FakeEngine;

class ViewTest extends VistaTestCase
{
    public function testInstantiation(): void
    {
        $this->assertInstanceOf(View::class, new View('test'));
    }

    /**
     * @return iterable<string, array{input: string, expectedFile: string, expectedExt: string, expectedContent: string}>
     */
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

    public function testFolderSetterStripsTrailingDirectorySeparator(): void
    {
        $view = new View(__DIR__ . '/views' . DIRECTORY_SEPARATOR . ':test');

        $this->assertStringEndsNotWith(DIRECTORY_SEPARATOR, $view->folder);
        $this->assertStringEndsWith('tests/views/test.php', $view->fullPath);
    }

    public function testToStringPropagatesExceptionWhenViewFileMissing(): void
    {
        $view = new View('missing-view');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('missing-view.php not found');

        (string) $view;
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testConstructorUsesCustomEngineFromNullaiVistaEngineConstant(): void
    {
        define('NULLAI_VISTA_ENGINE', FakeEngine::class);

        $view = new View('test');

        $this->assertSame(FakeEngine::class, $view->engine);
        $this->assertSame('FAKE_ENGINE_OUTPUT:test', $view->content());
        $this->assertSame($view, FakeEngine::$lastView);
    }
}
