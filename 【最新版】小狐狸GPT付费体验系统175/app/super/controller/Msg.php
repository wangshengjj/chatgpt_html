<?php

namespace app\super\controller;

use think\facade\Db;

class Msg extends Base
{

    public function getOldMsgList()
    {
        $page = input('page', 1, 'intval');
        $pagesize = input('pagesize', 10, 'intval');
        $date = input('date', []);
        $user_id = input('user_id', 0, 'intval');
        $site_id = input('site_id', 0, 'intval');
        $keyword = input('keyword', '', 'trim');
        $where = [
            ['is_delete', '=', 0]
        ];
        if ($site_id) {
            $where[] = ['site_id', '=', $site_id];
        }
        if ($user_id) {
            $where[] = ['user_id', '=', $user_id];
        }
        if ($keyword) {
            $where[] = ['message', 'like', '%' . $keyword, '%'];
        }
        if (!empty($date)) {
            $start_time = strtotime($date[0]);
            $end_time = strtotime($date[1]);
            $where[] = ['create_time', 'between', [$start_time, $end_time]];
        }

        $list = Db::name('msg')
            ->where($where)
            ->order('id asc')
            ->page($page, $pagesize)
            ->select()->each(function($item) {
                $item['create_time'] = date('Y-m-d H:i:s', $item['create_time']);
                $item['message'] = formatMsg($item['message']);
                $item['message_input'] = formatMsg($item['message_input']);
                if (empty($item['message_input']) || $item['message'] == $item['message_input']) {
                    unset($item['message_input']);
                }
                return $item;
            });
        $count = Db::name('msg')
            ->where($where)
            ->count();
        return successJson([
            'list' => $list,
            'count' => $count
        ]);
    }

    public function getOldMsgTongji()
    {
        $date = input('date', []);
        $site_id = input('site_id', 0, 'intval');
        $user_id = input('user_id', 0, 'intval');
        $keyword = input('keyword', '', 'trim');
        $where = [
            ['is_delete', '=', 0],
            ['user', '<>', '我']
        ];
        if ($site_id) {
            $where[] = ['site_id', '=', $site_id];
        }
        if ($user_id) {
            $where[] = ['user_id', '=', $user_id];
        }
        if ($keyword) {
            $where[] = ['message', 'like', '%' . $keyword, '%'];
        }
        if (!empty($date)) {
            $start_time = strtotime($date[0]);
            $end_time = strtotime($date[1]);
            $where[] = ['create_time', 'between', [$start_time, $end_time]];
        }
        $data = Db::name('msg')
            ->where($where)
            ->field('count(id) as msg_count,sum(total_tokens) as msg_tokens')
            ->find();

        return successJson([
            'msgCount' => intval($data['msg_count']),
            'msgTokens' => intval($data['msg_tokens'])
        ]);
    }


    public function getMsgList()
    {
        $page = input('page', 1, 'intval');
        $pagesize = input('pagesize', 10, 'intval');
        $date = input('date', []);
        $user_id = input('user_id', 0, 'intval');
        $site_id = input('site_id', 0, 'intval');
        $keyword = input('keyword', '', 'trim');
        $where = [
            ['is_delete', '=', 0]
        ];
        if ($site_id) {
            $where[] = ['site_id', '=', $site_id];
        }
        if ($user_id) {
            $where[] = ['user_id', '=', $user_id];
        }
        if ($keyword) {
            $where[] = ['message', 'like', '%' . $keyword, '%'];
        }
        if (!empty($date)) {
            $start_time = strtotime($date[0]);
            $end_time = strtotime($date[1]);
            $where[] = ['create_time', 'between', [$start_time, $end_time]];
        }

        $list = Db::name('msg_web')
            ->where($where)
            ->order('id desc')
            ->page($page, $pagesize)
            ->select()->each(function ($item) {
                $item['create_time'] = date('Y-m-d H:i:s', $item['create_time']);
                $item['message'] = formatMsg($item['message']);
                $item['message_input'] = formatMsg($item['message_input']);
                if ($item['message'] == $item['message_input']) {
                    unset($item['message_input']);
                }
                $item['response'] = formatMsg($item['response']);
                $item['response_input'] = formatMsg($item['response_input']);
                if ($item['response'] == $item['response_input']) {
                    unset($item['response_input']);
                }
                return $item;
            });
        $count = Db::name('msg_web')
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
    public function getMsgTongji()
    {
        $date = input('date', []);
        $user_id = input('user_id', 0, 'intval');
        $site_id = input('site_id', 0, 'intval');
        $keyword = input('keyword', '', 'trim');
        $where = [
            ['is_delete', '=', 0]
        ];
        if ($site_id) {
            $where[] = ['site_id', '=', $site_id];
        }
        if ($user_id) {
            $where[] = ['user_id', '=', $user_id];
        }
        if ($keyword) {
            $where[] = ['message_input|response_input', 'like', '%' . $keyword, '%'];
        }
        if (!empty($date)) {
            $start_time = strtotime($date[0]);
            $end_time = strtotime($date[1]);
            $where[] = ['create_time', 'between', [$start_time, $end_time]];
        }
        $data = Db::name('msg_web')
            ->where($where)
            ->field('count(id) as msg_count,sum(total_tokens) as msg_tokens')
            ->find();

        return successJson([
            'msgCount' => intval($data['msg_count']),
            'msgTokens' => intval($data['msg_tokens'])
        ]);
    }

    public function delMsg()
    {
        $id = input('id', 0, 'intval');
        try {
            Db::name('msg_web')
                ->where('id', $id)
                ->update([
                    'is_delete' => 1
                ]);
            return successJson('', '删除成功');
        } catch (\Exception $e) {
            return errorJson('删除失败：' . $e->getMessage());
        }
    }
}
