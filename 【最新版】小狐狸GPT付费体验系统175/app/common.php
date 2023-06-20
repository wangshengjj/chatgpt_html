<?php
// 应用公共文件
use think\facade\Db;

/**
 * @return mixed|string
 * 获取客户端ip
 */
function get_client_ip()
{
    $ip = '';
    if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $pos = array_search('unknown', $arr);
        if (false !== $pos) {
            unset($arr[$pos]);
        }
        $ip = trim($arr[0]);
    } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (isset($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }

    return $ip;
}

/**
 * @param array $data
 * @param string $message
 * @return string
 * 返回成功json
 */
function successJson($data = [], $message = '')
{
    echo json_encode([
        'errno' => 0,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

/**
 * @param string $message
 * @return string
 * 返回失败json
 */
function errorJson($message = '')
{
    echo json_encode([
        'errno' => 1,
        'message' => $message
    ]);
    exit;
}

/**
 * @param $length
 * @return string
 * 生成随机字符串
 */
function getNonceStr($length = 4)
{
    $chars = "abcdefghijklmnpqrstuvwxyz123456789";
    $str ="";
    for ( $i = 0; $i < $length; $i++ )  {
        $str .= substr($chars, mt_rand(0, strlen($chars)-1), 1);
    }
    return $str;
}

/**
 * @param $pass
 * @param $salt
 * @return string
 * 密码加密
 */
function encryptPass($pass, $salt)
{
    return md5(' ' . md5($pass) . $salt);
}

/**
 * @param array $shop
 * @return \AopSDK\AopClient|array
 * new Alipay AopClient
 */
function get_alipay_aop($config)
{
    $aop = new \AopSDK\AopClient();
    $aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
    $aop->appId = $config['appid'];
    $aop->rsaPrivateKey = $config['private_key'];
    $aop->alipayrsaPublicKey = $config['public_key'];
    $aop->apiVersion = '1.0';
    $aop->signType = 'RSA2';
    $aop->postCharset = 'UTF-8';
    $aop->format = 'json';
    return $aop;
}

/**
 * @param $brand
 * @return array|mixed
 * 获取系统配置
 */
function getSystemSetting($site_id, $name)
{
    $setting = Db::name('setting')
        ->where('site_id', $site_id)
        ->value($name);
    if (!$setting) {
        return [];
    }

    return json_decode($setting, true);
}

/**
 * @param $brand
 * @return array|mixed
 * 保存系统配置
 */
function setSystemSetting($site_id, $name, $value)
{
    $setting = Db::name('setting')
        ->where('site_id', $site_id)
        ->find();
    if (!$setting) {
        Db::name('setting')
            ->insert([
                'site_id' => $site_id
            ]);
    }
    $res = Db::name('setting')
        ->where('site_id', $site_id)
        ->update([
            $name => $value
        ]);

    return $res !== false;
}

/**
 * @param $content
 * 保存系统日志
 */
function saveLog($site_id, $content)
{
    Db::name('logs')
        ->insert([
            'site_id' => $site_id,
            'content' => $content,
            'create_time' => time()
        ]);
}

/**
 * 发送模板消息
 */
function sendTplNotice($appid, $appsecret, $openid, $template_id, $data, $url = '', $wxapp = null)
{
    $WeixinSDK = new \Weixin\Weixin($appid, $appsecret);
    foreach ($data as $k => $v) {
        $postData[$k] = [
            'value' => $v,
            'color' => ''
        ];
    }
    $result = $WeixinSDK->sendTplNotice([
        'openid' => $openid,
        'template_id' => $template_id,
        'data' => $postData,
        'url' => $url,
        'wxapp' => $wxapp
    ]);

    return $result;
}

if (!function_exists('mediaUrl')) {
    function mediaUrl($url = '', $full = false)
    {
        if ($url) {
            if (strpos($url, '://') !== false) {
                return $url;
            }
            $url = ltrim($url, './');
            $url = '/' . ltrim($url, '/');

            if ($full) {
                $url = 'https://' . $_SERVER['HTTP_HOST'] . $url;
            }
        }

        return $url;
    }
}

/**
 * @param $message
 * @return string|string[]|null
 * 敏感关键词过滤
 */
function wordFilter($message)
{
    $Filter = new \FoxFilter\words('*');
    $clearMessage = $Filter->filter($message);
    if ($clearMessage != $message) {
        $setting = getSystemSetting(0, 'filter');
        $handle_type = empty($setting['handle_type']) ? 1 : intval($setting['handle_type']);
        if ($handle_type == 2) {
            return errorJson('内容包含敏感信息');
        }
    }

    return $clearMessage;
}

/**
 * @param $message
 * @return string|string[]|null
 * 敏感关键词过滤
 */
function isSimpleCn($message)
{
    try {
        return iconv('UTF-8', 'GB2312', $message) === false ? false : true;
    } catch (\Exception $e) {
        return false;
    }
}

/**
 * 变更用户余额并增加日志
 */
function changeUserBalance($user_id, $num, $desc = '')
{
    if (!$num) {
        return;
    }
    $user = Db::name('user')
        ->where('id', $user_id)
        ->find();
    if (!$user) {
        return;
    }

    Db::startTrans();
    try {
        if ($num > 0) {
            Db::name('user')
                ->where('id', $user_id)
                ->inc('balance', $num)
                ->update();
        } else {
            Db::name('user')
                ->where('id', $user_id)
                ->dec('balance', -$num)
                ->update();
        }
        Db::name('user_balance_logs')
            ->insert([
                'site_id' => $user['site_id'],
                'user_id' => $user_id,
                'num' => $num,
                'desc' => $desc,
                'create_time' => time()
            ]);
        Db::commit();
    } catch (\Exception $e) {
        Db::rollback();
        saveLog($user['site_id'], '更新余额失败，用户' . $user_id . '，数量：' . $num . '，错误：' . $e->getMessage());
    }
}
/**
 * 变更用户余额并增加日志
 */
function setUserVipTime($user_id, $vip_expire_time = '', $desc = '')
{
    $user = Db::name('user')
        ->where('id', $user_id)
        ->find();
    if (!$user) {
        return;
    }

    Db::startTrans();
    try {
        if ($vip_expire_time) {
            $vip_expire_time = strtotime($vip_expire_time . ' 23:59:59');
        } else {
            $vip_expire_time = 0;
        }
        Db::name('user')
            ->where('id', $user_id)
            ->update([
                'vip_expire_time' => $vip_expire_time
            ]);

        Db::name('user_vip_logs')
            ->insert([
                'site_id' => $user['site_id'],
                'user_id' => $user_id,
                'vip_expire_time' => $vip_expire_time,
                'desc' => $desc,
                'create_time' => time()
            ]);
        Db::commit();
    } catch (\Exception $e) {
        Db::rollback();
        saveLog($user['site_id'], '修改会员时间失败，用户' . $user_id . '，到期时间：' . $vip_expire_time . '，错误：' . $e->getMessage());
    }
}