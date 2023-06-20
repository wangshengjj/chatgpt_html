<?php

namespace app\api\controller;

use think\facade\Db;
use think\worker\Server;

class Worker extends Server
{
    protected $socket = 'websocket://0.0.0.0:2346';
    protected $user;
    protected $site_id;


    public function onConnect($connection)
    {
        echo "Connected，ID：" . $connection->id . "\n";
    }

    public function onMessage($connection, $data)
    {
        $data = @json_decode($data, true);
        if (!is_array($data)) {
            return '';
        }
        if ($data['type'] == 'heart') {
            $connection->send('data: [HEART]');
            return '';
        }

        $site_id = $data['site_id'] ?? 1;
        $token = $data['token'] ?? '';
        $user = Db::name('user')
            ->where([
                ['site_id', '=', $site_id],
                ['token', '=', $token]
            ])
            ->find();
        if (!$user) {
            $connection->send('data: [NOLOGIN]');
            return '';
        }

        $now = time();
        if (intval($user['balance']) <= 0 && $user['vip_expire_time'] < $now) {
            $connection->send('提问次数用完了，请充值！（本条消息不扣费）');
            $connection->send('data: [DONE]');
            return '';
        }

        if($data['type'] == 'chat') {
            $this->chat($connection, $data, $user);
        } elseif ($data['type'] == 'write') {
            $this->write($connection, $data, $user);
        }
    }

    // 普通聊天
    private function chat($connection, $param, $user)
    {
        $now = time();
        $setting = getSystemSetting($user['site_id'], 'chatgpt');
        $apiSetting = getSystemSetting(0, 'api');
        if ($apiSetting['channel'] == 'diy' && $apiSetting['host']) {
            $apiUrl = rtrim($apiSetting['host'], '/') . '/stream.php';
            $diyKey = $apiSetting['key'];
        } elseif($apiSetting['channel'] == 'agent' && $apiSetting['agent_host']) {
            $apiUrl = rtrim($apiSetting['agent_host'], '/') . '/v1/chat/completions';
        } else {
            $apiUrl = 'https://api.openai.com/v1/chat/completions';
        }
        $temperature = floatval($setting['temperature']) ?? 0;
        $max_tokens = intval($setting['max_tokens']) ?? 0;
        $apiKey = $setting['apikey'] ?? '';
        $model = $setting['model'] ?? '';


        $message = $param['message'];
        $clearMessage = wordFilter($message);
        // 返回的文字
        $response = '';

        $callback = function ($ch, $data) use ($connection, $message, $clearMessage, $user) {
            global $response;
            $complete = @json_decode($data);
            if (isset($complete->error)) {
                if (strpos($complete->error->message, "Rate limit reached") === 0) {
                    $connection->send('访问频率超限');
                }
                elseif (strpos($complete->error->message, "Your access was terminated") === 0) {
                    $connection->send('违规使用，被封禁');
                }
                elseif (strpos($complete->error->message, "You exceeded your current quota") === 0) {
                    $connection->send('接口余额不足');
                }
                elseif (strpos($complete->error->message, "That model is currently overloaded") === 0) {
                    $connection->send('AI服务器繁忙');
                }
                else {
                    $connection->send($complete->error->message);
                    $connection->send('data: [DONE]');
                }
            } else {
                $word = $this->parseData($data);

                if ($word == 'data: [DONE]' || $word == 'data: [CONTINUE]') {
                    if (!empty($response)) {
                        // 将问题存入数据库
                        Db::name('msg')
                            ->insert([
                                'site_id' => $user['site_id'],
                                'user_id' => $user['id'],
                                'openid' => $user['openid'],
                                'user' => '我',
                                'message' => $clearMessage,
                                'message_input' => $message,
                                'create_time' => time()
                            ]);

                        // 将回答存入数据库
                        Db::name('msg')
                            ->insert([
                                'site_id' => $user['site_id'],
                                'user_id' => $user['id'],
                                'openid' => $user['openid'],
                                'user' => 'AI',
                                'message' => $response,
                                'message_input' => $response,
                                'total_tokens' => mb_strlen($clearMessage) + mb_strlen($response),
                                'create_time' => time()
                            ]);

                        // 扣费，判断是不是vip
                        if ($user['vip_expire_time'] < time()) {
                            changeUserBalance($user['id'], -1, '提问问题消费');
                        }

                        $response = '';
                    }

                    $connection->send($word);
                } else {
                    $response .= $word;
                    $connection->send($word);
                }

            }
            return strlen($data);
        };

        $question = [];
        // 连续对话需要带着上一个问题请求接口
        $lastQuestions = Db::name('msg')
            ->where([
                ['user_id', '=', $user['id']],
                ['create_time', '>', ($now - 300)]
            ])
            ->order('id desc')
            ->limit(2)
            ->select()->toArray();
        if (count($lastQuestions) == 2) {
            $lastQuestions = array_reverse($lastQuestions);
            // 如果超长，就不关联上下文了
            if (mb_strlen($lastQuestions[0]['message']) + mb_strlen($lastQuestions[1]['message']) + mb_strlen($message) < 4000) {
                $question[] = [
                    'role' => 'user',
                    'content' => $lastQuestions[0]['message']
                ];
                $question[] = [
                    'role' => 'assistant',
                    'content' => $lastQuestions[1]['message']
                ];
            }

        }
        $question[] = [
            'role' => 'user',
            'content' => $clearMessage
        ];

        $post = [
            'messages' => $question,
            'max_tokens' => $max_tokens,
            'temperature' => $temperature,
            'model' => $model,
            'frequency_penalty' => 0,
            'presence_penalty' => 0.6,
            'stream' => true
        ];
        if ($apiSetting['channel'] == 'diy' && $apiSetting['host']) {
            $post['apiKey'] = $apiKey;
            $post['diyKey'] = $diyKey;
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
    }

    // 创作
    private function write($connection, $param, $user)
    {
        $now = time();
        $setting = getSystemSetting($user['site_id'], 'chatgpt');
        $apiSetting = getSystemSetting(0, 'api');
        if ($apiSetting['channel'] == 'diy' && $apiSetting['host']) {
            $apiUrl = rtrim($apiSetting['host'], '/') . '/stream.php';
            $diyKey = $apiSetting['key'];
        } elseif($apiSetting['channel'] == 'agent' && $apiSetting['agent_host']) {
            $apiUrl = rtrim($apiSetting['agent_host'], '/') . '/v1/chat/completions';
        } else {
            $apiUrl = 'https://api.openai.com/v1/chat/completions';
        }
        $temperature = floatval($setting['temperature']) ?? 0;
        $max_tokens = intval($setting['max_tokens']) ?? 0;
        $apiKey = $setting['apikey'] ?? '';
        $model = $setting['model'] ?? '';


        $message = $param['message'];
        $clearMessage = wordFilter($message);
        $prompt_id = $param['prompt_id'];
        $lang = $param['lang'];
        $prompt = Db::name('write_prompts')
            ->where([
                ['id', '=', $prompt_id],
                ['is_delete', '=', 0]
            ])
            ->find();
        if (!$prompt) {
            $connection->send('模板不存在或已被删除');
            $connection->send('data: [DONE]');
        }

        // 返回的文字
        $response = '';

        $callback = function ($ch, $data) use ($connection, $message, $clearMessage, $user, $prompt) {
            global $response;
            $complete = @json_decode($data);
            if (isset($complete->error)) {
                if (strpos($complete->error->message, "Rate limit reached") === 0) {
                    $connection->send('访问频率超限');
                }
                elseif (strpos($complete->error->message, "Your access was terminated") === 0) {
                    $connection->send('违规使用，被封禁');
                }
                elseif (strpos($complete->error->message, "You exceeded your current quota") === 0) {
                    $connection->send('接口余额不足');
                }
                elseif (strpos($complete->error->message, "That model is currently overloaded") === 0) {
                    $connection->send('AI服务器繁忙');
                }
                else {
                    $connection->send($complete->error->message);
                }
                $connection->send('data: [DONE]');
            } else {
                $word = $this->parseData($data);

                if ($word == 'data: [DONE]' || $word == 'data: [CONTINUE]') {
                    if (!empty($response)) {
                        // 存入数据库
                        Db::name('msg_write')
                            ->insert([
                                'site_id' => $user['site_id'],
                                'user_id' => $user['id'],
                                'openid' => $user['openid'],
                                'topic_id' => $prompt['topic_id'],
                                'activity_id' => $prompt['activity_id'],
                                'prompt_id' => $prompt['id'],
                                'message' => $clearMessage,
                                'message_input' => $message,
                                'response' => $response,
                                'response_input' => $response,
                                'text_request' => $response,
                                'total_tokens' => mb_strlen($clearMessage) + mb_strlen($response),
                                'create_time' => time()
                            ]);

                        // 扣费，判断是不是vip
                        if ($user['vip_expire_time'] < time()) {
                            changeUserBalance($user['id'], -1, '提问问题消费');
                        }

                        // 模型使用量+1
                        Db::name('write_prompts')
                            ->where('id', $prompt['id'])
                            ->inc('usages', 1)
                            ->update();

                        $response = '';
                    }

                    $connection->send($word);
                } else {
                    $response .= $word;
                    $connection->send($word);
                }

            }
            return strlen($data);
        };

        $question = [];
        if ($message == '继续' || $message == 'go on') {
            $lastQuestions = Db::name('msg_write')
                ->where([
                    ['user_id', '=', $user['id']]
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

        $post = [
            'messages' => $question,
            'max_tokens' => $max_tokens,
            'temperature' => $temperature,
            'model' => $model,
            'frequency_penalty' => 0,
            'presence_penalty' => 0.6,
            'stream' => true
        ];
        if ($apiSetting['channel'] == 'diy' && $apiSetting['host']) {
            $post['apiKey'] = $apiKey;
            $post['diyKey'] = $diyKey;

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
    }

    private function parseData($data)
    {
        $data = str_replace('data: {', '{', $data);
        $data = rtrim($data, "\n\n");

        if(strpos($data, "}\n\n{") !== false) {
            $arr = explode("}\n\n{", $data);
            $data = '{' . $arr[1];
        }

        file_put_contents('./worker.txt', $data, 8);

        if (strpos($data, 'data: [DONE]') !== false) {
            return 'data: [DONE]';
        } else {
            $data = @json_decode($data, true);
            if (!is_array($data)) {
                return '';
            }
            if ($data['choices']['0']['finish_reason'] == 'stop') {
                return 'data: [DONE]';
            }
            elseif($data['choices']['0']['finish_reason'] == 'length') {
                return 'data: [CONTINUE]';
            }

            return $data['choices']['0']['delta']['content'] ?? '';
        }

    }

    public function onClose($connection)
    {
        echo "closed，ID：" . $connection->id . "\n";
    }

    public function onError($connection, $code, $msg)
    {
        echo 'onError' . "\n";
        $connection->close();
    }
}
