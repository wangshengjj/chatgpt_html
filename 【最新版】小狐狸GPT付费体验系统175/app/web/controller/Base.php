<?php

namespace app\web\controller;

use think\facade\Request;
use think\facade\Db;

class Base
{
    protected static $user;
    protected static $site_id;

    public function __construct()
    {
        $token = Request::header('x-token');
        if ($token) {
            session_id($token);
        }
        session_start();
        $sitecode = Request::header('x-site');

        if (!empty($sitecode) && !empty($_SESSION['sitecode']) && $_SESSION['sitecode'] != $sitecode) {
            $this->noLoginError();
        }
        if (empty($_SESSION['user'])) {
            $this->noLoginError();
        }
        $user = json_decode($_SESSION['user'], true);
        if (empty($user['openid']) && empty($user['openid_mp'])) {
            $this->noLoginError();
        }

        self::$user = $user;
        self::$site_id = $user['site_id'];
    }

    private function noLoginError()
    {
        if (Request::action() == 'sendText') {
            echo '[error]请登录';
        } else {
            echo json_encode(['errno' => 403, 'message' => '请登录']);
        }
        exit;
    }

}
