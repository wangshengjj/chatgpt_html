<?php

namespace app\api\controller;

use think\facade\Db;

class Write extends Base
{
    public function getTopicList()
    {
        $list = Db::name('write_topic')
            ->where([
                ['site_id', '=', self::$site_id],
                ['state', '=', 1]
            ])
            ->order('weight desc, id asc')
            ->field('id,title')
            ->select()
            ->toArray();

        return successJson($list);
    }

    public function getPrompts()
    {
        $page = input('page', 1, 'intval');
        $pagesize = input('pagesize', 10, 'intval');
        $topic_id = input('topic_id', 'all');
        $where = [
            ['site_id', '=', self::$site_id],
            ['state', '=', 1],
            ['is_delete', '=', 0]
        ];
        if ($topic_id != 'all') {
            $where[] = ['topic_id', '=', $topic_id];
        }
        $myVotes = Db::name('write_prompts_vote')
            ->where('user_id', self::$user['id'])
            ->column('prompt_id');

        $list = Db::name('write_prompts')
            ->where($where)
            ->order('weight desc,usages desc,views desc, id asc')
            ->page($page, $pagesize)
            ->field('id,title,desc,usages,views,fake_usages,fake_views')
            ->select()->each(function ($item) use ($myVotes) {
                $item['isVote'] = in_array($item['id'], $myVotes) ? 1 : 0;
                $item['views'] = $item['views'] + $item['fake_views'];
                $item['usages'] = $item['usages'] + $item['fake_usages'];
                unset($item['fake_usages'], $item['fake_views']);
                return $item;
            })->toArray();

        $count = Db::name('write_prompts')
            ->where($where)
            ->count();

        return successJson([
            'list' => $list,
            'count' => $count
        ]);
    }

    public function getVotePrompts()
    {
        $page = input('page', 1, 'intval');
        $pagesize = input('pagesize', 10, 'intval');
        $where = [
            ['site_id', '=', self::$site_id],
            ['user_id', '=', self::$user['id']]
        ];

        $list = Db::name('write_prompts_vote')
            ->where($where)
            ->order('id desc')
            ->page($page, $pagesize)
            ->select()->each(function ($item) {
                $prompt = Db::name('write_prompts')
                    ->where('id', $item['prompt_id'])
                    ->field('id,title,desc,usages,views,fake_usages,fake_views')
                    ->find();
                if ($prompt) {
                    $prompt['isVote'] = 1;
                    $prompt['views'] = $prompt['views'] + $prompt['fake_views'];
                    $prompt['usages'] = $prompt['usages'] + $prompt['fake_usages'];
                    unset($prompt['fake_usages'], $prompt['fake_views']);
                    return $prompt;
                }
                return $item;
            });

        $count = Db::name('write_prompts_vote')
            ->where($where)
            ->count();

        return successJson([
            'list' => $list,
            'count' => $count
        ]);
    }

    public function getPrompt()
    {
        $prompt_id = input('prompt_id', '', 'intval');
        $info = Db::name('write_prompts')
            ->where([
                ['site_id', '=', self::$site_id],
                ['id', '=', $prompt_id],
                ['is_delete', '=', 0]
            ])
            ->field('id,title,desc,hint')
            ->find();
        if (!$info) {
            return errorJson('未找到此模型');
        }
        // 点击量+1
        Db::name('write_prompts')
            ->where('id', $info['id'])
            ->inc('views', 1)
            ->update();

        return successJson($info);
    }

    public function votePrompt()
    {
        $prompt_id = input('prompt_id', '', 'intval');
        $info = Db::name('write_prompts_vote')
            ->where([
                ['site_id', '=', self::$site_id],
                ['user_id', '=', self::$user['id']],
                ['prompt_id', '=', $prompt_id]
            ])
            ->find();
        if ($info) {
            Db::name('write_prompts_vote')
                ->where([
                    ['site_id', '=', self::$site_id],
                    ['user_id', '=', self::$user['id']],
                    ['prompt_id', '=', $prompt_id]
                ])
                ->delete();
            // 收藏量-1
            Db::name('write_prompts')
                ->where('id', $prompt_id)
                ->dec('votes', 1)
                ->update();
            return successJson('', '已取消收藏');
        } else {
            Db::name('write_prompts_vote')
                ->insert([
                    'site_id' => self::$site_id,
                    'user_id' => self::$user['id'],
                    'prompt_id' => $prompt_id,
                    'create_time' => time()
                ]);
            // 收藏量+1
            Db::name('write_prompts')
                ->where('id', $prompt_id)
                ->inc('votes', 1)
                ->update();
            return successJson('', '收藏成功');
        }
    }

    public function sendText()
    {
        $now = time();
        $prompt_id = input('prompt_id', 0, 'intval');
        $message = input('message', '', 'trim');
        $lang = input('lang', '简体中文', 'trim');
        if (empty($message)) {
            return errorJson('请输入您的内容');
        }
        $user = Db::name('user')
            ->where('id', self::$user['id'])
            ->find();
        if (!$user) {
            $_SESSION['user'] = null;
            die(json_encode(['errno' => 403, 'message' => '请登录']));
        }

        if (intval($user['balance']) <= 0 && $user['vip_expire_time'] < $now) {
            usleep(1000000);
            return successJson(['提问次数用完了，请充值！（本条消息不扣费）']);
        }

        $prompt = Db::name('write_prompts')
            ->where([
                ['id', '=', $prompt_id],
                ['is_delete', '=', 0]
            ])
            ->find();
        if (!$prompt) {
            return errorJson('模板不存在或已被删除');
        }

        // 自定义敏感词替换
        $clearMessage = wordFilter($message);

        // 请求AI接口
        $setting = getSystemSetting(self::$site_id, 'chatgpt');
        $temperature = floatval($setting['temperature']) ?? 0;
        $max_tokens = intval($setting['max_tokens']) ?? 0;
        $apikey = $setting['apikey'] ?? '';
        $model = 'gpt-3.5-turbo';
        $ChatGPT = new \ChatGPT\sdk($apikey, $model, $temperature, $max_tokens);
        // 使用自定义接口
        $apiSetting = getSystemSetting(0, 'api');
        if ($apiSetting['channel'] == 'diy' && $apiSetting['host']) {
            $ChatGPT->setChannel($apiSetting['channel']);
            $ChatGPT->setDiyHost(rtrim($apiSetting['host'], '/') . '/api.php');
            $ChatGPT->setDiyKey($apiSetting['key']);
        }
        elseif ($apiSetting['channel'] == 'agent' && $apiSetting['agent_host']) {
            $ChatGPT->setChannel($apiSetting['channel']);
            $ChatGPT->setDiyHost(rtrim($apiSetting['agent_host'], '/'));
        }
        if (in_array($model, ['gpt-3.5-turbo', 'gpt-3.5-turbo-0301'])) {
            $question = [];
            // 连续对话需要带着上一个问题请求接口
            if ($message == '继续' || $message == 'go on') {
                $lastQuestions = Db::name('msg_write')
                    ->where([
                        ['user_id', '=', self::$user['id']],
                        ['create_time', '>', ($now - 300)]
                    ])
                    ->find();
                // 如果超长，就不关联上下文了
                if (mb_strlen($lastQuestions['text_request']) + mb_strlen($lastQuestions['response_input']) + mb_strlen($message) < 4000) {
                    $question[] = [
                        'role' => 'user',
                        'content' => $lastQuestions['text_request']
                    ];
                    $question[] = [
                        'role' => 'assistant',
                        'content' => $lastQuestions['response_input']
                    ];
                }
                $text_request = $message;
            } else {
                $text_request = str_replace('[TARGETLANGGE]', $lang, $prompt['prompt']);
                $text_request = str_replace('[PROMPT]', $message, $text_request);
            }
            $question[] = [
                'role' => 'user',
                'content' => $text_request
            ];

            $stream = (isset($apiSetting['outtype']) && $apiSetting['outtype'] == 'stream') ? true : false;
            $result = $ChatGPT->sendText35($question, $stream);
        } else {

        }

        if ($result['errno'] > 0) {
            return successJson([$result['message'] . '（本条消息不扣费）']);
        }

        // 解析回答
        $totalTokens = intval($result['data']['total_tokens']);
        $respText = explode("\n", $result['data']['text']);
        if (count($respText) > 1) {
            unset($respText[0]);
        }
        if (empty($respText)) {
            $respText = '对不起，我不知道该怎么回答。';
        }
        $respText = implode("\n", $respText);

        $clearRespText = $respText;
        // 小程序文本内容安全识别
        $pass = $this->msgSecCheck($respText);
        if (!$pass) {
            $clearRespText = '内容包含敏感信息，不予展示。';
        }
        // 自定义敏感词替换
        $Filter = new \FoxFilter\words('*');
        $clearRespText = $Filter->filter($clearRespText);

        // 将回答存入数据库
        Db::name('msg_write')
            ->insert([
                'site_id' => self::$site_id,
                'user_id' => self::$user['id'],
                'openid' => self::$user['openid'],
                'topic_id' => $prompt['topic_id'],
                'activity_id' => $prompt['activity_id'],
                'prompt_id' => $prompt_id,
                'message' => $clearMessage,
                'message_input' => $message,
                'response' => $clearRespText,
                'response_input' => $respText,
                'text_request' => $text_request,
                'total_tokens' => $totalTokens,
                'create_time' => time()
            ]);

        // 扣费，判断是不是vip
        if ($user['vip_expire_time'] < $now) {
            changeUserBalance(self::$user['id'], -1, '提问问题消费');
        }

        // 模型使用量+1
        Db::name('write_prompts')
            ->where('id', $prompt_id)
            ->inc('usages', 1)
            ->update();

        // 将回复简单格式化之后返回前端
        $clearRespText = formatMsg($clearRespText);

        return successJson($clearRespText);
    }

    private function msgSecCheck($content)
    {
        $setting = getSystemSetting(self::$site_id, 'wxapp');
        $Wxapp = new \Weixin\Wxapp($setting['appid'], $setting['appsecret']);
        return $Wxapp->msgSecCheck(self::$user['openid'], $content);
    }


    /**
     * 获取消息历史记录
     */
    public function getHistoryMsg()
    {
        $prompt_id = input('prompt_id', 0, 'intval');
        $list = Db::name('msg_write')
            ->where([
                ['user_id', '=', self::$user['id']],
                ['prompt_id', '=', $prompt_id],
                ['is_delete', '=', 0]
            ])
            ->field('message,response')
            ->order('id desc')
            ->limit(10)
            ->select()->each(function ($item) {
                $item['message'] = formatMsg($item['message']);
                $item['response'] = formatMsg($item['response']);
                return $item;
            })->toArray();
        $msgList = [];
        $list = array_reverse($list);
        foreach ($list as $v) {
            $msgList[] = [
                'user' => '我',
                'message' => $v['message']
            ];
            $msgList[] = [
                'user' => 'AI',
                'message' => $v['response']
            ];
        }
        if (empty($msgList)) {
            $prompt = Db::name('write_prompts')
                ->where([
                    ['id', '=', $prompt_id],
                    ['is_delete', '=', 0]
                ])
                ->field('hint,welcome')
                ->find();
            if ($prompt) {
                if (!empty($prompt['welcome'])) {
                    $msgList[] = [
                        'user' => 'AI',
                        'message' => formatMsg($prompt['welcome'])
                    ];
                } else {
                    $msgList[] = [
                        'user' => 'AI',
                        'message' => formatMsg('提示：' . $prompt['hint'])
                    ];
                }
            }
        }

        return successJson($msgList);
    }


}
