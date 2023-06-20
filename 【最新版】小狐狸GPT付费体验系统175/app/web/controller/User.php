<?php

namespace app\web\controller;

use think\facade\Db;
use think\facade\Request;

class User extends Base
{
    public function checkLogin()
    {
        return successJson();
    }

    public function info()
    {
        $user = Db::name('user')
            ->where([
                ['site_id', '=', self::$site_id],
                ['id', '=', self::$user['id']]
            ])
            ->find();
        if (!$user) {
            die(json_encode(['errno' => 403, 'message' => '请登录']));
        }
        // 分销入口
        $commissionSetting = getSystemSetting(self::$site_id, 'commission');
        $commissionIsOpen = empty($commissionSetting['is_open']) ? 0 : 1;
        return successJson([
            'user_id' => $user['id'],
            'nickname' => $user['nickname'] ?? '未设置昵称',
            'avatar' => $user['avatar'] ? mediaUrl($user['avatar'], true) : '',
            'commission_is_open' => $commissionIsOpen,
            'is_commission' => $user['commission_level'] ? 1 : 0,
            'vip_expire_time' =>  $user['vip_expire_time'] ? date('Y-m-d', $user['vip_expire_time']) : '',
            'balance' => $user['balance']
        ]);
    }

    /**
     * 退出登录
     */
    public function logout()
    {
        $_SESSION['user'] = null;
        $_SESSION['sitecode'] = null;
        self::$user = null;
        return successJson('', '已退出登录');
    }


    /**
     * 意见反馈
     */
    public function feedback()
    {
        $type = input('type', '', 'trim');
        $content = input('content', '', 'trim');
        $phone = input('phone', '', 'trim');
        if (empty($content)) {
            return errorJson('请输入反馈内容');
        }
        $today = strtotime(date('Y-m-d'));
        $count = Db::name('feedback')
            ->where([
                ['user_id', '=', self::$user['id']],
                ['create_time', '>', $today]
            ])
            ->count();
        if ($count >= 5) {
            return errorJson('今天提交太多了，明天再来！');
        }
        try {
            Db::name('feedback')
                ->insert([
                    'site_id' => self::$site_id,
                    'user_id' => self::$user['id'],
                    'type' => $type,
                    'content' => $content,
                    'phone' => $phone,
                    'create_time' => time()
                ]);
            return successJson('', '提交成功，谢谢！');
        } catch (\Exception $e) {
            return errorJson('提交失败：' . $e->getMessage());
        }
    }

    /**
     * 关于我们
     */
    public function about()
    {
        $setting = getSystemSetting(self::$site_id, 'about');
        $content = !empty($setting['content']) ? $setting['content'] : '';
        $contents = $content ? explode("\n", $content) : [];
        return successJson($contents);
    }

    /**
     * 关于我们
     */
    public function kefu()
    {
        $setting = getSystemSetting(self::$site_id, 'kefu');
        if (empty($setting)) {
            $setting = [
                'phone' => '',
                'wxno' => '',
                'email' => '',
                'wx_qrcode' => '',
                'qun_qrcode' => ''
            ];
        }
        return successJson($setting);
    }


}
