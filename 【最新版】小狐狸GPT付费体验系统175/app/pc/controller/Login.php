<?php

namespace app\pc\controller;

use think\facade\Db;

class Login
{
    public function check()
    {
        $site_id = input('site_id', 0, 'intval');
        $code = input('code', '', 'trim');
        $info = Db::name('pclogin')
            ->where([
                ['site_id', '=', $site_id],
                ['code', '=', $code]
            ])
            ->find();
        if (!$info || empty($info['user_id'])) {
            return successJson([
                'login' => 0
            ]);
        }

        // 用一次就过期
        Db::name('pclogin')
            ->where('id', $info['id'])
            ->delete();
        @unlink('./upload/qrcode/pclogin/' . $code . '.png');

        return successJson([
            'login' => 1,
            'user_id' => $info['user_id'],
            'openid' => $info['openid']
        ]);
    }

    /**
     * 微信小程序登录
     */
    public function getQrcode()
    {
        $site_id = input('site_id', 0, 'intval');
        $code = uniqid() . rand(1000, 9999);
        $page = 'pages/login/pc';
        $scene = 'code=' . $code;
        $qrcode = './upload/qrcode/pclogin/' . $code . '.png';
        if (!is_dir(dirname($qrcode))) {
            mkdir(dirname($qrcode), 0755, true);
        }
        $setting = getSystemSetting($site_id, 'wxapp');
        $Wxapp = new \Weixin\Wxapp($setting['appid'], $setting['appsecret']);
        $result = $Wxapp->getCodeUnlimit($scene, $page, 600);
        if (is_array($result) && $result['errno']) {
            return errorJson($result['message']);
        }
        file_put_contents($qrcode, $result);

        return successJson([
            'qrcode' => mediaUrl($qrcode, true),
            'code' => $code
        ]);
    }
}
