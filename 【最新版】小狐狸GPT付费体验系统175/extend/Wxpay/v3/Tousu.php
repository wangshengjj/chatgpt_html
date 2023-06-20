<?php

namespace Wxpay\v3;

class Tousu extends BaseWechat
{
    /**
     * 查询投诉单列表
     * @param array $param
     * @return mixed
     */
    public function getList($param = [])
    {
        $data = [
            // 分页大小
            'limit' => $param['limit'],
            // 分页开始位置
            'offset' => $param['offset'],
            // 开始日期
            'begin_date' => $param['begin_date'],
            // 结束日期
            'end_date' => $param['end_date']
        ];

        // 被诉商户号
        if (isset($param['complainted_mchid'])) {
            $data['complainted_mchid'] = $param['complainted_mchid'];
        }

        // 发起请求
        $url = self::WXAPIHOST . '/merchant-service/complaints-v2';
        $url = $url . '?' . http_build_query($data);

        return $this->apiRequest($url);
    }

    /**
     * 查询投诉单详情
     * @param $stock_id
     * @return mixed
     */
    public function getDetail($complaint_id)
    {
        // 发起请求
        $url = self::WXAPIHOST . '/merchant-service/complaints-v2/' . $complaint_id;
        return $this->apiRequest($url);
    }

    /**
     * 创建投诉通知回调地址
     * @param $url
     * @return mixed
     */
    public function createNotifyUrl($url)
    {
        $data = [
            'url' => $url
        ];
        $url = self::WXAPIHOST . '/merchant-service/complaint-notifications';
        return $this->apiRequest($url, json_encode($data));
    }

    /**
     * 创建投诉通知回调地址
     * @return mixed
     */
    public function queryNotifyUrl()
    {
        $url = self::WXAPIHOST . '/merchant-service/complaint-notifications';
        return $this->apiRequest($url);
    }

    /**
     * 删除投诉通知回调地址
     * @return mixed
     */
    public function deleteNotifyUrl()
    {
        $url = self::WXAPIHOST . '/merchant-service/complaint-notifications';
        return $this->httpDelete($url);
    }

    /**
     * 微信v3接口请求 - delete
     * @param $url
     * @return bool
     */
    protected function httpDelete($url)
    {
        $token = $this->makeHeaderToken($url, '', 'DELETE');
        $header = [
            'User-Agent:' . $_SERVER['HTTP_USER_AGENT'],
            'Content-Type:application/json;charset=utf-8',
            'Accept:application/json',
            'Authorization:WECHATPAY2-SHA256-RSA2048 ' . $token
        ];
        $public_serial_no = $this->getPublicSerialNo();
        $header[] = 'Wechatpay-Serial:' . $public_serial_no;


        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        if($header) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        }
        curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 信任任何证书
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2); // 检查证书中是否设置域名
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');

        curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);    // 获取响应状态码
        curl_close($ch);

        return $http_code == 204;
    }

}
