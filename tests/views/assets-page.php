<?php

use Nullai\Vista\Assets;

$this->layout('layouts.assets-layout');

Assets::css('/css/page.css');

$this->include('assets-partial');
$this->include('assets-partial');

echo '<main>assets page</main>';
