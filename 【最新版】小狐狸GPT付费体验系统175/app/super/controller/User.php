<?php

namespace app\super\controller;

use think\facade\Db;

class User extends Base
{
    /**
     * 返回当前登录管理员的角色
     */
    public function info()
    {
        $system = getSystemSetting(0, 'system');
        return successJson([
            'roles' => [self::$super['role']],
            'introduction' => '',
            'avatar' => mediaUrl(self::$super['avatar']),
            'logo' => !empty($system['system_logo']) ? $system['system_logo'] : mediaUrl('/static/img/logo.png'),
            'logo_mini' => mediaUrl('/static/img/logo-mini.png'),
            'system_title' => !empty($system['system_title']) ? $system['system_title'] : '',
            'name' => self::$super['realname'] ? self::$super['realname'] : self::$super['phone'],
            'nopass' => isset(self::$super['nopass']) ? self::$super['nopass'] : 0
        ]);
    }

    /**
     * 修改密码
     */
    public function changePassword()
    {
        $passwordOld = input('password_old');
        $passwordNew = input('password_new');
        $super = Db::name('super')
            ->where('id', self::$super['id'])
            ->find();
        if (!$super) {
            return errorJson('修改失败，请重新登录');
        }
        // 验证密码
        if (md5($super['password']) != md5($passwordOld)) {
            return errorJson('原密码不正确');
        }
        // 验证新密码
        if (strlen($passwordNew) < 6 || strlen($passwordNew) > 18) {
            return errorJson('新密码长度不符合规范');
        }

        $rs = Db::name('super')
            ->where('id', self::$super['id'])
            ->update([
                'password' => $passwordNew
            ]);
        if ($rs !== false) {
            return successJson('', '密码已修改，请重新登录');
        } else {
            return errorJson('修改失败，请重试');
        }
    }

    /**
     * 退出登录
     */
    public function logout()
    {
        $_SESSION['super'] = null;
        self::$super = null;
        return successJson('', '已退出登录');
    }

    public function getUserList()
    {
        $page = input('page', 1, 'intval');
        $pagesize = input('pagesize', 10, 'intval');
        $site_id = input('site_id', 0, 'intval');
        $date = input('date', []);
        $user_id = input('user_id', 0, 'intval');
        $keyword = input('keyword', '', 'trim');
        $where = [];
        if ($site_id) {
            $where[] = ['site_id', '=', $site_id];
        }
        if ($user_id) {
            $where[] = ['id', '=', $user_id];
        }
        if ($keyword) {
            $where[] = ['nickname', 'like', '%' . $keyword, '%'];
        }
        if (!empty($date)) {
            $start_time = strtotime($date[0]);
            $end_time = strtotime($date[1]);
            $where[] = ['create_time', 'between', [$start_time, $end_time]];
        }

        $list = Db::name('user')
            ->where($where)
            ->order('id desc')
            ->page($page, $pagesize)
            ->select()->each(function($item) {
                $msgOldCount = Db::name('msg')
                    ->where([
                        ['user_id', '=', $item['id']],
                        ['user', '=', '我']
                    ])
                    ->count();
                $msgWebCount = Db::name('msg_web')
                    ->where([
                        ['user_id', '=', $item['id']]
                    ])
                    ->count();
                $msgWriteCount = Db::name('msg_write')
                    ->where([
                        ['user_id', '=', $item['id']]
                    ])
                    ->count();
                $item['msg_count'] = $msgOldCount + $msgWebCount + $msgWriteCount;
                $item['order_total'] = Db::name('order')
                    ->where([
                        ['user_id', '=', $item['id']],
                        ['status', '=', 1]
                    ])
                    ->sum('total_fee');
                $item['order_total'] = $item['order_total'] / 100;
                if ($item['vip_expire_time']) {
                    $now = time();
                    if ($item['vip_expire_time'] < $now) {
                        $item['vip_expire_time'] = date('Y-m-d', $item['vip_expire_time']) . '（已过期）';
                    } else {
                        $item['vip_expire_time'] = date('Y-m-d', $item['vip_expire_time']);
                    }
                } else {
                    $item['vip_expire_time'] = '';
                }

                $item['create_time'] = date('Y-m-d H:i:s', $item['create_time']);
                return $item;
            });
        $count = Db::name('user')
            ->where($where)
            ->count();
        return successJson([
            'list' => $list,
            'count' => $count
        ]);
    }

    /**
     * 统计
     */
    public function getTongji()
    {
        $site_id = input('site_id', 0, 'intval');
        $date = input('date', []);
        $user_id = input('user_id', 0, 'intval');
        $keyword = input('keyword', '', 'trim');
        $where = [
            ['id', '>', 0]
        ];
        if ($site_id) {
            $where[] = ['site_id', '=', $site_id];
        }
        if ($user_id) {
            $where[] = ['id', '=', $user_id];
        }
        /*if ($keyword) {
            $where[] = ['message', 'like', '%' . $keyword, '%'];
        }*/
        if (!empty($date)) {
            $start_time = strtotime($date[0]);
            $end_time = strtotime($date[1]);
            $where[] = ['create_time', 'between', [$start_time, $end_time]];
        }
        $data = Db::name('user')
            ->where($where)
            ->field('count(id) as user_count,sum(balance) as user_balance')
            ->find();

        return successJson([
            'userCount' => intval($data['user_count']),
            'userBalance' => intval($data['user_balance'])
        ]);
    }
}
