<html lang="en">
<head>
    <title><?php echo $this->view->data['title'] ?></title>
    <?php $this->yield('scripts'); ?>
</head>
<body>
<?php $this->yield('main');  ?>

<?php $this->yield('footer'); ?>

<?php var_dump($_view_data); ?>
</body>
</html>