<?php

namespace Wxpay\v3;

use Wxpay\v3\Traits\WxException;

class BaseWechat
{
    //请求API
    const WXAPIHOST = 'https://api.mch.weixin.qq.com/v3';

    //根目录
    const DIR = __DIR__;

    //公众号appid
    protected $appid;

    //公众号secret
    protected $secret;

    // 服务商商户号
    protected $mch_id;

    // 商户key v3
    protected $aesKey;
    // 商户key v3
    protected $diyKey;

    // 商户证书序列号
    protected $private_serial_no;

    // 平台证书序列号
    protected $public_serial_no;

    // 最新的平台证书完整响应体存放位置
    public $publicKeyJsonFile;

    // 解密后平台证书地址
    public $publicKeyFile;

    // 商户私钥地址
    protected $privateKeyFile;

    //商户公钥地址
    protected $privateCertFile;

    const AUTH_TAG_LENGTH_BYTE = 16;

    public function __construct($config = [])
    {
        $this->mch_id = $config['mch_id'];
        $this->appid = $config['appid'];
        $this->secret = $config['secret'];
        $this->aesKey = $config['aes_key'];
        $this->diyKey = $config['aes_key'];
        $this->privateKeyFile = $config['apiclient_key'];
        $this->privateCertFile = $config['apiclient_cert'];
        $this->publicKeyJsonFile = self::DIR . '/Certificate/public_' . $this->mch_id . '.json';
        $this->publicKeyFile = self::DIR . '/Certificate/public_' . $this->mch_id . '.pem';
        $this->private_serial_no = $this->getPrivateSerialNo();
        if (!file_exists($this->publicKeyFile)) {
            $this->downloadPublicKeyFile();
        }
        $this->public_serial_no = $this->getPublicSerialNo();
    }

    /**
     * 下载平台证书
     */
    private function downloadPublicKeyFile()
    {
        $url = self::WXAPIHOST . "/certificates";
        $token = $this->makeHeaderToken($url, '', 'GET'); // $http_method大写
        $header[] = 'User-Agent:https://zh.wikipedia.org/wiki/User_agent';
        $header[] = 'Accept:application/json';
        $header[] = 'Authorization:WECHATPAY2-SHA256-RSA2048 ' . $token;
        $result = $this->httpRequest($url, $header);
        $result = json_decode($result, JSON_UNESCAPED_UNICODE);
        $cert = $result['data'][0]['encrypt_certificate'];
        $result = $this->decryptToString($cert['associated_data'], $cert['nonce'], $cert['ciphertext']);
        if(!is_dir(dirname($this->publicKeyFile))) {
            mkdir(dirname($this->publicKeyFile), 0755, true);
        }
        file_put_contents($this->publicKeyFile, $result);
    }

    /**
     * 解密publicKey
     */
    public function decryptToString($associatedData, $nonceStr, $ciphertext)
    {
        $ciphertext = base64_decode($ciphertext);
        if (strlen($ciphertext) < self::AUTH_TAG_LENGTH_BYTE) {
            return false;
        }
        // ext-sodium (default installed on >= PHP 7.2)
        if (function_exists('\sodium_crypto_aead_aes256gcm_is_available') &&
            \sodium_crypto_aead_aes256gcm_is_available()) {
            return \sodium_crypto_aead_aes256gcm_decrypt($ciphertext, $associatedData, $nonceStr, $this->diyKey);
        }

        // ext-libsodium (need install libsodium-php 1.x via pecl)
        if (function_exists('\Sodium\crypto_aead_aes256gcm_is_available') &&
            \Sodium\crypto_aead_aes256gcm_is_available()) {
            return \Sodium\crypto_aead_aes256gcm_decrypt($ciphertext, $associatedData, $nonceStr, $this->diyKey);
        }

        // openssl (PHP >= 7.1 support AEAD)
        if (PHP_VERSION_ID >= 70100 && in_array('aes-256-gcm', \openssl_get_cipher_methods())) {
            $ctext = substr($ciphertext, 0, -self::AUTH_TAG_LENGTH_BYTE);
            $authTag = substr($ciphertext, -self::AUTH_TAG_LENGTH_BYTE);

            return \openssl_decrypt($ctext, 'aes-256-gcm', $this->diyKey, \OPENSSL_RAW_DATA, $nonceStr,
                $authTag, $associatedData);
        }

        throw new \RuntimeException('AEAD_AES_256_GCM需要PHP 7.1以上或者安装libsodium-php');
    }

    /**
     * 获取商户证序列号
     * @param $certFile
     * @return mixed
     */
    private function getPrivateSerialNo()
    {
        $str = openssl_x509_parse(file_get_contents($this->privateCertFile));
        return $str['serialNumberHex'];
    }

    /**
     * 获取商户证序列号
     * @return mixed
     */
    protected function getPublicSerialNo()
    {
        $str = openssl_x509_parse(file_get_contents($this->publicKeyFile));
        return $str['serialNumberHex'];
    }

    /**
     * @return false|resourc
     * 获取商户私钥
     */
    public function getPrivateKey()
    {
        return openssl_pkey_get_private(file_get_contents($this->privateKeyFile));
    }

    /**
     * @return false|resourc
     * 获取平台公钥
     */
    public function getPublicKey()
    {
        return file_get_contents($this->publicKeyFile);
    }

    /**
     * 微信v3接口请求
     * @param $url
     * @param string $data
     * @param bool $auth
     * @return bool|mixed|string
     */
    protected function apiRequest($url, $data = '', $auth = true)
    {
        $method = empty($data) ? 'GET' : 'POST';
        $token = $this->makeHeaderToken($url, $data, $method);
        $header = [
            'User-Agent:' . $_SERVER['HTTP_USER_AGENT'],
            'Content-Type:application/json;charset=utf-8',
            'Accept:application/json',
            'Authorization:WECHATPAY2-SHA256-RSA2048 ' . $token
        ];
        if ($auth) {
            $public_serial_no = $this->getPublicSerialNo();
            $header[] = 'Wechatpay-Serial:' . $public_serial_no;
        }

        $result = $this->httpRequest($url, $header, $data);
        if (strpos($result, '<xml>') !== false) {
            return $result;
        } else {
            return json_decode($result, true, 512, JSON_BIGINT_AS_STRING);
        }
    }

    /**
     * 网络请求（支持GET和POST）
     * @param $url
     * @param null $header
     * @param string $data
     * @param int $timeout
     * @return bool|string
     */
    protected function httpRequest($url, $header = null, $data = '', $timeout = 30)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        if($header) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        }
        curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
        if (substr($url, 0, 5) == 'https') {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 信任任何证书
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2); // 检查证书中是否设置域名
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if ($data != '') {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data); //设置post提交数据
        }
        $output = curl_exec($ch);

        /*$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);    // 获得响应结果里的：头大小
        $response_header = substr($output, 0, $header_size);    // 根据头大小去获取头信息内容
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);    // 获取响应状态码
        $response_body = substr($output, $header_size);
        echo $response_body;exit;
        $error = curl_error($ch);*/
        curl_close($ch);

        return $output;
    }


    /*
     * 加密
     */
    protected function Encrypt($str)
    {
        $public_key = $this->getPublicKey();
        $encrypted = '';
        if (openssl_public_encrypt($str, $encrypted, $public_key, OPENSSL_PKCS1_OAEP_PADDING)) {
            //base64编码
            $sign = base64_encode($encrypted);
        } else {
            throw new \Exception('encrypt failed');
        }
        return $sign;
    }

    /*
     * 加密
     */
    public function Decrypt($str)
    {
        $private_key = $this->getPrivateKey();
        $decrypted = '';
        //私钥解密
        openssl_private_decrypt(base64_decode($str),$decrypted, $private_key, OPENSSL_PKCS1_OAEP_PADDING);
        return $decrypted;
    }


    /**
     * httpsRequest  https请求（支持GET和POST）
     * @param $url
     * @param null $data
     * @return mixed
     */
    /*protected function httpsRequest($url, $data = '', $auth = true, $timeout = 30)
    {

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        $method = 'GET';
        if (!empty($data)) {
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            $method = 'POST';
        }
        if ($auth) {
            $token = $this->makeToken($url, $data, $method);
            $headers = array(
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization: WECHATPAY2-SHA256-RSA2048 ' . $token,
            );
        }
        //设置超时
        curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);

        if (substr($url, 0, 5) == 'https') {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // 信任任何证书
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2); // 检查证书中是否设置域名
        }
        if (!empty($headers)) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        }
        curl_setopt($curl, CURLOPT_HEADER, true);    // 是否需要响应 header
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($curl);
        $header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);    // 获得响应结果里的：头大小
        $response_header = substr($output, 0, $header_size);    // 根据头大小去获取头信息内容
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);    // 获取响应状态码
        $response_body = substr($output, $header_size);
        $error = curl_error($curl);
        curl_close($curl);

        /*
        file_put_contents("./wxVoucher.txt", "请求时间：".date('Y-m-d H:i:s', time())."\n", FILE_APPEND);
        file_put_contents("./wxVoucher.txt", "请求URL：".$url."\n", FILE_APPEND);
        file_put_contents("./wxVoucher.txt", "请求参数：".$data."\n", FILE_APPEND);
        file_put_contents("./wxVoucher.txt", "-------------\n", FILE_APPEND);
        file_put_contents("./wxVoucher.txt", "返回数据：".$response_body."\n", FILE_APPEND);
        file_put_contents("./wxVoucher.txt", "-------------\n", FILE_APPEND);
        file_put_contents("./wxVoucher.txt", "响应头：".$response_header."\n", FILE_APPEND);
        if(!empty($error)) {
            file_put_contents("./wxVoucher.txt", "-------------\n", FILE_APPEND);
            file_put_contents("./wxVoucher.txt", "错误：".$error."\n", FILE_APPEND);
        }
        file_put_contents("./wxVoucher.txt", "=============================\n", FILE_APPEND);
        */

    //return [$response_body, $http_code, $response_header, $error];

    /*if (strpos($response_body, '<xml>') !== false) {
        return $this->fromXml($response_body);
    } else {
        return json_decode($response_body, true);
    }
}*/

    /**
     * parseHeaders    处理curl响应头
     * @param $header
     * @return array
     */
    protected function parseHeaders($header)
    {
        $headers = explode("\r\n", $header);
        $head = array();
        array_map(function ($v) use (&$head) {
            $t = explode(':', $v, 2);
            if (isset($t[1])) {
                $head[trim($t[0])] = trim($t[1]);
            } else {
                if (!empty($v)) {
                    $head[] = $v;
                    if (preg_match("#HTTP/[0-9\.]+\s+([0-9]+)#", $v, $out)) {
                        $head['reponse_code'] = intval($out[1]);
                    }
                }
            }
        }, $headers);
        return $head;
    }

    /**
     * getRandChar 获取随机字符串
     * @param int $length
     * @return null|string
     */
    protected function getRandChar($length = 32)
    {
        $str = NULL;
        $strPol = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz";
        $newStr = str_shuffle($strPol);
        $max = strlen($strPol) - 1;
        for ($i = 0; $i < $length; $i++) {
            $str .= $newStr[mt_rand(0, $max)];    // rand($min,$max)生成介于min和max两个数之间的一个随机整数
        }
        return $str;
    }

    /**
     * setHashSign SHA256 with RSA 签名
     * @param $signContent
     * @return string
     */
    protected function makeHeaderToken($url, $body, $http_method = 'POST')
    {
        $url_parts = parse_url($url);
        $timestamp = time();
        $nonce = $this->getRandChar(16);
        $canonical_url = ($url_parts['path'] . (!empty($url_parts['query']) ? "?${url_parts['query']}" : ""));
        $signContent = $http_method . "\n" .
            $canonical_url . "\n" .
            $timestamp . "\n" .
            $nonce . "\n" .
            $body . "\n";

        $privateKey = $this->getPrivateKey();

        openssl_sign($signContent, $raw_sign, $privateKey, 'sha256WithRSAEncryption');
        $sign = base64_encode($raw_sign);

        return sprintf('mchid="%s",nonce_str="%s",timestamp="%d",serial_no="%s",signature="%s"',
            $this->mch_id, $nonce, $timestamp, $this->private_serial_no, $sign);
    }

    /**
     * MakeSign 生成签名
     * @param $data
     * @param string $signType
     * @return string
     */
    protected function makeSign(array $data, $signType = 'HMAC-SHA256')
    {

        //签名步骤一：按字典序排序参数
        ksort($data);

        $string = $this->toUrlParams($data);
        //签名步骤二：在string后加入KEY
        $string = $string . "&key=" . $this->aesKey;

        //签名步骤三：MD5加密或者HMAC-SHA256
        if ($signType == 'md5') {
            //如果签名小于等于32个,则使用md5验证
            $string = md5($string);
        } else {
            //是用sha256校验
            $string = hash_hmac("sha256", $string, $this->aesKey);
        }
        //签名步骤四：所有字符转为大写
        $result = strtoupper($string);
        return $result;
    }

    /**
     * ToUrlParams     格式化参数格式化成url参数
     * @param $data
     * @return string
     */
    protected function toUrlParams(array $data)
    {
        $buff = "";
        foreach ($data as $k => $v) {
            if ($k != "sign" && $v !== "" && !is_array($v)) {
                $buff .= $k . "=" . $v . "&";
            }
        }
        $buff = trim($buff, "&");
        return $buff;
    }

    /**
     * @param WxPayConfigInterface $config 配置对象
     * 检测签名
     */
    protected function checkSign($data)
    {
        strlen($data['sign']) <= 32 && $sign_type = 'md5';
        return true;
        if ($this->makeSign($data, $sign_type ? $sign_type : '') == $data['sign']) {
            return true;
        }
        throw new WxException(20000);
    }

    protected function getSSLCertPath()
    {
        return [
            $this->privateCertFile,
            $this->privateKeyFile
        ];
    }

    /**
     * 输出xml字符
     * @throws WxPayException
     **/
    protected function toXml($data)
    {
        if (!is_array($data) || count($data) <= 0) {
            throw new WxException(30001);
        }

        $xml = "<xml>";
        foreach ($data as $key => $val) {
            if (is_numeric($val)) {
                $xml .= "<" . $key . ">" . $val . "</" . $key . ">";
            } else {
                $xml .= "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
            }
        }
        $xml .= "</xml>";
        return $xml;
    }

    /**
     * 将xml转为array
     * @param string $xml
     * @throws WxPayException
     */
    protected function fromXml($xml)
    {
        if (!$xml) {
            throw new WxException(30000);
        }
        //将XML转为array
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        $xml_parser = xml_parser_create();
        if (!xml_parse($xml_parser, $xml, true)) {
            xml_parser_free($xml_parser);
            throw new WxException(30000);
        } else {
            $arr = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        }
        return $arr;
    }

    /**
     * getMillisecond 获取毫秒级别的时间戳
     * @return float
     */
    protected function getMillisecond()
    {
        list($msec, $sec) = explode(' ', microtime());
        $msectime = (float)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);
        return $msectime;
    }

    /**
     * disposeReturn 处理微信小微商户接口返回值
     * @param $res  httpsRequest 方法调用接口返回的数组
     * @param array $need_fields 需要接口返回的字段（当return_code 、result_code都为SUCCESS时返回的字段的key）
     * @param array $arr 自定义的参数返回出去，例如入驻接口生成的业务编号
     * @return array 微信返回系统级错误不暴露出去，直接返回操作失败，业务级别错误返回具体错误消息
     */
    protected function disposeReturn($res, array $need_fields = [], array $arr = [])
    {
        if ($res[1] == 200) {
            $rt = $this->fromXml($res[0]);

            if ($rt['return_code'] != 'SUCCESS') {
//                    \Log::error($rt['return_msg']);
                return $rt;
                //throw new WxException(0, $rt['return_msg']);
            }
            if ($rt['result_code'] != 'SUCCESS') {
                return $rt;
                //throw new WxException(0, $rt['err_code_des'] ? $rt['err_code_des'] : $rt['err_code_msg']);
            }
            if ($this->checkSign($rt)) {
                if (!empty($need_fields)) {
                    $need = [];
                    array_map(function ($v) use ($rt, &$need) {
                        $need[$v] = $rt[$v] ? $rt[$v] : '';
                    }, $need_fields);
                    return array_merge($need, $arr);
                }
                return $arr;
            }
        }
        throw new WxException(30002);
    }

}