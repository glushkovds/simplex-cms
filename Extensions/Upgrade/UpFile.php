<?php


namespace App\Extensions\Upgrade;


class UpFile
{
    protected $path;
    protected $newPath;
    protected $data;
    protected $newData;

    public function __construct($path, $newPath)
    {
        $this->path = $path;
        $this->newPath = $newPath;
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
            $upgrader = new UpClass($this->path, $this->newPath);
            return $upgrader->upgrade();
        }
        if ($this->isTpl()) {

        }
        if ($this->isStatic()) {
            return $this->copy();
        }
        throw new \Exception("Unknown type of file $this->path");
    }

    protected function copy()
    {
        if (!is_dir(dirname($this->newPath))) {
            mkdir(dirname($this->newPath), 0755, true);
        }
        return copy($this->path, $this->newPath);
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

}