<?php


use BunnyPHP\BunnyPHP;
use BunnyPHP\Filter;

class AuthFilter extends Filter
{
    public function doFilter($param = [])
    {
        if ($this->_mode == BunnyPHP::MODE_NORMAL) {
            $req = BunnyPHP::getRequest();
            $token = $req->getSession('token');
            if (!$token) $token = $req->getHeader('token');
            if ($token) {
                $user = (new UserModel())->check($token);
                if ($user) {
                    BunnyPHP::app()->set('tp_user', $user);
                    $this->assign('tp_user', $user);
                    return self::NEXT;
                } else {
                    $this->redirect('user', 'login', ['referer' => $_SERVER['REQUEST_URI']]);
                }
            } else {
                $this->redirect('user', 'login', ['referer' => $_SERVER['REQUEST_URI']]);
            }
        } elseif ($this->_mode == BunnyPHP::MODE_API) {
            if (isset($_POST['client_id']) && isset($_POST['access_token'])) {
                $clientId = $_POST['client_id'];
                $accessToken = $_POST['access_token'];
                if ($apiInfo = (new ApiModel())->check($clientId)) {
                    if ($apiInfo['type'] == 1 || $param[0] == '' || in_array($param[0], $apiInfo['scope'])) {
                        if ($tokenInfo = (new OauthTokenModel())->check($clientId, $accessToken)) {
                            if (in_array($param[0], $tokenInfo['scope'])) {
                                $user = (new UserModel)->getUserByUid($tokenInfo['uid']);
                                BunnyPHP::app()->set('tp_user', $user);
                                BunnyPHP::app()->set('tp_api', $apiInfo);
                                return self::NEXT;
                            } else {
                                $this->error(['ret' => 2002, 'status' => 'permission denied']);
                            }
                        } else {
                            $this->error(['ret' => 2003, 'status' => 'invalid token']);
                        }
                    } else {
                        $this->error(['ret' => 2002, 'status' => 'permission denied']);
                    }
                } else {
                    $this->error(['ret' => 2001, 'status' => 'invalid client id']);
                }
            } else {
                $this->error(['ret' => -7, 'status' => 'parameter cannot be empty']);
            }
        } elseif ($this->_mode == BunnyPHP::MODE_AJAX) {
            if (BunnyPHP::app()->get('tp_ajax') === true) {
                $token = BunnyPHP::getRequest()->getSession('token');
                if (!$token) $token = BunnyPHP::getRequest()->getHeader('token');
                if ($token) {
                    $user = (new UserModel)->check($token);
                    if ($user != null) {
                        BunnyPHP::app()->set('tp_user', $user);
                        return self::NEXT;
                    } else {
                        $this->error(['ret' => 2002, 'status' => 'permission denied']);
                    }
                } else {
                    $this->error(['ret' => 2002, 'status' => 'permission denied']);
                }
            } else {
                $this->error(['ret' => 2002, 'status' => 'permission denied']);
            }
        } else {
            $this->error(['ret' => 2002, 'status' => 'permission denied']);
        }
        return self::STOP;
    }
}