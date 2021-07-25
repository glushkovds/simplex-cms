<?php

class Init
{

    /**
     * @return \Simplex\Core\Core
     */
    public static function _()
    {
        static::setTimezone();
        require_once SF_ROOT_PATH . '/vendor/autoload.php';
        static::setSessionParams();
        static::startDebug();
        static::startSession();
        static::setCharset();

        require_once SF_ROOT_PATH . '/config.php';
        \Simplex\Core\Container::set('config', new Config());

        if (SF_LOCATION_ADMIN == SF_LOCATION) {
            \Simplex\Core\Container::set('page', new \Simplex\Admin\Page());
            \Simplex\Core\Container::set('core', \Simplex\Admin\Core::class);
        } else {
            \Simplex\Core\Container::set('page', new \Simplex\Core\Page());
            \Simplex\Core\Container::set('core', \Simplex\Core\Core::class);
        }

        // TODO Lazy connect
        \Simplex\Core\DB::connect();
        static::auth();

        \Simplex\Core\Container::getCore()::init();
        \Simplex\Core\Container::getPage()::init();

        return \Simplex\Core\Container::getCore();
    }

    protected static function auth()
    {
        \Simplex\Auth\Bootstrap::authByMiddlewareChain((new \Simplex\Auth\Auth\Chain([
            new \Simplex\Auth\Auth\SessionMiddleware(),
            new \Simplex\Auth\Auth\CookieMiddleware(),
            new \Simplex\Auth\Auth\BasicAuthMiddleware(),
        ]))
        // You can change base user model for auth
//            ->setUserModelClass(YourUser::class)
        );
    }

    public static function loadConstants()
    {
        require_once __DIR__ . '/../vendor/glushkovds/simplex-core/src/constants.php';
        require_once __DIR__ . '/../constants.php';
    }

    protected static function setTimezone()
    {
        date_default_timezone_set('Asia/Yekaterinburg');
    }

    /**
     * One session for all subdomains
     */
    protected static function setSessionParams()
    {
        if (\Simplex\Core\Config::$subdomainOneSession) {
            $baseDomain = implode('.', array_slice(explode('.', $_SERVER['HTTP_HOST']), 1));
            session_set_cookie_params(0, '/', ".$baseDomain");
        }
    }

    protected static function startDebug()
    {
        $_ENV['start'] = ['time' => microtime(true), 'memory' => memory_get_usage()];
    }

    protected static function startSession()
    {
        session_start();
    }

    protected static function setCharset()
    {
        ini_set('default_charset', 'UTF-8');
    }

}