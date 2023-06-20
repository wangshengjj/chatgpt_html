<?php

namespace Weixin;

use think\facade\Cache;

class Weixin
{
    private $appid;
    private $appsecret;

    public function __construct($appid, $appsecret)
    {
        $this->appid = $appid;
        $this->appsecret = $appsecret;
    }

    public function getOauthInfo($code = '')
    {
        if (empty($code)) {
            $url = "https://" . $_SERVER['HTTP_HOST'] . "/api.php?{$_SERVER['QUERY_STRING']}";
            $forward = $this->getOauthUserInfoUrl(urlencode($url));
            header('Location: ' . $forward);
            exit;
        }
        $url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid={$this->appid}&secret={$this->appsecret}&code={$code}&grant_type=authorization_code";

        $response = $this->httpRequest($url);
        $result = @json_decode($response['content'], true);
        if ($this->is_error($response)) {
            return [
                'errno' => $result['errcode'],
                'message' => $this->errorCode($result['errcode'])
            ];
        }
        if (empty($result)) {
            return [
                'errno' => 1,
                'message' => '接口调用失败, 元数据:' . $response['meta']
            ];
        } elseif (!empty($result['errcode'])) {
            return [
                'errno' => $result['errcode'],
                'message' => $result['errmsg']
            ];
        }

        return $result;
    }

    /**
     * @param array $param
     * @return array|bool
     * [
     *  'openid' => '',
     *  'template_id' => '',
     *  'data' => [],
     *  'url' => '',
     *  'topcolor' => '#FF683F',
     *  'wxapp' => [
     *      'appid' => '',
     *      'pagepath' => ''
     *  ]
     * ]
     */
    public function sendTplNotice($param = [])
    {
        if (empty($param['openid'])) {
            return [
                'errno' => 1,
                'message' => 'openid不能为空'
            ];
        }
        if (empty($param['template_id'])) {
            return [
                'errno' => 1,
                'message' => '模板ID不能为空'
            ];
        }
        if (empty($param['data']) || !is_array($param['data'])) {
            return [
                'errno' => 1,
                'message' => '请根据模板规则完善消息内容'
            ];
        }
        $accessToken = $this->getAccessToken();
        if (is_array($accessToken)) {
            return [
                'errno' => 1,
                'message' => $accessToken['message']
            ];
        }

        $postData = [
            'touser' => $param['openid'],
            'template_id' => trim($param['template_id']),
            'url' => trim($param['url']),
            'topcolor' => isset($param['topcolor']) ? trim($param['topcolor']) : '#FF683F',
            'data' => $param['data']
        ];
        if (!empty($param['wxapp'])) {
            $postData['miniprogram'] = $param['wxapp'];
        }
        $postUrl = "https://api.weixin.qq.com/cgi-bin/message/template/send?access_token={$accessToken}";
        $result = $this->httpRequest($postUrl, json_encode($postData));
        $result = json_decode($result, true);
        if ($this->is_error($result)) {
            return [
                'errno' => 1,
                'message' => isset($result['errmsg']) ? $result['errmsg'] : 'sendTplNotice接口调用失败'
            ];
        }

        return true;
    }

    public function getAccessToken()
    {
        $accessToken = Cache::get('weixinAccessToken');
        if (!empty($accessToken)) {
            return $accessToken;
        }

        if (empty($this->appid) || empty($this->appsecret)) {
            return [
                'errno' => 1,
                'message' => '未填写公众号的 appid 或 appsecret'
            ];
        }
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$this->appid}&secret={$this->appsecret}";
        $content = $this->httpRequest($url);
        $content = @json_decode($content, true);
        if (isset($content['access_token'])) {
            Cache::set('wxappAccessToken', $content['access_token'], $content['expires_in'] - 200);
            return $content['access_token'];
        } else {
            return [
                'errno' => 1,
                'message' => 'access_token获取失败'
            ];
        }
    }

    public function getOauthCodeUrl($callback, $state = '')
    {
        return "https://open.weixin.qq.com/connect/oauth2/authorize?appid={$this->appid}&redirect_uri={$callback}&response_type=code&scope=snsapi_base&state={$state}#wechat_redirect";
    }

    public function getOauthUserInfoUrl($callback, $state = '')
    {
        return "https://open.weixin.qq.com/connect/oauth2/authorize?appid={$this->appid}&redirect_uri={$callback}&response_type=code&scope=snsapi_userinfo&state={$state}#wechat_redirect";
    }

    public function fansQueryInfo($uniid, $isOpen = true)
    {
        if ($isOpen) {
            $openid = $uniid;
        } else {
            exit('error');
        }
        $token = $this->getAccessToken();
        if ($this->is_error($token)) {
            return $token;
        }
        $url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token={$token}&openid={$openid}&lang=zh_CN";
        $response = $this->httpRequest($url);
        if ($this->is_error($response)) {
            return [
                'errno' => 1,
                'message' => $response['message']
            ];
        }
        preg_match('/city":"(.*)","province":"(.*)","country":"(.*)"/U', $response['content'], $reg_arr);
        $city = htmlentities(bin2hex($reg_arr[1]));
        $province = htmlentities(bin2hex($reg_arr[2]));
        $country = htmlentities(bin2hex($reg_arr[3]));
        $response['content'] = str_replace('"city":"' . $reg_arr[1] . '","province":"' . $reg_arr[2] . '","country":"' . $reg_arr[3] . '"', '"city":"' . $city . '","province":"' . $province . '","country":"' . $country . '"', $response['content']);
        $result = @json_decode($response['content'], true);
        $result['city'] = hex2bin(html_entity_decode($result['city']));
        $result['province'] = hex2bin(html_entity_decode($result['province']));
        $result['country'] = hex2bin(html_entity_decode($result['country']));
        $result['headimgurl'] = str_replace('http:', 'https:', $result['headimgurl']);
        unset($result['remark'], $result['subscribe_scene'], $result['qr_scene'], $result['qr_scene_str']);
        if (empty($result)) {
            return [
                'errno' => 1,
                'message' => 'fansQueryInfo接口调用失败'
            ];
        } elseif (!empty($result['errcode'])) {
            return [
                'errno' => $result['errcode'],
                'message' => $result['errmsg']
            ];
        }

        return $result;
    }

    /**
     * http请求
     */
    protected function httpRequest($url, $postData = "")
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        if (!empty($header)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        }
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 2);
        if ($postData != '') {
            curl_setopt($ch, CURLOPT_POST, TRUE);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData); //设置post提交数据
        }
        //判断当前是不是有post数据的发
        $output = curl_exec($ch);
        if ($output === FALSE) {
            $output = 'curl 错误信息: ' . curl_error($ch);
        }
        curl_close($ch);
        return $output;
    }

    protected function errorCode($code, $errmsg = '未知错误')
    {
        $errors = array(
            '-1' => '系统繁忙',
            '0' => '请求成功',
            '20002' => 'POST参数非法',
            '40001' => '获取access_token时AppSecret错误，或者access_token无效',
            '40002' => '不合法的凭证类型',
            '40003' => '不合法的OpenID',
            '40004' => '不合法的媒体文件类型',
            '40005' => '不合法的文件类型',
            '40006' => '不合法的文件大小',
            '40007' => '不合法的媒体文件id',
            '40008' => '不合法的消息类型',
            '40009' => '不合法的图片文件大小',
            '40010' => '不合法的语音文件大小',
            '40011' => '不合法的视频文件大小',
            '40012' => '不合法的缩略图文件大小',
            '40013' => '不合法的APPID',
            '40014' => '不合法的access_token',
            '40015' => '不合法的菜单类型',
            '40016' => '不合法的按钮个数',
            '40017' => '不合法的按钮个数',
            '40018' => '不合法的按钮名字长度',
            '40019' => '不合法的按钮KEY长度',
            '40020' => '不合法的按钮URL长度',
            '40021' => '不合法的菜单版本号',
            '40022' => '不合法的子菜单级数',
            '40023' => '不合法的子菜单按钮个数',
            '40024' => '不合法的子菜单按钮类型',
            '40025' => '不合法的子菜单按钮名字长度',
            '40026' => '不合法的子菜单按钮KEY长度',
            '40027' => '不合法的子菜单按钮URL长度',
            '40028' => '不合法的自定义菜单使用用户',
            '40029' => '不合法的oauth_code',
            '40030' => '不合法的refresh_token',
            '40031' => '不合法的openid列表',
            '40032' => '不合法的openid列表长度',
            '40033' => '不合法的请求字符，不能包含\uxxxx格式的字符',
            '40035' => '不合法的参数',
            '40036' => '不合法的 template_id 长度',
            '40037' => 'template_id不正确',
            '40038' => '不合法的请求格式',
            '40039' => '不合法的URL长度',
            '40048' => '不合法的 url 域名',
            '40050' => '不合法的分组id',
            '40051' => '分组名字不合法',
            '40054' => '不合法的子菜单按钮 url 域名',
            '40055' => '不合法的菜单按钮 url 域名',
            '40060' => '删除单篇图文时，指定的 article_idx 不合法',
            '40066' => '不合法的 url',
            '40117' => '分组名字不合法',
            '40118' => 'media_id 大小不合法',
            '40119' => 'button 类型错误',
            '40120' => 'button 类型错误',
            '40121' => '不合法的 media_id 类型',
            '40125' => '无效的appsecret',
            '40132' => '微信号不合法',
            '40137' => '不支持的图片格式',
            '40155' => '请勿添加其他公众号的主页链接',
            '40163' => 'oauth_code已使用',
            '40199' => '运单 ID 不存在',
            '41001' => '缺少access_token参数',
            '41002' => '缺少appid参数',
            '41003' => '缺少refresh_token参数',
            '41004' => '缺少secret参数',
            '41005' => '缺少多媒体文件数据',
            '41006' => '缺少media_id参数',
            '41007' => '缺少子菜单数据',
            '41008' => '缺少oauth code',
            '41009' => '缺少openid',
            '41010' => '缺失 url 参数',
            '41028' => 'form_id不正确，或者过期',
            '41029' => 'form_id已被使用',
            '41030' => 'page不正确',
            '42001' => 'access_token超时',
            '42002' => 'refresh_token超时',
            '42003' => 'oauth_code超时',
            '43001' => '需要GET请求',
            '43002' => '需要POST请求',
            '43003' => '需要HTTPS请求',
            '43004' => '需要接收者关注',
            '43005' => '需要好友关系',
            '44001' => '多媒体文件为空',
            '44002' => 'POST的数据包为空',
            '44003' => '图文消息内容为空',
            '44004' => '文本消息内容为空',
            '45001' => '多媒体文件大小超过限制',
            '45002' => '消息内容超过限制',
            '45003' => '标题字段超过限制',
            '45004' => '描述字段超过限制',
            '45005' => '链接字段超过限制',
            '45006' => '图片链接字段超过限制',
            '45007' => '语音播放时间超过限制',
            '45008' => '图文消息超过限制',
            '45009' => '接口调用超过限制',
            '45010' => '创建菜单个数超过限制',
            '45011' => 'API 调用太频繁，请稍候再试',
            '45012' => '模板大小超过限制',
            '45015' => '回复时间超过限制',
            '45016' => '系统分组，不允许修改',
            '45017' => '分组名字过长',
            '45018' => '分组数量超过上限',
            '45047' => '客服接口下行条数超过上限',
            '45056' => '创建的标签数过多，请注意不能超过100个',
            '45057' => '该标签下粉丝数超过10w，不允许直接删除',
            '45058' => '不能修改0/1/2这三个系统默认保留的标签',
            '45059' => '有粉丝身上的标签数已经超过限制',
            '45064' => '创建菜单包含未关联的小程序',
            '45065' => '24小时内不可给该组人群发该素材',
            '45072' => 'command字段取值不对',
            '45080' => '下发输入状态，需要之前30秒内跟用户有过消息交互',
            '45081' => '已经在输入状态，不可重复下发',
            '45157' => '标签名非法，请注意不能和其他标签重名',
            '45158' => '标签名长度超过30个字节',
            '45159' => '非法的标签',
            '46001' => '不存在媒体数据',
            '46002' => '不存在的菜单版本',
            '46003' => '不存在的菜单数据',
            '46004' => '不存在的用户',
            '47001' => '解析JSON/XML内容错误',
            '47501' => '参数 activity_id 错误',
            '47502' => '参数 target_state 错误',
            '47503' => '参数 version_type 错误',
            '47504' => 'activity_id 过期',
            '48001' => 'api功能未授权',
            '48002' => '粉丝拒收消息',
            '48003' => '请在微信平台开启群发功能',
            '48004' => 'api 接口被封禁',
            '48005' => 'api 禁止删除被自动回复和自定义菜单引用的素材',
            '48006' => 'api 禁止清零调用次数，因为清零次数达到上限',
            '48008' => '没有该类型消息的发送权限',
            '50001' => '用户未授权该api',
            '50002' => '用户受限，可能是违规后接口被封禁',
            '50005' => '用户未关注公众号',
            '40070' => '基本信息baseinfo中填写的库存信息SKU不合法。',
            '41011' => '必填字段不完整或不合法，参考相应接口。',
            '40056' => '无效code，请确认code长度在20个字符以内，且处于非异常状态（转赠、删除）。',
            '43009' => '无自定义SN权限，请参考开发者必读中的流程开通权限。',
            '43010' => '无储值权限,请参考开发者必读中的流程开通权限。',
            '43011' => '无积分权限,请参考开发者必读中的流程开通权限。',
            '40078' => '无效卡券，未通过审核，已被置为失效。',
            '40079' => '基本信息base_info中填写的date_info不合法或核销卡券未到生效时间。',
            '45021' => '文本字段超过长度限制，请参考相应字段说明。',
            '40080' => '卡券扩展信息cardext不合法。',
            '40097' => '基本信息base_info中填写的参数不合法。',
            '45029' => '生成码个数总和到达最大个数限制',
            '49004' => '签名错误。',
            '43012' => '无自定义cell跳转外链权限，请参考开发者必读中的申请流程开通权限。',
            '40099' => '该code已被核销。',
            '61005' => '缺少接入平台关键数据，等待微信开放平台推送数据，请十分钟后再试或是检查“授权事件接收URL”是否写错（index.php?c=account&amp;a=auth&amp;do=ticket地址中的&amp;符号容易被替换成&amp;amp;）',
            '61023' => '请重新授权接入该公众号',
            '61451' => '参数错误 (invalid parameter)',
            '61452' => '无效客服账号 (invalid kf_account)',
            '61453' => '客服帐号已存在 (kf_account exsited)',
            '61454' => '客服帐号名长度超过限制 ( 仅允许 10 个英文字符，不包括 @ 及 @ 后的公众号的微信号 )',
            '61455' => '客服帐号名包含非法字符 ( 仅允许英文 + 数字 )',
            '61456' => '客服帐号个数超过限制 (10 个客服账号 )',
            '61457' => '无效头像文件类型',
            '61450' => '系统错误',
            '61500' => '日期格式错误',
            '63001' => '部分参数为空',
            '63002' => '无效的签名',
            '65301' => '不存在此 menuid 对应的个性化菜单',
            '65302' => '没有相应的用户',
            '65303' => '没有默认菜单，不能创建个性化菜单',
            '65304' => 'MatchRule 信息为空',
            '65305' => '个性化菜单数量受限',
            '65306' => '不支持个性化菜单的帐号',
            '65307' => '个性化菜单信息为空',
            '65308' => '包含没有响应类型的 button',
            '65309' => '个性化菜单开关处于关闭状态',
            '65310' => '填写了省份或城市信息，国家信息不能为空',
            '65311' => '填写了城市信息，省份信息不能为空',
            '65312' => '不合法的国家信息',
            '65313' => '不合法的省份信息',
            '65314' => '不合法的城市信息',
            '65316' => '该公众号的菜单设置了过多的域名外跳（最多跳转到 3 个域名的链接）',
            '65317' => '不合法的 URL',
            '88000' => '没有留言权限',
            '88001' => '该图文不存在',
            '88002' => '文章存在敏感信息',
            '88003' => '精选评论数已达上限',
            '88004' => '已被用户删除，无法精选',
            '88005' => '已经回复过了',
            '88007' => '回复超过长度限制或为0',
            '88008' => '该评论不存在',
            '88010' => '获取评论数目不合法',
            '87009' => '该回复不存在',
            '87014' => '内容含有违法违规内容',
            '89002' => '没有绑定开放平台帐号',
            '89044' => '不存在该插件appid',
            '89236' => '该插件不能申请',
            '89237' => '已经添加该插件',
            '89238' => '申请或使用的插件已经达到上限',
            '89239' => '该插件不存在',
            '89240' => '无法进行此操作，只有“待确认”的申请可操作通过/拒绝',
            '89241' => '无法进行此操作，只有“已拒绝/已超时”的申请可操作删除',
            '89242' => '该appid不在申请列表内',
            '89243' => '“待确认”的申请不可删除',
            '89300' => '订单无效',
            '92000' => '该经营资质已添加，请勿重复添加',
            '92002' => '附近地点添加数量达到上线，无法继续添加',
            '92003' => '地点已被其它小程序占用',
            '92004' => '附近功能被封禁',
            '92005' => '地点正在审核中',
            '92006' => '地点正在展示小程序',
            '92007' => '地点审核失败',
            '92008' => '程序未展示在该地点',
            '93009' => '小程序未上架或不可见',
            '93010' => '地点不存在',
            '93011' => '个人类型小程序不可用',
            '93012' => '非普通类型小程序（门店小程序、小店小程序等）不可用',
            '93013' => '从腾讯地图获取地址详细信息失败',
            '93014' => '同一资质证件号重复添加',
            '9001001' => 'POST 数据参数不合法',
            '9001002' => '远端服务不可用',
            '9001003' => 'Ticket 不合法',
            '9001004' => '获取摇周边用户信息失败',
            '9001005' => '获取商户信息失败',
            '9001006' => '获取 OpenID 失败',
            '9001007' => '上传文件缺失',
            '9001008' => '上传素材的文件类型不合法',
            '9001009' => '上传素材的文件尺寸不合法',
            '9001010' => '上传失败',
            '9001020' => '帐号不合法',
            '9001021' => '已有设备激活率低于 50% ，不能新增设备',
            '9001022' => '设备申请数不合法，必须为大于 0 的数字',
            '9001023' => '已存在审核中的设备 ID 申请',
            '9001024' => '一次查询设备 ID 数量不能超过 50',
            '9001025' => '设备 ID 不合法',
            '9001026' => '页面 ID 不合法',
            '9001027' => '页面参数不合法',
            '9001028' => '一次删除页面 ID 数量不能超过 10',
            '9001029' => '页面已应用在设备中，请先解除应用关系再删除',
            '9001030' => '一次查询页面 ID 数量不能超过 50',
            '9001031' => '时间区间不合法',
            '9001032' => '保存设备与页面的绑定关系参数错误',
            '9001033' => '门店 ID 不合法',
            '9001034' => '设备备注信息过长',
            '9001035' => '设备申请参数不合法',
            '9001036' => '查询起始值 begin 不合法',
            '9300501' => '快递侧逻辑错误，详细原因需要看 delivery_resultcode',
            '9300502' => '预览模板中出现该错误，一般是waybill_data数据错误',
            '9300503' => 'delivery_id 不存在',
            '9300506' => '运单 ID 已经存在轨迹，不可取消',
            '9300507' => 'Token 不正确',
            '9300510' => 'service_type 不存在',
            '9300512' => '模板格式错误，渲染失败',
            '9300517' => 'update_type 不正确',
            '9300524' => '取消订单失败（一般为重复取消订单）',
            '9300525' => '商户未申请过审核',
            '9300526' => '字段长度不正确',
            '9300529' => '账号已绑定过',
            '9300530' => '解绑的biz_id不存在',
            '9300531' => '账号或密码错误',
            '9300532' => '绑定已提交，审核中',
            '89249' => '该主体已有任务执行中，距上次任务24h后再试',
            '89247' => '内部错误',
            '86004' => '无效微信号',
            '61070' => '法人姓名与微信号不一致',
            '89248' => '企业代码类型无效，请选择正确类型填写',
            '89250' => '未找到该任务',
            '89251' => '待法人人脸核身校验',
            '89252' => '法人&企业信息一致性校验中',
            '89253' => '缺少参数',
            '89254' => '第三方权限集不全，补全权限集全网发布后生效',
            '100001' => '已下发的模板消息法人并未确认且已超时（24h），未进行身份证校验',
            '100002' => '已下发的模板消息法人并未确认且已超时（24h），未进行人脸识别校验',
            '100003' => '已下发的模板消息法人并未确认且已超时（24h）',
            '101' => '工商数据返回：“企业已注销”',
            '102' => '工商数据返回：“企业不存在或企业信息未更新”',
            '103' => '工商数据返回：“企业法定代表人姓名不一致”',
            '104' => '工商数据返回：“企业法定代表人身份证号码不一致”',
            '105' => '法定代表人身份证号码，工商数据未更新，请5-15个工作日之后尝试',
            '1000' => '工商数据返回：“企业信息或法定代表人信息不一致”',
            '1001' => '主体创建小程序数量达到上限',
            '1002' => '主体违规命中黑名单',
            '1003' => '管理员绑定账号数量达到上限',
            '1004' => '管理员违规命中黑名单',
            '1005' => '管理员手机绑定账号数量达到上限',
            '1006' => '管理员手机号违规命中黑名单',
            '1007' => '管理员身份证创建账号数量达到上限',
            '1008' => '管理员身份证违规命中黑名单',
        );
        $code = strval($code);

        if ('40164' == $code) {
            $pattern = "((([0-9]{1,3})(\.)){3}([0-9]{1,3}))";
            preg_match($pattern, $errmsg, $out);

            $ip = !empty($out) ? $out[0] : '';

            return '错误信息: IP（' . $ip . '）不在公众号白名单';
        }

        if ($errors[$code]) {
            return $errors[$code];
        } else {
            return $errmsg;
        }
    }

    private function is_error($data)
    {
        if (!is_array($data)) {
            return false;
        }
        if (isset($data['errno']) && $data['errno']) {
            return true;
        }
        if (isset($data['errcode']) && $data['errcode']) {
            return true;
        }
        if (empty($data)) {
            return true;
        }

        return false;
    }
}
