<?php

class Config extends \Simplex\Core\Config
{

    public static $db_type = 'mysql';
    public static $db_host = 'localhost';
    public static $db_user = 'root';
    public static $db_pass = 'root';
    public static $db_name = 'simplex3';
    public static $component_default = '\App\Extensions\Content\Content';
    public static $theme = 'default';

    public static $subdomainOneSession = false;

    /**
     * @see /core/sflog.class.php
     */
    public static $logLevel = 'debug';
    public static $logPath = '/var/log';

}

if (!function_exists('imDev')) {

    function imDev()
    {
        return !empty($_COOKIE['imdev']);
    }

}

