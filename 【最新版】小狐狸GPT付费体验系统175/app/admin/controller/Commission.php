<?php

namespace app\admin\controller;

use think\facade\Db;

class Commission extends Base
{
    public function getList()
    {
            $page = input('page', 1, 'intval');
            $pagesize = input('pagesize', 10, 'intval');
            $keyword = input('keyword', '', 'trim');
            $where = [
                ['site_id', '=', self::$site_id],
                ['commission_level', '>', 0],
                ['is_delete', '=', 0]
            ];
            if (!empty($keyword)) {
                $where[] = ['nickname|realname|phone', 'like', '%' . $keyword . '%'];
            }
            $list = Db::name('user')
                ->where($where)
                ->page($page, $pagesize)
                ->order('commission_time desc')
                ->select()
                ->each(function ($item) {
                    $item['commission_time'] = date('Y-m-d H:i:s', $item['commission_time']);
                    $item['commission_money'] = $item['commission_money'] / 100;
                    $item['commission_total'] = $item['commission_total'] / 100;
                    // 查找下级数量
                    $item['son_count'] = Db::name('commission_apply')
                        ->where(['pid' => $item['id'], 'status' => 1])
                        ->count();
                    $item['tuser_count'] = Db::name('user')
                        ->where('tuid', $item['id'])
                        ->count();
                    $item['order_count'] = Db::name('order')
                        ->where([
                            ['commission1|commission2', '=', $item['id']],
                            ['pay_time', '>', 0],
                            ['is_refund', '=', 0]
                        ])
                        ->count();
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
     * 下级分销商列表
     */
    public function getSonList()
    {
        $page = input('page', 1, 'intval');
        $pagesize = input('pagesize', 10, 'intval');
        $page = max(1, $page);
        $pagesize = max(1, $pagesize);
        $pid = input('pid', 0, 'intval');

        $where = [
            ['site_id', '=', self::$site_id],
            ['commission_level', '>', 0],
            ['commission_pid', '=', $pid],
            ['is_delete', '=', 0]
        ];

        $list = Db::name('user')
            ->where($where)
            ->field('id,nickname,avatar,commission_money,commission_total,commission_time')
            ->page($page, $pagesize)
            ->order('commission_time desc')
            ->select()
            ->each(function ($item) {
                $item['commission_time'] = date('Y-m-d H:i:s', $item['commission_time']);
                $item['commission_money'] = $item['commission_money'] / 100;
                $item['commission_total'] = $item['commission_total'] / 100;
                return $item;
            });


        $count = Db::name('user')
            ->where($where)
            ->count();

        return successJson([
            'count' => $count,
            'list' => $list
        ]);
    }

    /**
     * 推广的用户列表
     */
    public function getTuserList()
    {
        $page = input('page', 1, 'intval');
        $pagesize = input('pagesize', 10, 'intval');
        $page = max(1, $page);
        $pagesize = max(1, $pagesize);
        $pid = input('pid', 0, 'intval');

        $where = [
            ['site_id', '=', self::$site_id],
            ['tuid', '=', $pid],
            ['is_delete', '=', 0]
        ];

        $list = Db::name('user')
            ->where($where)
            ->field('id,nickname,avatar,commission_level,create_time')
            ->page($page, $pagesize)
            ->order('id desc')
            ->select()
            ->each(function ($item) {
                $item['create_time'] = date('Y-m-d H:i:s', $item['create_time']);
                return $item;
            });


        $count = Db::name('user')
            ->where($where)
            ->count();

        return successJson([
            'count' => $count,
            'list' => $list
        ]);
    }

    /**
     * 分销订单列表
     */
    public function getOrderList()
    {
        try {
            $page = input('page', 1, 'intval');
            $pagesize = input('pagesize', 10, 'intval');
            $page = max(1, $page);
            $pagesize = max(1, $pagesize);
            $pid = input('pid', 0, 'intval');
            $keyword = input('keyword', '', 'trim');

            $where = [
                ['site_id', '=', self::$site_id],
                ['pay_time', '>', 0],
                ['is_refund', '=', 0]
            ];
            if ($pid) {
                $where[] = ['commission1|commission2', '=', $pid];
            } else {
                $where[] = ['commission1', '>', 0];
            }
            if ($keyword) {
                $where[] = ['out_trade_no', '=', $keyword];
            }

            $list = Db::name('order')
                ->where($where)
                ->field('id,user_id,out_trade_no,total_fee,status,pay_time,is_refund,commission1,commission2,commission1_fee,commission2_fee,create_time')
                ->page($page, $pagesize)
                ->order('id desc')
                ->select()
                ->each(function ($item) {
                    $item['total_fee'] = $item['total_fee'] / 100;
                    $item['create_time'] = date('Y-m-d H:i:s', $item['create_time']);

                    $user = Db::name('user')
                        ->where('id', $item['user_id'])
                        ->field('avatar,nickname')
                        ->find();
                    if ($user) {
                        $item['nickname'] = $user['nickname'];
                        $item['avatar'] = $user['avatar'];
                    }

                    //一级
                    if ($item['commission1']) {
                        $user = Db::name('user')
                            ->where('id', $item['commission1'])
                            ->field('avatar,nickname')
                            ->find();
                        $item['commission1_nickname'] = $user['nickname'];
                        $item['commission1_avatar'] = $user['avatar'];
                        $item['commission1_fee'] = $item['commission1_fee'] / 100;
                    }

                    //获取二级
                    if ($item['commission2']) {
                        $user = Db::name('user')
                            ->where('id', $item['commission2'])
                            ->field('avatar,nickname')
                            ->find();
                        $item['commission2_nickname'] = $user['nickname'];
                        $item['commission2_avatar'] = $user['avatar'];
                        $item['commission2_fee'] = $item['commission2_fee'] / 100;
                    }

                    return $item;
                });

            $count = Db::name('order')
                ->where($where)
                ->count();

            return successJson([
                'count' => $count,
                'list' => $list
            ]);
        } catch (\Exception $e) {
            echo $e->getMessage();
        }

    }

    /**
     * 申请列表
     */
    public function getApplyList()
    {
        $page = input('page', 1, 'intval');
        $pagesize = input('pagesize', 10, 'intval');
        $page = max(1, $page);
        $pagesize = max(1, $pagesize);
        $keyword = input('keyword', '', 'trim');
        $status = input('status', '', 'trim');

        $where = [
            ['site_id', '=', self::$site_id]
        ];
        if (!empty($keyword)) {
            $where[] = ['name|phone', 'like', '%' . $keyword . '%'];
        }
        if ($status != 'all') {
            $where[] = ['status', '=', $status];
        }
        $list = Db::name('commission_apply')
            ->where($where)
            ->page($page, $pagesize)
            ->order('id desc')
            ->select()
            ->each(function ($item) {
                $user = Db::name('user')
                    ->where('id', $item['user_id'])
                    ->field('avatar,nickname')
                    ->find();
                if ($user) {
                    $item['avatar'] = $user['avatar'];
                    $item['nickname'] = $user['nickname'];
                }
                $item['create_time'] = date('Y-m-d H:i:s', $item['create_time']);
                if ($item['pid']) {
                    $puser = Db::name('user')
                        ->where('id', $item['pid'])
                        ->field('avatar, nickname')
                        ->find();
                    $item['invite_avatar'] = $puser['avatar'];
                    $item['invite_nickname'] = $puser['nickname'];
                }
                return $item;
            });

        $count = Db::name('commission_apply')
            ->where($where)
            ->count();

        return successJson([
            'count' => $count,
            'list' => $list
        ]);
    }

    /**
     * 查询申请详情
     */
    public function getApplyInfo()
    {
        //当前分销商详情
        $id = input('id', 0, 'intval');
        $info = Db::name('commission_apply')
            ->where([
                ['site_id', '=', self::$site_id],
                ['id', '=', $id]
            ])
            ->field('id,status,reject_reason')
            ->find();
        if (!$info) {
            return errorJson('没有找到相关信息，请刷新页面重试');
        }
        return successJson($info);
    }

    /**
     * 分销商入驻审核
     */
    public function setApplyStatus()
    {
        $id = input('id', 0, 'intval');
        $reject_reason = input('reject_reason', '', 'trim');
        $status = input('status', 0, 'intval');

        $apply = Db::name('commission_apply')
            ->where([
                ['site_id', '=', self::$site_id],
                ['id', '=', $id]
            ])
            ->find();
        if(!$apply) {
            return errorJson('没找到数据');
        }

        Db::startTrans();
        try {
            Db::name('commission_apply')
                ->where('id', $id)
                ->update([
                    'status' => $status,
                    'reject_reason' => $reject_reason
                ]);
            if ($status === 1) {
                Db::name('user')
                    ->where('id', $apply['user_id'])
                    ->update([
                        'realname' => $apply['name'],
                        'phone' => $apply['phone'],
                        'commission_level' => 1,
                        'commission_pid' => $apply['pid'],
                        'commission_time' => time()
                    ]);
                if ($apply['pid']) {
                    Db::name('user')
                        ->where('id', $apply['user_id'])
                        ->update([
                            'tuid' => $apply['pid']
                        ]);
                }
            }

            Db::commit();
            return successJson('', '操作成功');
        } catch (\Exception $e) {
            Db::rollback();
            return errorJson($e->getMessage());
        }
    }

    /**
     * 提现列表
     */
    public function getWithdrawList()
    {
        $page = input('page', 1, 'intval');
        $pagesize = input('pagesize', 10, 'intval');
        $page = max(1, $page);
        $pagesize = max(1, $pagesize);
        $status = input('status', '', 'trim');
        $where = [
            ['site_id', '=', self::$site_id],
        ];
        if ($status != 'all') {
            $where[] = ['status', '=', $status];
        }
        $list = Db::name('commission_withdraw')
            ->where($where)
            ->page($page, $pagesize)
            ->order('id desc')
            ->select()
            ->each(function ($item) {
                $user = Db::name('user')
                    ->where('id', $item['user_id'])
                    ->field('avatar,nickname')
                    ->find();
                $item['money'] = $item['money'] / 100;
                $item['nickname'] = $user['nickname'];
                $item['avatar'] = $user['avatar'];
                $item['create_time'] = date('Y-m-d H:i:s', $item['create_time']);
                return $item;
            });

        $count = Db::name('commission_withdraw')
            ->where($where)
            ->count();

        return successJson([
            'count' => $count,
            'list' => $list
        ]);
    }

    /**
     * 删除分销商列表
     */
    public function delWithdraw()
    {
        $id = input('id', 0, 'intval');
        $res = Db::name('commission_withdraw')
            ->where([
                ['site_id', '=', self::$site_id],
                ['id', '=', $id]
            ])
            ->delete();
        if ($res !== false) {
            return successJson('', '删除成功');
        } else {
            return errorJson('删除失败，请重试！');
        }
    }

    /**
     * 查询提现详情
     */

    public function getWithdrawInfo()
    {
        $id = input('id', 0, 'intval');

        $info = Db::name('commission_withdraw')
            ->where([
                ['site_id', '=', self::$site_id],
                ['id', '=', $id]
            ])
            ->field('id,status,user_id,money,reject_reason,remark')
            ->find();
        if (!$info) {
            return errorJson('没有找到数据');
        }
        $info['money'] = $info['money'] / 100;
        $user = Db::name('user')
            ->where('id', $info['user_id'])
            ->field('avatar,nickname')
            ->find();
        if ($user) {
            $info['nickname'] = $user['nickname'];
            $info['avatar'] = $user['avatar'];
        }


        return successJson($info);
    }

    /**
     * 修改提现详情信息
     */
    public function saveWithdrawInfo()
    {
        $id = input('id', 0, 'intval');
        $status = input('status', 0, 'intval');
        $reject_reason = input('reject_reason', '', 'trim');

        $withdraw = Db::name('commission_withdraw')
            ->where([
                ['site_id', '=', self::$site_id],
                ['id', '=', $id]
            ])
            ->find();
        if ($withdraw['status'] > 0) {
            return errorJson('不能修改审核状态');
        }

        if ($status == 1) {
            //只修改状态
            $res = Db::name('commission_withdraw')
                ->where([
                    ['site_id', '=', self::$site_id],
                    ['id', '=', $id]
                ])
                ->update([
                    'status' => $status,
                    'reject_reason' => '',
                    'audit_time' => time()
                ]);
            if ($res !== false) {
                return successJson('', '操作成功');
            } else {
                return errorJson('操作失败，请重试！');
            }
        } elseif ($status == 2) {
            // 驳回
            Db::startTrans();
            try {
                //修改提现表
                Db::name('commission_withdraw')
                    ->where([
                        ['site_id', '=', self::$site_id],
                        ['id', '=', $id]
                    ])
                    ->update([
                        'status' => $status,
                        'reject_reason' => $reject_reason,
                        'audit_time' => time()
                    ]);
                //查询用户表
                $userInfo = Db::name('user')
                    ->where('id', $withdraw['user_id'])
                    ->find();
                //更改用户金额
                Db::name('user')
                    ->where('id', $withdraw['user_id'])
                    ->update([
                        'commission_money' => $userInfo['commission_money'] + $withdraw['money']
                    ]);
                //增加流水记录
                Db::name('commission_bill')
                    ->insert([
                        'site_id' => self::$site_id,
                        'user_id' => $withdraw['user_id'],
                        'title' => '提现失败退回',
                        'type' => 4,
                        'order_id' => $id,
                        'money' => $withdraw['money'],
                        'create_time' => time()
                    ]);

                Db::commit();
                return successJson('', '操作成功');
            } catch (\Exception $e) {
                Db::rollback();
                return errorJson('操作失败，请重试！');
            }

        }
    }
}
