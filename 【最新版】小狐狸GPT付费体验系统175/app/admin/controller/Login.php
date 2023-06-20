<?php

namespace app\admin\controller;

use think\facade\Db;

class Login
{
    public function login()
    {
        try {
            $token = input('token', '', 'trim');
            if ($token) {
                $admin = Db::name('site')->where(['token' => $token])->find();
                if (!$admin) {
                    return errorJson('登录失败，请重试！');
                }
            } else {
                $username = input('username');
                $password = input('password');
                $admin = Db::name('site')->where(['phone' => $username])->find();
                if (!$admin || md5($admin['password']) != md5($password)) {
                    return errorJson('账号或密码错误');
                }
            }

            $now = time();
            if ($admin['is_delete']) {
                return errorJson('站点已被删除');
            }
            if (!$admin['status']) {
                return errorJson('账户已被禁用');
            }
            if ($admin['expire_time'] && $admin['expire_time'] < $now) {
                return errorJson('账户已过期');
            }

            if (!$token) {
                Db::name('site')
                    ->where('id', $admin['id'])
                    ->update([
                        'last_time' => time(),
                        'last_ip' => get_client_ip()
                    ]);
            }

            $token = uniqid() . $admin['id'];
            session_id($token);
            session_start();
            $_SESSION['admin'] = json_encode($admin);
        } catch (\Exception $e) {
            echo $e->getMessage();exit;
        }



        return successJson(['token' => $token], '登录成功');
    }

    public function system()
    {
        $site_id = input('site_id', 0, 'intval');
        $superSetting = getSystemSetting(0, 'system');
        if (!isset($superSetting['system_title'])) {
            $superSetting['system_title'] = '';
        }
        if (!isset($superSetting['system_logo'])) {
            $superSetting['system_logo'] = '';
        }
        if (!isset($superSetting['system_icp'])) {
            $superSetting['system_icp'] = '';
        }
        if (!isset($superSetting['system_gongan'])) {
            $superSetting['system_gongan'] = '';
        }

        $siteSetting = getSystemSetting($site_id, 'system');

        return successJson([
            'title' => $siteSetting['system_title'] ?? $superSetting['system_title'],
            'icp' => $superSetting['system_icp'],
            'gongan' => $superSetting['system_gongan'],
        ]);
    }
}
