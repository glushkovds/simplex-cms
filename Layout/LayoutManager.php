<?php

namespace App\Layout;

use ScssPhp\ScssPhp\Compiler;
use Simplex\Core\Container;
use tubalmartin\CssMin\Minifier;

class LayoutManager
{
    /**
     * @var LayoutBase[]
     */
    protected static $usedLayouts = [];
    protected static $usedStyles = [];

    public static function useLayout(string $class)
    {
        static::$usedLayouts[$class] = $class;
    }

    public static function useStyle(string $path)
    {
        static::$usedStyles[] = $path;
    }

    public static function init()
    {
        Container::getPage()::css('/Layout/assetsGlobal/style.css');
        Container::getPage()::js('/Layout/assetsGlobal/script.js');
        foreach (static::$usedLayouts as $class) {
            $class::initAssets();
        }

        // abort if no styles were used.
        if (!static::$usedStyles) {
            return;
        }

        // compose cache path.
        if (!\Config::$devMode) {
            $cachePath = '';
            foreach (static::$usedStyles as $style) {
                $cachePath .= md5($style);
            }

            $cachePath = '/cache/css/' . md5($cachePath) . '.css';
        } else {
            $cachePath = '/cache/css/style.css';
        }

        // check if cache file exists.
        if (\Config::$devMode || !is_file(SF_ROOT_PATH . $cachePath)) {
            // cache styles.
            $styleString = '';

            $compiler = new Compiler();
            foreach (static::$usedStyles as $style) {
                if (!is_file($style)) {
                    continue;
                }

                $cssData = file_get_contents($style);
                if (str_ends_with($style, '.scss')) {
                    // compile scss.
                    $compiler->setImportPaths(dirname($style));
                    $cssData = $compiler->compileString($cssData)->getCss();
                }

                $styleString .= $cssData;
            }

            // minify resulting css.
            if (!\Config::$devMode) {
                $min = new Minifier();
                $styleString = $min->run($styleString);
            }

            // write new style.
            file_put_contents(SF_ROOT_PATH . $cachePath, $styleString);
        }

        // use cached style.
        Container::getPage()::css($cachePath);
    }
}