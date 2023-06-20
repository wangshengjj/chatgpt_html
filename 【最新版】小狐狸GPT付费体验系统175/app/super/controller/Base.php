<?php
namespace app\super\controller;

use think\facade\Request;

class Base
{
    protected static $super;

    public function __construct()
    {
        $token = Request::header('s-token');
        if($token) {
            session_id($token);
        }
        session_start();
        if (empty($_SESSION['super'])) {
            die(json_encode(['errno' => 403, 'message' => '你已掉线，请重新登录']));
        }
        self::$super = json_decode($_SESSION['super'], true);
    }
}
