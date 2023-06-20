<?php

namespace AopSDK;

use AopSDK\request\AlipayMarketingCashlessvoucherTemplateCreateRequest;
use AopSDK\request\AlipayMarketingVoucherSendRequest;


class Voucher
{
    private static $payConfig;
    public function __construct($payConfig)
    {
        self::$payConfig = $payConfig;
    }
    /**
     * @param $param
     * @return array
     * 创建支付宝代金券
     */
    public static function create($param)
    {
        $voucher_description = !empty($param['voucher_description']) ? explode("\n", $param['voucher_description']) : [];
        $data = [
            'voucher_type' => 'CASHLESS_FIX_VOUCHER',
            'brand_name' => $param['brand_name'],
            'publish_start_time' => $param['publish_start_time'],
            'publish_end_time' => $param['publish_end_time'],
            'voucher_valid_period' => json_encode($param['voucher_valid_period']),
            'out_biz_no' => $param['out_biz_no'],
            'voucher_description' => json_encode($voucher_description),
            'voucher_quantity' => $param['voucher_quantity'],
            'amount' => $param['amount'],
            'floor_amount' => $param['floor_amount'],
            'rule_conf' => json_encode($param['rule_conf']),
            'notify_uri' => 'https://' . $_SERVER['HTTP_HOST'] . '/pay.php/alipay/voucher_notify',
            'voucher_available_time' => json_encode([]),
        ];
        $aop = get_alipay_aop(self::$payConfig);
        $request = new AlipayMarketingCashlessvoucherTemplateCreateRequest();
        $request->setBizContent(json_encode($data));
        $result = $aop->execute($request, '', self::$payConfig['app_auth_token']);
        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
        $resultCode = $result->$responseNode->code;
        if (!empty($resultCode) && $resultCode == 10000) {
            return [
                'errno' => 0,
                'message' => '创建成功',
                'template_id' => $result->$responseNode->template_id
            ];
        } else {
            return [
                'errno' => 1,
                'message' => $result->$responseNode->sub_msg
            ];
        }
    }

    /**
     * @param $out_biz_no
     * @param $user_id
     * @param $template_id
     * @return array
     * @throws \Exception
     * 发送优惠券
     */
    public static function send($out_biz_no, $user_id, $template_id)
    {
        $data = [
            'template_id' => $template_id,
            'user_id' => $user_id,
            'out_biz_no' => $out_biz_no
        ];
        $aop = get_alipay_aop(self::$payConfig);
        $request = new AlipayMarketingVoucherSendRequest();
        $request->setBizContent(json_encode($data));
        $result = $aop->execute($request, '', self::$payConfig['app_auth_token']);
        if (isset($result->error_response)) {
            return [
                'errno' => 1,
                'message' => $result->error_response->sub_msg
            ];
        }
        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
        $response = $result->$responseNode;
        if (isset($response->code) && $response->code != 10000) {
            return [
                'errno' => 1,
                'message' => $response->sub_msg
            ];
        }

        return [
            'errno' => 0,
            'message' => '发送成功',
            'voucher_id' => $response->voucher_id
        ];
    }
}