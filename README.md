# Vista

A blazing-fast and ultra-lightweight PHP view engine designed for modern PHP 8.4+ applications.

## Overview

Vista is a minimalist PHP view engine that focuses on performance and simplicity. It provides powerful tools to manage layouts, partials/includes, and dynamic content rendering, making it an ideal choice for modern PHP projects. Unlike traditional template engines like Blade or Twig, Vista does not rely on compiling or caching template files. This makes it an ideal choice for developers who prioritize performance and need a straightforward solution for managing views.

## Key Features

Vista is only two files and very capable:

- **No Compilation Required**: Renders views directly which makes debugging simple.
- **Lightning Fast**: Optimized for speed, with minimal overhead.
- **Extremely Lightweight**: Small footprint, easy to integrate with any PHP application.
- **Modern Syntax**: Intuitive, clean, and developer-friendly APIs.
- **Layout Management**: Easily create reusable layouts and templates.
- **Partial Rendering**: Modularize your views with include and section methods.
- **Scoped Data Passing**: Pass variables to views with isolated scopes for security and clarity.
- **Extensible**: Works seamlessly with other PHP frameworks or custom solutions.
- **Sanitize**: Sanitize raw HTML, Attributes, and JSON.

## Installation

To get started with Vista, you need to have PHP 8.4 installed on your system. You can install Vista via Composer. Run the following command in your terminal:

```
composer require nullaidev/vista
```

## Project Example

```text
project-root/
├── vendor/                  # Composer dependencies
│   └── autoload.php         # Composer autoloader
├── views/                   # Views folder
│   ├── layouts/             # Layouts folder
│   │   └── main-layout.php  # Main layout file
│   └── home.php             # View file using the layout
│   └── sidebar.php          # Site sidebar
├── public                   # Public web folder
│   └── index.php            # Project main entry point

```

### Layout

First create a layout file as `views/layouts/main-layout.php`:

```php
<?php
/**
 * views/layouts/main-layout.php
 * 
 * @var $this \Nullai\Vista\Engines\ViewRenderEngine
 */
?>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>My Site</title>
        <?php $this->yield('scripts'); ?>
    </head>

    <body>
        <?php $this->yield('main');  ?>
        
        <?php $this->yield('footer'); ?>
    </body>
</html>
```

### Home Page

Next, create a file that uses the layout `views/home.php`:

```php
<?php
/**
 * views/home.php
 * 
 * @var $this \Nullai\Vista\Engines\ViewRenderEngine
 * @var $content string
 */
$this->layout('layouts.main-layout');

// Yielded by the layout's $this->yield('main')
echo $content;
?>

<?php $this->section('scripts'); ?>
<script>
    console.log(<?= json_encode(['site' => '<My Site>'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>);
</script>
<?php $this->end(); ?>

<?php $this->section('footer'); ?>
<footer>My footer</footer>  
<?php $this->end(); ?>
```

### Entry Point

Now, create a View and render it from your project main entry point, such as a typical `public/index.php`:

```php
require_once __DIR__ . '/../vendor/autoload.php';
const NULLAI_VISTA_VIEWS_FOLDER = __DIR__ . '/views';

echo new \Nullai\Vista\View('home');
```

### Includes

You can use includes within any view relative to the root views folder. In this example, the layout file:

```php
<?php
/**
 * views/layouts/main-layout.php
 * 
 * @var $this \Nullai\Vista\Engines\ViewRenderEngine
 */
?>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>My Site</title>
        <?php $this->yield('scripts'); ?>
    </head>

    <body>
        <?php $this->yield('main');  ?>
        
        <?php $this->include('sidebar', ['menu' => ['Home', 'About', 'Contact']]); ?>

        <?php $this->yield('footer'); ?>
    </body>
</html>
```

The `include('sidebar', ['menu' => ['Home', 'About', 'Contact']])` method passes a menu variable to the `sidebar.php` view.
Inside `sidebar.php`, the `$menu` array will be accessible.

```php
<?php
/**
 * Sidebar view file
 * @var array $menu The array of menu items passed to this view
 * @var $this \Nullai\Vista\Engines\ViewRenderEngine
 */
?>
<aside class="sidebar">
    <nav class="menu">
        <ul>
            <?php foreach ($menu ?? [] as $item): ?>
                <li>
                    <a href="#"><?= htmlspecialchars($item, ENT_QUOTES, 'UTF-8') ?></a>
                </li>
            <?php endforeach; ?>
        </ul>
    </nav>
</aside>
```

## Sanitization: Raw HTML, Attributes, Slugs, and JSON

Sanitize HTML from using the rendering engine's `escHtml()`:

```php
<?php
/**
 * Sidebar view file
 * @var array $menu The array of menu items passed to this view
 * @var $this \Nullai\Vista\Engines\ViewRenderEngine
 */
?>
<aside class="sidebar">
    <nav class="menu">
        <ul>
            <?php foreach ($menu ?? [] as $item): ?>
                <li>
                    <a href="<?= $this->escAttr($item) ?>">
                        <?= $this->escHtml($item) ?>       
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </nav>
</aside>
```

Sanitize data and encode it as JSON using thw rendering engine's `escJson()`:

```php
<?php
/**
 * Sidebar view file
 * @var array $menu The array of menu items passed to this view
 * @var $this \Nullai\Vista\Engines\ViewRenderEngine
 */
?>

<?php $this->section('scripts'); ?>
<script>
    // Safely embed JSON in a JavaScript variable
    console.log(<?= $this->escJson(['site' => '<My Site>']) ?>);
</script>
<?php $this->end(); ?>
```
