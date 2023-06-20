<?php

namespace app\web\controller;

use think\facade\Db;

class Order extends Base
{
    /**
     * 获取充值套餐
     */
    public function getGoodsList()
    {
        $list = Db::name('goods')
            ->where([
                ['site_id', '=', self::$site_id],
                ['status', '=', 1]
            ])
            ->field('id,title,price,market_price,num,is_default')
            ->order('weight desc, id asc')
            ->select()->toArray();

        return successJson($list);
    }

    /**
     * 获取包月套餐
     */
    public function getVipList()
    {
        $list = Db::name('vip')
            ->where([
                ['site_id', '=', self::$site_id],
                ['status', '=', 1]
            ])
            ->field('id,title,price,market_price,num,is_default')
            ->order('weight desc, id asc')
            ->select()->toArray();

        return successJson($list);
    }

    public function createOrder()
    {
        $goods_id = input('goods_id', 0, 'intval');
        $vip_id = input('vip_id', 0, 'intval');
        $payConfig = getSystemSetting(self::$site_id, 'pay');
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

        $order_id = Db::name('order')->insertGetId([
            'site_id' => self::$site_id,
            'goods_id' => $goods_id,
            'vip_id' => $vip_id,
            'user_id' => self::$user['id'],
            'out_trade_no' => $out_trade_no,
            'transaction_id' => '',
            'total_fee' => $total_fee,
            'pay_type' => 'wxpay',
            'status' => 0, // 0-待付款；1-成功；2-失败
            'num' => $num,
            'create_time' => time()
        ]);

        return successJson([
            'order_id' => $order_id,
            'pay_url' => 'https://' . $_SERVER['HTTP_HOST'] . '/web.php/pay/order/id/' . $order_id
        ]);
    }

    public function checkPay()
    {
        $order_id = input('order_id', 0, 'intval');
        $order = Db::name('order')
            ->where('id', $order_id)
            ->find();
        if($order && $order['status'] == 1) {
            $ispay = 1;
        } else {
            $ispay = 0;
        }
        return successJson([
            'ispay' => $ispay
        ]);
    }

    /**
     * 创建订单号
     */
    private function createOrderNo()
    {
        return 'Chat' . date('YmdHis') . rand(1000, 9999);
    }
}
