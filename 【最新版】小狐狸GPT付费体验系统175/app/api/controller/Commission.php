<?php

namespace app\api\controller;

use think\facade\Db;

class Commission extends Base
{
    public function __construct()
    {
        parent::__construct();
        // 分销入口
        $commissionSetting = getSystemSetting(self::$site_id, 'commission');
        if (empty($commissionSetting['is_open'])) {
            return errorJson('分销功能已关闭，如有疑问请联系平台客服');
        }
    }

    public function index()
    {
        $user = Db::name('user')
            ->where([
                ['site_id', '=', self::$site_id],
                ['id', '=', self::$user['id']]
            ])
            ->find();
        if (!$user['commission_level']) {
            return successJson('', '不是代理商');
        }

        // 队员数、上级昵称、等级
        if (!$user['commission_pid']) {
            $commission_title = '分销商';
            // $commission_puser = '';
            $member_count = Db::name('user')
                ->where('commission_pid', $user['id'])
                ->count();
        } else {
            $puser = Db::name('user')
                ->where('id', $user['commission_pid'])
                ->find();
            $commission_puser = $puser['nickname'];
            $commission_title = '推荐人：' . $commission_puser;
            $member_count = 0;
        }
        // 订单数
        $order_count = Db::name('order')
            ->where([
                ['site_id', '=', self::$site_id],
                ['commission1|commission2', '=', $user['id']],
                ['pay_time', '>', 0],
                ['is_refund', '=', 0]
            ])
            ->count();
        // 直推用户数
        $tuser_count = Db::name('user')
            ->where([
                ['site_id', '=', self::$site_id],
                ['tuid', '=', $user['id']],
                ['is_delete', '=', 0]
            ])
            ->count();

        return successJson([
            'member' => [
                'nickname' => $user['nickname'],
                'avatar' => $user['avatar'],
                'commission_title' => $commission_title,
                // 'commission_puser' => $commission_puser,
                'commission_pid' => $user['commission_pid']
            ],
            'commission' => [
                'total' => $user['commission_total'] / 100,
                'paid' => $user['commission_paid'] / 100,
                'money' => $user['commission_money'] / 100,
                'order_count' => $order_count,
                'member_count' => $member_count,
                'tuser_count' => $tuser_count
            ]
        ]);
    }

    public function apply()
    {
        $now = time();
        $name = input('name', '', 'trim');
        $phone = input('phone', '', 'trim');
        $pid = input('pid', 0, 'intval');

        // 判断当前身份是不是推广员
        $user = Db::name('user')
            ->where([
                ['site_id', '=', self::$site_id],
                ['id', '=', self::$user['id']]
            ])
            ->find();
        if ($user['commission_level']) {
            return errorJson('你已经是推广员了，无需重复申请');
        }

        // 判断有没有正在处理中的申请
        $info = Db::name('commission_apply')
            ->where([
                ['site_id', '=', self::$site_id],
                ['user_id', '=', self::$user['id']]
            ])
            ->order('id desc')
            ->find();
        if ($info) {
            if ($info['status'] == 0) {
                return errorJson('有正在审核中的申请单，请勿重复提交');
            }
        }

        // 判断输入的表单内容
        if (empty($name)) {
            return errorJson('请输入姓名');
        }
        if (empty($phone)) {
            return errorJson('请输入手机号');
        }

        Db::startTrans();
        try {
            if (!$pid && $user['tuid']) {
                // 以推荐人作为上级
                $puser = Db::name('user')
                    ->where([
                        ['site_id', '=', self::$site_id],
                        ['id', '=', $user['tuid']]
                    ])
                    ->find();
                if ($puser && $puser['commission_level']) {
                    $pid = $puser['id'];
                }
            }

            $applyId = Db::name('commission_apply')
                ->insertGetId([
                    'site_id' => self::$site_id,
                    'user_id' => self::$user['id'],
                    'level' => 1,
                    'pid' => $pid,
                    'name' => $name,
                    'phone' => $phone,
                    'status' => 0,
                    'create_time' => $now
                ]);

            // 自动通过审核
            $setting = getSystemSetting(self::$site_id, 'commission');
            if (!empty($setting['auto_audit'])) {
                Db::name('commission_apply')
                    ->where('id', $applyId)
                    ->update([
                        'status' => 1
                    ]);
                Db::name('user')
                    ->where('id', self::$user['id'])
                    ->update([
                        'realname' => $name,
                        'phone' => $phone,
                        'tuid' => $pid ? $pid : self::$user['tuid'],
                        'commission_level' => 1,
                        'commission_pid' => $pid,
                        'commission_time' => $now
                    ]);
                $message = '提交成功';
            } else {
                $message = '提交成功，请等待审核';
            }

            Db::commit();
            return successJson('', $message);
        } catch (\Exception $e) {
            Db::rollback();
            return errorJson('提交失败，请重试');
        }
    }

    public function getLastApply()
    {
        $info = Db::name('commission_apply')
            ->where([
                ['site_id', '=', self::$site_id],
                ['user_id', '=', self::$user['id']]
            ])
            ->field('name,phone,status,reject_reason,create_time')
            ->order('id desc')
            ->find();
        if ($info) {
            $info['create_time'] = date('Y-m-d H:i', $info['create_time']);
        }
        return successJson($info);
    }

    public function memberList()
    {
        $page = input('page', 1, 'intval');
        $page = max(1, $page);
        $pagesize = 10;
        $list = Db::name('user')
            ->where([
                ['site_id', '=', self::$site_id],
                ['commission_level', '>', 0],
                ['commission_pid', '=', self::$user['id']],
                ['is_delete', '=', 0]
            ])
            ->page($page, $pagesize)
            ->field('id,avatar,nickname,commission_time')
            ->order('commission_time desc')
            ->select()->each(function ($item) {
                $item['commission_time'] = date('Y-m-d H:i', $item['commission_time']);
                $item['order_count'] = Db::name('order')
                    ->where([
                        ['commission2', '=', $item['id']],
                        ['pay_time', '>', 0],
                        ['is_refund', '=', 0]
                    ])
                    ->count();
                unset($item['id']);
                return $item;
            });
        return successJson($list);
    }

    public function memberInviteQrcode()
    {
        // 保存图片
        $page = 'pages/commission/apply';
        $scene = 'pid=' . self::$user['id'];
        $qrcode = './upload/qrcode/invite/' . substr(md5($page . $scene), 0, 16) . '.png';
        if (!is_dir(dirname($qrcode))) {
            mkdir(dirname($qrcode), 0755, true);
        }
        $setting = getSystemSetting(self::$site_id, 'wxapp');
        $Wxapp = new \Weixin\Wxapp($setting['appid'], $setting['appsecret']);
        $result = $Wxapp->getCodeUnlimit($scene, $page, 600);
        if (is_array($result) && $result['errno']) {
            return errorJson($result['message']);
        }
        file_put_contents($qrcode, $result);

        return successJson([
            'qrcode' => mediaUrl($qrcode, true)
        ]);
    }

    /**
     * 我推广的用户
     */
    public function tuserList()
    {
        $page = input('page', 1, 'intval');
        $page = max(1, $page);
        $pagesize = 15;
        $list = Db::name('user')
            ->where([
                ['site_id', '=', self::$site_id],
                ['tuid', '=', self::$user['id']],
                ['is_delete', '=', 0]
            ])
            ->page($page, $pagesize)
            ->field('id,avatar,nickname,commission_level,create_time')
            ->order('id desc')
            ->select()->each(function ($item) {
                $item['create_time'] = date('Y-m-d H:i', $item['create_time']);
                $item['order_count'] = Db::name('order')
                    ->where([
                        ['user_id', '=', $item['id']],
                        ['pay_time', '>', 0],
                        ['is_refund', '=', 0]
                    ])
                    ->count();
                return $item;
            });
        return successJson($list);
    }

    public function orderList()
    {
        $page = input('page', 1, 'intval');
        $page = max(1, $page);
        $pagesize = 10;
        $where = [
            ['site_id', '=', self::$site_id],
            ['commission1|commission2', '=', self::$user['id']],
            ['status', '>', 0]
        ];
        $list = Db::name('order')
            ->where($where)
            ->page($page, $pagesize)
            ->field('id,user_id,out_trade_no,total_fee,commission1,commission1_fee,commission2,commission2_fee,pay_time,create_time')
            ->order('id desc')
            ->select()->each(function ($item) {
                $user = Db::name('user')
                    ->where('id', $item['user_id'])
                    ->field('avatar,nickname')
                    ->find();
                $item['avatar'] = $user['avatar'];
                $item['nickname'] = $user['nickname'];
                $item['total_fee'] = $item['total_fee'] / 100;
                $item['pay_time'] = date('Y-m-d H:i:s', $item['pay_time']);
                $item['create_time'] = date('Y-m-d H:i:s', $item['create_time']);
                if ($item['commission1']) {
                    $commission1 = Db::name('user')
                        ->where('id', $item['commission1'])
                        ->field('avatar,nickname')
                        ->find();
                    if ($commission1) {
                        $item['commission1'] = [
                            'avatar' => $commission1['avatar'],
                            'nickname' => $commission1['nickname'],
                            'fee' => $item['commission1_fee'] / 100
                        ];
                    } else {
                        unset($item['commission1']);
                    }
                }
                if ($item['commission2']) {
                    $commission2 = Db::name('user')
                        ->where('id', $item['commission2'])
                        ->field('avatar,nickname')
                        ->find();
                    if ($commission2) {
                        $item['commission2'] = [
                            'avatar' => $commission2['avatar'],
                            'nickname' => $commission2['nickname'],
                            'fee' => $item['commission2_fee'] / 100
                        ];
                    } else {
                        unset($item['commission2']);
                    }

                }
                unset($item['commission1_fee'], $item['commission2_fee']);
                return $item;

            });
        return successJson($list);
    }

    public function billList()
    {
        $type = input('type', 'all', 'trim');
        $page = input('page', 1, 'intval');
        $page = max(1, $page);
        $pagesize = 20;

        $where = [
            ['site_id', '=', self::$site_id],
            ['user_id', '=', self::$user['id']]
        ];
        if ($type == 'is_lock') {
            $where[] = ['type', '=', 1];
            $where[] = ['is_lock', '=', 1];
        }
        $list = Db::name('commission_bill')
            ->where($where)
            ->field('id,order_id,money,type,title,is_lock,create_time')
            ->order('id desc')
            ->page($page, $pagesize)
            ->select()->each(function ($item) {
                $item['money'] = $item['money'] / 100;
                $item['create_time'] = date('Y-m-d H:i:s', $item['create_time']);
                return $item;
            });

        return successJson($list);
    }

    public function lastWithdraw()
    {
        $info = Db::name('commission_withdraw')
            ->where([
                ['site_id', '=', self::$site_id],
                ['user_id', '=', self::$user['id']]
            ])
            ->field('bank_name,account_name,account_number')
            ->order('id desc')
            ->find();
        if (!$info) {
            return successJson([
                'bank_name' => '微信零钱',
                'account_name' => '',
                'account_number' => ''
            ]);
        }
        return successJson([
            'bank_name' => $info['bank_name'],
            'account_name' => $info['account_name'],
            'account_number' => $info['account_number']
        ]);
    }

    public function withdrawList()
    {
        $page = input('page', 1, 'intval');
        $page = max(1, $page);
        $pagesize = 10;
        $list = Db::name('commission_withdraw')
            ->where([
                ['site_id', '=', self::$site_id],
                ['user_id', '=', self::$user['id']]
            ])
            ->field('id,money,bank_name,account_name,account_number,status,reject_reason,create_time')
            ->order('id desc')
            ->page($page, $pagesize)
            ->select()->each(function ($item) {
                $item['money'] = $item['money'] / 100;
                $item['create_time'] = date('Y-m-d H:i:s', $item['create_time']);
                return $item;
            });

        return successJson($list);
    }

    public function withdraw()
    {
        $money = input('money', '', 'trim');
        $bank_name = input('bank_name', '', 'trim');
        $account_name = input('account_name', '', 'trim');
        $account_number = input('account_number', '', 'trim');
        $qrcode = input('qrcode', '', 'trim');

        if ($account_name == '') {
            return errorJson('请填写户名');
        }
        if ($bank_name == '微信零钱' && !$qrcode) {
            return errorJson('请上传微信收款码');
        }
        if ($money * 100 <= 0) {
            return errorJson('参数错误');
        }
        if ($bank_name == '支付宝' && !$account_number) {
            return errorJson('请填写支付宝账号');
        }
        $info = Db::name('commission_withdraw')
            ->where([
                ['site_id', '=', self::$site_id],
                ['user_id', '=', self::$user['id']],
                ['status', '=', 0]
            ])
            ->find();
        if ($info) {
            return errorJson('有正在审核中的提现，请审核之后再提交');
        }

        $user = Db::name('user')
            ->where([
                ['site_id', '=', self::$site_id],
                ['id', '=', self::$user['id']]
            ])
            ->find();
        // 验证金额
        if ($user['commission_money'] < $money * 100) {
            return errorJson('可提现余额不足，请刷新后重试');
        }

        Db::startTrans();
        try {
            $withdraw_id = Db::name('commission_withdraw')
                ->insertGetId([
                    'site_id' => self::$site_id,
                    'user_id' => self::$user['id'],
                    'money' => $money * 100,
                    'bank_name' => $bank_name,
                    'account_name' => $account_name,
                    'account_number' => ($bank_name == '支付宝' ? $account_number : ''),
                    'qrcode' => ($bank_name == '微信零钱' ? $qrcode : ''),
                    'create_time' => time()
                ]);
            Db::name('user')
                ->where([
                    ['site_id', '=', self::$site_id],
                    ['id', '=', self::$user['id']]
                ])
                ->dec('commission_money', $money * 100)
                ->update();
            Db::name('commission_bill')
                ->insert([
                    'site_id' => self::$site_id,
                    'user_id' => self::$user['id'],
                    'order_id' => $withdraw_id,
                    'title' => '申请提现',
                    'type' => 2,
                    'money' => $money * 100,
                    'create_time' => time()
                ]);
            Db::commit();
            return successJson('', '提交成功，请等待财务审核打款，预计1-3个工作日');
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return errorJson('提交失败：' . $e->getMessage());
        }
    }

    /**
     * 邀请下单海报
     */
    public function poster()
    {

    }

    /**
     * 分销协议
     */
    public function agreement()
    {
        $setting = getSystemSetting(self::$site_id, 'commission');
        $content = !empty($setting['agreement']) ? $setting['agreement'] : '';
        $contents = $content ? explode("\n", $content) : [];
        return successJson($contents);
    }
}
