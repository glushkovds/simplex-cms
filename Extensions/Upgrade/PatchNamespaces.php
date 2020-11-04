<?php


namespace App\Extensions\Upgrade;


class PatchNamespaces
{
    protected $classes = [];
    /** @var Config */
    protected $config;

    public function __construct($config)
    {
        $this->config = $config;
    }

    public function upgrade()
    {
        $this->collectClasses();
    }

    protected function collectClasses()
    {
        ConsoleUpgrade::job('Collect new classes...', function () {
            $files = array_filter(explode("\n", shell_exec("find {$this->config->toRoot} -type f -not -path '*/vendor/*' -name '*.php'")));
            $this->classes = array_values(array_filter(array_map(function ($file) {
                $possible = substr(basename($file), 0, -4);
                $contents = file_get_contents($file);
                if (
                    strpos($contents, "class $possible")
                    || strpos($contents, "interface $possible")
                ) {
                    try {
                        $up = new UpClass($file, ['oldRoot' => $this->config->fromRoot, 'newRoot' => $this->config->toRoot]);
                        return $up->getFqn();
                    } catch (SkipFileException $e) {
                        return false;
                    }
                }
                return false;
            }, $files)));
            print_r($this->classes);
            die;
            return true;
        });
    }

}