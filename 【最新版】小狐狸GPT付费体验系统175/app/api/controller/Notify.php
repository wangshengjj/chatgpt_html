<?php

namespace app\api\controller;

use Wxpay\v2\WxPayConfig;
use Wxpay\v2\lib\WxPayNotifyResults;
use think\facade\Db;

class Notify
{

    public function wxpay()
    {
        $xml = file_get_contents("php://input");
        // file_put_contents('./payResultWxpay.txt', "$xml\n\n", 8);
        libxml_disable_entity_loader(true);
        $data = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);

        $result_code = $data['result_code'];
        $out_trade_no = $data['out_trade_no'];      // 商户订单号
        $transaction_id = $data['transaction_id'];  // 微信流水单号
        $time_end = $data['time_end'];              // 支付时间
        // $total_fee = $data['total_fee'];         // 交易金额

        // 验签
        $order = Db::name('order')
            ->where('out_trade_no', $out_trade_no)
            ->find();
        if (!$order || $order['status'] != 0) {
            self::wxpayAnswer('FAIL', '订单错误');
        }
        $payConfig = getSystemSetting($order['site_id'], 'pay');
        $config = new WxPayConfig();
        $config->SetKey($payConfig['key']);
        $Notify = new WxPayNotifyResults();
        $checkSign = $Notify->Init($config, $xml);

        if (!$checkSign) {
            self::wxpayAnswer('FAIL', '签名错误');
        }

        if ($result_code == 'SUCCESS') {
            Db::startTrans();
            try {
                // 改订单状态
                Db::name('order')
                    ->where('out_trade_no', $out_trade_no)
                    ->update([
                        'status' => 1,
                        'transaction_id' => $transaction_id,
                        'pay_time' => strtotime($time_end)
                    ]);
                if ($order['goods_id']) {
                    // 加用户余额
                    changeUserBalance($order['user_id'], $order['num'], '充值次数');
                    // 加已售数
                    Db::name('goods')
                        ->where('id', $order['goods_id'])
                        ->inc('sales', 1)
                        ->update();
                } elseif ($order['vip_id']) {
                    // 加用户会员时长
                    $today = strtotime(date('Y-m-d 23:59:59', time()));
                    $user = Db::name('user')
                        ->where('id', $order['user_id'])
                        ->find();
                    $vip_expire_time = max($today, $user['vip_expire_time']);
                    $vip_expire_time = strtotime('+' . $order['num'] . ' month', $vip_expire_time);
                    Db::name('user')
                        ->where('id', $order['user_id'])
                        ->update([
                            'vip_expire_time' => $vip_expire_time
                        ]);
                    Db::name('user_vip_logs')
                        ->insert([
                            'site_id' => $order['site_id'],
                            'user_id' => $order['user_id'],
                            'vip_expire_time' => $vip_expire_time,
                            'desc' => '购买套餐',
                            'create_time' => time()
                        ]);
                    // 加已售数
                    Db::name('vip')
                        ->where('id', $order['vip_id'])
                        ->inc('sales', 1)
                        ->update();
                }

                // 加分销余额
                if ($order['commission1'] && $order['commission1_fee'] > 0) {
                    $user = Db::name('user')
                        ->where('id', $order['commission1'])
                        ->find();
                    if($user && $user['commission_level'] > 0) {
                        Db::name('user')
                            ->where('id', $user['id'])
                            ->update([
                                'commission_money' => $user['commission_money'] + $order['commission1_fee'],
                                'commission_total' => $user['commission_total'] + $order['commission1_fee'],
                            ]);
                        Db::name('commission_bill')
                            ->insert([
                                'site_id' => $user['site_id'],
                                'user_id' => $user['id'],
                                'order_id' => $order['id'],
                                'title' => '用户下单佣金（直推）',
                                'type' => 1,
                                'money' => $order['commission1_fee'],
                                'create_time' => time()
                            ]);
                    }
                }
                if ($order['commission2'] && $order['commission2_fee'] > 0) {
                    $user = Db::name('user')
                        ->where('id', $order['commission2'])
                        ->find();
                    if($user && $user['commission_level'] > 0) {
                        Db::name('user')
                            ->where('id', $user['id'])
                            ->update([
                                'commission_money' => $user['commission_money'] + $order['commission2_fee'],
                                'commission_total' => $user['commission_total'] + $order['commission2_fee'],
                            ]);
                        Db::name('commission_bill')
                            ->insert([
                                'site_id' => $user['site_id'],
                                'user_id' => $user['id'],
                                'order_id' => $order['id'],
                                'title' => '用户下单佣金（间推）',
                                'type' => 1,
                                'money' => $order['commission2_fee'],
                                'create_time' => time()
                            ]);
                    }
                }
                

                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();
                saveLog($order['site_id'], $e->getMessage() . ' | ' . $xml);
                self::wxpayAnswer('FAIL', '支付失败');
            }

            self::wxpayAnswer('SUCCESS', 'OK');
        } else {
            self::wxpayAnswer('FAIL', '支付失败');
        }
    }

    private static function wxpayAnswer($code = 'SUCCESS', $msg = 'OK')
    {
        echo '<xml><return_code><![CDATA[' . $code . ']]></return_code><return_msg><![CDATA[' . $msg . ']]></return_msg></xml>';
        exit;
    }
}
