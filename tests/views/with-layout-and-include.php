<?php
/**
 * @var $this \Nullai\Vista\Engines\ViewRenderEngine
 * @var $content string
 */
$this->layout('layouts.main-layout');

echo $content;

$this->include('test');

$this->section('footer');
echo PHP_EOL . '<footer>';
$this->end();