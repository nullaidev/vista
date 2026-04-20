---
name: vista
description: Use when working with the Nullai Vista PHP templating library — rendering views, building layouts with sections, including partials, or writing custom engines. Trigger on code using `new View(...)`, `ViewRenderEngine`, `$this->layout()`, `$this->section()`, `$this->include()`, `$this->yield()`, or files under a `views/` folder in projects depending on `nullaidev/vista`.
---

# Vista templating

Vista is a minimal PHP 8.4 view engine. Two public classes:

- `Nullai\Vista\View` — addresses a view file on disk and renders it.
- `Nullai\Vista\Engines\ViewRenderEngine` — default engine; handles layouts, sections, includes.

Custom engines implement `Nullai\Vista\Engines\RenderEngineInterface` (one method: `render(): void`).

## Quick reference

| Need                            | Call                                              |
|---------------------------------|---------------------------------------------------|
| Create a view                   | `new View('users.show', ['user' => $u])`          |
| Get rendered HTML               | `$view->content()` or `(string) $view`            |
| Declare a layout (child view)   | `$this->layout('layouts.main')`                   |
| Define a named block            | `$this->section('scripts'); ... $this->end();`    |
| Emit a block (layout)           | `$this->yield('scripts')`                         |
| Include a partial               | `$this->include('partials.button', ['label'=>…])` |
| Include relative to current     | `$this->include(':sidebar')`                      |
| Include only if a condition     | `$this->includeIf($cond, 'user.badge', [...])`    |
| Clear engine state for re-use   | `$engine->reset()`                                |

## Setup

```php
// Required once before using dot notation:
const NULLAI_VISTA_VIEWS_FOLDER = __DIR__ . '/views';

// Optional — swap the rendering engine for the whole app:
const NULLAI_VISTA_ENGINE = \App\MyEngine::class;
```

If `NULLAI_VISTA_ENGINE` is undefined, Vista uses `ViewRenderEngine`.

## View — how paths are resolved

`new View(string $identifier, array $data = [])` accepts three identifier forms:

### 1. Dot notation

```php
new View('users.show');
// folder: NULLAI_VISTA_VIEWS_FOLDER
// file:   users/show
// ext:    php
// fullPath: {NULLAI_VISTA_VIEWS_FOLDER}/users/show.php
```

### 2. `folder:file` (colon)

```php
new View(__DIR__ . '/mail-views:welcome.header');
// folder: __DIR__ . '/mail-views'
// file:   welcome/header
// fullPath: __DIR__ . '/mail-views/welcome/header.php'
```

Empty folder before the colon (`:welcome.header`) falls back to `NULLAI_VISTA_VIEWS_FOLDER`.

### 3. Direct file path (any extension)

```php
new View(__DIR__ . '/emails/welcome.html');
// folder: __DIR__ . '/emails'
// file:   welcome
// ext:    html
```

Extension is preserved exactly. PHP still parses `<?php ?>` tags inside even when the extension is `.html`, `.txt`, etc.

### View properties (all public, read/write unless noted)

| Property     | Type                                   | Notes                                         |
|--------------|----------------------------------------|-----------------------------------------------|
| `$data`      | `array<string, mixed>`                 | Extracted into view scope at render time.     |
| `$folder`    | `string`                               | Setter strips trailing `DIRECTORY_SEPARATOR`. |
| `$file`      | `string`                               | Path segment under `$folder`, no extension.   |
| `$ext`       | `string`                               | Default `'php'`.                              |
| `$fullPath`  | `string` (read-only)                   | `{$folder}/{$file}.{$ext}`.                   |
| `$engine`    | `class-string<RenderEngineInterface>`  | Set at construct-time from `NULLAI_VISTA_ENGINE` or defaulted. |

## Rendering a view

```php
$html = $view->content();   // returns string
echo $view;                 // same output via __toString()
```

Both throw `\Exception` if `$view->fullPath` does not exist. `View::content()` is idempotent — calling it twice on the same `View` produces identical output.

## Inside a view file — `$this` is the engine

```php
<?php
/** @var \Nullai\Vista\Engines\ViewRenderEngine $this */
/** @var string $title */   // comes from $data
/** @var string $content */

$this->layout('layouts.main');

$this->section('scripts');
?>
<script>window.__title = <?= json_encode($title, JSON_HEX_TAG) ?>;</script>
<?php
$this->end();

echo '<h1>' . htmlspecialchars($title) . '</h1>';
echo $content;
```

Data passed via `new View('page', ['title' => 'Hi', 'content' => '…'])` is `extract(..., EXTR_SKIP)`-ed into the view's local scope. Vista does **not** escape automatically — use `htmlspecialchars()` or `json_encode()` explicitly.

## Engine API (`ViewRenderEngine`)

Everything below is called as `$this->…()` from inside a view file.

### `layout(string $layout): void`

Marks the current view as content-for-layout. After the current view finishes executing, its output is captured and the named layout file is rendered in its place.

```php
$this->layout('layouts.main');   // resolves via new View('layouts.main')
```

If the child view never calls `section('main')`, its loose output is stored as the `main` section. If it does call `section('main')`, any trailing loose output is stored as `__main`.

### `section(string $name): void` and `end(): void`

Open a named buffer, capture echoed content until `end()`, store the captured string under `$name`.

```php
$this->section('scripts');
echo '<script>…</script>';
$this->end();
```

- `end()` throws `\LogicException` if called without a matching `section()`.
- Re-opening a closed section overwrites its previous content.

### `yield(string $section): void`

Echo a previously-stored section. Missing sections echo nothing (silent).

```php
// inside a layout file:
$this->yield('scripts');
$this->yield('main');
$this->yield('footer');
```

### `include(string|View $view, array $data = []): void`

Include a partial. Accepts three forms, matching `View` construction:

```php
// 1. Dot notation (from NULLAI_VISTA_VIEWS_FOLDER)
$this->include('partials.button', ['label' => 'Save']);

// 2. Relative (leading colon) — resolved against current view's folder
$this->include(':sidebar');

// 3. Pre-built View instance
$this->include(new View('mail.header', ['logo' => $logo]));
```

Inside the partial:
- Keys in `$data` are extracted as local variables (`EXTR_SKIP`).
- `$parent` (array) exposes the **root** view's `$data` — see below.
- Keys that collide with engine internals (`_view`, `_data`, `_parent_view`, `parent`) are silently dropped by `EXTR_SKIP` — rename them.

Throws `\Exception` if the included file does not exist.

### `$parent` — reading up from a partial

Every time Vista includes a partial, it injects a local variable `$parent` into that partial's scope. It's an **array** — a snapshot of the root view's `$data`.

Key points:

- **`$parent` is an `array`, not a `View` object.** It's exactly the `$data` array that was passed to the top-level `new View(..., $data)`. Access keys like any array: `$parent['user']`, `$parent['csrf']`.
- **It is the *root* view's data, not the immediate includer's.** If `root` includes `A`, which includes `B`, which includes `C`, then inside `A`, `B`, and `C` alike, `$parent` is `root`'s data. It does **not** walk one level up per nesting.
  Need the immediate caller's data instead? Pass it explicitly: `$this->include('child', $_data)` from inside the parent (where `$_data` is the parent's already-extracted data array — or just forward individual keys).
- **Layouts see it too.** A layout is rendered through the same `include()` path, so `$parent` inside the layout is the root view's `$data`. That's how layouts typically read `title`, `user`, etc., without the child having to re-pass them.
- **Collisions with `$parent` in your own `$data` are dropped.** Vista assigns `$parent` *before* `extract($data, EXTR_SKIP)`, so a `'parent' => …` key you pass into `include()` is silently skipped. Rename it.
- **Only populated in partials/layouts.** In the root view itself (the one you passed to `new View()`), `$parent` is undefined — because there is no parent.

Example:

```php
// app.php — caller
echo new View('page', [
    'user' => $currentUser,
    'csrf' => $token,
])->content();
```

```php
// views/page.php  (root view — $parent is NOT defined here)
$this->layout('layouts.main');
$this->include('partials.user-card');
```

```php
// views/layouts/main.php  (layout — $parent IS defined)
?>
<title>Hello, <?= htmlspecialchars($parent['user']->name) ?></title>
<input type="hidden" name="csrf" value="<?= $parent['csrf'] ?>">
<?php
$this->yield('main');
```

```php
// views/partials/user-card.php  (nested partial — $parent IS defined, still root's data)
?>
<div>Signed in as <?= htmlspecialchars($parent['user']->name) ?></div>
```

### `includeIf(bool $condition, mixed ...$args): bool`

Shortcut: include only when truthy. Returns the condition.

```php
$this->includeIf($user !== null, 'user.badge', ['user' => $user]);
$this->includeIf($cart->hasItems(), new View('cart.summary', $cart->toArray()));
```

Remaining args are forwarded verbatim to `include()`.

### `reset(): void`

Clear `$sections`, `$layout`, and the current section marker. Called automatically at the top of `render()`, so you normally don't need this — reach for it only when manually reusing a `ViewRenderEngine` instance across unrelated renders.

```php
$engine = new ViewRenderEngine($viewA);
$a = $engine->get();

$engine = new ViewRenderEngine($viewB);   // idiomatic: fresh instance
// or:
$engine->reset();                         // if you must reuse
```

### `render(): void` and `get(): string`

Low-level entry points:

- `render()` executes the view and emits to the current output buffer.
- `get()` wraps `render()` in its own buffer and returns the captured string. `(string) $engine` calls `get()`.

Normal code calls `View::content()`, which internally builds a fresh engine and calls `get()`.

## Data flow summary

```
new View('page', ['title' => 'Hi'])
        │
        ▼ content()
   ┌──────────────────────────┐
   │ new ViewRenderEngine     │
   │   → reset()              │
   │   → extract($data)       │   // $title available here
   │   → include view file    │   // echoes into buffer
   │   → if layout set:       │
   │       capture as 'main'  │
   │       include(layout)    │   // yields sections
   └──────────────────────────┘
        │
        ▼
      string
```

## Custom engines

Implement `RenderEngineInterface`, then point `NULLAI_VISTA_ENGINE` at it.

```php
namespace App;

use Nullai\Vista\Engines\RenderEngineInterface;
use Nullai\Vista\View;

class JsonEngine implements RenderEngineInterface
{
    public function __construct(private View $view) {}

    public function render(): void
    {
        echo json_encode($this->view->data, JSON_THROW_ON_ERROR);
    }
}
```

```php
const NULLAI_VISTA_ENGINE = \App\JsonEngine::class;

echo new View('ignored-but-required-identifier', ['name' => 'Ada']);
// => {"name":"Ada"}
```

The engine constructor receives the `View` instance. `render()` must echo — the surrounding code captures output via buffering.

## Exceptions thrown

| Exception           | When                                                                  |
|---------------------|-----------------------------------------------------------------------|
| `\Exception`        | `render()` or `include()` given a path that doesn't exist.            |
| `\LogicException`   | `end()` called without a matching `section()`.                        |
| `\RuntimeException` | `ob_get_clean()` returned `false` — output-buffer stack was corrupted externally. |

`View::__toString()` and `ViewRenderEngine::__toString()` propagate all of the above.

## Gotchas

- **`section()` / `end()` must pair.** A stray `end()` throws `\LogicException`.
- **No auto-escaping.** Use `htmlspecialchars()` / `json_encode(..., JSON_HEX_*)` in your templates.
- **Don't close Vista's buffers externally.** If `ob_end_clean()` is called from unrelated code while Vista is mid-render, Vista throws `\RuntimeException` on the next `ob_get_clean()`.
- **Prefer `View::content()` over reusing an engine.** Each `content()` call builds a fresh engine; you never need to think about `reset()` if you follow this pattern.
