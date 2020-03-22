<?php

use BunnyPHP\Model;

class OauthTokenModel extends Model
{
    protected $_column = [
        'id' => ['integer', 'not null'],
        'uid' => ['integer', 'not null'],
        'client_id' => ['text', 'not null'],
        'access_token' => ['text', 'not null'],
        'scope' => ['text'],
        'expire' => ['text']
    ];
    protected $_pk = ['id'];
    protected $_ai = 'id';

    public function check($clientId, $accessToken)
    {
        if ($tokenInfo = $this->where(['client_id = ? and access_token = ? and expire > ?'], [$clientId, $accessToken, time()])->fetch(['uid', 'scope'])) {
            $tokenInfo['scope'] = explode('|', $tokenInfo['scope']);
            return $tokenInfo;
        } else {
            return null;
        }
    }

    public function get($uid, $clientId, $appType, $scope = [])
    {
        $timestamp = time();
        $scopeStr = implode('|', $scope);
        if (intval($appType) == 1 || intval($appType) == 2) {
            $seconds = 1296000;
        } else {
            $seconds = 172800;
        }
        if ($tokenRow = $this->where(['client_id = :ak and uid = :u'], ['ak' => $clientId, 'u' => $uid])->fetch()) {
            $tokenId = $tokenRow['id'];
            if ($timestamp < intval($tokenRow['expire'])) {
                $accessToken = $tokenRow['access_token'];
                $expire = $tokenRow['expire'];
            } else {
                $accessToken = $this->createToken($uid, $clientId, $timestamp);
                $expire = $timestamp + $seconds;
                $this->where(['id = :id'], ['id' => $tokenId])->update(['access_token' => $accessToken, 'expire' => $expire]);
            }
            if ($tokenRow['scope'] != $scopeStr) {
                $this->where(['id = :id'], ['id' => $tokenId])->update(['scope' => $scopeStr]);
            }
        } else {
            $accessToken = $this->createToken($uid, $clientId, $timestamp);
            $expire = $timestamp + $seconds;
            $tokenId = $this->add(['uid' => $uid, 'client_id' => $clientId, 'access_token' => $accessToken, 'expire' => $expire, 'scope' => $scopeStr]);
        }
        $response = ['access_token' => $accessToken, 'expire' => $expire];
        if ($scopeStr != '') {
            $response['scope'] = $scope;
        }
        if (intval($appType) == 1) {
            $response['refresh_token'] = $this->createRefreshToken($tokenId, $clientId, $accessToken);
        }
        return $response;
    }

    public function refresh($accessToken, $clientId, $refreshToken)
    {
        $tokenRow = $this->where(['client_id = ? and token = ?'], [$clientId, $accessToken])->fetch();
        $tokenId = $tokenRow['id'];
        if ($this->createRefreshToken($tokenId, $clientId, $accessToken) == $refreshToken) {
            $timestamp = time();
            $newAccessToken = $this->createToken($tokenRow['uid'], $clientId, $timestamp);
            $expire = $timestamp + 1296000;
            $this->where(['id = :id'], ['id' => $tokenId])->update(['access_token' => $newAccessToken, 'expire' => $expire]);
            return ['access_token' => $newAccessToken, 'expire' => $expire, 'refresh_token' => $this->createRefreshToken($tokenId, $clientId, $newAccessToken)];
        } else {
            return null;
        }
    }

    private function createToken($uid, $clientId, $timestamp)
    {
        return md5($uid . $clientId . $timestamp);
    }

    private function createRefreshToken($tokenId, $clientId, $accessToken)
    {
        return md5(md5($tokenId) . $accessToken . md5($clientId));
    }
}