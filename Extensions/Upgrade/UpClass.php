<?php


namespace App\Extensions\Upgrade;


use App\Core\Console\Alert;

class UpClass extends UpFile
{

    protected $knownClasses = [
        'APIBase' => 'Simplex\Core\ApiBase',
    ];

    public function upgrade()
    {
        $this->upgradeNamespace();
        $this->upgradeClass();
        $this->upgradeExtends();
        $this->save();
        print_r($this->data);
        print_r($this->newData);
        die;
    }

    protected function upgradeClass()
    {
        if (strpos($this->data['class'], 'API') === 0) {
            $this->newData['class'] = substr($this->data['class'], 3);
        } else {
            throw new \Exception("Unknown type of class $this->path");
        }
    }

    protected function upgradeNamespace()
    {
        $matches = [];
        preg_match('@Extensions/([\w\d_]+)@', $this->newPath, $matches);
        $extensionName = $matches[1];
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

    protected function upgradeExtends()
    {
        if ($new = $this->knownClasses[$this->data['extends']] ?? null) {
            $fqnParts = array_filter(explode('\\', $new));
            if (count($fqnParts) > 1) {
//                $namespace = array_slice($fqnParts, -1);
                $newFull = $new;
                $new = end($fqnParts);
                $this->newData['use'][$newFull] = $newFull;
            }
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
        file_put_contents($this->newPath, $contents);
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

}