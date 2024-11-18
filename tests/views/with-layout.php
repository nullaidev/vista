<?php
/**
 * @var $this \Nullai\Vista\Engines\ViewRenderEngine
 * @var $content string
 */
$this->layout('layouts.main-layout');

echo $content;

$this->section('scripts');
echo '<script>';
$this->end();

$this->section('footer');
echo PHP_EOL . '<footer>';
$this->end();

