<?php

namespace Wxpay\v3;

use Wxpay\v3\Traits\UploadMedia;

class Jinjian extends BaseWechat
{
    use UploadMedia;

    /**
     * 申请入驻
     * @return mixed
     */
    public function applyEnter(array $params)
    {
        $url = self::WXAPIHOST . '/applyment4sub/applyment/';
        $data = [
            'business_code' => $params['business_code'],
            'contact_info' => [
                'contact_type' => 'LEGAL',
                'contact_name' => $this->Encrypt($params['contact_name']),
                'contact_id_number' => $this->Encrypt($params['contact_id_number']),
                'mobile_phone' => $this->Encrypt($params['mobile_phone']),
                'contact_email' => $this->Encrypt($params['contact_email'])
            ],
            'subject_info' => [
                'subject_type' => $params['subject_type'],
                'business_license_info' => [
                    'license_copy' => $params['license_copy'],
                    'license_number' => $params['license_number'],
                    'merchant_name' => $params['merchant_name'],
                    'legal_person' => $params['legal_person']
                ],
                'identity_info' => [
                    'id_doc_type' => $params['id_doc_type'],
                    'id_card_info' => [
                        'id_card_copy' => $params['id_card_copy'],
                        'id_card_national' => $params['id_card_national'],
                        'id_card_name' => $this->Encrypt($params['id_card_name']),
                        'id_card_number' => $this->Encrypt($params['id_card_number']),
                        'id_card_address' => $this->Encrypt($params['id_card_address']),
                        'card_period_begin' => $params['card_period_begin'],
                        'card_period_end' => $params['card_period_end']
                    ],
                    'owner' => $params['subject_type'] == 'SUBJECT_TYPE_INDIVIDUAL' ? null : !!$params['owner']
                ]
            ],
            'business_info' => [
                'merchant_shortname' => $params['merchant_shortname'],
                'service_phone' => $params['service_phone'],
                'sales_info' => [
                    'sales_scenes_type' => [
                        $params['sales_scenes_type']
                    ],
                    'biz_store_info' => [
                        'biz_store_name' => $params['biz_store_name'],
                        'biz_address_code' => $params['biz_address_code'],
                        'biz_store_address' => $params['biz_store_address'],
                        'store_entrance_pic' => [
                            $params['store_entrance_pic']
                        ],
                        'indoor_pic' => [
                            $params['indoor_pic']
                        ]
                    ]
                ]
            ],
            'settlement_info' => [
                'settlement_id' => $params['settlement_id'] . '',
                'qualification_type' => $params['qualification_type'],
                'qualifications' => !empty($params['qualifications']) ? $params['qualifications'] : [],
                'activities_id' => $params['activities_rate'] ? '20191030111cff5b5e' : '',
                'activities_rate' => $params['activities_rate']
            ],
            'bank_account_info' => [
                'bank_account_type' => $params['bank_account_type'],
                'account_name' => $this->Encrypt($params['account_name']),
                'account_bank' => $params['account_bank'],
                'bank_address_code' => $params['bank_address_code'],
                'bank_name' => isset($params['bank_name']) ? $params['bank_name'] : '',
                'account_number' => $this->Encrypt($params['account_number'])
            ]
        ];


        $info = $this->apiRequest($url, json_encode($data));
        /*file_put_contents('./jinjianWxpay.txt', '时间：' . date('Y-m-d H:i:s') . "\n", 8);
        file_put_contents('./jinjianWxpay.txt', 'header：' . json_encode($data) . "\n", 8);
        file_put_contents('./jinjianWxpay.txt', '参数：' . json_encode($data) . "\n", 8);
        file_put_contents('./jinjianWxpay.txt', '结果：' . $info . "\n\n", 8);*/

        if (isset($info['code'])) {
            return [
                'errno' => 1,
                'message' => $info['message']
            ];
        } else {
            return [
                'errno' => 0,
                'applyment_id' => $info['applyment_id']
            ];
        }
    }

    /**
     * 入驻申请状态查询
     * @param array $params
     * @return mixed
     * @throws WxException
     */
    public function enquiryOfApplyStatus(array $params)
    {
        if (!isset($params['applyment_id']) && !isset($params['business_code'])) {
            throw new WxException(20004);
        }
        $url = self::WXAPIHOST . '/applyment4sub/applyment/applyment_id/' . $params['applyment_id'];
        return $this->apiRequest($url);
    }

    /**
     * 生成业务申请编号
     * @return mixed|null|string|string[]
     */
    public function getBusinessCode()
    {
        $millisecond = $this->getMillisecond();
        return mb_strtoupper(md5(uniqid($millisecond . mt_rand())));
    }

}
