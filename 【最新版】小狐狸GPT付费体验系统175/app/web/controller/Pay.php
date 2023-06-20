<?php

namespace app\web\controller;

use Jssdk\jssdk;
use think\facade\Db;
use Wxpay\v2\JsApiPay;
use Wxpay\v2\lib\WxPayApi;
use Wxpay\v2\lib\WxPayUnifiedOrder;
use Wxpay\v2\WxPayConfig;

class Pay
{
    public function order()
    {
        $code = input('code', '', 'trim');
        $order_id = input('id', '', 'trim');
        $order = Db::name('order')
            ->where('id', $order_id)
            ->find();
        if (!$order) {
            return $this->error('订单不存在');
        }
        if ($order['status'] == 1) {
            return $this->success($order);
        }
        $wxmpConfig = getSystemSetting($order['site_id'], 'wxmp');

        session_start();
        if ((empty($_SESSION['wxpay_appid']) || $_SESSION['wxpay_appid'] != $wxmpConfig['appid'] || empty($_SESSION['wxpay_openid'])) && empty($code)) {
            $redirect_uri = 'https://' . $_SERVER['HTTP_HOST'] . '/web.php/pay/order/id/' . $order_id;
            $query = $this->toUrlParams([
                'appid' => $wxmpConfig['appid'],
                'response_type' => 'code',
                'scope' => 'snsapi_base',
                'redirect_uri' => urlencode($redirect_uri)
            ]);
            header('location:https://open.weixin.qq.com/connect/oauth2/authorize?' . $query);
            exit();
        }
        if (!(!empty($_SESSION['wxpay_appid']) && $_SESSION['wxpay_appid'] == $wxmpConfig['appid'] && !empty($_SESSION['wxpay_openid']))) {
            $config = new WxPayConfig();
            $config->SetAppId($wxmpConfig['appid']);
            $config->SetAppSecret($wxmpConfig['appsecret']);
            $JsApiPay = new JsApiPay();
            $openid = $JsApiPay->getOpenidFromMp($config, $code);
            if (is_array($openid)) {
                if (strpos($openid['errmsg'], 'code been used') !== false) {
                    return $this->error('请重新扫码打开');
                } else {
                    return $this->error($openid['errmsg']);
                }
            }
            $_SESSION['wxpay_appid'] = $wxmpConfig['appid'];
            $_SESSION['wxpay_openid'] = $openid;
        }

        $jssdk = new jssdk($wxmpConfig['appid'], $wxmpConfig['appsecret']);
        $signPackage = $jssdk->getSignPackage();
        return view('order', [
            'signPackage' => $signPackage,
            'order' => $order
        ]);
    }

    public function wxpay()
    {
        $order_id = input('order_id', '', 'trim');
        $order = Db::name('order')
            ->where('id', $order_id)
            ->find();
        if (!$order) {
            return errorJson('订单不存在');
        }
        if ($order['status'] == 1) {
            return errorJson('订单已经付过款了');
        }

        try {
            $wxmpConfig = getSystemSetting($order['site_id'], 'wxmp');
            $out_trade_no = $this->createOrderNo();
            session_start();
            $openid = $_SESSION['wxpay_openid'];

            Db::name('order')
                ->where('id', $order_id)
                ->update([
                    'out_trade_no' => $out_trade_no
                ]);
            $payConfig = getSystemSetting($order['site_id'], 'pay');
            $notifyUrl = 'https://' . $_SERVER['HTTP_HOST'] . '/api.php/notify/wxpay';
            $input = new WxPayUnifiedOrder();
            $input->SetBody('订购商品');
            $input->SetOut_trade_no($out_trade_no);
            $input->SetTotal_fee($order['total_fee']);
            $input->SetTime_start(date('YmdHis'));
            $input->SetTime_expire(date('YmdHis', time() + 600));
            $input->SetNotify_url($notifyUrl);
            $input->SetTrade_type('JSAPI');
            $input->SetMch_id($payConfig['mch_id']);
            $input->SetAppid($wxmpConfig['appid']);
            $input->SetOpenid($openid);

            $WxPayApi = new WxPayApi();
            $config = new WxPayConfig();
            $config->SetAppId($wxmpConfig['appid']);
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
        $jsApi = $JsApiPay->GetJsApiParameters($config, $unifiedOrder);

        $jsApi = json_decode($jsApi, true);
        $jsApi['out_trade_no'] = $out_trade_no;

        return successJson($jsApi);
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
     * 创建订单号
     */
    private function createOrderNo()
    {
        return 'Ch' . date('YmdHis') . rand(1000, 9999);
    }

    /**
     * @param $msg
     * 在页面上输出成功信息
     */
    private function success($order)
    {
        $order['total_fee'] = $order['total_fee'] / 100;
        if($order['pay_time']) {
            $order['pay_time'] = date('Y-m-d H:i:s', $order['pay_time']);
        }
        return view('success', ['order' => $order]);
    }

    /**
     * @param $msg
     * 在页面上输出错误信息
     */
    private function error($message)
    {
        return view('error', ['message' => $message]);
    }
}
