<?php


namespace App\Extensions\Upgrade;


use App\Core\Console\Alert;

class UpClass extends UpFile
{

    public function upgrade()
    {
        $this->upgradeInner();
        $this->save();
        return true;
//        print_r($this->data);
//        print_r($this->newData);
//        die;
    }

    protected function upgradeInner()
    {
        $this->upgradeNamespace();
        $this->upgradeClass();
        $this->upgradeExtends();
        $this->replaceClasses();
//        print_r($this->data);
//        print_r($this->newData);
//        die;
    }

    protected function upgradeClass()
    {
        if (strpos($this->data['class'], 'API') === 0) {
            $this->newData['class'] = 'Api' . substr($this->data['class'], 3);
        } else {
//            throw new \Exception("Unknown type of class $this->path");
        }
    }

    protected function upgradeNamespace()
    {
        $relPath = dirname(str_replace("{$this->config['oldRoot']}/ext", '', $this->path));
        $relPathParts = array_filter(explode('/', $relPath));
        $ns = array_merge(['App', 'Extensions'], array_map('ucfirst', $relPathParts));
        $this->newData['namespace'] = implode('\\', $ns);
    }

    protected function extNamespace()
    {
        ['new' => $extensionName] = $this->findExtName();
        return "App\Extensions\\$extensionName";
    }

    protected function simplifyFqn($class)
    {
        $fqnParts = array_filter(explode('\\', $class));
        if (count($fqnParts) > 1) {
            $newFull = $class;
            $class = end($fqnParts);
            $this->newData['use'][$newFull] = $newFull;
        }
        return $class;
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

    protected function save()
    {
        $contents = '<?php' . "\n\n";
        $contents .= "namespace {$this->newData['namespace']};\n\n";
        $contents .= $this->useToStr() . "\n\n";
        $contents .= $this->newData['annotations'] ? $this->newData['annotations'] . "\n" : '';
        $contents .= "class {$this->newData['class']}";
        if ($this->newData['extends']) {
            $contents .= " extends {$this->newData['extends']}";
        }
        $contents .= "\n{\n";
        $contents .= $this->newData['classContents'] . "\n}\n";
        $this->newData['contents'] = $contents;
        $newPath = $this->findNewPath();
        if (!is_dir(dirname($newPath))) {
            mkdir(dirname($newPath), 0777, true);
        }
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
            if (strpos($this->newData['classContents'], $from)) {
                $this->replaceClassContents($from, $this->simplifyFqn($to));
            }
        }
        $matches = [];
        preg_match_all('@([\w\d\_]+)::@', $this->newData['classContents'], $matches);
        $classes = $matches[1] ?? [];
        preg_match_all('@new ([\w\d\_]+)@', $this->newData['classContents'], $matches);
        $classes = array_merge($classes, $matches[1] ?? []);
        $classes = array_filter(array_unique($classes));
        foreach ($classes as $class) {
            if (strpos($class, 'Model') === 0) {
                $classParts = static::splitCamelCase($class);
                $modelExtName = $classParts[1];
                $ns = "App\\Extensions\\$modelExtName\\Models\\$class";
                $this->newData['use'][$ns] = $ns;
//                $this->replaceClassContents($from, $this->simplifyFqn($to));
            }
        }
    }

    protected static function splitCamelCase($input)
    {
        return preg_split(
            '/(^[^A-Z]+|[A-Z][^A-Z]+)/',
            $input,
            -1, /* no limit for replacement count */
            PREG_SPLIT_NO_EMPTY /*don't return empty elements*/
            | PREG_SPLIT_DELIM_CAPTURE /*don't strip anything from output array*/
        );
    }

}