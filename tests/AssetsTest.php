<?php

namespace Tests;

use Nullai\Vista\Assets;
use Nullai\Vista\Engines\ViewRenderEngine;
use Nullai\Vista\View;
use PHPUnit\Framework\Attributes\DataProvider;

class AssetsTest extends VistaTestCase
{
    private ?string $originalDocumentRoot = null;

    /** @var list<string> */
    private array $tempDocumentRoots = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalDocumentRoot = $_SERVER['DOCUMENT_ROOT'] ?? null;
    }

    protected function tearDown(): void
    {
        Assets::setVersionResolver(null);
        Assets::reset();
        $this->restoreDocumentRoot();
        $this->cleanupTempDocumentRoots();

        parent::tearDown();
    }

    public function testRenderMethodsReturnEmptyStringWhenRegistryIsEmpty(): void
    {
        $this->assertSame('', Assets::renderCss());
        $this->assertSame('', Assets::renderJs());
    }

    public function testDuplicateRegistrationsRenderOncePerPath(): void
    {
        Assets::setVersionResolver(static fn(string $webPath): string => '42');

        Assets::css('/css/app.css');
        Assets::css('/css/app.css');
        Assets::js('/js/app.js');
        Assets::js('/js/app.js');

        $this->assertSame('<link rel="stylesheet" href="/css/app.css?v=42">', Assets::renderCss());
        $this->assertSame('<script src="/js/app.js?v=42"></script>', Assets::renderJs());
    }

    public function testRegistrationOrderIsPreservedWhenDuplicatesReappearLater(): void
    {
        Assets::setVersionResolver(static fn(string $webPath): string => '7');

        Assets::css('/css/first.css');
        Assets::css('/css/second.css');
        Assets::css('/css/first.css');
        Assets::css('/css/third.css');
        Assets::js('/js/alpha.js');
        Assets::js('/js/beta.js');
        Assets::js('/js/alpha.js');

        $this->assertSame(implode(PHP_EOL, [
            '<link rel="stylesheet" href="/css/first.css?v=7">',
            '<link rel="stylesheet" href="/css/second.css?v=7">',
            '<link rel="stylesheet" href="/css/third.css?v=7">',
        ]), Assets::renderCss());

        $this->assertSame(implode(PHP_EOL, [
            '<script src="/js/alpha.js?v=7"></script>',
            '<script src="/js/beta.js?v=7"></script>',
        ]), Assets::renderJs());
    }

    public function testCssAndJsRegistriesRemainIndependentForSamePath(): void
    {
        Assets::setVersionResolver(static fn(string $webPath): string => '11');

        Assets::css('/assets/runtime');
        Assets::js('/assets/runtime');

        $this->assertSame('<link rel="stylesheet" href="/assets/runtime?v=11">', Assets::renderCss());
        $this->assertSame('<script src="/assets/runtime?v=11"></script>', Assets::renderJs());
    }

    public function testRenderEscapesHrefAndSrcAttributes(): void
    {
        Assets::setVersionResolver(static fn(string $webPath): string => '"unsafe"&<>');

        Assets::css('/css/app.css?theme="quoted"&mode=<dark>');
        Assets::js('/js/app.js?name="quoted"&mode=<dark>');

        $this->assertSame('<link rel="stylesheet" href="/css/app.css?theme=&quot;quoted&quot;&amp;mode=&lt;dark&gt;&amp;v=%22unsafe%22%26%3C%3E">', Assets::renderCss());
        $this->assertSame('<script src="/js/app.js?name=&quot;quoted&quot;&amp;mode=&lt;dark&gt;&amp;v=%22unsafe%22%26%3C%3E"></script>', Assets::renderJs());
    }

    public function testExistingQueryStringsUseAmpersandForVersionParameter(): void
    {
        Assets::setVersionResolver(static fn(string $webPath): string => '99');

        Assets::css('/css/app.css?theme=dark');
        Assets::js('/js/app.js?module=faq');

        $this->assertStringContainsString('/css/app.css?theme=dark&amp;v=99', Assets::renderCss());
        $this->assertStringContainsString('/js/app.js?module=faq&amp;v=99', Assets::renderJs());
    }

    #[DataProvider('assetPathRenderingProvider')]
    public function testAssetPathRenderingHandlesQueriesFragmentsAndAbsoluteUrls(string $path, string $expectedUrl): void
    {
        Assets::setVersionResolver(static fn(string $webPath): string => '9');

        Assets::css($path);
        Assets::js($path);

        $this->assertSame('<link rel="stylesheet" href="' . $expectedUrl . '">', Assets::renderCss());
        $this->assertSame('<script src="' . $expectedUrl . '"></script>', Assets::renderJs());
    }

    public function testResetClearsRegisteredAssetsButKeepsCustomResolver(): void
    {
        Assets::setVersionResolver(static fn(string $webPath): string => '15');

        Assets::css('/css/old.css');
        Assets::js('/js/old.js');

        Assets::reset();

        $this->assertSame('', Assets::renderCss());
        $this->assertSame('', Assets::renderJs());

        Assets::css('/css/new.css');

        $this->assertSame('<link rel="stylesheet" href="/css/new.css?v=15">', Assets::renderCss());
    }

    public function testCustomResolverPersistsAcrossReset(): void
    {
        Assets::setVersionResolver(static fn(string $webPath): string => strtoupper(trim($webPath, '/')));

        Assets::css('/css/app.css');
        $this->assertStringContainsString('/css/app.css?v=CSS%2FAPP.CSS', Assets::renderCss());

        Assets::reset();
        Assets::css('/css/again.css');

        $this->assertStringContainsString('/css/again.css?v=CSS%2FAGAIN.CSS', Assets::renderCss());
    }

    public function testCustomResolverReceivesOriginalRegisteredPath(): void
    {
        $received = null;

        Assets::setVersionResolver(static function(string $webPath) use (&$received): string {
            $received = $webPath;

            return '13';
        });

        Assets::css('https://static.example.com/css/app.css?theme=dark#hero');
        Assets::renderCss();

        $this->assertSame('https://static.example.com/css/app.css?theme=dark#hero', $received);
    }

    public function testSetVersionResolverNullRestoresDefaultResolver(): void
    {
        $this->useDocumentRoot([
            '/css/app.css' => 1700002222,
        ]);

        Assets::setVersionResolver(static fn(string $webPath): string => 'custom');
        Assets::setVersionResolver(null);
        Assets::css('/css/app.css');

        $this->assertSame('<link rel="stylesheet" href="/css/app.css?v=1700002222">', Assets::renderCss());
    }

    public function testDefaultResolverFallsBackToOneForMissingFileWithoutWarnings(): void
    {
        unset($_SERVER['DOCUMENT_ROOT']);

        $css = $this->withoutWarnings(static function(): string {
            Assets::css('/css/missing.css');

            return Assets::renderCss();
        });

        $js = $this->withoutWarnings(static function(): string {
            Assets::js('/js/missing.js');

            return Assets::renderJs();
        });

        $this->assertSame('<link rel="stylesheet" href="/css/missing.css?v=1">', $css);
        $this->assertSame('<script src="/js/missing.js?v=1"></script>', $js);
    }

    public function testDefaultResolverUsesFilemtimeForExistingDocumentRootFile(): void
    {
        $this->useDocumentRoot([
            '/css/app.css' => 1700000000,
        ]);

        Assets::css('/css/app.css');

        $this->assertSame('<link rel="stylesheet" href="/css/app.css?v=1700000000">', Assets::renderCss());
    }

    #[DataProvider('defaultResolverPathProvider')]
    public function testDefaultResolverUsesFilemtimeAcrossAssetPathVariants(string $path, string $expectedUrl): void
    {
        $this->useDocumentRoot([
            '/css/app.css' => 1700001234,
        ]);

        Assets::css($path);

        $this->assertSame('<link rel="stylesheet" href="' . $expectedUrl . '">', Assets::renderCss());
    }

    #[DataProvider('missingAssetPathProvider')]
    public function testDefaultResolverFallsBackToOneWithoutWarningsForDecoratedMissingPaths(string $path, string $expectedUrl): void
    {
        unset($_SERVER['DOCUMENT_ROOT']);

        $css = $this->withoutWarnings(function() use ($path): string {
            Assets::css($path);

            return Assets::renderCss();
        });

        $js = $this->withoutWarnings(function() use ($path): string {
            Assets::js($path);

            return Assets::renderJs();
        });

        $this->assertSame('<link rel="stylesheet" href="' . $expectedUrl . '">', $css);
        $this->assertSame('<script src="' . $expectedUrl . '"></script>', $js);
    }

    public function testViewRenderEngineResetsAssetsBeforeEachRender(): void
    {
        Assets::setVersionResolver(static fn(string $webPath): string => '5');
        Assets::css('/css/leaked.css');

        $engine = new ViewRenderEngine(new View('assets-page'));
        $html = $engine->get();

        $this->assertStringNotContainsString('/css/leaked.css', $html);
        $this->assertSame(1, substr_count($html, '/css/card.css?v=5'));
        $this->assertSame(1, substr_count($html, '/js/card.js?feature=faq&amp;mode=split&amp;v=5'));
        $this->assertStringContainsString('/css/page.css?v=5', $html);
    }

    public function testNestedViewRendersKeepOuterAssetsAndMergeInnerAssets(): void
    {
        Assets::setVersionResolver(static fn(string $webPath): string => '8');

        $html = (string) new ViewRenderEngine(new View('nested-assets-parent'));

        $this->assertSame(1, substr_count($html, '/css/outer.css?v=8'));
        $this->assertSame(1, substr_count($html, '/css/inner.css?v=8'));
        $this->assertSame(1, substr_count($html, '/js/outer.js?v=8'));
        $this->assertSame(1, substr_count($html, '/js/inner.js?v=8'));
    }

    /** @return array<string, array{0: string, 1: string}> */
    public static function assetPathRenderingProvider(): array
    {
        return [
            'plain root-relative path' => [
                '/css/app.css',
                '/css/app.css?v=9',
            ],
            'query string' => [
                '/css/app.css?theme=dark',
                '/css/app.css?theme=dark&amp;v=9',
            ],
            'fragment only' => [
                '/css/app.css#hero',
                '/css/app.css?v=9#hero',
            ],
            'query string and fragment' => [
                '/css/app.css?theme=dark#hero',
                '/css/app.css?theme=dark&amp;v=9#hero',
            ],
            'absolute url with query and fragment' => [
                'https://static.example.com/css/app.css?theme=dark#hero',
                'https://static.example.com/css/app.css?theme=dark&amp;v=9#hero',
            ],
        ];
    }

    /** @return array<string, array{0: string, 1: string}> */
    public static function defaultResolverPathProvider(): array
    {
        return [
            'fragment only' => [
                '/css/app.css#hero',
                '/css/app.css?v=1700001234#hero',
            ],
            'query string and fragment' => [
                '/css/app.css?theme=dark#hero',
                '/css/app.css?theme=dark&amp;v=1700001234#hero',
            ],
            'absolute url with query and fragment' => [
                'https://static.example.com/css/app.css?theme=dark#hero',
                'https://static.example.com/css/app.css?theme=dark&amp;v=1700001234#hero',
            ],
        ];
    }

    /** @return array<string, array{0: string, 1: string}> */
    public static function missingAssetPathProvider(): array
    {
        return [
            'query string and fragment' => [
                '/css/missing.css?theme=dark#hero',
                '/css/missing.css?theme=dark&amp;v=1#hero',
            ],
            'absolute url with query and fragment' => [
                'https://static.example.com/css/missing.css?theme=dark#hero',
                'https://static.example.com/css/missing.css?theme=dark&amp;v=1#hero',
            ],
        ];
    }

    /**
     * @param array<string, int> $files
     */
    private function useDocumentRoot(array $files): void
    {
        $documentRoot = sys_get_temp_dir() . '/vista-assets-' . bin2hex(random_bytes(4));
        $this->tempDocumentRoots[] = $documentRoot;

        foreach($files as $relativePath => $mtime) {
            $fullPath = $documentRoot . $relativePath;
            $directory = dirname($fullPath);

            if(!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
                $this->fail("Failed to create directory {$directory}.");
            }

            if(file_put_contents($fullPath, 'body{}') === false) {
                $this->fail("Failed to write asset fixture {$fullPath}.");
            }

            if(!touch($fullPath, $mtime)) {
                $this->fail("Failed to set mtime for {$fullPath}.");
            }
        }

        $_SERVER['DOCUMENT_ROOT'] = $documentRoot;
    }

    private function restoreDocumentRoot(): void
    {
        if($this->originalDocumentRoot === null) {
            unset($_SERVER['DOCUMENT_ROOT']);
            return;
        }

        $_SERVER['DOCUMENT_ROOT'] = $this->originalDocumentRoot;
    }

    private function cleanupTempDocumentRoots(): void
    {
        foreach($this->tempDocumentRoots as $documentRoot) {
            $this->deleteDirectory($documentRoot);
        }

        $this->tempDocumentRoots = [];
    }

    private function deleteDirectory(string $directory): void
    {
        if(!is_dir($directory)) {
            return;
        }

        $items = scandir($directory);
        if($items === false) {
            return;
        }

        foreach($items as $item) {
            if($item === '.' || $item === '..') {
                continue;
            }

            $path = $directory . DIRECTORY_SEPARATOR . $item;

            if(is_dir($path)) {
                $this->deleteDirectory($path);
                continue;
            }

            unlink($path);
        }

        rmdir($directory);
    }

    private function withoutWarnings(callable $callback): mixed
    {
        $warnings = [];

        set_error_handler(static function(int $severity, string $message) use (&$warnings): bool {
            $warnings[] = [$severity, $message];

            return true;
        });

        try {
            $result = $callback();
        } finally {
            restore_error_handler();
        }

        $this->assertSame([], $warnings, 'Expected asset rendering to avoid PHP warnings.');

        return $result;
    }
}
