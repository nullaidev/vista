<?php
/**
 * @var $this \Nullai\Vista\Engines\ViewRenderEngine
 * @var array $_data
 */
$this->include(':nest-include', ['nested' => 3, ...$_data]);
$this->include('test');