<?php


namespace App\Extensions\Upgrade;


use App\Core\Console\Alert;
use Simplex\Core\ConsoleBase;

class ConsoleUpgrade extends ConsoleBase
{

    /** @var Config */
    protected $config;

    public function createConfig($from, $to, $configPath, $newDbName = null)
    {
        $config = new Config();
        $config->fromRoot = $from;
        $config->toRoot = $to;
        $config->loadDbCredentials($newDbName);
        $config->save($configPath);
        Alert::text($config->getSerialized());
    }

    public function launch($configPath, $copyDb, $copyCms)
    {
        $this->config = new Config($configPath);
        $cms = SF_ROOT_PATH;
        if ($copyCms) {
            static::job('Copying new cms...', function () use ($cms) {
                $to = $this->config->toRoot;
                $result = shell_exec("[ ! -d $to ] && mkdir $to; cd $cms; find . -type f -not -path '*/.git/*' -not -path '*/.idea/*' -exec cp --parents '{}' '$to/' \; 2>&1");
                return $result == '';
            });
        }
        if ($copyDb) {
            $this->upgradeConfig($this->config->configPath);
//            $this->copyDb($this->config->configPath);
        }
        $this->copyExts($this->config->configPath);
        $this->copyPlugs($this->config->configPath);
        $this->copyTheme($this->config->configPath);
    }

    public function copyTheme($configPath)
    {
        $this->config = new Config($configPath);
        $from = $this->config->fromRoot;
        $to = $this->config->toRoot;
        static::job('Drop previous theme...', function () use ($to) {
            $result = shell_exec("[ -d $to/theme ] && rm -rf $to/theme 2>&1");
            return $result == '';
        });
        static::job("Copy theme...", function () {
            $from = $this->config->fromRoot;
            $files = array_filter(explode("\n", shell_exec("find $from/theme -type f")));
            foreach ($files as $file) {
                $this->copyFile($this->config->configPath, $file);
            }
            return true;
        });
    }

    public function copyExts($configPath)
    {
        $this->config = new Config($configPath);
        $from = $this->config->fromRoot;
        $exts = array_filter(explode("\n", shell_exec("cd $from/ext; find . -maxdepth 1 -type d | cut -c 3-")));
        foreach ($exts as $ext) {
            $this->copyExt($this->config->configPath, $ext);
        }
    }

    public function copyPlugs($configPath)
    {
        $this->config = new Config($configPath);
        $from = $this->config->fromRoot;
        $names = array_filter(explode("\n", shell_exec("cd $from/plug; find . -maxdepth 1 -type d | cut -c 3-")));
//        print_r($exts);
        foreach ($names as $name) {
            $this->copyPlug($this->config->configPath, $name);
        }
    }

    public function upgradeConfig($configPath)
    {
        $this->config = new Config($configPath);
        static::job('Upgrading config...', function () {
            $to = $this->config->toRoot;
            $newConfig = file_get_contents($to . '/config.php');
            $newConfig = preg_replace("@(db_host = ')(.+)(';)@U", "$1{$this->config->toDbHost}$3", $newConfig);
            $newConfig = preg_replace("@(db_user = ')(.+)(';)@U", "$1{$this->config->toDbUser}$3", $newConfig);
            $newConfig = preg_replace("@(db_pass = ')(.+)(';)@U", "$1{$this->config->toDbPass}$3", $newConfig);
            $newConfig = preg_replace("@(db_name = ')(.+)(';)@U", "$1{$this->config->toDbName}$3", $newConfig);
            return file_put_contents($to . '/config.php', $newConfig);
        });
    }

    /**
     * @param $from
     * @param $to
     * @param $host
     * @param $login
     * @param $pass
     * @example ./sf upgrade/copyDb team newteam
     */
    public function copyDb($configPath)
    {
        $this->config = new Config($configPath);
        static::job("Dump db {$this->config->fromDbName}...", function () {
            $login = $this->config->fromDbUser;
            $pass = $this->config->fromDbPass;
            $host = $this->config->fromDbHost;
            $db = $this->config->fromDbName;
            return shell_exec("mysqldump -u$login -p$pass -h $host $db > /tmp/1.sql") == '';
        });
        static::job("Import db {$this->config->toDbName}...", function () {
            $login = $this->config->toDbUser;
            $pass = $this->config->toDbPass;
            $host = $this->config->toDbHost;
            $db = $this->config->toDbName;
            return shell_exec("mysql -u$login -p$pass -h $host -e 'create database $db'") == ''
                && shell_exec("mysql -u$login -p$pass -h $host -e 'use $db; \. /tmp/1.sql'") == '';
        });
    }

    public function copyExt($configPath, $name)
    {
        $this->config = new Config($configPath);
        static::job("Copy extension $name...", function () use ($name) {
            $from = $this->config->fromRoot;
            $to = $this->config->toRoot;
            $files = array_filter(explode("\n", shell_exec("find $from/ext/$name -type f")));
            foreach ($files as $file) {
                $this->copyFile($this->config->configPath, $file);
            }
            return true;
        });
    }

    public function copyPlug($configPath, $name)
    {
        $this->config = new Config($configPath);
        static::job("Copy plugin $name...", function () use ($name) {
            $from = $this->config->fromRoot;
            $files = array_filter(explode("\n", shell_exec("find $from/plug/$name -type f")));
            foreach ($files as $file) {
                $this->copyFile($this->config->configPath, $file);
            }
            return true;
        });
    }

    public function copyFile($configPath, $file)
    {
        $this->config = new Config($configPath);
        static::job("Upgrade file $file...", function () use ($file) {
            $from = $this->config->fromRoot;
            $to = $this->config->toRoot;
            try {
                return (new UpFile($file, ['oldRoot' => $from, 'newRoot' => $to]))->upgrade();
            } catch (SkipFileException $e) {
                return ['result' => 'Skip', 'message' => $e->getMessage()];
            }
        });
    }

    public function patchNamespaces($configPath)
    {
        $this->config = new Config($configPath);
        $handler = new PatchNamespaces($this->config);
        $handler->upgrade();
    }

    public static function job($name, $closure)
    {
        Alert::text($name);
        $result = $closure();
        if (is_scalar($result)) {
            Alert::result($result, 'Success', 'Fail');
        } elseif (is_array($result)) {
            Alert::warning("{$result['result']}: {$result['message']}");
        }
        if (!$result) {
            exit;
        }
    }

}