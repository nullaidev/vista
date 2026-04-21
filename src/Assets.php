<?php

namespace Nullai\Vista;

/**
 * Per-render asset registry for Vista templates.
 *
 * Views, partials, and included templates register CSS/JS during execution via
 * {@see css()} and {@see js()}. Layouts then decide where to emit the final
 * tags by calling {@see renderCss()} and {@see renderJs()} after the page view
 * has already run. Vista resets this registry at the start of the outermost
 * ViewRenderEngine::render() call, so repeated renders stay isolated and
 * nested renders can still contribute assets to the surrounding page.
 */
final class Assets
{
    /** @var (\Closure(string): string)|null */
    private static ?\Closure $versionResolver = null;

    /** @var array<string, string> */
    private array $css = [];

    /** @var array<string, string> */
    private array $js = [];

    private static ?self $instance = null;

    public static function css(string $path): void
    {
        self::store('css', $path);
    }

    public static function js(string $path): void
    {
        self::store('js', $path);
    }

    public static function renderCss(): string
    {
        return self::render(self::paths('css'), static fn(string $path): string => '<link rel="stylesheet" href="' . self::escape(self::versioned($path)) . '">');
    }

    public static function renderJs(): string
    {
        return self::render(self::paths('js'), static fn(string $path): string => '<script src="' . self::escape(self::versioned($path)) . '"></script>');
    }

    public static function setVersionResolver(?callable $resolver): void
    {
        self::$versionResolver = $resolver instanceof \Closure ? $resolver : ($resolver ? $resolver(...) : null);
    }

    public static function reset(): void
    {
        self::$instance = new self();
    }

    /**
     * @param array<string, string> $paths
     * @param \Closure(string):string $renderer
     */
    private static function render(array $paths, \Closure $renderer): string
    {
        return implode(PHP_EOL, array_map($renderer, $paths));
    }

    private static function versioned(string $path): string
    {
        [$pathWithoutFragment, $fragment] = self::splitFragment($path);
        $separator = str_contains($pathWithoutFragment, '?') ? '&' : '?';

        return $pathWithoutFragment . $separator . 'v=' . rawurlencode(self::resolveVersion($path)) . $fragment;
    }

    private static function resolveVersion(string $path): string
    {
        return (self::$versionResolver ?? self::defaultVersionResolver())($path);
    }

    /** @return \Closure(string):string */
    private static function defaultVersionResolver(): \Closure
    {
        return static function(string $webPath): string {
            $documentRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
            if($documentRoot === '') {
                return '1';
            }

            $diskPath = rtrim($documentRoot, DIRECTORY_SEPARATOR) . self::filesystemPath($webPath);
            if(!is_file($diskPath)) {
                return '1';
            }

            $mtime = filemtime($diskPath);

            return $mtime === false ? '1' : (string) $mtime;
        };
    }

    private static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private static function filesystemPath(string $webPath): string
    {
        return parse_url($webPath, PHP_URL_PATH) ?: $webPath;
    }

    /** @return array{0: string, 1: string} */
    private static function splitFragment(string $path): array
    {
        $parts = explode('#', $path, 2);

        return [
            $parts[0],
            isset($parts[1]) ? '#' . $parts[1] : '',
        ];
    }

    /**
     * @param 'css'|'js' $type
     * @param string $path
     */
    private static function store(string $type, string $path): void
    {
        $assets = self::instance();

        if($type === 'css') {
            $assets->css[$path] ??= $path;
            return;
        }

        $assets->js[$path] ??= $path;
    }

    /**
     * @param 'css'|'js' $type
     * @return array<string, string>
     */
    private static function paths(string $type): array
    {
        $assets = self::instance();

        return $type === 'css' ? $assets->css : $assets->js;
    }

    private static function instance(): self
    {
        if(self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }
}
