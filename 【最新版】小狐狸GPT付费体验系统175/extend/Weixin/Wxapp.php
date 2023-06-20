<?php

namespace Weixin;

class Wxapp
{
    protected static $appid;
    protected static $appsecret;

    public function __construct($appid = '', $appsecret = '')
    {
        self::$appid = $appid;
        self::$appsecret = $appsecret;
    }

    public function getOauthInfo($code = '')
    {

        $url = "https://api.weixin.qq.com/sns/jscode2session?appid=" . self::$appid . "&secret=" . self::$appsecret . "&js_code={$code}&grant_type=authorization_code";

        return $response = $this->requestApi($url);
    }

    protected function getAccessToken()
    {
        if (empty(self::$appid) || empty(self::$appsecret)) {
            return [
                'errno' => 1,
                'message' => '未配置小程序参数'
            ];
        }

        $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=' . self::$appid . '&secret=' . self::$appsecret;
        $result = $this->requestApi($url);
        if(isset($result['errcode']) && $result['errcode'] != 0) {
            return '';
        }

        return $result['access_token'];
    }

    public function getUrlLink($path = '', $query = '')
    {
        $access_token = $this->getAccessToken();
        $url = 'https://api.weixin.qq.com/wxa/generate_urllink?access_token=' . $access_token;
        $post = [
            'path' => $path,
            'query' => $query,
            'expire_type' => 0,
            'expire_interval' => 1
        ];
        $result = $this->httpRequest($url, json_encode($post));
        $result = @json_decode($result, true);
        if(isset($result['errcode']) && $result['errcode'] != 0) {
            return '';
        }

        return $result['url_link'];
    }

    public function getScheme($path = '', $query = '')
    {
        $access_token = $this->getAccessToken();
        $url = 'https://api.weixin.qq.com/wxa/generatescheme?access_token=' . $access_token;
        $post = [
            'jump_wxa' => [
                'path' => $path,
                'query' => $query
            ],
            'expire_type' => 0,
            'expire_interval' => 1
        ];
        $result = $this->httpRequest($url, json_encode($post));
        $result = @json_decode($result, true);
        if($result['errcode'] != 0) {
            return '';
        }

        return $result['openlink'];
    }

    /**
     * @param string $openid
     * @param string $content
     * @return mixed|string
     * 文本内容安全识别
     */
    public function msgSecCheck($openid = '', $content = '')
    {
        try {
            $access_token = $this->getAccessToken();
            $url = 'https://api.weixin.qq.com/wxa/msg_sec_check?access_token=' . $access_token;
            $post = [
                'content' => $content,
                'version' => 2,
                'scene' => 1,
                'openid' => $openid
            ];
            $result = $this->httpRequest($url, json_encode($post));
            $result = @json_decode($result, true);
            if($result['errcode'] != 0) {
                return true;
            }
            if (!isset($result['result'])) {
                return true;
            }

            return $result['result']['suggest'] == 'pass';
        } catch (\Exception $e) {
            return true;
        }
    }

    public function getCodeUnlimit($scene, $page = '', $width = '430', $option = array())
    {
        try {
            if (!preg_match('/[0-9a-zA-Z\!\#\$\&\'\(\)\*\+\,\/\:\;\=\?\@\-\.\_\~]{1,32}/', $scene)) {
                return [
                    'errno' => 1,
                    'message' => '场景值不合法'
                ];
            }
            $data = [
                'scene' => $scene,
                'width' => intval($width)
            ];
            if (!empty($page)) {
                $data['page'] = $page;
            }
            if (!empty($option['auto_color'])) {
                $data['auto_color'] = intval($option['auto_color']);
            }

            if (!empty($option['line_color'])) {
                $data['line_color'] = array(
                    'r' => $option['line_color']['r'],
                    'g' => $option['line_color']['g'],
                    'b' => $option['line_color']['b'],
                );
                $data['auto_color'] = false;
            }

            $access_token = $this->getAccessToken();
            $url = 'https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token=' . $access_token;
            $result = $this->httpRequest($url, json_encode($data));
            if (isset($result['errcode'])) {
                return [
                    'errno' => 1,
                    'message' => $result['errmsg']
                ];
            }
            if ($this->is_error($result)) {
                return [
                    'errno' => 1,
                    'message' => isset($result['message']) ? $result['message'] : ''
                ];
            }

            return $result;
        } catch(\Exception $e) {
            return '';
        }
    }

    public function pkcs7Decode($sessionKey, $encryptedData, $iv)
    {
        $aesKey = base64_decode($sessionKey);
        $aesIV = base64_decode($iv);
        $aesCipher = base64_decode($encryptedData);

        $result = openssl_decrypt($aesCipher, "AES-128-CBC", $aesKey, 1, $aesIV);

        $result = json_decode($result, true);
        if (empty($result)) {
            return [
                'errno' => 1,
                'message' => '解密失败'
            ];
        }

        return $result;
    }

    protected function requestApi($url, $post = '')
    {
        $response = $this->httpRequest($url, $post);
        $result = @json_decode($response['content'], true);

        if ($this->is_error($response)) {
            return [
                'errno' => 1,
                'message' => "错误: {$result['errmsg']}"
            ];
        }
        if (empty($result)) {
            return @json_decode($response, true);
        } elseif (!empty($result['errcode'])) {
            return [
                'errno' => 1,
                'message' => "错误: {$result['errmsg']}"
            ];
        }

        return $result;
    }

    /**
     * http请求
     */
    protected function httpRequest($url, $post = '')
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        if (!empty($header)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        }
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 2);
        if ($post != "") {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post); //设置post提交数据
        }
        //判断当前是不是有post数据的发
        $output = curl_exec($ch);
        if ($output === false) {
            $output = "curl 错误信息: " . curl_error($ch);
        }
        curl_close($ch);
        return $output;
    }

    private function is_error($data)
    {
        if (empty($data) || !is_array($data) || !array_key_exists('errno', $data) || (array_key_exists('errno', $data) && 0 == $data['errno'])) {
            return false;
        } else {
            return true;
        }
    }
}
