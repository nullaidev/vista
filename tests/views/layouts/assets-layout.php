<html lang="en">
<head>
<?= \Nullai\Vista\Assets::renderCss() ?>
</head>
<body>
<?php $this->yield('main'); ?>
<?= \Nullai\Vista\Assets::renderJs() ?>
</body>
</html>
