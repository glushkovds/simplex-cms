<?php


namespace App\Extensions\Auth;


use Simplex\Core\ControllerBase;
use Simplex\Core\DB;

class Auth extends ControllerBase
{

    public function login()
    {
        $login = $_REQUEST['login'];
        $password = $_REQUEST['password'];
        $redirect = $_REQUEST['r'] ?? '/';
        if (strpos($redirect, '//') !== false) {
            $redirect = '/';
        }
        if (preg_match('@^[0-9a-z\@\-\.]+$@i', $login)) {
            DB::bind(array('USER_LOGIN' => strtolower($login)));
            $q = "SELECT u.user_id, u.role_id, u.login, u.password
                    FROM user u
                    JOIN user_role r ON r.role_id=u.role_id
                    WHERE login=@USER_LOGIN
                      AND u.active=1
                      AND r.active=1";
            if ($row = DB::result($q)) {
                if (md5($password) === $row['password']) {
                    $hash = md5(rand(0, 999) . microtime());
                    $_SESSION['user_id'] = $row['user_id'];
                    $_SESSION['user_hash'] = $hash;

                    DB::bind(array('USER_ID' => $row['user_id'], 'USER_HASH' => $hash));
                    $q = "UPDATE user SET hash = @USER_HASH WHERE user_id=@USER_ID";
                    DB::query($q);

                    if (isset($_POST['login']['remember']) && $row['role_id'] != 5) {
                        setcookie("ch", md5($row['user_id']), time() + 60 * 60 * 24 * 3, "/");
                        setcookie("cs", $hash, time() + 60 * 60 * 24 * 3, "/");
                    }
                }
            }
        }
        header("Location: $redirect");
        exit;
    }

    public function logout()
    {
        $redirect = $_REQUEST['r'] ?? '/';
        if (strpos($redirect, '//') !== false) {
            $redirect = '/';
        }
        unset($_SESSION['user_id']);
        unset($_SESSION['user_hash']);
        setcookie("ch", '', 0, "/");
        setcookie("cs", '', 0, "/");
        header("Location: $redirect");
        exit;
    }

}