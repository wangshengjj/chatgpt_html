<?php

namespace app\admin\controller;

use think\facade\Db;

class User extends Base
{
    /**
     * 返回当前登录管理员的角色
     */
    public function info()
    {
        $superSetting = getSystemSetting(0, 'system');
        if (!isset($superSetting['system_title'])) {
            $superSetting['system_title'] = '';
        }
        if (!isset($superSetting['system_logo'])) {
            $superSetting['system_logo'] = mediaUrl('/static/img/logo.png');
        }
        $siteSetting = getSystemSetting(self::$site_id, 'system');

        return successJson([
            'roles' => ['admin'],
            'introduction' => '',
            'avatar' => mediaUrl(self::$admin['avatar']),
            'logo' => !empty($siteSetting['system_logo']) ? $siteSetting['system_logo'] : $superSetting['system_logo'],
            'logo_mini' => mediaUrl('/static/img/logo-mini.png'),
            'system_title' => $siteSetting['system_title'] ?? $superSetting['system_title'],
            'name' => self::$admin['title'] ? self::$admin['title'] : self::$admin['phone'],
            'nopass' => 0
        ]);
    }

    /**
     * 修改密码
     */
    public function changePassword()
    {
        $passwordOld = input('password_old');
        $passwordNew = input('password_new');
        $admin = Db::name('site')
            ->where('id', self::$admin['id'])
            ->find();
        if (!$admin) {
            return errorJson('修改失败，请重新登录');
        }
        // 验证密码
        if (md5($admin['password']) != md5($passwordOld)) {
            return errorJson('原密码不正确');
        }
        // 验证新密码
        if (strlen($passwordNew) < 6 || strlen($passwordNew) > 18) {
            return errorJson('新密码长度不符合规范');
        }

        $rs = Db::name('site')
            ->where('id', self::$admin['id'])
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
        $_SESSION['admin'] = null;
        self::$admin = null;
        return successJson('', '已退出登录');
    }

    public function getUserList()
    {
        $page = input('page', 1, 'intval');
        $pagesize = input('pagesize', 10, 'intval');
        $date = input('date', []);
        $user_id = input('user_id', 0, 'intval');
        $keyword = input('keyword', '', 'trim');
        $where = [
            ['site_id', '=', self::$site_id]
        ];
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
            ->select()->each(function ($item) {
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
        $date = input('date', []);
        $user_id = input('user_id', 0, 'intval');
        $keyword = input('keyword', '', 'trim');
        $where = [
            ['site_id', '=', self::$site_id]
        ];
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

    public function doRecharge()
    {
        $user_id = input('user_id', 0, 'intval');
        $num = input('num', 0, 'intval');
        if (!$user_id) {
            return errorJson('参数错误');
        }
        if (!$num) {
            return errorJson('请输入充值数量');
        }
        $user = Db::name('user')
            ->where([
                ['site_id', '=', self::$site_id],
                ['id', '=', $user_id]
            ])
            ->find();
        if (!$user) {
            return errorJson('没有找到此用户');
        }
        changeUserBalance($user_id, $num, '后台调整');
        return successJson('', '更新成功');
    }

    /**
     * 调整会员时间
     */
    public function setVipTime()
    {
        $user_id = input('user_id', 0, 'intval');
        $vip_expire_time = input('vip_expire_time', '', 'trim');
        if (!$user_id) {
            return errorJson('参数错误');
        }
        $user = Db::name('user')
            ->where([
                ['site_id', '=', self::$site_id],
                ['id', '=', $user_id]
            ])
            ->find();
        if (!$user) {
            return errorJson('没有找到此用户');
        }
        setUserVipTime($user_id, $vip_expire_time, '后台调整');
        return successJson('', '更新成功');
    }

    public function getWebSiteUrl()
    {
        $site = Db::name('site')
            ->where('id', self::$admin['id'])
            ->find();
        if (empty($site['sitecode'])) {
            while (1) {
                $sitecode = getNonceStr(4);
                $info = Db::name('site')
                    ->where('sitecode', $sitecode)
                    ->find();
                if (!$info) {
                    Db::name('site')
                        ->where('id', self::$admin['id'])
                        ->update([
                            'sitecode' => $sitecode
                        ]);
                    break;
                }
            }
        }
        $pcurl = 'https://' . $_SERVER['HTTP_HOST'] . '/web';
        if ($site['id'] != 1) {
            $pcurl .= '/?' . $site['sitecode'];
        }
        $h5url = 'https://' . $_SERVER['HTTP_HOST'] . '/h5/?' . $site['sitecode'];

        return successJson([
            'pcurl' => $pcurl,
            'h5url' => $h5url
        ]);
    }

}
