<?php

namespace app\admin\controller;

use think\facade\Db;

class Reward extends Base
{
    public function getShareList()
    {
        $page = input('page', 1, 'intval');
        $pagesize = input('pagesize', 10, 'intval');
        $date = input('date', []);
        $user_id = input('user_id', 0, 'intval');
        $where = [
            ['site_id', '=', self::$site_id]
        ];
        if ($user_id) {
            $where[] = ['user_id', '=', $user_id];
        }
        if (!empty($date)) {
            $start_time = strtotime($date[0]);
            $end_time = strtotime($date[1]);
            $where[] = ['create_time', 'between', [$start_time, $end_time]];
        }

        $list = Db::name('reward_share')
            ->where($where)
            ->order('id desc')
            ->page($page, $pagesize)
            ->select()->each(function($item) {
                $item['create_time'] = date('Y-m-d H:i:s', $item['create_time']);
                return $item;
            });
        $count = Db::name('reward_share')
            ->where($where)
            ->count();
        return successJson([
            'list' => $list,
            'count' => $count
        ]);
    }

    /**
     * 统计
     */
    public function getShareTongji()
    {
        $date = input('date', []);
        $user_id = input('user_id', 0, 'intval');
        $where = [
            ['site_id', '=', self::$site_id]
        ];
        if ($user_id) {
            $where[] = ['user_id', '=', $user_id];
        }
        if (!empty($date)) {
            $start_time = strtotime($date[0]);
            $end_time = strtotime($date[1]);
            $where[] = ['create_time', 'between', [$start_time, $end_time]];
        }
        $data = Db::name('reward_share')
            ->where($where)
            ->field('count(id) as share_count,sum(invite_num) as invite_total,sum(reward_num) as reward_total')
            ->find();

        return successJson([
            'shareCount' => intval($data['share_count']),
            'inviteTotal' => intval($data['invite_total']),
            'rewardTotal' => intval($data['reward_total'])
        ]);
    }

    public function getInviteList()
    {
        $page = input('page', 1, 'intval');
        $pagesize = input('pagesize', 10, 'intval');
        $date = input('date', []);
        $user_id = input('user_id', 0, 'intval');
        $where = [
            ['site_id', '=', self::$site_id]
        ];
        if ($user_id) {
            $where[] = ['user_id', '=', $user_id];
        }
        if (!empty($date)) {
            $start_time = strtotime($date[0]);
            $end_time = strtotime($date[1]);
            $where[] = ['create_time', 'between', [$start_time, $end_time]];
        }

        $list = Db::name('reward_invite')
            ->where($where)
            ->order('id desc')
            ->page($page, $pagesize)
            ->select()->each(function($item) {
                $item['create_time'] = date('Y-m-d H:i:s', $item['create_time']);
                return $item;
            });
        $count = Db::name('reward_invite')
            ->where($where)
            ->count();
        return successJson([
            'list' => $list,
            'count' => $count
        ]);
    }

    /**
     * 统计
     */
    public function getInviteTongji()
    {
        $date = input('date', []);
        $user_id = input('user_id', 0, 'intval');
        $where = [
            ['site_id', '=', self::$site_id]
        ];
        if ($user_id) {
            $where[] = ['user_id', '=', $user_id];
        }
        if (!empty($date)) {
            $start_time = strtotime($date[0]);
            $end_time = strtotime($date[1]);
            $where[] = ['create_time', 'between', [$start_time, $end_time]];
        }
        $data = Db::name('reward_invite')
            ->where($where)
            ->field('count(id) as invite_count,sum(reward_num) as reward_total')
            ->find();

        return successJson([
            'inviteCount' => intval($data['invite_count']),
            'rewardTotal' => intval($data['reward_total'])
        ]);
    }

    public function getAdList()
    {
        $page = input('page', 1, 'intval');
        $pagesize = input('pagesize', 10, 'intval');
        $date = input('date', []);
        $user_id = input('user_id', 0, 'intval');
        $where = [
            ['site_id', '=', self::$site_id]
        ];
        if ($user_id) {
            $where[] = ['user_id', '=', $user_id];
        }
        if (!empty($date)) {
            $start_time = strtotime($date[0]);
            $end_time = strtotime($date[1]);
            $where[] = ['create_time', 'between', [$start_time, $end_time]];
        }

        $list = Db::name('reward_ad')
            ->where($where)
            ->order('id desc')
            ->page($page, $pagesize)
            ->select()->each(function($item) {
                $item['create_time'] = date('Y-m-d H:i:s', $item['create_time']);
                return $item;
            });
        $count = Db::name('reward_ad')
            ->where($where)
            ->count();
        return successJson([
            'list' => $list,
            'count' => $count
        ]);
    }

    /**
     * 统计
     */
    public function getAdTongji()
    {
        $date = input('date', []);
        $user_id = input('user_id', 0, 'intval');
        $where = [
            ['site_id', '=', self::$site_id]
        ];
        if ($user_id) {
            $where[] = ['user_id', '=', $user_id];
        }
        if (!empty($date)) {
            $start_time = strtotime($date[0]);
            $end_time = strtotime($date[1]);
            $where[] = ['create_time', 'between', [$start_time, $end_time]];
        }
        $data = Db::name('reward_ad')
            ->where($where)
            ->field('count(id) as ad_count,sum(reward_num) as reward_total')
            ->find();

        return successJson([
            'adCount' => intval($data['ad_count']),
            'rewardTotal' => intval($data['reward_total'])
        ]);
    }

}
