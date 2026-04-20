---
name: vista
description: Use when working with the Nullai Vista PHP templating library — rendering views, building layouts with sections, or including partials. Trigger on code using `new View(...)`, `ViewRenderEngine`, `$this->layout()`, `$this->section()`, or files in a `views/` folder of projects depending on `nullaidev/vista`.
---

# Vista templating

Vista is a minimal PHP 8.4 view engine with two public classes:

- `Nullai\Vista\View` — resolves a view identifier to a file and renders it.
- `Nullai\Vista\Engines\ViewRenderEngine` — the default engine: layouts, sections, includes.

Custom engines implement `Nullai\Vista\Engines\RenderEngineInterface` (one method: `render(): void`).

## Setup

Before using dot notation, define the base views folder:

```php
const NULLAI_VISTA_VIEWS_FOLDER = __DIR__ . '/views';
```

To swap in a custom engine:

```php
const NULLAI_VISTA_ENGINE = MyEngine::class;
```

## Constructing a View

`new View($identifier, $data)` accepts three forms:

```php
// 1. Dot notation — resolved against NULLAI_VISTA_VIEWS_FOLDER
new View('users.show', ['user' => $user]);
// => {views}/users/show.php

// 2. Explicit folder with colon prefix
new View(__DIR__ . '/views:users.show');

// 3. Direct file path (any extension — .html, .txt, etc.)
new View(__DIR__ . '/emails/welcome.html');
```

Render:

```php
echo $view;                // via __toString()
$html = $view->content();  // returns string
```

Both throw `\Exception` if the view file is missing.

## Inside a view file — `$this` is the engine

```php
<?php
/** @var \Nullai\Vista\Engines\ViewRenderEngine $this */
$this->layout('layouts.main');

$this->section('scripts');
?>
<script>console.log('hi');</script>
<?php
$this->end();

echo '<h1>' . htmlspecialchars($title) . '</h1>';
```

Data passed to `new View(..., $data)` is `extract(..., EXTR_SKIP)`-ed into the view's scope. Keys that collide with engine locals (`_view`, `_data`, `_parent_view`, `parent`) are silently dropped — rename them.

## Layouts

```php
// page.php
$this->layout('layouts.main');

$this->section('scripts');
    echo '<script>…</script>';
$this->end();

echo '<main>…</main>';
```

```php
// layouts/main.php
$this->yield('scripts');
$this->yield('main');
$this->yield('footer');
```

If the child never opens `section('main')` explicitly, loose output becomes the `main` section. If it *does* open `section('main')`, any trailing loose output goes to `__main`.

## Partials

```php
// Absolute (relative to NULLAI_VISTA_VIEWS_FOLDER)
$this->include('partials.button', ['label' => 'Save']);

// Relative (starts with `:`) — resolved against the current view's folder
$this->include(':sidebar');

// View instance
$this->include(new View('mail.header', ['logo' => $logo]));

// Conditional
$this->includeIf($user !== null, 'user.badge', ['user' => $user]);
```

Inside a partial, the parent view's data is available as `$parent` (array).

## Gotchas

- **`section()` / `end()` must pair.** Calling `end()` without an open section throws `\LogicException`.
- **Buffer invariants.** If `ob_get_clean()` returns false (buffer stack broken by external code), Vista throws `\RuntimeException`. Don't `ob_end_clean()` Vista's buffers from outside.
- **Escaping is not automatic.** Use `htmlspecialchars()` / `json_encode(..., JSON_HEX_*)` / short-echo `<?= ?>` with explicit escaping.

## Custom engine

```php
namespace App;

use Nullai\Vista\Engines\RenderEngineInterface;
use Nullai\Vista\View;

class JsonEngine implements RenderEngineInterface
{
    public function __construct(private View $view) {}

    public function render(): void
    {
        echo json_encode($this->view->data);
    }
}
```

Then: `const NULLAI_VISTA_ENGINE = \App\JsonEngine::class;`
