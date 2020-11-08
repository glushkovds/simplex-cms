<?php


namespace App\Extensions\Upgrade;


use App\Extensions\Upgrade\Hooks\Hooks;

class UpFile
{

    use Configable;

    protected $path;
    protected $data;
    public $newData;

    protected $knownClasses = [
        'APIBase' => 'Simplex\Core\ApiBase',
        'SFDB' => 'Simplex\Core\DB',
        'SFDBWhere' => 'Simplex\Core\DB\Where',
        'SFModelBase' => 'Simplex\Core\ModelBase',
        'SFUser' => 'Simplex\Core\User',
        'SFConfig' => 'Simplex\Core\Container::getConfig()',
        'SFCore' => 'Simplex\Core\Core',
        'SFPage' => 'Simplex\Core\Page',
        'SFComBase' => 'Simplex\Core\ComponentBase',
        'SFModBase' => 'Simplex\Core\ModuleBase',
        'ComAction' => 'Simplex\Core\ControllerBase',
        'Notifier' => 'Simplex\Core\Alert\Site\Alert',
        'PlugMail' => 'Simplex\Core\Mail',
        'PlugSMS' => 'Simplex\Core\Sms',
        'Service' => 'Simplex\Core\Service',
        'PlugJQuery' => 'App\Plugins\Jquery\Jquery',
    ];

    public function __construct($path, $config)
    {
        $this->path = $path;
        $this->config = $config;
        $this->newData = $this->data = static::parse($path);
    }

    public function addKnownClasses($classes)
    {
        $this->knownClasses += $classes;
    }

    protected function isClass()
    {
        return (bool)strpos($this->path, '.class.php');
    }

    protected function isInterface()
    {
        return (bool)strpos($this->path, '.interface.php');
    }

    protected function isTpl()
    {
        return (bool)strpos($this->path, '.tpl')
            || !$this->isClass() && strpos($this->path, '.php');
    }

    protected function isStatic()
    {
        return !$this->isClass() && !$this->isTpl() && !$this->isInterface();
    }

    protected function getHooks($up = null)
    {
        if (empty($up)) {
            $up = $this;
        }
        return new Hooks($up);
    }

    public function upgrade()
    {
        if ($this->isClass()) {
            $upgrader = new UpClass($this->path, $this->config);
            $hooks = $this->getHooks($upgrader);
            $hooks->before('class');
            $result = $upgrader->upgrade();
            $hooks->after('class');
            return $result;
        }
        if ($this->isInterface()) {
            $upgrader = new UpInterface($this->path, $this->config);
            $hooks = $this->getHooks($upgrader);
            $hooks->before('interface');
            $result = $upgrader->upgrade();
            $hooks->after('interface');
            return $result;
        }
        if ($this->isTpl()) {
            $hooks = $this->getHooks();
            $hooks->before('tpl');
            $this->replaceClasses();
            $hooks->after('tpl');
            return $this->save();
        }
        if ($this->isStatic()) {
            return $this->copy();
        }
        throw new \Exception("Unknown type of file $this->path");
    }

    protected function getPlace()
    {
        $getPlace = function ($what) {
            $pathParts = explode('/', $this->path);
            foreach ($pathParts as $index => $part) {
                if ($part == $what) {
                    break;
                }
            }
            $oldExtName = $pathParts[$index + 1];
            $extName = ucfirst($oldExtName);
            return ['oldPlace' => $oldExtName, 'newPlace' => $extName];
        };
        if (strpos($this->path, '/ext/') !== false) {
            return ['oldBase' => 'ext', 'newBase' => 'Extensions'] + $getPlace('ext');
        }
        if (strpos($this->path, '/plug/') !== false) {
            return ['oldBase' => 'plug', 'newBase' => 'Plugins'] + $getPlace('plug');
        }
        return null;
    }

    protected function findNewPath()
    {
        $p = $this->getPlace();
        if ($p) {
            $relPath = dirname(str_replace("{$this->config->fromRoot}/{$p['oldBase']}/{$p['oldPlace']}", '', $this->path));
            return rtrim("{$this->config->toRoot}/{$p['newBase']}/{$p['newPlace']}$relPath", '/') . '/' . $this->findNewName();
        }
        return str_replace($this->config->fromRoot, $this->config->toRoot, $this->path);
    }

    protected function findNewName()
    {
        return basename($this->path);
    }

    public function save()
    {
        $newPath = $this->findNewPath();
        if (!is_dir(dirname($newPath))) {
            mkdir(dirname($newPath), 0755, true);
        }
        return file_put_contents($newPath, $this->newData['contents']);
    }

    protected function copy()
    {
        $newPath = $this->findNewPath();
        if (!is_dir(dirname($newPath))) {
            mkdir(dirname($newPath), 0755, true);
        }
        return copy($this->path, $newPath);
    }

    protected static function parse($path)
    {
        $contents = file_get_contents($path);
        $result = [
            'contents' => $contents,
        ];
        return $result;
    }

    public function replace($search, $replace)
    {
        $this->newData['contents'] = str_replace($search, $replace, $this->newData['contents']);
    }

    protected function replaceClasses()
    {
        foreach ($this->knownClasses as $from => $to) {
            $this->replace($from, $to);
        }
        $classes = static::findClassesInContent($this->newData['contents']);
        foreach ($classes as $class) {
            $newClass = UpClass::upgradeClassName($class);
            if ($newClass) {
//                ['fqn' => $fqn] = UpClass::classNameInfo($newClass);
                $this->replace($class, $newClass);
            }
        }
    }

    protected static function findClassesInContent($content)
    {
        $matches = [];
        preg_match_all('@([\w\d\_]+)::@', $content, $matches);
        $classes = $matches[1] ?? [];
        preg_match_all('@new ([\w\d\_]+)@', $content, $matches);
        $classes = array_merge($classes, $matches[1] ?? []);
        $classes = array_filter(array_unique($classes));
        return $classes;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }



}