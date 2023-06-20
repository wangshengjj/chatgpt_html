<?php

namespace Wxpay\v3;

class Voucher extends BaseWechat
{
    /**
     * 创建代金券批次
     * @param array $param
     * @return mixed
     */
    public function create($param = [])
    {
        $data = [
            // 批次名称
            'stock_name' => $param['stock_name'],
            // 批次备注
            'comment' => $param['comment'],
            // 归属商户号
            'belong_merchant' => $param['belong_merchant'],
            // 开始时间
            'available_begin_time' => $param['available_begin_time'],
            // 结束时间
            'available_end_time' => $param['available_end_time'],
            // 是否无资金流
            'no_cash' => $param['no_cash'],
            // 批次类型
            'stock_type' => $param['stock_type'],
            // 商户单据号
            'out_request_no' => $param['out_request_no'],
            // 扩展属性
            'ext_info' => $param['ext_info'],
        ];

        // 发放规则
        if (isset($param['stock_use_rule'])) {
            $data['stock_use_rule'] = $param['stock_use_rule'];
        }

        // 核销规则
        if (isset($param['coupon_use_rule'])) {
            $data['coupon_use_rule'] = $param['coupon_use_rule'];
        }

        // 样式设置
        if (isset($param['pattern_info'])) {
            $data['pattern_info'] = $param['pattern_info'];
        }

        if (isset($param['coupon_use_rule'])) {
            $data['coupon_use_rule'] = $param['coupon_use_rule'];
        }

        // 发起请求
        $url = self::WXAPIHOST . '/marketing/favor/coupon-stocks';

        return $this->apiRequest($url, json_encode($data));
    }

    /**
     * 获取代金券批次列表
     * @param array $param
     * @return mixed
     */
    public function stocks($param = [])
    {
        $data = [
            'offset' => $param['offset'],
            'limit' => $param['limit'] ? $param['limit'] : 10,
            'stock_creator_mchid' => $this->mch_id
        ];
        if (!empty($param['create_start_time'])) {
            $data['create_start_time'] = $param['create_start_time'];
        }
        if (!empty($param['create_end_time'])) {
            $data['create_end_time'] = $param['create_end_time'];
        }

        // 发起请求
        $url = self::WXAPIHOST . '/marketing/favor/stocks';
        $url .= '?' . $this->toUrlParams($data);
        return $this->apiRequest($url);
    }

    /**
     * 获取代金券详情
     * @param $stock_id
     * @return mixed
     */
    public function getStock($stock_id)
    {
        // 发起请求
        $url = self::WXAPIHOST . '/marketing/favor/stocks/' . $stock_id . '?stock_creator_mchid=' . $this->mch_id;
        return $this->apiRequest($url);
    }

    /**
     * 激活代金券批次
     * @param $stock_id
     * @return mixed
     */
    public function start($stock_id)
    {
        $data = [
            'stock_creator_mchid' => $this->mch_id
        ];
        $url = self::WXAPIHOST . '/marketing/favor/stocks/' . $stock_id . '/start';
        return $this->apiRequest($url, json_encode($data));
    }

    /**
     * 暂停代金券批次
     * @param $stock_id
     * @return mixed
     */
    public function pause($stock_id)
    {
        $data = [
            'stock_creator_mchid' => $this->mch_id
        ];
        $url = self::WXAPIHOST . '/marketing/favor/stocks/' . $stock_id . '/pause';
        return $this->apiRequest($url, json_encode($data));
    }

    /**
     * 重启代金券批次
     * @param $stock_id
     * @return mixed
     */
    public function restart($stock_id)
    {
        $data = [
            'stock_creator_mchid' => $this->mch_id
        ];
        $url = self::WXAPIHOST . '/marketing/favor/stocks/' . $stock_id . '/restart';
        return $this->apiRequest($url, json_encode($data));
    }

    /**
     * 下载批次核销明细
     * @param $stock_id
     * @return mixed
     */
    public function downloadUseFlow($stock_id)
    {
        $url = self::WXAPIHOST . '/marketing/favor/stocks/' . $stock_id . '/use-flow';
        return $this->apiRequest($url);
    }

    /**
     * 下载批次退款明细
     * @param $stock_id
     * @return mixed
     */
    public function downloadRefundFlow($stock_id)
    {
        $url = self::WXAPIHOST . '/marketing/favor/stocks/' . $stock_id . '/refund-flow';
        return $this->apiRequest($url);
    }

    /**
     * 发放代金券
     * @param $openid
     * @param $param
     * @return mixed
     */
    public function send($openid, $param)
    {
        $url = self::WXAPIHOST . '/marketing/favor/users/' . $openid . '/coupons';
        return $this->apiRequest($url, json_encode($param));
    }

}
