<?php

namespace ChatGPT;

class sdk
{
    protected static $model = 'gpt-3.5-turbo';
    protected static $apiKey = '';
    protected static $temperature = 1;
    protected static $max_tokens = 1000;
    protected static $channel = 'gpt';
    protected static $diyHost = '';
    protected static $diyKey = '';

    /**
     * sdk constructor.
     * @param string $apiKey
     * @param string $model
     * @param string $temperature
     * @param string $max_tokens
     */
    public function __construct($apiKey = '', $model = '', $temperature = '', $max_tokens = '')
    {
        if ($model) {
            self::$model = $model;
        }
        if ($temperature) {
            self::$temperature = $temperature;
        }
        if ($max_tokens) {
            self::$max_tokens = $max_tokens;
        }
        self::$apiKey = $apiKey;
    }

    public function setChannel($channel = '')
    {
        self::$channel = $channel;
    }

    public function setDiyHost($host = '')
    {
        self::$diyHost = $host;
    }

    public function setDiyKey($key = '')
    {
        self::$diyKey = $key;
    }

    /**
     * @param string $message
     * @return array
     * GPT3模型
     */
    public function sendText30($message = '')
    {
        if (self::$channel == 'diy') {
            return $this->requestDiyApi('sendText30', $message);
        } elseif (self::$channel == 'agent') {
            $url = self::$diyHost . '/v1/completions';
        } else {
            $url = 'https://api.openai.com/v1/completions';
        }

        $post = [
            'prompt' => $message,
            'max_tokens' => self::$max_tokens,
            'temperature' => self::$temperature,
            'model' => self::$model,
            'frequency_penalty' => 0,
            'presence_penalty' => 0.6,
            'stop' => [" Human:", " AI:"]
        ];
        $result = $this->httpRequest($url, json_encode($post));
        if (isset($result['error'])) {
            return [
                'errno' => 1,
                'message' => $result['error']['message']
            ];
        } else {
            return [
                'errno' => 0,
                'data' => [
                    'text' => $result['choices']['0']['text'],
                    'total_tokens' => $result['usage']['total_tokens']
                ]
            ];
        }
    }

    /**
     * @param string $message
     * @return array
     * GPT3.5模型
     */
    public function sendText35($message = [], $stream = false)
    {
        if (self::$channel == 'diy') {
            return $this->requestDiyApi('sendText35', $message);
        } elseif (self::$channel == 'agent') {
            $url = self::$diyHost . '/v1/chat/completions';
        } else {
            $url = 'https://api.openai.com/v1/chat/completions';
        }

        $post = [
            'messages' => $message,
            'max_tokens' => self::$max_tokens,
            'temperature' => self::$temperature,
            'model' => self::$model,
            'frequency_penalty' => 0,
            'presence_penalty' => 0.6,
            'stream' => $stream
        ];
        $result = $this->httpRequest($url, json_encode($post));
        if (isset($result['error'])) {
            return [
                'errno' => 1,
                'message' => $result['error']['message']
            ];
        } else {
            return [
                'errno' => 0,
                'data' => [
                    'text' => $result['choices']['0']['message']['content'],
                    'total_tokens' => $result['usage']['total_tokens']
                ]
            ];
        }
    }


    public function getModelList()
    {
        $url = 'https://api.openai.com/v1/models';
        return $this->httpRequest($url);
    }

    /**
     * @return array|mixed
     * 查询账户余额
     */
    public function getBalance()
    {
        $now = time();
        $startDate = date('Y-m-01', $now);
        $endDate = date('Y-m-d', $now);

        if (self::$channel == 'diy') {
            return $this->requestDiyApi('getBalance');
        } elseif (self::$channel == 'agent') {
            $usageUrl = self::$diyHost . '/v1/dashboard/billing/usage?start_date=' . $startDate . '&end_date=' . $endDate;
            $subUrl = self::$diyHost . '/v1/dashboard/billing/subscription';
        } else {
            // $url = 'https://api.openai.com/dashboard/billing/credit_grants';
            $usageUrl = 'https://api.openai.com/v1/dashboard/billing/usage?start_date=' . $startDate . '&end_date=' . $endDate;
            $subUrl = 'https://api.openai.com/v1/dashboard/billing/subscription';
        }

        $result= $this->httpRequest($usageUrl);
        $total_used = round($result['total_usage'] / 100, 3);

        $result = $this->httpRequest($subUrl);
        $total_granted = round($result['hard_limit_usd'], 3);
        $total_available = round(($total_granted * 1000 - $total_used * 1000) / 1000, 3);

        return [
            'total_granted' => $total_granted,
            'total_used' => $total_used,
            'total_available' => $total_available
        ];
    }

    /**
     * @param $api
     * @param $message
     * @return array|mixed
     * 请求自定义接口
     */
    private function requestDiyApi($api, $message = null)
    {
        $post = [
            'diyKey' => self::$diyKey,
            'apiKey' => self::$apiKey,
            'api' => $api,
            'model' => self::$model,
            'temperature' => self::$temperature,
            'max_tokens' => self::$max_tokens
        ];
        if (!empty($message)) {
            $post['message'] = $message;
        }
        return $this->httpRequest(self::$diyHost, base64_encode(json_encode($post)));
    }

    /**
     * http请求
     */
    protected function httpRequest($url, $post = '')
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . self::$apiKey
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        if ($post) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        }
        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            return [
                'errno' => 1,
                'message' => 'curl 错误信息: ' . curl_error($ch)
            ];
        }
        curl_close($ch);
        return json_decode($result, true);
    }
}
