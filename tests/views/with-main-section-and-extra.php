<?php
/**
 * @var $this \Nullai\Vista\Engines\ViewRenderEngine
 */
$this->layout('layouts.main-and-extra-layout');

$this->section('main');
echo 'MAIN_CONTENT';
$this->end();

echo 'EXTRA_CONTENT';
