<?php
namespace app\admin\controller;

use think\facade\Request;

class Base
{
    protected static $admin;
    protected static $site_id;

    public function __construct()
    {
        $token = Request::header('x-token');
        if($token) {
            session_id($token);
        }
        session_start();
        if (empty($_SESSION['admin'])) {
            die(json_encode(['errno' => 403, 'message' => '你已掉线，请重新登录']));
        }
        $admin = json_decode($_SESSION['admin'], true);
        self::$admin = $admin;
        self::$site_id = $admin['id'];
    }
}
