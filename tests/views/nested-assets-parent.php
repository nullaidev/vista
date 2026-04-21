<?php

use Nullai\Vista\Assets;
use Nullai\Vista\View;

$this->layout('layouts.assets-layout');

Assets::css('/css/outer.css');
Assets::js('/js/outer.js');

echo (new View('nested-assets-child'))->content();

?>
<main>outer</main>
