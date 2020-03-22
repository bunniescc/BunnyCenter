<?php


use BunnyPHP\BunnyPHP;
use BunnyPHP\Language;
use BunnyPHP\Service;

class UserService extends Service
{
    public function getLoginUser()
    {
        $req = BunnyPHP::getRequest();
        $token = $req->getSession('token');
        if (!$token) $token = $req->getHeader('token');
        if ($token) {
            return (new UserModel())->check($token);
        } else {
            return null;
        }
    }

    public function login(string $username, string $password)
    {
        $res = (new UserModel())->login($username, $password);
        return $res;
    }

    public function register($username, $password, $email, $nickname = '')
    {
        $res = (new UserModel())->register($username, $password, $email, $nickname);
        if ($res['ret'] == 0) {
            $emailService = new EmailService();
            $emailService->sendMail('email/reg.html', ['nickname' => $res['nickname']], $res['email'], Language::get(''));
        }
        return $res;
    }
}