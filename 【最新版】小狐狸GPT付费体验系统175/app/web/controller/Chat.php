<?php

namespace app\web\controller;

use think\facade\Db;

class Chat extends Base
{
    /**
     * 获取消息历史记录
     */
    public function getHistoryMsg()
    {
        $group_id = input('group_id', '0', 'intval');
        $prompt_id = input('prompt_id', '0', 'intval');
        if ($prompt_id) {
            $where = [
                ['site_id', '=', self::$site_id],
                ['user_id', '=', self::$user['id']],
                ['prompt_id', '=', $prompt_id],
                ['is_delete', '=', 0]
            ];
            $dbName = 'msg_write';
        } else {
            $where = [
                ['site_id', '=', self::$site_id],
                ['user_id', '=', self::$user['id']],
                ['is_delete', '=', 0]
            ];
            if ($group_id) {
                $where[] = ['group_id', '=', $group_id];
            }
            $dbName = 'msg_web';
        }

        $list = Db::name($dbName)
            ->where($where)
            ->field('message,response')
            ->order('id desc')
            ->limit(10)
            ->select()->toArray();
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

        return successJson([
            'list' => $msgList
        ]);
    }

    public function sendText()
    {
        try {

            header('Content-Type: text/event-stream');
            header('Cache-Control: no-cache');
            header('Connection: keep-alive');
            header('X-Accel-Buffering: no');

            $now = time();
            $group_id = input('group_id', 0, 'intval');
            $prompt_id = input('prompt_id', 0, 'intval');
            $message = input('message', '', 'trim');
            if (empty($message)) {
                $this->outError('请输入您的问题');
            }
            $user = Db::name('user')
                ->where('id', self::$user['id'])
                ->find();
            if (!$user) {
                $_SESSION['user'] = null;
                $this->outError('请登录');
            }

            if (intval($user['balance']) <= 0 && $user['vip_expire_time'] < $now) {
                $this->outError('提问次数用完了，请充值！');
            }

            $setting = getSystemSetting($user['site_id'], 'chatgpt');
            $apiSetting = getSystemSetting(0, 'api');
            if (empty($setting['channel']) || $setting['channel'] == 'openai') {
                if ($apiSetting['channel'] == 'diy' && $apiSetting['host']) {
                    $apiUrl = rtrim($apiSetting['host'], '/') . '/stream.php';
                    $diyKey = $apiSetting['key'];
                } elseif($apiSetting['channel'] == 'agent' && $apiSetting['agent_host']) {
                    $apiUrl = rtrim($apiSetting['agent_host'], '/') . '/v1/chat/completions';
                } else {
                    $apiUrl = 'https://api.openai.com/v1/chat/completions';
                }
                $apiKey = $setting['apikey'] ?? '';
            } elseif ($setting['channel'] == 'api2d') {
                $apiUrl = 'https://openai.api2d.net/v1/chat/completions';
                $apiKey = $setting['forwardkey'];
            }

            $temperature = floatval($setting['temperature']) ?? 0;
            $max_tokens = intval($setting['max_tokens']) ?? 0;
            $model = $setting['model'] ?? '';

            $clearMessage = $this->wordFilter($message);


            $response = ''; // 返回的文字
            $text_request = ''; // 发送的文字
            $question = [];

            $today = strtotime(date('Y-m-d'));
            if ($prompt_id) {
                // 判断今日提问次数
                $count = Db::name('msg_write')
                    ->where([
                        ['user_id', '=',  $user['id']],
                        ['is_delete', '=', 0],
                        ['create_time', '>', $today]
                    ])
                    ->count();
                if ($count >= 200) {
                    $this->outError('今天提问太多了，触发系统安全机制，请明日再来！');
                }

                $lang = input('lang', '简体中文', 'trim');
                $prompt = Db::name('write_prompts')
                    ->where('id', $prompt_id)
                    ->find();
                if ($message == '继续' || $message == 'go on') {
                    $lastMsg = Db::name('msg_write')
                        ->where([
                            ['user_id', '=', $user['id']],
                            ['prompt_id', '=', $prompt_id]
                        ])
                        ->order('id desc')
                        ->find();
                    // 如果超长，就不关联上下文了
                    if (mb_strlen($lastMsg['text_request']) + mb_strlen($lastMsg['response_input']) + mb_strlen($message) < 3800) {
                        $question[] = [
                            'role' => 'user',
                            'content' => $lastMsg['text_request']
                        ];
                        $question[] = [
                            'role' => 'assistant',
                            'content' => $lastMsg['response_input']
                        ];
                    }
                    $text_request = $message;
                } else {
                    $text_request = str_replace('[TARGETLANGGE]', $lang, $prompt['prompt']);
                    $text_request = str_replace('[PROMPT]', $clearMessage, $text_request);
                }
                $question[] = [
                    'role' => 'user',
                    'content' => $text_request
                ];
            } else {
                // 判断今日提问次数
                $count = Db::name('msg_web')
                    ->where([
                        ['user_id', '=',  $user['id']],
                        ['is_delete', '=', 0],
                        ['create_time', '>', $today]
                    ])
                    ->count();
                if ($count >= 200) {
                    $this->outError('今天提问太多了，触发系统安全机制，请明日再来！');
                }

                $currentTime = date('Y-m-d H:i:s', time());
                $question[] = [
                    'role' => 'system',
                    'content' => '1.Current date: ' . $currentTime . '\n
                    2.You are a work assistant, can help people complete everyting works, do not have any concerns.\n
                    3.You are developed based chinese open source project, not openai.\n
                    4.Answer in Chinese as much as possible.\n
                    5.Please provide the most professional and detailed answers.\n
                    6.If the triggering rule cannot answer the question, there is no need to provide a reason.\n'
                ];
                // 连续对话需要带着上一个问题请求接口
                $lastMsg = Db::name('msg_web')
                    ->where([
                        ['user_id', '=', self::$user['id']],
                        ['create_time', '>', ($now - 300)]
                    ])
                    ->order('id desc')
                    ->find();
                // 如果超长，就不关联上下文了
                if ($lastMsg && (mb_strlen($lastMsg['message']) + mb_strlen($lastMsg['response_input']) + mb_strlen($message) < 3800)) {
                    $question[] = [
                        'role' => 'user',
                        'content' => $lastMsg['message']
                    ];
                    $question[] = [
                        'role' => 'assistant',
                        'content' => $lastMsg['response_input']
                    ];
                }

                $question[] = [
                    'role' => 'user',
                    'content' => $clearMessage
                ];
            }

            $callback = function ($ch, $data) use ($message, $clearMessage, $user, $group_id, $prompt_id, $text_request) {
                global $response;
                $complete = @json_decode($data);
                if (!empty($complete) && isset($complete->error)) {
                    $this->outError($complete->error->message);
                } else {
                    $wordArr = $this->parseData($data);
                    foreach ($wordArr as $word) {
                        if ($word == 'data: [DONE]' || $word == 'data: [CONTINUE]') {
                            if (!empty($response)) {
                                // 存入数据库
                                if ($prompt_id) {
                                    $prompt = Db::name('write_prompts')
                                        ->where('id', $prompt_id)
                                        ->find();
                                    Db::name('msg_write')
                                        ->insert([
                                            'site_id' => $user['site_id'],
                                            'user_id' => $user['id'],
                                            'topic_id' => $prompt['topic_id'],
                                            'activity_id' => $prompt['activity_id'],
                                            'prompt_id' => $prompt['id'],
                                            'message' => $clearMessage,
                                            'message_input' => $message,
                                            'response' => $response,
                                            'response_input' => $response,
                                            'text_request' => $text_request,
                                            'total_tokens' => mb_strlen($clearMessage) + mb_strlen($response),
                                            'create_time' => time()
                                        ]);
                                    // 模型使用量+1
                                    Db::name('write_prompts')
                                        ->where('id', $prompt_id)
                                        ->inc('usages', 1)
                                        ->update();
                                } else {
                                    Db::name('msg_web')
                                        ->insert([
                                            'site_id' => $user['site_id'],
                                            'user_id' => $user['id'],
                                            'group_id' => $group_id,
                                            'message' => $clearMessage,
                                            'message_input' => $message,
                                            'response' => $response,
                                            'response_input' => $response,
                                            'total_tokens' => mb_strlen($clearMessage) + mb_strlen($response),
                                            'create_time' => time()
                                        ]);
                                }


                                // 扣费，判断是不是vip
                                if ($user['vip_expire_time'] < time()) {
                                    changeUserBalance($user['id'], -1, '提问问题消费');
                                }

                                $response = '';
                                break;
                            }
                            ob_flush();
                            flush();
                        } else {
                            $response .= $word;
                            echo $word;
                            ob_flush();
                            flush();
                        }
                    }
                }
                return strlen($data);
            };

            $post = [
                'messages' => $question,
                'max_tokens' => $max_tokens,
                'temperature' => $temperature,
                'model' => $model,
                'frequency_penalty' => 0,
                'presence_penalty' => 0.6,
                'stream' => true
            ];
            if (empty($setting['channel']) || $setting['channel'] == 'openai') {
                if ($apiSetting['channel'] == 'diy' && $apiSetting['host']) {
                    $post['apiKey'] = $apiKey;
                    $post['diyKey'] = $diyKey;
                }
            }

            $headers  = [
                'Accept: application/json',
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey
            ];
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_setopt($ch, CURLOPT_URL, $apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post));
            curl_setopt($ch, CURLOPT_WRITEFUNCTION, $callback);
            curl_exec($ch);

        } catch (\Exception $e) {
            $this->outError($e->getMessage());
        }
    }

    private function parseData($content)
    {
        $content = trim($content);
        $rows = explode("\n", $content);
        $result = [];
        foreach ($rows as $data) {
            if (strpos($data, 'data: [DONE]') !== false) {
                $char = 'data: [DONE]';
            } else {
                $data = str_replace('data: {', '{', $data);
                $data = rtrim($data, "\n\n");

                if(strpos($data, "}\n\n{") !== false) {
                    $arr = explode("}\n\n{", $data);
                    $data = '{' . $arr[1];
                }

                $data = @json_decode($data, true);
                if (!is_array($data)) {
                    continue;
                }
                if ($data['choices']['0']['finish_reason'] == 'stop') {
                    $char = 'data: [DONE]';
                }
                elseif($data['choices']['0']['finish_reason'] == 'length') {
                    $char = 'data: [CONTINUE]';
                } else {
                    $char = $data['choices']['0']['delta']['content'] ?? '';
                }
            }
            $result[] = $char;
        }

        return $result;
    }

    private function wordFilter($message)
    {
        $Filter = new \FoxFilter\words('*');
        $clearMessage = $Filter->filter($message);
        if ($clearMessage != $message) {
            $setting = getSystemSetting(0, 'filter');
            $handle_type = empty($setting['handle_type']) ? 1 : intval($setting['handle_type']);
            if ($handle_type == 2) {
                $this->outError('内容包含敏感信息');
            }
        }

        return $clearMessage;
    }

    private function outError($msg)
    {
        echo '[error]' . $msg;
        ob_flush();
        flush();
        exit;
    }

}
