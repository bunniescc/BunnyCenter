<?php

use BunnyPHP\Model;

class ApiModel extends Model
{
    protected $_column = [
        'id' => ['integer', 'not null'],
        'uid' => ['integer', 'not null'],
        'name' => ['text', 'not null'],
        'client_id' => ['text', 'not null'],
        'client_secret' => ['text', 'not null'],
        'redirect_uri' => ['text', 'not null'],
        'url' => ['text', 'not null'],
        'icon' => ['text', 'not null'],
        'type' => ['integer'],
        'scope' => ['integer'],
    ];
    protected $_pk = ['id'];
    protected $_ai = 'id';

    public function check($clientId)
    {
        if ($api = $this->where(['client_id = ?'], [$clientId])->fetch(['id', 'name', 'type', 'url', 'icon', 'redirect_uri', 'scope'])) {
            $api['scope'] = explode('|', $api['scope']);
            return $api;
        } else {
            return null;
        }
    }

    public function validate($clientId, $clientSecret)
    {
        if ($api = $this->where(['client_id = ? and client_secret = ?'], [$clientId, $clientSecret])->fetch(['id', 'name', 'type', 'url', 'icon', 'redirect_uri', 'scope'])) {
            $api['scope'] = explode('|', $api['scope']);
            return $api;
        } else {
            return null;
        }
    }

    public function getAuthorByClientId($clientId)
    {
        if ($row = $this->where(['client_id = ?'], [$clientId])->fetch(['uid'])) {
            return $row['uid'];
        } else {
            return null;
        }
    }

    public function getAuthorByAppId($aid)
    {
        if ($row = $this->where(['id = ?'], [$aid])->fetch('uid')) {
            return $row['uid'];
        } else {
            return null;
        }
    }
}