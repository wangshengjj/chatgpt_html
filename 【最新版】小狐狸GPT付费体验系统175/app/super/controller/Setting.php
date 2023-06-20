<?php

namespace app\super\controller;

use think\facade\Db;

class Setting extends Base
{
    public function getSetting()
    {
        $name = input('name', 'system', 'trim');

        $setting = getSystemSetting(0, $name);

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
                    'share_title' => '',
                    'share_image' => ''
                ];
            }
            if ($name == 'wxapp_shop_upload') {
                $setting = [
                    'upload_secret' => '',
                    'host' => ''
                ];
            }
            if ($name == 'chatgpt') {
                $setting = [
                    'apikey' => '',
                    'temperature' => 0.9,
                    'max_tokens' => 150,
                    'model' => 'text-davinci-003',
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
            if ($name == 'filter') {
                $setting = [
                    'handle_type' => 1
                ];
            }
            if ($name == 'api') {
                $setting = [
                    'outstream' => 'nostream',
                    'channel' => 'gpt',
                    'host' => '',
                    'key' => '',
                    'agent_host' => ''
                ];
            }
        }

        return successJson($setting);
    }

    public function setSetting()
    {
        $name = input('name', '', 'trim');
        $data = input('data', '', 'trim');
        $res = setSystemSetting(0, $name, $data);
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
}
