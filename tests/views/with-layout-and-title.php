<?php
/**
 * @var $this \Nullai\Vista\Engines\ViewRenderEngine
 * @var $content string
 */
$this->layout('layouts.html-layout');

echo $content;

$this->includeIf(false, new \Nullai\Vista\View('test'));
$this->includeIf(true,'test-php-short-tag'); ?>

<?php $this->section('scripts'); ?>
<script>
    console.log('test');
</script>
<?php $this->end(); ?>

<?php
$this->section('footer');
echo PHP_EOL . '<footer>';
$this->end();