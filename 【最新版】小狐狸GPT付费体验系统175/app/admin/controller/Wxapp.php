<?php

namespace app\admin\controller;

use think\facade\Db;

class Wxapp extends Base
{
    /**
     * 检查小程序代码更新
     */
    public function checkUpdate()
    {
        $wxapp_name = input('wxapp_name', '', 'trim');

        try {
            $setting = getSystemSetting(self::$site_id, $wxapp_name . '_upload');
            if (!empty($setting['upload_time'])) {
                $upload_time = strtotime($setting['upload_time']);
            } else {
                $upload_time = 0;
            }
            $WxappSdk = new \FoxUpgrade\wxapp($wxapp_name);
            $result = $WxappSdk->checkUpdate($upload_time);

            if (is_array($result) && isset($result['errno'])) {
                if ($result['errno'] > 0) {
                    return errorJson($result['message']);
                } else {
                    return successJson($result['data']);
                }
            } else {
                return errorJson('检查更新失败');
            }

        } catch (\Exception $e) {
            return errorJson('出现错误：' . $e->getMessage());
        }
    }

    /**
     * 上传小程序代码
     */
    public function uploadCode()
    {
        $wxapp_name = input('wxapp_name', '', 'trim');
        $upload_secret = input('upload_secret', '', 'trim');
        $host = input('host', '', 'trim');
        $host = str_replace('https://', '', $host);
        $host = str_replace('http://', '', $host);
        $host = explode('/', $host)[0];

        try {
            $wxapp = getSystemSetting(self::$site_id, $wxapp_name);
            if (empty($wxapp['appid'])) {
                return errorJson('请先配置小程序参数');
            }
            $appid = $wxapp['appid'];

            $setting = getSystemSetting(self::$site_id,$wxapp_name . '_upload');
            $setting['appid'] = $appid;
            $setting['upload_secret'] = $upload_secret;
            $setting['host'] = $host;
            setSystemSetting(self::$site_id,$wxapp_name . '_upload', json_encode($setting));
            $WxappSdk = new \FoxUpgrade\wxapp($wxapp_name);
            $result = $WxappSdk->uploadCode(self::$site_id, $appid, $upload_secret, $host);
            if (is_array($result) && isset($result['errno'])) {
                if ($result['errno'] > 0) {
                    return errorJson($result['message']);
                } else {
                    $setting['upload_time'] = date('Y-m-d H:i:s', time());
                    setSystemSetting(self::$site_id,$wxapp_name . '_upload', json_encode($setting));
                    return successJson('', $result['message']);
                }
            } else {
                return errorJson('上传失败：网络错误，请稍后重试！');
            }

        } catch (\Exception $e) {
            return errorJson('出现错误：' . $e->getMessage());
        }
    }

}
