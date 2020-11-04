<?php


namespace App\Extensions\Upgrade;


use App\Core\Console\Alert;

class UpClass extends UpEntity
{

    protected static $name = 'class';

    protected function upgradeDelegate()
    {
        if (get_class($this) == self::class) {
            if ($this->data['extends'] == 'SFComBase') {
                $upgrader = new UpComponent($this->path, $this->config);
                return $upgrader->upgrade();
            }
            if ($this->data['extends'] == 'SFModBase') {
                $upgrader = new UpModule($this->path, $this->config);
                return $upgrader->upgrade();
            }
        }
        return false;
    }

    protected function upgradeInner()
    {

        parent::upgradeInner();
        $this->upgradeClass();
        $this->upgradeExtends();
//        print_r($this->data);
//        print_r($this->newData);
//        die;
    }

    protected function upgradeClass()
    {
        $classParts = static::splitCamelCase($this->data['class']);
        if (strpos($this->data['class'], 'API') === 0) {
            $this->newData['class'] = 'Api' . substr($this->data['class'], 3);
        } elseif ($classParts[0] == 'Com') {
            $this->newData['class'] = substr($this->data['class'], 3);
        } elseif ($classParts[0] == 'Mod') {
            $this->newData['class'] = 'Module' . substr($this->data['class'], 3);
        } elseif ($classParts[0] == 'Plug') {
            $this->newData['class'] = substr($this->data['class'], 4);
        } else {
//            throw new \Exception("Unknown type of class $this->path");
        }
    }

    protected function upgradeExtends()
    {
        if ($new = $this->knownClasses[$this->data['extends']] ?? null) {
            $new = $this->simplifyFqn($new);
            $this->newData['extends'] = $new;
            return;
        }
//        throw new \Exception("Unknown class {$this->data['extends']}");
    }

    protected function putEntity()
    {
        $stack = [];
        if (!empty($this->newData['modifiers']['abstract'])) {
            $stack[] = 'abstract';
        }
        $stack[] = static::$name;
        $stack[] = $this->newData[static::$name];
        return implode(' ', $stack);
    }


    protected function replaceClassContents($search, $replace)
    {
        $this->newData['classContents'] = str_replace($search, $replace, $this->newData['classContents']);
    }

    protected static function parse($path)
    {
        $result = parent::parse($path);
        $contents = file_get_contents($path);
        $result['modifiers']['abstract'] = strpos($contents, 'abstract class') !== false;
        return $result;
    }


}