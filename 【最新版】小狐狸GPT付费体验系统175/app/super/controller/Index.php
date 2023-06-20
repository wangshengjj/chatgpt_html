<?php

namespace app\super\controller;

use think\facade\Db;

class Index extends Base
{
    public function getTongji()
    {
        $site_id = input('site_id', 0, 'intval');
        $today = date('Y-m-d');
        $start_time = strtotime($today);
        $end_time = strtotime($today . ' 23:59:59');

        if ($site_id) {
            // 查用户 - 总数
            $userTotal = Db::name('user')
                ->where('site_id', $site_id)
                ->count();
            // 查用户 - 新增
            $userTotalNew = Db::name('user')
                ->where([
                    ['site_id', '=', $site_id],
                    ['create_time', 'between', [$start_time, $end_time]]
                ])
                ->count();

            // 订单数量、订单金额 - 总数
            $data = Db::name('order')
                ->where([
                    ['site_id', '=', $site_id],
                    ['status', '=', 1]
                ])
                ->where([
                    ['is_refund', '=', 0],
                    ['status', '=', 1]
                ])
                ->field('count(id) as order_count,sum(total_fee) as order_amount')
                ->find();
            $orderTotal = $data['order_count'];
            $orderAmount = $data['order_amount'] / 100;

            // 订单数量、订单金额 - 新增
            $data = Db::name('order')
                ->where([
                    ['site_id', '=', $site_id],
                    ['is_refund', '=', 0],
                    ['status', '=', 1],
                    ['pay_time', 'between', [$start_time, $end_time]]
                ])
                ->field('count(id) as order_count,sum(total_fee) as order_amount')
                ->find();
            $orderTotalNew = $data['order_count'];
            $orderAmountNew = $data['order_amount'] / 100;

            // 问题数量 - 总数
            $msgTotalOld = Db::name('msg')
                ->where([
                    ['site_id', '=', $site_id],
                    ['user', '<>', 'ChatGPT'],
                    ['user', '<>', 'AI'],
                    ['is_delete', '=', 0]
                ])
                ->count();
            $msgTotalWeb = Db::name('msg_web')
                ->where([
                    ['site_id', '=', $site_id],
                    ['is_delete', '=', 0]
                ])
                ->count();
            $msgTotal = intval($msgTotalOld + $msgTotalWeb);
            $writeTotal = Db::name('msg_write')
                ->where([
                    ['site_id', '=', $site_id],
                    ['is_delete', '=', 0]
                ])
                ->count();
            $msgTotalNew = Db::name('msg_web')
                ->where([
                    ['site_id', '=', $site_id],
                    ['create_time', 'between', [$start_time, $end_time]],
                    ['is_delete', '=', 0]
                ])
                ->count();
            $writeTotalNew = Db::name('msg_write')
                ->where([
                    ['site_id', '=', $site_id],
                    ['create_time', 'between', [$start_time, $end_time]],
                    ['is_delete', '=', 0]
                ])
                ->count();
        } else {
            // 查用户 - 总数
            $userTotal = Db::name('user')->count();
            // 查用户 - 新增
            $userTotalNew = Db::name('user')
                ->where('create_time', 'between', [$start_time, $end_time])
                ->count();

            // 订单数量、订单金额 - 总数
            $data = Db::name('order')
                ->where('status', 1)
                ->where([
                    ['is_refund', '=', 0],
                    ['status', '=', 1]
                ])
                ->field('count(id) as order_count,sum(total_fee) as order_amount')
                ->find();
            $orderTotal = $data['order_count'];
            $orderAmount = $data['order_amount'] / 100;

            // 订单数量、订单金额 - 新增
            $data = Db::name('order')
                ->where([
                    ['is_refund', '=', 0],
                    ['status', '=', 1],
                    ['pay_time', 'between', [$start_time, $end_time]]
                ])
                ->field('count(id) as order_count,sum(total_fee) as order_amount')
                ->find();
            $orderTotalNew = $data['order_count'];
            $orderAmountNew = $data['order_amount'] / 100;

            // 问题数量 - 总数
            $msgTotalOld = Db::name('msg')
                ->where([
                    ['user', '<>', 'ChatGPT'],
                    ['user', '<>', 'AI'],
                    ['is_delete', '=', 0]
                ])
                ->count();
            $msgTotalWeb = Db::name('msg_web')
                ->count();
            $msgTotal = intval($msgTotalOld + $msgTotalWeb);
            $writeTotal = Db::name('msg_write')
                ->where([
                    ['is_delete', '=', 0]
                ])
                ->count();
            $msgTotalNew = Db::name('msg_web')
                ->where([
                    ['create_time', 'between', [$start_time, $end_time]],
                    ['is_delete', '=', 0]
                ])
                ->count();
            $writeTotalNew = Db::name('msg_write')
                ->where([
                    ['create_time', 'between', [$start_time, $end_time]],
                    ['is_delete', '=', 0]
                ])
                ->count();
        }


        return successJson([
            'userTotal' => $userTotal ? $userTotal : 0,
            'userTotalNew' => $userTotalNew ? $userTotalNew : 0,
            'orderTotal' => $orderTotal ? $orderTotal : 0,
            'orderTotalNew' => $orderTotalNew ? $orderTotalNew : 0,
            'orderAmount' => $orderAmount ? $orderAmount : 0,
            'orderAmountNew' => $orderAmountNew ? $orderAmountNew : 0,
            'msgTotal' => $msgTotal ? $msgTotal : 0,
            'msgTotalNew' => $msgTotalNew ? $msgTotalNew : 0,
            'writeTotal' => $writeTotal ? $writeTotal : 0,
            'writeTotalNew' => $writeTotalNew ? $writeTotalNew : 0
        ]);
    }

    public function getOrderChartData()
    {
        $site_id = input('site_id', 0, 'intval');
        $today = date('Y-m-d');
        $where = [
            ['is_refund', '=', 0],
            ['status', '=', 1]
        ];
        if ($site_id) {
            $where[] = ['site_id', '=', $site_id];
        }

        $timeArr = [];
        $countArr = [];
        $amountArr = [];
        for ($i = 15; $i >= 0; $i--) {
            $start_time = strtotime($today . "-{$i} day");
            $end_time = $start_time + 24 * 3600 - 1;

            $where2 = $where;
            $where2[] = ['pay_time', 'between', [$start_time, $end_time]];
            $data = Db::name('order')
                ->where($where2)
                ->field('count(id) as order_count,sum(total_fee) as order_amount')
                ->find();

            $timeArr[] = date('m-d', $start_time);
            $countArr[] = intval($data['order_count']);
            $amountArr[] = $data['order_amount'] / 100;
        }

        return successJson([
            'times' => $timeArr,
            'count' => $countArr,
            'amount' => $amountArr
        ]);
    }

    public function getMsgChartData()
    {
        $site_id = input('site_id', 0, 'intval');
        $today = date('Y-m-d');
        $where = [
            ['is_delete', '=', 0]
        ];
        if ($site_id) {
            $where[] = ['site_id', '=', $site_id];
        }
        $timeArr = [];
        $msgCountArr = [];
        $writeCountArr = [];
        for ($i = 15; $i >= 0; $i--) {
            $start_time = strtotime($today . "-{$i} day");
            $end_time = $start_time + 24 * 3600 - 1;
            $timeArr[] = date('m-d', $start_time);

            $where2 = $where;
            $where2[] = ['create_time', 'between', [$start_time, $end_time]];
            $msgCount = Db::name('msg_web')
                ->where($where2)
                ->count();
            $msgCountArr[] = intval($msgCount);
            $writeCount = Db::name('msg_write')
                ->where($where2)
                ->count();
            $writeCountArr[] = intval($writeCount);
        }

        return successJson([
            'times' => $timeArr,
            'msgCount' => $msgCountArr,
            'writeCount' => $writeCountArr
        ]);
    }
}
