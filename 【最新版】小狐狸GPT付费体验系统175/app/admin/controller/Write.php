<?php

namespace app\admin\controller;

use think\facade\Db;

class Write extends Base
{
    public function getMsgList()
    {
        $page = input('page', 1, 'intval');
        $pagesize = input('pagesize', 10, 'intval');
        $date = input('date', []);
        $user_id = input('user_id', 0, 'intval');
        $keyword = input('keyword', '', 'trim');
        $where = [
            ['site_id', '=', self::$site_id],
            ['is_delete', '=', 0]
        ];
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
        $keyword = input('keyword', '', 'trim');
        $where = [
            ['site_id', '=', self::$site_id],
            ['is_delete', '=', 0]
        ];
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

    public function getTopicList()
    {
        try {
            $where = [
                ['site_id', '=', self::$site_id]
            ];
            $list = Db::name('write_topic')
                ->where($where)
                ->field('id,title,weight,state')
                ->order('weight desc, id asc')
                ->select()
                ->toArray();

            return successJson($list);
        } catch (\Exception $e) {
            return errorJson($e->getMessage());
        }
    }

    public function getTopic()
    {
        $id = input('id', 0, 'intval');

        try {
            $info = Db::name('write_topic')
                ->where([
                    ['site_id', '=', self::$site_id],
                    ['id', '=', $id]
                ])
                ->find();
            if (!$info) {
                return errorJson('没有找到数据，请刷新页面重试');
            }
            return successJson($info);
        } catch (\Exception $e) {
            return errorJson($e->getMessage());
        }
    }

    public function saveTopic()
    {
        $id = input('id', 0, 'intval');
        $title = input('title', '', 'trim');
        $weight = input('weight', 100, 'intval');

        try {
            $data = [
                'title' => $title,
                'weight' => $weight,
                'update_time' => time()
            ];
            if ($id) {
                Db::name('write_topic')
                    ->where('id', $id)
                    ->update($data);
            } else {
                $data['site_id'] = self::$site_id;
                $data['create_time'] = time();
                Db::name('write_topic')
                    ->insert($data);
            }
            return successJson('', '保存成功');
        } catch (\Exception $e) {
            return errorJson('保存失败：' . $e->getMessage());
        }
    }

    public function delTopic()
    {
        $id = input('id', 0, 'intval');
        try {
            Db::name('write_topic')
                ->where('id', $id)
                ->delete();
            return successJson('', '删除成功');
        } catch (\Exception $e) {
            return errorJson('删除失败：' . $e->getMessage());
        }
    }

    /**
     * @return string
     * 设置分类状态
     */
    public function setTopicState()
    {
        $id = input('id', 0, 'intval');
        $state = input('state', 0, 'intval');
        try {
            Db::name('write_topic')
                ->where([
                    ['site_id', '=', self::$site_id],
                    ['id', '=', $id]
                ])
                ->update([
                    'state' => $state
                ]);
            return successJson('', '设置成功');
        } catch (\Exception $e) {
            return errorJson('设置失败：' . $e->getMessage());
        }
    }

    public function getPromptList()
    {
        $page = input('page', 1, 'intval');
        $pagesize = input('pagesize', 10, 'intval');
        $topic_id = input('topic_id', 'all');

        $where = [
            ['site_id', '=', self::$site_id],
            ['is_delete', '=', 0]
        ];
        if ($topic_id && $topic_id != 'all') {
            $where[] = ['topic_id', '=', $topic_id];
        }

        try {
            $list = Db::name('write_prompts')
                ->where($where)
                ->field('id,topic_id,title,desc,views,usages,votes,weight,state')
                ->order('weight desc, id asc')
                ->page($page, $pagesize)
                ->select()->each(function ($item) {
                    $item['topic_title'] = Db::name('write_topic')
                        ->where('id', $item['topic_id'])
                        ->value('title');
                    return $item;
                })
                ->toArray();

            $count = Db::name('write_prompts')
                ->where($where)
                ->count();

            return successJson([
                'list' => $list,
                'count' => $count
            ]);
        } catch (\Exception $e) {
            return errorJson($e->getMessage());
        }
    }

    public function getPrompt()
    {
        $id = input('id', 0, 'intval');

        try {
            $info = Db::name('write_prompts')
                ->where([
                    ['site_id', '=', self::$site_id],
                    ['id', '=', $id],
                    ['is_delete', '=', 0]
                ])
                ->field('id,title,topic_id,desc,prompt,hint,welcome,fake_votes,fake_views,fake_usages,weight')
                ->find();
            if (!$info) {
                return errorJson('没有找到数据，请刷新页面重试');
            }
            return successJson($info);
        } catch (\Exception $e) {
            return errorJson($e->getMessage());
        }
    }

    public function savePrompt()
    {
        $id = input('id', 0, 'intval');
        $topic_id = input('topic_id', 0, 'intval');
        $title = input('title', '', 'trim');
        $desc = input('desc', '', 'trim');
        $prompt = input('prompt', '', 'trim');
        $hint = input('hint', '', 'trim');
        $welcome = input('welcome', '', 'trim');
        $weight = input('weight', 100, 'intval');
        $fake_votes = input('fake_votes', 0, 'intval');
        $fake_views = input('fake_views', 0, 'intval');
        $fake_usages = input('fake_usages', 0, 'intval');

        try {
            $data = [
                'topic_id' => $topic_id,
                'title' => $title,
                'desc' => $desc,
                'prompt' => $prompt,
                'hint' => $hint,
                'welcome' => $welcome,
                'weight' => $weight,
                'fake_votes' => $fake_votes,
                'fake_views' => $fake_views,
                'fake_usages' => $fake_usages,
                'update_time' => time()
            ];
            if ($id) {
                Db::name('write_prompts')
                    ->where('id', $id)
                    ->update($data);
            } else {
                $data['site_id'] = self::$site_id;
                $data['create_time'] = time();
                Db::name('write_prompts')
                    ->insert($data);
            }
            return successJson('', '保存成功');
        } catch (\Exception $e) {
            return errorJson('保存失败：' . $e->getMessage());
        }
    }

    public function delPrompt()
    {
        $id = input('id', 0, 'intval');
        try {
            Db::name('write_prompts')
                ->where('id', $id)
                ->delete();
            return successJson('', '删除成功');
        } catch (\Exception $e) {
            return errorJson('删除失败：' . $e->getMessage());
        }
    }

    /**
     * @return string
     * 设置分类状态
     */
    public function setPromptState()
    {
        $id = input('id', 0, 'intval');
        $state = input('state', 0, 'intval');
        try {
            Db::name('write_prompts')
                ->where([
                    ['site_id', '=', self::$site_id],
                    ['id', '=', $id]
                ])
                ->update([
                    'state' => $state
                ]);
            return successJson('', '设置成功');
        } catch (\Exception $e) {
            return errorJson('设置失败：' . $e->getMessage());
        }
    }
}
