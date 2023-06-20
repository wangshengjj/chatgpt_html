<?php

namespace app\web\controller;

use think\facade\Db;
use think\facade\Request;

class Login
{
    public function check()
    {
        $sitecode = Request::header('x-site');
        $code = input('code', '', 'trim');
        if ($sitecode) {
            $site_id = Db::name('site')
                ->where('sitecode', $sitecode)
                ->value('id');
        } else {
            $site_id = 1;
            $sitecode = Db::name('site')
                ->where('id', $site_id)
                ->value('sitecode');
        }

        $loginInfo = Db::name('pclogin')
            ->where([
                ['site_id', '=', $site_id],
                ['code', '=', $code]
            ])
            ->order('id desc')
            ->find();
        if (!$loginInfo || empty($loginInfo['user_id'])) {
            return successJson([
                'login' => 0
            ]);
        }

        // 用一次就过期
        Db::name('pclogin')
            ->where('id', $loginInfo['id'])
            ->delete();

        $user = Db::name('user')
            ->where([
                ['site_id', '=', $site_id],
                ['id', '=', $loginInfo['user_id']]
            ])
            ->find();
        if (!$user) {
            return errorJson('登录失败，请重新扫码');
        }

        // 存入session
        $token = uniqid() . $user['id'];
        session_id($token);
        session_start();
        $_SESSION['user'] = json_encode($user);
        $_SESSION['sitecode'] = $sitecode;

        return successJson([
            'login' => 1,
            'token' => $token
        ], '登录成功');
    }

    /**
     * 微信小程序登录
     */
    public function getQrcode()
    {
        $sitecode = Request::header('x-site');
        if ($sitecode) {
            $site_id = Db::name('site')
                ->where('sitecode', $sitecode)
                ->value('id');
        } else {
            $site_id = 1;
        }
        $wxmpSetting = getSystemSetting($site_id, 'wxmp');
        if (!isset($wxmpSetting['appid'])) {
            return errorJson('请先配置公众号参数');
        }
        $config = [
            'app_id' => $wxmpSetting['appid'] ?? '',
            'secret' => $wxmpSetting['appsecret'] ?? '',
            'token' => $wxmpSetting['token'] ?? '',
            'aes_key' => $wxmpSetting['aes_key'] ?? '',
            'response_type' => 'array'
        ];

        $code = getNonceStr(4) . '' . uniqid() . getNonceStr(4);

        $app = \EasyWeChat\Factory::officialAccount($config);
        $result = $app->qrcode->temporary($code, 600);
        if (isset($result['errcode']) && $result['errcode']) {
            return errorJson($result['errmsg']);
        }
        $qrcode = $app->qrcode->url($result['ticket']);

        return successJson([
            'qrcode' => $qrcode,
            'code' => $code
        ]);
    }

    public function system()
    {
        $sitecode = Request::header('x-site');
        if ($sitecode) {
            $site_id = Db::name('site')
                ->where('sitecode', $sitecode)
                ->value('id');
        } else {
            $site_id = 1;
        }
        $webSetting = getSystemSetting($site_id, 'web');
        if (empty($webSetting['is_open'])) {
            echo json_encode([
                'errno' => 403,
                'message' => '已暂停服务'
            ]);
            exit;
        }
        return successJson([
            'logo' => $webSetting['logo'] ?? '',
            'logo_mini' => $webSetting['logo_mini'] ?? '',
            'page_title' => $webSetting['page_title'] ?? '',
            'copyright' => $webSetting['copyright'] ?? '',
            'copyright_link' => $webSetting['copyright_link'] ?? ''
        ]);
    }

    /**
     * H5公众号登录
     */
    public function h5()
    {
        $fromUrl = input('from', '', 'trim');
        $sitecode = input('sitecode', '', 'trim');
        $code = input('code', '', 'trim');
        $tuid = input('tuid', 0, 'intval');
        if ($sitecode) {
            $site_id = Db::name('site')
                ->where('sitecode', $sitecode)
                ->value('id');
        } else {
            $site_id = 1;
            $sitecode = Db::name('site')
                ->where('id', $site_id)
                ->value('sitecode');
        }
        $wxmpConfig = getSystemSetting($site_id, 'wxmp');
        $webConfig = getSystemSetting($site_id, 'web');
        if (empty($webConfig['is_open'])) {
            return $this->error('此站点已停止服务');
        }
        if (!isset($wxmpConfig['appid'])) {
            return $this->error('请先配置公众号参数');
        }
        session_start();

        if (empty($code)) {
            $_SESSION['fromUrl'] = $fromUrl;
            $_SESSION['tuid'] = $tuid;

            $redirect_uri = 'https://' . $_SERVER['HTTP_HOST'] . '/web.php/login/h5/sitecode/' . $sitecode;
            $query = $this->toUrlParams([
                'appid' => $wxmpConfig['appid'],
                'response_type' => 'code',
                'scope' => 'snsapi_base',
                'redirect_uri' => urlencode($redirect_uri)
            ]);
            header('location:https://open.weixin.qq.com/connect/oauth2/authorize?' . $query);
            exit();
        }

        $config = new \Wxpay\v2\WxPayConfig();
        $config->SetAppId($wxmpConfig['appid']);
        $config->SetAppSecret($wxmpConfig['appsecret']);
        $JsApiPay = new \Wxpay\v2\JsApiPay();
        $openid = $JsApiPay->getOpenidFromMp($config, $code);
        if (is_array($openid)) {
            if (strpos($openid['errmsg'], 'code been used') !== false) {
                return $this->error('请重新扫码打开 code been used');
            } else {
                return $this->error($openid['errmsg']);
            }
        }

        // 登录成功
        $user_id = Db::name('user')
            ->where([
                ['site_id', '=', $site_id],
                ['openid_mp', '=', $openid]
            ])
            ->value('id');
        if (!$user_id) {
            $tuid = isset($_SESSION['tuid']) ? intval($_SESSION['tuid']) : 0;
            $_SESSION['tuid'] = null;

            $user_id = Db::name('user')
                ->insertGetId([
                    'site_id' => $site_id,
                    'openid_mp' => $openid,
                    'tuid' => $tuid,
                    'create_time' => time()
                ]);
            // 送免费条数
            $config = getSystemSetting($site_id, 'chatgpt');
            $free_num = isset($config['free_num']) ? intval($config['free_num']) : 0;
            if ($free_num > 0) {
                changeUserBalance($user_id, $free_num, '新人免费赠送');
            }
            // 送邀请人次数
            if ($tuid) {
                $today = strtotime(date('Y-m-d'));
                $count = Db::name('user')
                    ->where([
                        ['tuid', '=', $tuid],
                        ['create_time', '>', $today]
                    ])
                    ->count();
                $setting = getSystemSetting($site_id, 'reward_invite');
                if (!empty($setting['is_open']) && !empty($setting['max']) && $count < intval($setting['max']) && !empty($setting['num'])) {
                    $reward_num = intval($setting['num']);
                    changeUserBalance($tuid, $reward_num, '邀请朋友奖励');
                }
            }
        }
        $user = Db::name('user')
            ->where([
                ['site_id', '=', $site_id],
                ['id', '=', $user_id]
            ])
            ->find();
        $_SESSION['user'] = json_encode($user);
        $_SESSION['sitecode'] = $sitecode;

        if (!empty($_SESSION['fromUrl'])) {
            $fromUrl = urldecode($_SESSION['fromUrl']);
        } else {
            $fromUrl = '/h5';
        }
        $_SESSION['fromUrl'] = null;
        header('location:' . $fromUrl);
    }

    private function toUrlParams($urlObj)
    {
        $buff = '';
        foreach ($urlObj as $k => $v) {
            if ($k != 'sign') {
                $buff .= $k . '=' . $v . '&';
            }
        }

        $buff = trim($buff, '&');
        return $buff;
    }

    /**
     * @param $msg
     * 在页面上输出错误信息
     */
    private function error($message)
    {
        return view('pay/error', ['message' => $message]);
    }
}
