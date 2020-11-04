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
//        $isStatic = false;
//        $fexts = 'png|gif|jpg|jpeg|ico|js|css|php|htm|html|swf|mp3|txt|pdf|doc|docx|xls|xlsx|zip|rar'
//            . '|ppt|pptx|xml|ttf|woff|eot|otf|less|csv|tmp|class|old|template|md|json';
//        foreach (explode('|', $fexts) as $fext) {
//            $isStatic |= preg_match("@\.$fext$@", $this->path);
//        }
//        return $isStatic;
    }

    public function upgrade()
    {
        if ($this->isClass()) {
            $upgrader = new UpClass($this->path, $this->config);
            return $upgrader->upgrade();
        }
        if ($this->isInterface()) {
            $upgrader = new UpInterface($this->path, $this->config);
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
    }

    protected function findNewPath()
    {
        $p = $this->getPlace();
        $relPath = dirname(str_replace("{$this->config['oldRoot']}/{$p['oldBase']}/{$p['oldPlace']}", '', $this->path));
        return rtrim("{$this->config['newRoot']}/{$p['newBase']}/{$p['newPlace']}$relPath", '/') . '/' . $this->findNewName();
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

    protected function getNewDbCredentials()
    {
        return static::getDbCredentials($this->config['newRoot'] . '/config.php');
    }

    public static function getDbCredentials($file)
    {
        $raw = file_get_contents($file);
        $data = [];
        $matches = [];
        preg_match("@db_host = '(.+)';@U", $raw, $matches);
        $data['host'] = $matches[1];
        preg_match("@db_user = '(.+)';@U", $raw, $matches);
        $data['user'] = $matches[1];
        preg_match("@db_pass = '(.+)';@U", $raw, $matches);
        $data['pass'] = $matches[1];
        preg_match("@db_name = '(.+)';@U", $raw, $matches);
        $data['db'] = $matches[1];
        return $data;
    }

    protected function getNewDb()
    {
        $cred = $this->getNewDbCredentials();
        $db = new MySQL();
        $db->connect(...array_values($cred));
        return $db;
    }

}