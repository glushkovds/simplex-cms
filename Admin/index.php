<?php

require_once __DIR__ . '/../Core/Init.php';

Init::loadConstants();
define('SF_LOCATION', SF_LOCATION_ADMIN);

Init::_();
\Simplex\Core\Container::set('page', new \Simplex\Admin\Page());

\Simplex\Core\DB::connect();
\Simplex\Core\User::login('admin');
\Simplex\Admin\Core::init();
\Simplex\Admin\Core::execute();
