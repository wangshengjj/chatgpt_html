<?php

namespace app\web\controller;

use think\facade\Db;
use think\facade\Request;
use Wxpay\v2\JsApiPay;
use Wxpay\v2\lib\WxPayApi;
use Wxpay\v2\lib\WxPayUnifiedOrder;
use Wxpay\v2\WxPayConfig;

class H5 extends Base
{
    public function checkLogin()
    {
        return successJson();
    }

    /**
     * 获取账户余额
     */
    public function getBalance()
    {
        $user = Db::name('user')
            ->where('id', self::$user['id'])
            ->find();
        $now = time();
        if ($user['vip_expire_time'] > $now) {
            $vip_expire_time = date('Y-m-d', $user['vip_expire_time']);
        } else {
            $vip_expire_time = '';
        }
        return successJson([
            'balance' => $user['balance'] ?? 0,
            'vip_expire_time' => $vip_expire_time
        ]);
    }

    /**
     * H5获取用户头像昵称
     */
    public function getProfile()
    {
        $code = input('code', '', 'trim');
        $wxmpConfig = getSystemSetting(self::$site_id, 'wxmp');
        $config = [
            'app_id' => $wxmpConfig['appid'] ?? '',
            'secret' => $wxmpConfig['appsecret'] ?? '',
            'response_type' => 'array',
            'debug' => false
        ];
        $app = \EasyWeChat\Factory::officialAccount($config);
        $oauth = $app->oauth;
        if (!$code) {
            $callback = Request::url(true);
            $response = $oauth->scopes(['snsapi_userinfo'])
                ->redirect($callback);
            $response->send();
            exit;
        } else {
            try {
                $user = $oauth->user()->toArray();
                Db::name('user')
                    ->where([
                        ['site_id', '=', self::$site_id],
                        ['id', '=', self::$user['id']]
                    ])
                    ->update([
                        'avatar' => $user['avatar'],
                        'nickname' => $user['nickname']
                    ]);
                header('location:/h5/#/pages/user/index');
            } catch (\Exception $e) {
                echo $e->getMessage();
            }
        }
    }

    /**
     * 获取小程序分享参数
     */
    public function getShareInfo()
    {
        $url = input('url', '', 'trim');
        $wxmpSetting = getSystemSetting(self::$site_id, 'wxmp');
        $config = [
            'app_id' => $wxmpSetting['appid'] ?? '',
            'secret' => $wxmpSetting['appsecret'] ?? '',
            'response_type' => 'array'
        ];
        $app = \EasyWeChat\Factory::officialAccount($config);
        $apis = [
            'checkJsApi',
            'updateAppMessageShareData',
            'updateTimelineShareData',
            'chooseWXPay'
        ];
        if (strpos($url, '#') !== false) {
            $url = explode('#', $url)[0];
        }
        $app->jssdk->setUrl($url);
        $jssdkConfig = $app->jssdk->buildConfig($apis, false, false, false);
        $h5Config = getSystemSetting(self::$site_id, 'h5');
        $page_title = $h5Config['page_title'] ?? 'AI创作助手';
        $share_title = $h5Config['share_title'] ?? $page_title;
        $share_desc = $h5Config['share_desc'] ?? '';
        $share_image = $h5Config['share_image'] ?? '';
        $share_link = 'https://' . $_SERVER['HTTP_HOST'] . '/h5/?' . $_SESSION['sitecode'] . '&tuid=' . self::$user['id'];
        return successJson([
            'page_title' => $page_title,
            'share_title' => $share_title,
            'share_link' => $share_link,
            'share_image' => $share_image,
            'share_desc' => $share_desc,
            'jssdk_config' => $jssdkConfig
        ]);
    }

    /**
     * 获取任务配置
     */
    public function getTasks()
    {
        $share = getSystemSetting(self::$site_id, 'reward_share');
        $invite = getSystemSetting(self::$site_id, 'reward_invite');

        $tasks = [];
        $today = strtotime(date('Y-m-d'));
        if (!empty($share['is_open']) && !empty($share['max']) && !empty($share['num'])) {
            // 获取今日已分享次数
            $count = Db::name('reward_share')
                ->where([
                    ['user_id', '=', self::$user['id']],
                    ['create_time', '>', $today]
                ])
                ->count();
            $share['count'] = intval($count);
            $tasks['share'] = $share;
        }
        if (!empty($invite['is_open']) && !empty($invite['max']) && !empty($invite['num'])) {
            // 获取今日已邀请人数
            $count = Db::name('reward_invite')
                ->where([
                    ['user_id', '=', self::$user['id']],
                    ['create_time', '>', $today]
                ])
                ->count();
            $invite['count'] = intval($count);
            $tasks['invite'] = $invite;
        }

        $tasks = count($tasks) > 0 ? $tasks : null;

        return successJson($tasks);
    }

    public function createOrder()
    {
        $goods_id = input('goods_id', 0, 'intval');
        $vip_id = input('vip_id', 0, 'intval');
        $payConfig = getSystemSetting(self::$site_id, 'pay');
        $wxmp = getSystemSetting(self::$site_id, 'wxmp');
        $out_trade_no = $this->createOrderNo();

        if ($goods_id) {
            $goods = Db::name('goods')
                ->where([
                    ['site_id', '=', self::$site_id],
                    ['id', '=', $goods_id]
                ])
                ->find();
            if ($goods['status'] != 1) {
                return errorJson('该套餐已下架，请刷新页面重新提交');
            }
            $total_fee = $goods['price'];
            $num = $goods['num'];
        } else if ($vip_id) {
            $vip = Db::name('vip')
                ->where([
                    ['site_id', '=', self::$site_id],
                    ['id', '=', $vip_id]
                ])
                ->find();
            if ($vip['status'] != 1) {
                return errorJson('该套餐已下架，请刷新页面重新提交');
            }
            $total_fee = $vip['price'];
            $num = $vip['num'];
        } else {
            return errorJson('参数错误');
        }
        $openid = self::$user['openid_mp'];

        // 推荐人信息
        $commission1 = 0;
        $commission1_fee = 0;
        $commission2 = 0;
        $commission2_fee = 0;
        $commissionSetting = getSystemSetting(self::$site_id, 'commission');
        if (!empty($commissionSetting['is_open'])) {
            $bili_1 = floatval($commissionSetting['bili_1']);
            $bili_2 = floatval($commissionSetting['bili_2']);

            $tuid = Db::name('user')
                ->where('id', self::$user['id'])
                ->value('tuid');
            if (!empty($tuid)) {
                $tuser = Db::name('user')
                    ->where('id', $tuid)
                    ->find();
                if ($tuser && $tuser['commission_level'] > 0) {
                    $commission1 = $tuid;
                    $commission1_fee = intval($total_fee * $bili_1 / 100);
                    if ($tuser['commission_pid']) {
                        $commission2 = $tuser['commission_pid'];
                        $commission2_fee = intval($total_fee * $bili_2 / 100);
                    }
                }
            }
        }

        Db::name('order')->insertGetId([
            'site_id' => self::$site_id,
            'goods_id' => $goods_id,
            'vip_id' => $vip_id,
            'user_id' => self::$user['id'],
            'openid' => $openid,
            'out_trade_no' => $out_trade_no,
            'transaction_id' => '',
            'total_fee' => $total_fee,
            'pay_type' => 'wxpay',
            'status' => 0, // 0-待付款；1-成功；2-失败
            'num' => $num,
            'commission1' => $commission1,
            'commission2' => $commission2,
            'commission1_fee' => $commission1_fee,
            'commission2_fee' => $commission2_fee,
            'create_time' => time()
        ]);

        try {
            $notifyUrl = 'https://' . $_SERVER['HTTP_HOST'] . '/api.php/notify/wxpay';
            $input = new WxPayUnifiedOrder();
            $input->SetBody('订购商品');
            $input->SetOut_trade_no($out_trade_no);
            $input->SetTotal_fee($total_fee);
            $input->SetTime_start(date('YmdHis'));
            $input->SetTime_expire(date('YmdHis', time() + 600));
            $input->SetNotify_url($notifyUrl);
            $input->SetTrade_type('JSAPI');
            $input->SetMch_id($payConfig['mch_id']);
            $input->SetAppid($wxmp['appid']);
            $input->SetOpenid($openid);

            $WxPayApi = new WxPayApi();
            $config = new WxPayConfig();
            $config->SetAppId($wxmp['appid']);
            $config->SetMerchantId($payConfig['mch_id']);
            $config->SetKey($payConfig['key']);
            $config->SetSignType('MD5');
            $config->SetNotifyUrl($notifyUrl);

            $unifiedOrder = $WxPayApi->unifiedOrder($config, $input);
            if (isset($unifiedOrder['return_code']) && $unifiedOrder['return_code'] == 'FAIL') {
                return errorJson($unifiedOrder['return_msg']);
            } elseif (isset($unifiedOrder['err_code']) && !empty($unifiedOrder['err_code_des'])) {
                return errorJson($unifiedOrder['err_code_des']);
            }
        } catch (\Exception $e) {
            return errorJson('发起支付失败：' . $e->getMessage());
        }

        // 生成调起jsapi-pay的js参数
        $JsApiPay = new JsApiPay();
        if (isset($unifiedOrder['sub_appid'])) {
            $unifiedOrder['appid'] = $unifiedOrder['sub_appid'];
        }
        $jsApiParameters = $JsApiPay->GetJsApiParameters($config, $unifiedOrder);

        $jsApiParameters = json_decode($jsApiParameters, true);
        $jsApiParameters['out_trade_no'] = $out_trade_no;

        return successJson($jsApiParameters);
    }

    /**
     * 创建订单号
     */
    private function createOrderNo()
    {
        return 'Ch' . date('YmdHis') . rand(1000, 9999);
    }

    /**
     * 分享动作
     */
    public function doShare()
    {
        $way = input('way', 'wechat', 'trim');
        $today = strtotime(date('Y-m-d'));
        $count = Db::name('reward_share')
            ->where([
                ['user_id', '=', self::$user['id']],
                ['create_time', '>', $today]
            ])
            ->count();

        Db::startTrans();
        try {
            $setting = getSystemSetting(self::$site_id, 'reward_share');
            if (!empty($setting['is_open']) && !empty($setting['max']) && $count < intval($setting['max']) && !empty($setting['num'])) {
                $reward_num = intval($setting['num']);
                changeUserBalance(self::$user['id'], $reward_num, '分享奖励');
            } else {
                $reward_num = 0;
            }
            $share_id = Db::name('reward_share')
                ->insertGetId([
                    'site_id' => self::$site_id,
                    'user_id' => self::$user['id'],
                    'way' => $way,
                    'reward_num' => $reward_num,
                    'create_time' => time()
                ]);
            Db::commit();
            return successJson([
                'share_id' => $share_id
            ]);
        } catch (\Exception $e) {
            Db::rollback();
            return errorJson('获取分享参数失败：' . $e->getMessage());
        }
    }
}
