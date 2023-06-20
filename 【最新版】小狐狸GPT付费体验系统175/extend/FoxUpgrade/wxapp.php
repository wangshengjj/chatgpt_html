<?php

namespace FoxUpgrade;

class wxapp
{
    private $ApiHost = 'https://console.ttk.ink/api.php/wxapp';
    private $Product = 'fox_chatgpt';
    private $WxappName = '';

    public function __construct($wxappName = '')
    {
        $this->WxappName = $wxappName;
    }

    /**
     * 检查小程序代码是否有更新
     */
    public function checkUpdate($upload_time)
    {
        return $this->curl_post($this->ApiHost . '/checkUpdate', [
            'product' => $this->Product,
            'wxapp_name' => $this->WxappName,
            'upload_time' => $upload_time
        ]);
    }

    /**
     * 上传小程序代码
     */
    public function uploadCode($site_id, $appid, $upload_secret, $host = '')
    {
        if (!$host) {
            $host = $_SERVER['HTTP_HOST'];
        }
        return $this->curl_post($this->ApiHost . '/uploadCode', [
            'product' => $this->Product,
            'wxapp_name' => $this->WxappName,
            'appid' => $appid,
            'upload_secret' => $upload_secret,
            'host' => $host,
            'site_id' => $site_id
        ]);
    }

    private function curl_post($url, $data = [])
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_TIMEOUT, 60);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        if (!empty($data)) {
            $url .= '?r=' . rand(100000, 999999);
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }

        if (strpos($url, 'https://') !== false) {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        }

        $result = curl_exec($curl);

        if (curl_errno($curl)) {
            return [
                'errno' => 1,
                'message' => '网络错误'
            ];
        }
        curl_close($curl);

        return @json_decode($result, true);
    }
}