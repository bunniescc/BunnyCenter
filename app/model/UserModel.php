<?php


use BunnyPHP\Language;
use BunnyPHP\Model;

class UserModel extends Model
{
    protected $_column = [
        'uid' => ['integer', 'not null'],
        'username' => ['varchar(16)', 'not null'],
        'password' => ['varchar(32)', 'not null'],
        'nickname' => ['varchar(32)'],
        'email' => ['text', 'not null'],
        'token' => ['text', 'not null'],
        'expire' => ['text']
    ];
    protected $_pk = ['uid'];
    protected $_ai = 'uid';

    public function refreshToken($uid)
    {
        $user = $this->where('uid = :u', ['u' => $uid])->fetch();
        $timestamp = time();
        if (empty($user['expire']) || $timestamp > intval($user['expire'])) {
            $token = $this->createToken($user['username'], $timestamp);
            $updates = ['token' => $token, 'expire' => $timestamp + 604800];
            $this->where(["uid = :uid"], ['uid' => $uid])->update($updates);
        } else {
            $token = $user['token'];
        }
        return $token;
    }

    public function resetPassword($uid, $password)
    {
        return $this->where('uid = :u', ['u' => $uid])->update(['password' => md5($password)]);
    }

    public function login(string $username, string $password)
    {
        $user = $this->where('username=:u or email=:e', ['u' => $username, 'e' => $username])->fetch();
        if (!$user) {
            return ['ret' => 1002, 'status' => 'user does not exist', 'tp_error_msg' => Language::get('user_not_exist')];
        }
        if ($user['password'] != $this->encodePassword($password)) {
            return ['ret' => 1001, 'status' => 'wrong password', 'tp_error_msg' => Language::get('wrong_password')];
        }
        $timestamp = time();
        $uid = $user['uid'];
        if (empty($user['expire']) || $timestamp > intval($user['expire'])) {
            $token = $this->createToken($user['username'], $timestamp);
            $expire = $timestamp + 604800;
            $updates = ['token' => $token, 'expire' => $expire];
            $this->where(['uid=:uid'], ['uid' => $uid])->update($updates);
        } else {
            $expire = $user['expire'];
            $token = $user['token'];
        }
        return ['ret' => 0, 'status' => 'ok', 'uid' => $uid, 'username' => $user['username'], 'email' => $user['email'], 'token' => $token, 'nickname' => $user['nickname'], 'expire' => $expire];
    }

    public function register($username, $password, $email, $nickname = '')
    {
        if (!isset($username) || !isset($password) || !isset($email)) {
            return ['ret' => -7, 'status' => 'parameter cannot be empty', 'tp_error_msg' => Language::get('param_required')];
        }
        if (!$this->validateUsername($username)) {
            return ['ret' => 1004, 'status' => 'invalid username', 'tp_error_msg' => Language::get('invalid_username')];
        }
        if (!$this->validateEmail($email)) {
            return ['ret' => 1004, 'status' => 'invalid email', 'tp_error_msg' => Language::get('invalid_email')];
        }
        if ($this->where('username=:u or email=:e', ['u' => $username, 'e' => $email])->fetch()) {
            return ['ret' => 1003, 'status' => 'username already exists', 'tp_error_msg' => Language::get('username_exists')];
        }
        if ($nickname == '') {
            $nickname = $username;
        }
        $timestamp = time();
        $token = $this->createToken($username, $timestamp);
        $new_data = [
            'username' => $username,
            'email' => $email,
            'password' => $this->encodePassword($password),
            'nickname' => $nickname,
            'token' => $token,
            'expire' => $timestamp + 604800
        ];
        if ($uid = $this->add($new_data)) {
            return ['ret' => 0, 'status' => 'ok', 'uid' => $uid, 'username' => $username, 'email' => $email, 'token' => $token, 'nickname' => $nickname];
        } else {
            return ['ret' => -6, 'status' => 'database error', 'tp_error_msg' => Language::get('database_error')];
        }
    }

    private function validateUsername($username)
    {
        return preg_match('/^[A-Za-z0-9_]+$/u', $username) && strlen($username) >= 4;
    }

    private function validateEmail($email)
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    private function encodePassword($password)
    {
        return md5($password);
    }

    private function createToken($username, $timestamp)
    {
        return md5($username . $timestamp);
    }

    public function check($token)
    {
        return $this->where(['token = ? and expire> ?'], [$token, time()])->fetch();
    }

    public function getUserByUid($uid)
    {
        return $this->where('uid = ?', [$uid])->fetch(['uid', 'username', 'email', 'nickname']);
    }

    public function getUserByUsername($username)
    {
        return $this->where('username = ?', [$username])->fetch(['uid', 'username', 'email', 'nickname']);
    }

    public function getTokenByUid($uid)
    {
        if ($user = $this->where('uid = ?', [$uid])->fetch(['uid', 'token'])) {
            return $user['token'];
        } else {
            return null;
        }
    }
}