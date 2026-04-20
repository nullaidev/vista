<?php
/**
 * @var $this \Nullai\Vista\Engines\ViewRenderEngine
 * @var bool $include_footer
 */
$this->layout('layouts.main-layout');

echo 'BODY';

if(!empty($include_footer ?? false)) {
    $this->section('footer');
    echo 'LEAKY_FOOTER';
    $this->end();
}
