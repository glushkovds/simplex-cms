<?php


namespace App\Extensions\Upgrade;


class UpFile
{
    protected $path;
    protected $data;
    protected $newData;
    protected $config;

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
    ];

    public function __construct($path, $config)
    {
        $this->path = $path;
        $this->config = $config;
        $this->newData = $this->data = static::parse($path);
    }

    protected function isClass()
    {
        return (bool)strpos($this->path, '.class.php');
    }

    protected function isTpl()
    {
        return (bool)strpos($this->path, '.tpl');
    }

    protected function isStatic()
    {
        return (bool)strpos($this->path, '.css')
            || (bool)strpos($this->path, '.js')
            || (bool)strpos($this->path, '.png')
            || (bool)strpos($this->path, '.jpg');
    }

    public function upgrade()
    {
        if ($this->isClass()) {
            $upgrader = new UpClass($this->path, $this->config);
            return $upgrader->upgrade();
        }
        if ($this->isTpl()) {
            $this->replaceClasses();
            return $this->save();
        }
        if ($this->isStatic()) {
            return $this->copy();
        }
        throw new \Exception("Unknown type of file $this->path");
    }

    protected function findExtName()
    {
        if (strpos($this->path, '/ext/') !== false) {
            $pathParts = explode('/', $this->path);
            foreach ($pathParts as $index => $part) {
                if ($part == 'ext') {
                    break;
                }
            }
            $oldExtName = $pathParts[$index + 1];
            $extName = ucfirst($oldExtName);
            return ['old' => $oldExtName, 'new' => $extName];
        }
    }

    protected function findNewPath()
    {
        ['old' => $oldExtName, 'new' => $extName] = $this->findExtName();
        $relPath = dirname(str_replace("{$this->config['oldRoot']}/ext/$oldExtName", '', $this->path));
        return rtrim("{$this->config['newRoot']}/Extensions/$extName$relPath", '/') . '/' . $this->findNewName();
    }

    protected function findNewName()
    {
        return basename($this->path);
    }

    protected function save()
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

    protected function replace($search, $replace)
    {
        $this->newData['contents'] = str_replace($search, $replace, $this->newData['contents']);
    }

    protected function replaceClasses()
    {
        foreach ($this->knownClasses as $from => $to) {
            $this->replace($from, $to);
        }
    }

}