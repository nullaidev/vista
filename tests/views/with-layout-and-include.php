<?php
/**
 * @var $this \Nullai\Vista\Engines\TemplateEngine
 * @var $content string
 */
$this->layout('layouts.main-layout');

echo $content;

// if starts with & -> relative path in dot notation
$this->include('&test');

$this->section('footer');
echo PHP_EOL . '<footer>';
$this->end();