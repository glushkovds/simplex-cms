<?php

namespace App\Layout;

use Simplex\Core\Container;

class LayoutManager
{
    /**
     * @var LayoutBase[]
     */
    protected static $usedLayouts = [];

    public static function useLayout(string $class)
    {
        static::$usedLayouts[$class] = $class;
    }

    public static function init()
    {
        Container::getPage()::css('/Layout/assetsGlobal/style.css');
        Container::getPage()::js('/Layout/assetsGlobal/script.js');
        foreach (static::$usedLayouts as $class) {
            $class::initAssets();
        }
    }
}