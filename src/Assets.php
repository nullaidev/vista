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

    /**
     * Register a stylesheet URL for the current render tree.
     *
     * Registration is deduplicated by the exact path string and preserves the
     * first-seen order. Call this from page views, layouts, or partials; the
     * layout later emits the accumulated tags with {@see renderCss()}.
     *
     * @param string $path Root-relative or fully-qualified asset URL.
     */
    public static function css(string $path): void
    {
        self::store('css', $path);
    }

    /**
     * Register a JavaScript URL for the current render tree.
     *
     * Registration is deduplicated by the exact path string and preserves the
     * first-seen order. Call this during rendering, then let the layout emit
     * the final tags with {@see renderJs()}.
     *
     * @param string $path Root-relative or fully-qualified asset URL.
     */
    public static function js(string $path): void
    {
        self::store('js', $path);
    }

    /**
     * Render all registered stylesheet tags in first-registration order.
     *
     * Each URL receives a `v` query parameter from the active version resolver.
     * The returned markup is newline-delimited and is an empty string when no
     * CSS assets were registered for the current render tree.
     */
    public static function renderCss(): string
    {
        return self::render(self::paths('css'), static fn(string $path): string => '<link rel="stylesheet" href="' . self::escape(self::versioned($path)) . '">');
    }

    /**
     * Render all registered script tags in first-registration order.
     *
     * Each URL receives a `v` query parameter from the active version resolver.
     * The returned markup is newline-delimited and is an empty string when no
     * JavaScript assets were registered for the current render tree.
     */
    public static function renderJs(): string
    {
        return self::render(self::paths('js'), static fn(string $path): string => '<script src="' . self::escape(self::versioned($path)) . '"></script>');
    }

    /**
     * Override how asset versions are resolved for emitted URLs.
     *
     * The callable receives the original registered path, including any query
     * string or fragment, and must return the version string to append as
     * `v=...`. Pass `null` to restore Vista's default `filemtime()`-based
     * resolver.
     *
     * @param callable(string):string|null $resolver
     */
    public static function setVersionResolver(?callable $resolver): void
    {
        self::$versionResolver = $resolver instanceof \Closure ? $resolver : ($resolver ? $resolver(...) : null);
    }

    /**
     * Clear the current render tree's asset registry.
     *
     * Vista calls this automatically at the start of the outermost render, so
     * application code usually does not need it. Reach for it in tests or in
     * manual long-running scripts that reuse the same PHP process.
     */
    public static function reset(): void
    {
        self::$instance = new self();
    }

    /**
     * Turn an ordered path list into newline-delimited HTML tags.
     *
     * @param array<string, string> $paths
     * @param \Closure(string):string $renderer
     */
    private static function render(array $paths, \Closure $renderer): string
    {
        return implode(PHP_EOL, array_map($renderer, $paths));
    }

    /**
     * Append the active version to a path while keeping any fragment at the end.
     *
     * Query strings receive `&v=...`; plain paths receive `?v=...`. Fragments
     * are preserved after the versioned URL so browsers still interpret them
     * correctly.
     */
    private static function versioned(string $path): string
    {
        [$pathWithoutFragment, $fragment] = self::splitFragment($path);
        $separator = str_contains($pathWithoutFragment, '?') ? '&' : '?';

        return $pathWithoutFragment . $separator . 'v=' . rawurlencode(self::resolveVersion($path)) . $fragment;
    }

    /**
     * Resolve a version string for the original registered asset path.
     *
     * Custom resolvers win when configured; otherwise Vista falls back to the
     * default `filemtime()` strategy.
     */
    private static function resolveVersion(string $path): string
    {
        return (self::$versionResolver ?? self::defaultVersionResolver())($path);
    }

    /**
     * Build Vista's default cache-busting strategy.
     *
     * The resolver strips query strings and fragments for filesystem lookup,
     * resolves the remaining path against `$_SERVER['DOCUMENT_ROOT']`, and
     * returns `filemtime()` when the file exists. Missing files or unset
     * document roots fall back to `'1'`.
     *
     * @return \Closure(string):string
     */
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

    /**
     * Escape an emitted asset URL for use inside an HTML attribute.
     */
    private static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * Reduce a web URL to the filesystem path used by the default resolver.
     *
     * Absolute URLs contribute only their path component. When parsing fails,
     * the original string is returned so the resolver can still fall back
     * cleanly.
     */
    private static function filesystemPath(string $webPath): string
    {
        return parse_url($webPath, PHP_URL_PATH) ?: $webPath;
    }

    /**
     * Split a URL into the portion that can accept query parameters and the
     * trailing fragment that must remain last.
     *
     * @return array{0: string, 1: string}
     */
    private static function splitFragment(string $path): array
    {
        $parts = explode('#', $path, 2);

        return [
            $parts[0],
            isset($parts[1]) ? '#' . $parts[1] : '',
        ];
    }

    /**
     * Store a path in the current registry without disturbing first-seen order.
     *
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
     * Fetch the current registry list for one asset type.
     *
     * @param 'css'|'js' $type
     * @return array<string, string>
     */
    private static function paths(string $type): array
    {
        $assets = self::instance();

        return $type === 'css' ? $assets->css : $assets->js;
    }

    /**
     * Return the lazily created registry instance for the current PHP request.
     */
    private static function instance(): self
    {
        if(self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }
}
