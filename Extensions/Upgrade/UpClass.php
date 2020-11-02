<?php


namespace App\Extensions\Upgrade;


use App\Core\Console\Alert;

class UpClass extends UpFile
{

    public function upgrade()
    {
        $this->upgradeNamespace();
        $this->upgradeClass();
        $this->upgradeExtends();
        $this->replaceClasses();
        $this->save();
        return true;
//        print_r($this->data);
//        print_r($this->newData);
//        die;
    }

    protected function upgradeClass()
    {
        if (strpos($this->data['class'], 'API') === 0) {
            $this->newData['class'] = 'Api' . substr($this->data['class'], 3);
        } else {
            throw new \Exception("Unknown type of class $this->path");
        }
    }

    protected function upgradeNamespace()
    {
        ['new' => $extensionName] = $this->findExtName();
        $this->newData['namespace'] = "App\Extensions\\$extensionName";
    }

    protected function saveNamespace()
    {
        if ($this->data['namespace']) {
            $this->replace($this->data['namespace'], $this->newData['namespace']);
        } else {
            $this->replace('<?php', '<?php ' . "\n\n" . $this->newData['namespace'] . ';');
        }
    }

    protected function simplifyDqn($class)
    {
        $fqnParts = array_filter(explode('\\', $class));
        if (count($fqnParts) > 1) {
//                $namespace = array_slice($fqnParts, -1);
            $newFull = $class;
            $class = end($fqnParts);
            $this->newData['use'][$newFull] = $newFull;
        }
        return $class;
    }

    protected function upgradeExtends()
    {
        if ($new = $this->knownClasses[$this->data['extends']] ?? null) {
            $new = $this->simplifyDqn($new);
            $this->newData['extends'] = $new;
//            $this->replacePart('extends');
            return;
        }
        throw new \Exception("Unknown class {$this->data['extends']}");
    }

    protected function saveUse()
    {
        if ($this->data['use']) {
            jopa();
        } else {
//            preg_replace('@namespace @')
        }
    }

    protected function save()
    {
        $contents = '<?php' . "\n\n";
        $contents .= "namespace {$this->newData['namespace']};\n\n";
        $contents .= $this->useToStr() . "\n\n";
        $contents .= $this->newData['annotations'] ? $this->newData['annotations'] . "\n" : '';
        $contents .= "class {$this->newData['class']} extends {$this->newData['extends']}\n{\n";
        $contents .= $this->newData['classContents'] . "\n}\n";
        $this->newData['contents'] = $contents;
        $newPath = $this->findNewPath();
        mkdir(dirname($newPath), 0777, true);
        file_put_contents($newPath, $contents);
    }

    protected function findNewName()
    {
        return $this->newData['class'] . '.php';
    }

    protected function useToStr()
    {
        $str = [];
        foreach ($this->newData['use'] as $use) {
            $str[] = "use $use;";
        }
        return implode("\n", $str);
    }

    protected static function parse($path)
    {
        $contents = file_get_contents($path);
        $matches = [];
        preg_match('@class ([\w\d_]+)@', $contents, $matches);
        $class = $matches[1];
        preg_match('@extends ([\w\d_]+)@', $contents, $matches);
        $extends = $matches[1] ?? null;
        preg_match('@namespace ([\w\d_\\\]+)@', $contents, $matches);
        $namespace = $matches[1] ?? null;
        preg_match('@(\/\*\*.+\*\/)[\n\r\s]+class@Uim', $contents, $matches);
        $annotations = $matches[1] ?? null;
        preg_match('@class [\w\d_]+( extends [\w\d_]+)?[\n\r\s]+\{(.*)\}@smi', $contents, $matches);
        $classContents = $matches[2] ?? null;
        if (empty($classContents)) {
            throw new \Exception("Cant detect class contents $path");
        }
        $result = [
            'contents' => $contents,
            'class' => $class,
            'extends' => $extends,
            'namespace' => $namespace,
            'use' => [],
            'annotations' => $annotations,
            'classContents' => $classContents,
        ];
        return $result;
    }

    protected function replacePart($partName)
    {
        $this->replace($this->data[$partName], $this->newData[$partName]);
    }

    protected function replaceClassContents($search, $replace)
    {
        $this->newData['classContents'] = str_replace($search, $replace, $this->newData['classContents']);
    }

    protected function replaceClasses()
    {
        foreach ($this->knownClasses as $from => $to) {
            $this->replaceClassContents($from, $this->simplifyDqn($to));
        }
        $matches = [];
        preg_match_all('@([\w\d\_]+)::@', $this->newData['classContents'], $matches);
        $classes = $matches[1] ?? [];
        preg_match_all('@new ([\w\d\_]+)@', $this->newData['classContents'], $matches);
        $classes = array_merge($classes, $matches[1] ?? []);
        $classes = array_filter(array_unique($classes));
        foreach ($classes as $class) {
            if (strpos($class, 'Model') === 0) {
                $this->newData['use'][$class] = "{$this->newData['namespace']}\\Models\\$class";
//                $this->replaceClassContents($from, $this->simplifyDqn($to));
            }
        }
    }

}