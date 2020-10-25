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

        // TODO Lazy connect
        \Simplex\Core\DB::connect();

        \Simplex\Core\User::login();
        \Simplex\Core\Core::init();
        \Simplex\Core\Page::init();

        return \Simplex\Core\Core::class;
    }

    public static function loadConstants()
    {
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