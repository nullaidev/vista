<?php
/**
 * @var $this \Nullai\Vista\Engines\TemplateEngine
 * @var $content string
 */
$this->layout('layouts.html-layout');

echo $content;

// if starts with & -> relative path in dot notation
$this->includeIf(false,'&test');
$this->includeIf(true,'&test-php-short-tag');

$this->section('footer');
echo PHP_EOL . '<footer>';
$this->end();