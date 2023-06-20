<?php

namespace app\admin\controller;

use think\facade\Db;

class Setting extends Base
{
    public function getSetting()
    {
        $name = input('name', 'system', 'trim');

        $setting = getSystemSetting(self::$site_id, $name);

        if (!$setting || count($setting) == 0) {
            if ($name == 'system') {
                $setting = [
                    'system_title' => '',
                    'system_logo' => '',
                    'system_icp' => ''
                ];
            }
            if ($name == 'wxapp') {
                $setting = [
                    'title' => '',
                    'qrcode' => '',
                    'appid' => '',
                    'appsecret' => '',
                    'page_title' => '',
                    'welcome' => '',
                    'share_title' => '',
                    'share_image' => '',
                    'is_check' => 0,
                    'is_ios_pay' => 1
                ];
            }
            if ($name == 'wxapp_upload') {
                $setting = [
                    'upload_secret' => '',
                    'host' => ''
                ];
            }
            if ($name == 'wxapp_index') {
                $setting = [
                    'type' => 'chat',
                    'content' => ''
                ];
            }
            if ($name == 'chatgpt') {
                $setting = [
                    'apikey' => '',
                    'temperature' => 1,
                    'max_tokens' => 1000,
                    'model' => 'gpt-3.5-turbo',
                    'free_num' => 0
                ];
            }
            if ($name == 'pay') {
                $setting = [
                    'mch_id' => '',
                    'key' => '',
                    'apiclient_cert' => '',
                    'apiclient_key' => ''
                ];
            }
            if ($name == 'ad') {
                $setting = [
                    'inter_is_open' => 0,
                    'inter_unit_id' => '',
                    'banner_is_open' => 0,
                    'banner_unit_id' => ''
                ];
            }
            if ($name == 'reward_share') {
                $setting = [
                    'is_open' => 0,
                    'num' => '',
                    'max' => ''
                ];
            }
            if ($name == 'reward_invite') {
                $setting = [
                    'is_open' => 0,
                    'num' => '',
                    'max' => ''
                ];
            }
            if ($name == 'reward_ad') {
                $setting = [
                    'is_open' => 0,
                    'num' => '',
                    'max' => '',
                    'ad_unit_id' => ''
                ];
            }
            if ($name == 'about') {
                $setting = [
                    'content' => ''
                ];
            }
            if ($name == 'kefu') {
                $setting = [
                    'phone' => '',
                    'email' => '',
                    'wxno' => '',
                    'wx_qrcode' => '',
                    'qun_qrcode' => ''
                ];
            }
            if ($name == 'commission') {
                $setting = [
                    'is_open' => 0,
                    'auto_audit' => 0,
                    'deep' => 2,
                    'bili_1' => 0,
                    'bili_2' => 0
                ];
            }
            if ($name == 'web') {
                $setting = [
                    'is_open' => 0,
                    'bind_wxapp_user' => 0,
                    'page_title' => '',
                    'copyright' => '',
                    'copyright_link' => ''
                ];
            }
            if ($name == 'h5') {
                $setting = [
                    'page_title' => '',
                    'share_title' => '',
                    'share_desc' => '',
                    'share_image' => ''
                ];
            }
            if ($name == 'wxmp') {
                $setting = [
                    'title' => '',
                    'appid' => '',
                    'appsecret' => '',
                    'token' => getNonceStr(32),
                    'aes_key' => getNonceStr(43),
                    'server_url' => 'https://' . $_SERVER['HTTP_HOST'] . '/web.php/wxmp/server/site/' . self::$site_id
                ];
            }
        }

        return successJson($setting);
    }

    public function setSetting()
    {
        $name = input('name', '', 'trim');
        $data = input('data', '', 'trim');
        $res = setSystemSetting(self::$site_id, $name, $data);
        if ($res) {
            return successJson('', '保存成功');
        } else {
            return errorJson('保存失败，请重试！');
        }
    }

    /**
     * 获取openai引擎
     */
    public function getEngines()
    {
        $list = Db::name('engine')
            ->where('ready', 1)
            ->field('title,name')
            ->select()->toArray();

        return successJson($list);
    }

    /**
     * 获取openai余额
     */
    public function getBalance()
    {
        $apikey = input('apikey', '', 'trim');
        if (empty($apikey)) {
            return errorJson('请输入apikey');
        }
        try {
            $ChatGPT = new \ChatGPT\sdk($apikey);
            // 使用自定义接口
            $apiSetting = getSystemSetting(0, 'api');
            if ($apiSetting['channel'] == 'diy' && $apiSetting['host']) {
                $ChatGPT->setChannel($apiSetting['channel']);
                $ChatGPT->setDiyHost(rtrim($apiSetting['host'], '/') . '/api.php');
                $ChatGPT->setDiyKey($apiSetting['key']);
            } elseif ($apiSetting['channel'] == 'agent' && $apiSetting['agent_host']) {
                $ChatGPT->setChannel($apiSetting['channel']);
                $ChatGPT->setDiyHost(rtrim($apiSetting['agent_host'], '/'));
            }

            $result = $ChatGPT->getBalance();
            if (!isset($result['total_granted'])) {
                return errorJson('查询余额失败');
            }
            return successJson([
                'total_granted' => $result['total_granted'],
                'total_used' => $result['total_used'],
                'total_available' => $result['total_available']
            ]);
        } catch (\Exception $e) {
            return errorJson('查询余额失败');
        }
    }
}
