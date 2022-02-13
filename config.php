<?php

class Config extends \Simplex\Core\Config
{

    public static $db_type = 'mysql';
    public static $db_host; // Init from env. May put values statically
    public static $db_user;
    public static $db_pass;
    public static $db_name;
    public static $component_default = '\App\Extensions\Content\Content';
    public static $theme = 'default';

    public static $subdomainOneSession = false;

    /**
     * @see /core/sflog.class.php
     */
    public static $logLevel = 'debug';
    public static $logPath = '/var/log';

    public static function load()
    {
        static::$db_host = env('DB_HOST', 'db');
        static::$db_user = env('DB_USER', 'simplex');
        static::$db_pass = env('DB_PASS', 'simplex');
        static::$db_name = env('DB_NAME', 'simplex');
    }
}
