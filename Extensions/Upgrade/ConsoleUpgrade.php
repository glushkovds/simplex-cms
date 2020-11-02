<?php


namespace App\Extensions\Upgrade;


use App\Core\Console\Alert;
use Simplex\Core\ConsoleBase;

class ConsoleUpgrade extends ConsoleBase
{

    public function launch($from, $to)
    {
        $cms = SF_ROOT_PATH;
        Alert::text('Copying new cms...');
        $result = shell_exec("[ ! -d $to ] && mkdir $to; cd $cms; find . -type f -not -path '*/.git/*' -not -path '*/.idea/*' -exec cp --parents '{}' '$to/' \; 2>&1");
        $success = $result == '';
        Alert::result($success, 'Success', 'Fail');
        if ($success) {
        } else {
            exit;
        }
    }

    public function copyExt($from, $to, $name)
    {
        $this->job("Copy extension $name...", function () use ($from, $to, $name) {
            $newDirName = ucfirst($name);
            $files = array_filter(explode("\n", shell_exec("find $from/ext/$name -type f")));
            foreach ($files as $file) {
                $this->job("Upgrade file $file...", function () use ($file, $newDirName, $from, $to, $name) {
                    $relPath = dirname(str_replace($from, '', $file));
                    return (new UpFile($file, ['oldRoot' => $from, 'newRoot' => $to]))->upgrade();
                });
            }
            return true;
        });
    }

    protected function job($name, $closure)
    {
        Alert::text($name);
        $success = $closure();
        Alert::result($success, 'Success', 'Fail');
        if (!$success) {
            exit;
        }
    }

}