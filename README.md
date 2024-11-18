# Vista


## Installation

To get started with Vista, you need to have PHP 8.4 installed on your system. You can install Vista via Composer. Run the following command in your terminal:

```
composer require nullaidev/vista
```

First create a layout file as `views/layouts/main-layout.php`:

```php
<?php
/**
 * @var $this \Nullai\Vista\Engines\ViewRenderEngine
 */
?>
<html lang="en">
    <head>
        <?php $this->yield('scripts'); ?>
    </head>

    <body>
        <?php $this->yield('main');  ?>
        
        <?php $this->yield('footer'); ?>
    </body>
</html>
```

Next, create a file that uses the layout `views/home.php`:

```php
<?php
/**
 * @var $this \Nullai\Vista\Engines\ViewRenderEngine
 * @var $content string
 */
$this->layout('layouts.main-layout');

// Yielded by the layout's $this->yield('main')
echo $content;
?>

<?php $this->section('scripts'); ?>
<script src="my.js"></script>
<?php $this->end(); ?>

<?php $this->section('footer'); ?>
<footer>My footer</footer>  
<?php $this->end(); ?>
```

Now, create a View and render it `index.php`:

```php
require_once __DIR__ . '/../vendor/autoload.php';
const NULLAI_VISTA_VIEWS_FOLDER = __DIR__ . '/views';

echo new \Nullai\Vista\View('home');
```

