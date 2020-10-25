<!DOCTYPE html>
<html lang="ru">
    <head>
        <?php
        \App\Plugins\Jquery\Jquery::core();
        \Simplex\Core\Page::css('/theme/default/css/default.css');
        \Simplex\Core\Page::js('/theme/default/js/default.js');
        \Simplex\Core\Page::meta();
        ?>
    </head>
    <body>
        <?php \Simplex\Core\Page::position('content-before'); ?>
        <?php \Simplex\Core\Page::content(); ?>
        <?php \Simplex\Core\Page::position('content-after'); ?>

        <?php \Simplex\Core\Page::position('absolute'); ?>
    </body>
</html>
