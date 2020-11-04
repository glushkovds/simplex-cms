<?php


namespace App\Extensions\Upgrade;


use App\Core\Console\Alert;
use Simplex\Core\ConsoleBase;

class ConsoleUpgrade extends ConsoleBase
{

    protected $fromRoot;
    protected $toRoot;

    public function launch($from, $to, $copyDb, $copyCms, $newDbName = null)
    {
        $this->fromRoot = $from;
        $this->toRoot = $to;
        $cms = SF_ROOT_PATH;
        if ($copyCms) {
            $this->job('Copying new cms...', function () use ($from, $to, $cms) {
                $result = shell_exec("[ ! -d $to ] && mkdir $to; cd $cms; find . -type f -not -path '*/.git/*' -not -path '*/.idea/*' -exec cp --parents '{}' '$to/' \; 2>&1");
                return $result == '';
            });
        }
        if ($copyDb) {
            $old = UpFile::getDbCredentials($from . '/config.php');
            $newDbName || $newDbName = 'new' . $old['db'];
            $this->upgradeConfig($from, $to, $newDbName);
            $this->copyDb($old['db'], $newDbName, $old['host'], $old['user'], $old['pass']);
        }
        $this->copyExts($from, $to);
        $this->copyPlugs($from, $to);
    }

    public function copyExts($from, $to)
    {
        $exts = array_filter(explode("\n", shell_exec("cd $from/ext; find . -type d -maxdepth 1 | cut -c 3-")));
//        print_r($exts);
        foreach ($exts as $ext) {
            $this->copyExt($from, $to, $ext);
        }
    }

    public function copyPlugs($from, $to)
    {
        $names = array_filter(explode("\n", shell_exec("cd $from/plug; find . -type d -maxdepth 1 | cut -c 3-")));
//        print_r($exts);
        foreach ($names as $name) {
            $this->copyPlug($from, $to, $name);
        }
    }

    public function upgradeConfig($from, $to, $newDbName)
    {
        $this->job('Upgrading config...', function () use ($from, $to, $newDbName) {
            $old = UpFile::getDbCredentials($from . '/config.php');
            $newConfig = file_get_contents($to . '/config.php');
            $newConfig = preg_replace("@(db_host = ')(.+)(';)@U", "$1{$old['host']}$3", $newConfig);
            $newConfig = preg_replace("@(db_user = ')(.+)(';)@U", "$1{$old['user']}$3", $newConfig);
            $newConfig = preg_replace("@(db_pass = ')(.+)(';)@U", "$1{$old['pass']}$3", $newConfig);
            $newConfig = preg_replace("@(db_name = ')(.+)(';)@U", "$1$newDbName$3", $newConfig);
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
    public function copyDb($from, $to, $host, $login, $pass)
    {
//        $old = UpFile::getDbCredentials($from . '/config.php');
//        $host = $old['host'];
//        $login = $old['user'];
//        $pass = $old['pass'];
        $this->job("Dump db $from...", function () use ($from, $host, $login, $pass) {
            return shell_exec("mysqldump -u$login -p$pass -h $host $from > /tmp/1.sql") == '';
        });
        $this->job("Import db $to...", function () use ($to, $host, $login, $pass) {
            return shell_exec("mysql -u$login -p$pass -h $host -e 'create database $to'") == ''
                && shell_exec("mysql -u$login -p$pass -h $host -e 'use $to; \. /tmp/1.sql'") == '';
        });
    }

    public function copyExt($from, $to, $name)
    {
        $this->job("Copy extension $name...", function () use ($from, $to, $name) {
            $files = array_filter(explode("\n", shell_exec("find $from/ext/$name -type f")));
            foreach ($files as $file) {
                $this->copyFile($from, $to, $file);
            }
            return true;
        });
    }

    public function copyPlug($from, $to, $name)
    {
        $this->job("Copy plugin $name...", function () use ($from, $to, $name) {
            $files = array_filter(explode("\n", shell_exec("find $from/plug/$name -type f")));
            foreach ($files as $file) {
                $this->copyFile($from, $to, $file);
            }
            return true;
        });
    }

    public function copyFile($from, $to, $file)
    {
        $this->job("Upgrade file $file...", function () use ($file, $from, $to) {
            try {
                return (new UpFile($file, ['oldRoot' => $from, 'newRoot' => $to]))->upgrade();
            } catch (SkipFileException $e) {
                return ['result' => 'Skip', 'message' => $e->getMessage()];
            }
        });
    }

    protected function job($name, $closure)
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