<?php

namespace App\Extensions\Content;

use App\Extensions\Breadcrumbs\Breadcrumbs;
use Simplex\Core\Core;
use Simplex\Core\DB;
use Simplex\Core\Page;

/**
 * ComContent class
 *
 * Output content on site page
 *
 */
class Content extends \Simplex\Core\ComponentBase
{

    public function &get()
    {
        $q = "SELECT * FROM content WHERE active=1 AND path=@SITE_PATH";
        if ($content = DB::result($q)) {
            $content['params'] = unserialize($content['params']);
        }
        return $content;
    }

    public static function getStatic($alias)
    {
        $q = "SELECT * FROM content WHERE active=1 AND alias = '$alias'";
        if ($content = DB::result($q)) {
            $content['params'] = unserialize($content['params']);
        }
        return $content;
    }

    protected function content()
    {
        $content = $this->get();

        if ($content) {
            if (!Core::ajax()) {
                $this->breadcrumbs($content);
            }

            Page::seo($content['title']);

            $children = array();
            if (empty($content['params']['hide_children'])) {
                $q = "SELECT content_id, title, short, text, path, photo FROM content WHERE active=1 AND pid=" . (int)$content['content_id'];
                $children = DB::assoc($q);
            }
            include dirname(__FILE__) . '/tpl/com_item.tpl';
        } else {
            Core::error404();
        }
    }

    private function breadcrumbs($content)
    {
        Breadcrumbs::add($content['title'], $content['path']);
        $id = (int)$content['pid'];
        while ($id) {
            $q = "SELECT pid, title, path FROM content WHERE content_id=$id";
            $id = 0;
            if ($content = DB::result($q)) {
                Breadcrumbs::add($content['title'], $content['path']);
                $id = (int)$content['pid'];
            }
        }
    }

}
