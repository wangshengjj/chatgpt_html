<?php

namespace app\web\controller;

use think\facade\Db;

class Wxmp
{
    private static $site_id = 1;
    public function server()
    {
        self::$site_id = input('site', 1, 'intval');
        $wxmpSetting = getSystemSetting(self::$site_id, 'wxmp');
        $config = [
            'app_id' => $wxmpSetting['appid'] ?? '',
            'secret' => $wxmpSetting['appsecret'] ?? '',
            'token' => $wxmpSetting['token'] ?? '',
            'aes_key' => $wxmpSetting['aes_key'] ?? '',
            'response_type' => 'array'
        ];

        $app = \EasyWeChat\Factory::officialAccount($config);
        $app->server->push(function ($message) use ($app) {
            file_put_contents('./xml.txt', json_encode($message) . "\n\n", 8);
            switch ($message['MsgType']) {
                case 'event':
                    return $this->handleEvent($app, $message);
                    break;
                case 'text':
                    return $this->handleText($app, $message);;
                    break;
                case 'image':
                    return '收到图片消息';
                    break;
                case 'voice':
                    return '收到语音消息';
                    break;
                case 'video':
                    return '收到视频消息';
                    break;
                case 'location':
                    return '收到坐标消息';
                    break;
                case 'link':
                    return '收到链接消息';
                    break;
                case 'file':
                    return '收到文件消息';
                    break;
            }
        });

        $response = $app->server->serve();
        ob_clean();
        $response->send();
    }

    private function handleEvent($app, $message)
    {
        if ($message['Event'] == 'subscribe' && !empty($message['EventKey'])) {
            $code = str_replace('qrscene_', '', $message['EventKey']);
        } else if ($message['Event'] == 'SCAN') {
            $code = $message['EventKey'];
        }
        $openid = $message['FromUserName'];
        if (empty($code) || empty($openid)) {
            return '';
        }

        // pc登录
        $user_id = Db::name('user')
            ->where([
                ['site_id', '=', self::$site_id],
                ['openid_mp', '=', $openid]
            ])
            ->value('id');
        if (!$user_id) {
            $user = $app->user->get($openid);
            $user_id = Db::name('user')
                ->insertGetId([
                    'site_id' => self::$site_id,
                    'openid_mp' => $openid,
                    'create_time' => time()
                ]);
            // 送免费条数
            $config = getSystemSetting(self::$site_id, 'chatgpt');
            $free_num = isset($config['free_num']) ? intval($config['free_num']) : 0;
            if ($free_num > 0) {
                changeUserBalance($user_id, $free_num, '新人免费赠送');
            }

        }
        Db::name('pclogin')
            ->insert([
                'site_id' => self::$site_id,
                'user_id' => $user_id,
                'openid' => $openid,
                'code' => $code,
                'create_time' => time()
            ]);

        return '恭喜您，登录成功！请回到网页继续使用！';
    }

    private function handleText($app, $message)
    {
        return '请在网页端使用对话服务！';
    }

}
