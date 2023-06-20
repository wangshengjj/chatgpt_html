<?php

namespace app\super\controller;

use think\facade\Db;

class Write extends Base
{
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

        $list = Db::name('msg_write')
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
                $item['topic_title'] = Db::name('write_topic')
                    ->where('id', $item['topic_id'])
                    ->value('title');
                $item['prompt_title'] = Db::name('write_prompts')
                    ->where('id', $item['prompt_id'])
                    ->value('title');
                return $item;
            });
        $count = Db::name('msg_write')
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
        $data = Db::name('msg_write')
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
            Db::name('msg_write')
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
