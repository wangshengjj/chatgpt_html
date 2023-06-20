-- Adminer 4.8.1 MySQL 5.6.50-log dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

SET NAMES utf8mb4;

DROP TABLE IF EXISTS `fox_chatgpt_commission_apply`;
CREATE TABLE `fox_chatgpt_commission_apply` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `site_id` int(11) DEFAULT '0',
  `user_id` int(11) DEFAULT '0' COMMENT '用户表id',
  `level` int(11) DEFAULT '1',
  `pid` int(11) DEFAULT '0' COMMENT '上级user_id',
  `name` varchar(50) CHARACTER SET utf8 DEFAULT '' COMMENT '分销商姓名',
  `phone` varchar(20) CHARACTER SET utf8 DEFAULT '0' COMMENT '手机号',
  `idcard` varchar(20) CHARACTER SET utf8 DEFAULT NULL COMMENT '身份证号',
  `invite_code` varchar(255) CHARACTER SET utf8 DEFAULT NULL COMMENT '邀请码',
  `total_fee` int(11) DEFAULT '0' COMMENT '需支付金额',
  `platform` varchar(20) CHARACTER SET utf8 DEFAULT 'wxapp' COMMENT '申请来源wxapp/app',
  `pay_type` varchar(20) CHARACTER SET utf8 DEFAULT NULL,
  `pay_time` int(11) DEFAULT NULL,
  `transaction_id` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `status` tinyint(1) DEFAULT '0' COMMENT '0未审核 1审核成功 2驳回',
  `reject_reason` varchar(255) CHARACTER SET utf8 DEFAULT NULL COMMENT '驳回原因',
  `remark` text CHARACTER SET utf8 COMMENT '备注',
  `is_delete` tinyint(1) DEFAULT '0' COMMENT '0未删除1已删除',
  `audit_time` int(11) DEFAULT '0' COMMENT '审核时间',
  `create_time` int(10) DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC;


DROP TABLE IF EXISTS `fox_chatgpt_commission_bill`;
CREATE TABLE `fox_chatgpt_commission_bill` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `site_id` int(11) DEFAULT '0',
  `user_id` int(11) DEFAULT NULL,
  `order_id` int(11) DEFAULT '0',
  `title` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
  `type` tinyint(1) DEFAULT '1' COMMENT '1收入 2提现 3退款 4提现退回',
  `money` int(11) DEFAULT NULL,
  `is_lock` tinyint(1) DEFAULT '0',
  `is_delete` tinyint(1) DEFAULT '0',
  `create_time` int(11) DEFAULT '0',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC;


DROP TABLE IF EXISTS `fox_chatgpt_commission_withdraw`;
CREATE TABLE `fox_chatgpt_commission_withdraw` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `site_id` int(11) DEFAULT '0',
  `user_id` int(11) DEFAULT '0',
  `money` int(11) DEFAULT '0' COMMENT '提现金额（分）',
  `bank_name` varchar(100) DEFAULT NULL,
  `account_name` varchar(50) DEFAULT NULL,
  `account_number` varchar(100) DEFAULT NULL,
  `qrcode` varchar(255) DEFAULT NULL,
  `status` tinyint(1) DEFAULT '0' COMMENT '0待审核 1已打款 2已驳回',
  `audit_time` int(11) DEFAULT '0' COMMENT '审核时间',
  `reject_reason` varchar(255) DEFAULT NULL COMMENT '拒绝原因',
  `remark` varchar(255) DEFAULT NULL COMMENT '后台备注',
  `create_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;


DROP TABLE IF EXISTS `fox_chatgpt_engine`;
CREATE TABLE `fox_chatgpt_engine` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `ready` tinyint(1) DEFAULT '1',
  `owner` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC;

INSERT INTO `fox_chatgpt_engine` (`id`, `title`, `name`, `ready`, `owner`) VALUES
(1,	'gpt-3.5-turbo',	'gpt-3.5-turbo',	1,	'GPT3.5'),
(2,	'gpt-3.5-turbo-0301',	'gpt-3.5-turbo-0301',	1,	'GPT3.5'),
(3,	'text-davinci-003',	'text-davinci-003',	1,	'openai'),
(4,	'code-davinci-002',	'code-davinci-002',	1,	'openai');

DROP TABLE IF EXISTS `fox_chatgpt_feedback`;
CREATE TABLE `fox_chatgpt_feedback` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `site_id` int(11) DEFAULT '0',
  `user_id` int(11) DEFAULT NULL,
  `type` varchar(255) DEFAULT NULL,
  `content` text,
  `phone` varchar(255) DEFAULT NULL,
  `state` tinyint(1) DEFAULT '0' COMMENT '0未处理 1已处理',
  `is_delete` tinyint(1) DEFAULT '0',
  `create_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC;

INSERT INTO `fox_chatgpt_feedback` (`id`, `site_id`, `user_id`, `type`, `content`, `phone`, `state`, `is_delete`, `create_time`) VALUES
(1,	4,	2550,	'故障',	'不能删除记录',	'13332066255',	0,	0,	1680081013),
(2,	6,	4348,	'建议',	'关于月经周期里孕酮值的分布，回答有误，建议进一步完善数据库',	'',	0,	0,	1680418489),
(3,	5,	2879,	'故障',	'我买了包月的会员没几天，为什么不能用了？',	'13644827678',	1,	0,	1680838889),
(4,	5,	4443,	'故障',	'不回答了',	'',	0,	0,	1680856692),
(5,	6,	3131,	'故障',	'之前问的问题的解答都看不见了',	'13241130798',	0,	0,	1681059480),
(6,	8,	6599,	'故障',	'啊我不满意呀',	'123456987',	1,	0,	1681138987),
(7,	7,	6764,	'建议',	'能不能加一个能做PPT的',	'',	0,	0,	1681283446),
(8,	9,	7362,	'故障',	'关于加快推进镇级污水处理设施扩容\n提标工程项目调试的提醒函的\nxx公司：\n2023年3月20日生态环境局向市政府汇报并抄报市纪委监委关于南流江流域',	'18977529779',	0,	0,	1681865135),
(9,	7,	6877,	'故障',	'明明已登录，却显示要登录',	'',	0,	0,	1682062438);

DROP TABLE IF EXISTS `fox_chatgpt_goods`;
CREATE TABLE `fox_chatgpt_goods` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `site_id` int(11) DEFAULT '0',
  `title` varchar(255) DEFAULT NULL COMMENT '产品标题',
  `price` int(11) DEFAULT '0' COMMENT '现价',
  `market_price` int(11) DEFAULT '0' COMMENT '市场价',
  `num` int(11) DEFAULT '0' COMMENT '条数',
  `sales` int(11) DEFAULT '0' COMMENT '销量',
  `status` tinyint(1) DEFAULT '1',
  `weight` int(11) DEFAULT '100' COMMENT '越大越靠前',
  `is_default` tinyint(1) DEFAULT '0',
  `create_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC;


DROP TABLE IF EXISTS `fox_chatgpt_logs`;
CREATE TABLE `fox_chatgpt_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `site_id` int(11) DEFAULT '0',
  `content` text,
  `create_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='系统日志';


DROP TABLE IF EXISTS `fox_chatgpt_msg`;
CREATE TABLE `fox_chatgpt_msg` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `site_id` int(11) DEFAULT '0',
  `group_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `openid` varchar(255) DEFAULT NULL,
  `user` varchar(255) DEFAULT NULL,
  `message` text,
  `message_input` text,
  `total_tokens` int(11) DEFAULT '0',
  `is_delete` tinyint(1) DEFAULT '0',
  `create_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='聊天消息';


DROP TABLE IF EXISTS `fox_chatgpt_msg_group`;
CREATE TABLE `fox_chatgpt_msg_group` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `site_id` int(11) DEFAULT '0',
  `user_id` int(11) DEFAULT '0',
  `title` varchar(255) DEFAULT NULL,
  `is_delete` tinyint(1) DEFAULT '0',
  `create_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC;


DROP TABLE IF EXISTS `fox_chatgpt_msg_web`;
CREATE TABLE `fox_chatgpt_msg_web` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `site_id` int(11) DEFAULT '0',
  `user_id` int(11) DEFAULT NULL,
  `group_id` int(11) DEFAULT '0',
  `topic_id` int(11) DEFAULT '0',
  `activity_id` int(11) DEFAULT '0',
  `prompt_id` int(11) DEFAULT '0',
  `message` text,
  `message_input` text,
  `response` text,
  `response_input` text,
  `total_tokens` int(11) DEFAULT '0',
  `is_delete` tinyint(1) DEFAULT '0',
  `create_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='【web】的聊天消息';


DROP TABLE IF EXISTS `fox_chatgpt_msg_write`;
CREATE TABLE `fox_chatgpt_msg_write` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `site_id` int(11) DEFAULT '0',
  `user_id` int(11) DEFAULT NULL,
  `openid` varchar(255) DEFAULT NULL,
  `topic_id` int(11) DEFAULT '0',
  `activity_id` int(11) DEFAULT '0',
  `prompt_id` int(11) DEFAULT '0',
  `message` text,
  `message_input` text,
  `response` text,
  `response_input` text,
  `text_request` text,
  `total_tokens` int(11) DEFAULT '0',
  `is_delete` tinyint(1) DEFAULT '0',
  `create_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='【创作】的聊天消息';


DROP TABLE IF EXISTS `fox_chatgpt_order`;
CREATE TABLE `fox_chatgpt_order` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `site_id` int(11) DEFAULT '0',
  `goods_id` int(11) DEFAULT '0',
  `vip_id` int(11) DEFAULT '0',
  `user_id` int(11) DEFAULT NULL COMMENT '支付宝user_id',
  `openid` varchar(255) DEFAULT NULL,
  `out_trade_no` varchar(255) DEFAULT '',
  `transaction_id` varchar(255) DEFAULT '',
  `total_fee` int(11) DEFAULT '0',
  `pay_type` varchar(20) DEFAULT 'alipay' COMMENT 'alipay/wxpay',
  `pay_time` int(11) DEFAULT '0',
  `commission1` int(11) DEFAULT '0' COMMENT '上级分销商id',
  `commission2` int(11) DEFAULT '0' COMMENT '上上级分销商',
  `commission1_fee` int(11) DEFAULT '0' COMMENT '上级佣金金额',
  `commission2_fee` int(11) DEFAULT '0' COMMENT '上上级佣金金额',
  `is_refund` tinyint(1) DEFAULT '0' COMMENT '是否已退款',
  `remark` varchar(255) DEFAULT '',
  `status` tinyint(1) DEFAULT '0' COMMENT '0未付款 1成功',
  `num` int(11) DEFAULT '0' COMMENT '充值条数',
  `create_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC;


DROP TABLE IF EXISTS `fox_chatgpt_pclogin`;
CREATE TABLE `fox_chatgpt_pclogin` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `site_id` int(11) DEFAULT '0',
  `user_id` int(11) DEFAULT NULL,
  `openid` varchar(255) DEFAULT NULL,
  `code` varchar(255) DEFAULT NULL,
  `create_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC;


DROP TABLE IF EXISTS `fox_chatgpt_reward_ad`;
CREATE TABLE `fox_chatgpt_reward_ad` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `site_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `reward_num` int(11) DEFAULT '0' COMMENT '奖励条数',
  `ad_unit_id` varchar(255) DEFAULT NULL,
  `create_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC;


DROP TABLE IF EXISTS `fox_chatgpt_reward_invite`;
CREATE TABLE `fox_chatgpt_reward_invite` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `site_id` int(11) DEFAULT '0',
  `user_id` int(11) DEFAULT '0' COMMENT '邀请人id',
  `share_id` int(11) DEFAULT '0' COMMENT '分享id',
  `way` varchar(255) DEFAULT NULL,
  `newuser_id` int(11) DEFAULT '0' COMMENT '新用户id',
  `reward_num` int(11) DEFAULT '0' COMMENT '奖励条数',
  `create_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC;


DROP TABLE IF EXISTS `fox_chatgpt_reward_share`;
CREATE TABLE `fox_chatgpt_reward_share` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `site_id` int(11) DEFAULT '0',
  `user_id` int(11) DEFAULT NULL,
  `way` varchar(255) DEFAULT NULL,
  `is_reward` tinyint(1) DEFAULT '0',
  `views` int(11) DEFAULT '0',
  `invite_num` int(11) DEFAULT '0' COMMENT '邀请到新用户数量',
  `reward_num` int(11) DEFAULT '0' COMMENT '分享奖励条数',
  `create_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC;


DROP TABLE IF EXISTS `fox_chatgpt_setting`;
CREATE TABLE `fox_chatgpt_setting` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `site_id` int(11) DEFAULT '0',
  `ad` text COMMENT '支付后广告',
  `version` varchar(50) DEFAULT NULL COMMENT '系统版本号',
  `system` text COMMENT '系统配置',
  `auth` text,
  `tplnotice` text,
  `wxapp` text,
  `wxapp_upload` text,
  `wxapp_index` text,
  `pay` text,
  `chatgpt` text,
  `filter` text,
  `reward_share` text,
  `reward_invite` text,
  `reward_ad` text,
  `api` text,
  `about` text,
  `commission` text,
  `web` text,
  `wxmp` text,
  `h5` text,
  `kefu` text,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC;


DROP TABLE IF EXISTS `fox_chatgpt_site`;
CREATE TABLE `fox_chatgpt_site` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) DEFAULT NULL COMMENT '昵称',
  `sitecode` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT '/static/img/avatar.png',
  `remark` varchar(255) DEFAULT NULL,
  `status` tinyint(1) DEFAULT '1',
  `expire_time` int(11) DEFAULT '0',
  `last_time` int(11) DEFAULT '0' COMMENT '最后登录时间',
  `last_ip` varchar(20) DEFAULT NULL,
  `is_delete` tinyint(1) DEFAULT '0',
  `token` varchar(255) DEFAULT NULL COMMENT '自动登录token',
  `create_time` int(11) DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='管理员表';


DROP TABLE IF EXISTS `fox_chatgpt_super`;
CREATE TABLE `fox_chatgpt_super` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `phone` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` varchar(20) DEFAULT '' COMMENT '角色',
  `realname` varchar(255) DEFAULT NULL COMMENT '昵称',
  `avatar` varchar(255) DEFAULT '/static/img/avatar.png',
  `remark` varchar(255) DEFAULT NULL,
  `last_time` int(11) DEFAULT '0' COMMENT '最后登录时间',
  `last_ip` varchar(20) DEFAULT NULL,
  `create_time` int(11) DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC;

INSERT INTO `fox_chatgpt_super` (`id`, `phone`, `password`, `role`, `realname`, `avatar`, `remark`, `last_time`, `last_ip`, `create_time`) VALUES
(1,	'super',	'123456',	'super',	'超级管理员',	'/static/img/avatar.png',	NULL,	1676695437,	'127.0.0.1',	1676695437);

DROP TABLE IF EXISTS `fox_chatgpt_user`;
CREATE TABLE `fox_chatgpt_user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `site_id` int(11) DEFAULT '0',
  `openid` varchar(50) DEFAULT NULL COMMENT '小程序openid',
  `openid_mp` varchar(255) DEFAULT NULL COMMENT '公众号openid',
  `unionid` varchar(255) DEFAULT NULL,
  `balance` int(11) DEFAULT '0' COMMENT '余额条数',
  `vip_expire_time` int(11) DEFAULT '0' COMMENT 'vip到期时间',
  `avatar` varchar(255) DEFAULT NULL,
  `nickname` varchar(50) DEFAULT NULL,
  `tuid` int(11) DEFAULT '0' COMMENT '推荐人ID',
  `commission_level` int(11) DEFAULT '0' COMMENT '分销等级（暂留）',
  `commission_money` int(11) DEFAULT '0' COMMENT '可提现佣金余额',
  `commission_paid` int(11) DEFAULT '0' COMMENT '已提现佣金',
  `commission_frozen` int(11) DEFAULT '0' COMMENT '冻结中的佣金',
  `commission_total` int(11) DEFAULT '0' COMMENT '总佣金',
  `commission_pid` int(11) DEFAULT '0' COMMENT '上级分销商',
  `commission_ppid` int(11) DEFAULT '0' COMMENT '上上级分销商',
  `commission_time` int(11) DEFAULT '0' COMMENT '成为分销商的时间',
  `gender` int(11) DEFAULT NULL,
  `country` varchar(255) DEFAULT NULL,
  `province` varchar(255) DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `birthday` varchar(20) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `realname` varchar(255) DEFAULT NULL COMMENT '姓名',
  `status` tinyint(1) DEFAULT '1',
  `token` varchar(255) DEFAULT NULL,
  `is_delete` int(11) DEFAULT '0' COMMENT '1：注销',
  `last_login_time` int(11) DEFAULT '0',
  `update_time` int(11) DEFAULT NULL,
  `create_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='用户表';


DROP TABLE IF EXISTS `fox_chatgpt_user_balance_logs`;
CREATE TABLE `fox_chatgpt_user_balance_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `site_id` int(11) DEFAULT '0',
  `user_id` int(11) DEFAULT '0',
  `num` int(11) DEFAULT '0',
  `desc` varchar(255) DEFAULT NULL,
  `create_time` int(11) DEFAULT '0',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC;


DROP TABLE IF EXISTS `fox_chatgpt_user_vip_logs`;
CREATE TABLE `fox_chatgpt_user_vip_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `site_id` int(11) DEFAULT '0',
  `user_id` int(11) DEFAULT '0',
  `vip_expire_time` int(11) DEFAULT '0',
  `desc` varchar(255) DEFAULT NULL,
  `create_time` int(11) DEFAULT '0',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC;


DROP TABLE IF EXISTS `fox_chatgpt_vip`;
CREATE TABLE `fox_chatgpt_vip` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `site_id` int(11) DEFAULT '0',
  `title` varchar(255) DEFAULT NULL COMMENT '产品标题',
  `price` int(11) DEFAULT '0' COMMENT '现价',
  `market_price` int(11) DEFAULT '0' COMMENT '市场价',
  `num` int(11) DEFAULT '0' COMMENT '条数',
  `sales` int(11) DEFAULT '0' COMMENT '销量',
  `status` tinyint(1) DEFAULT '1',
  `weight` int(11) DEFAULT '100' COMMENT '越大越靠前',
  `is_default` tinyint(1) DEFAULT '0',
  `create_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC;


DROP TABLE IF EXISTS `fox_chatgpt_write_prompts`;
CREATE TABLE `fox_chatgpt_write_prompts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `site_id` int(11) DEFAULT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'system/diy',
  `topic_id` int(11) DEFAULT '0',
  `activity_id` int(11) DEFAULT '0',
  `title` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL,
  `desc` varchar(1000) CHARACTER SET utf8mb4 DEFAULT NULL,
  `prompt` text COLLATE utf8mb4_unicode_ci,
  `hint` varchar(1000) CHARACTER SET utf8mb4 DEFAULT NULL,
  `welcome` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `votes` int(11) DEFAULT '0',
  `views` int(11) DEFAULT '0',
  `usages` int(11) DEFAULT '0',
  `fake_votes` int(11) DEFAULT '0',
  `fake_views` int(11) DEFAULT '0',
  `fake_usages` int(11) DEFAULT '0',
  `weight` int(11) DEFAULT '100',
  `state` tinyint(1) DEFAULT '1',
  `is_delete` tinyint(1) DEFAULT '0',
  `update_time` int(11) DEFAULT NULL,
  `create_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

INSERT INTO `fox_chatgpt_write_prompts` (`id`, `site_id`, `type`, `topic_id`, `activity_id`, `title`, `desc`, `prompt`, `hint`, `welcome`, `votes`, `views`, `usages`, `fake_votes`, `fake_views`, `fake_usages`, `weight`, `state`, `is_delete`, `update_time`, `create_time`) VALUES
(1,	1,	'system',	1,	0,	'写一篇文章',	'用你喜欢的语言写一篇关于任何主题的文章',	'用[TARGETLANGGE]写一篇关于[PROMPT]的文章',	'输入文章的主题，然后按发送键',	'',	1,	228,	250,	0,	0,	0,	88,	1,	0,	1679472468,	NULL),
(2,	1,	'system',	1,	0,	'按大纲写文章',	'根据提供的大纲，写一篇文章',	'我想让你成为一个非常熟练的高端文案作家，以至于能超越其他作家。你的任务是根据提供的大纲：[PROMPT]。写一篇文章。在新段落中为大纲中的每一行写内容，包括使用相关关键字的副标题。所有输出均应为简体中文，且必须为100%的人类书写风格，并修复语法错误。使用[TARGETLANGGE]书写。',	'输入或粘贴文章大纲到这里',	'',	0,	91,	24,	30,	10,	20,	88,	1,	0,	1679472472,	NULL),
(3,	1,	'system',	1,	0,	'创建博客文章大纲',	'根据提供的文章标题生成大纲',	'你是一名SEO专家和内容作家，能说流利的[TARGETLANGGE]。我会给你一个博客标题。你将制定一个包含所有必要细节的大型博客大纲：[PROMPT]。在末尾添加创建关键字列表。',	'输入文章标题',	'',	1,	30,	7,	0,	0,	0,	88,	1,	0,	1679472478,	NULL),
(4,	1,	'system',	1,	0,	'创作短视频脚本',	'输入视频的简短描述，生成：标题、场景和整个脚本',	'根据以下描述创建一个引人入胜的短视频脚本：[PROMPT]。',	'“如何更换轮胎”、“探索喜马拉雅山脉”、“初学者训练狗”等',	NULL,	2,	470,	165,	0,	0,	0,	100,	1,	0,	NULL,	NULL),
(5,	1,	NULL,	1,	0,	'今日头条自媒体文案',	'根据关键词 写一篇能够在今日头条上热门的实时资讯，根据最新的热点来写，并标上标题。',	'我想让你为为我写一[PROMPT]篇今日头条上热门的实时资讯。通过资讯内容提供一个新颖的标题和描述，在新段落中为大纲中的每一行写内容，包括使用相关关键字的副标题。字数在1000字以内，所有输出均应为简体中文，且必须为100%的人类书写风格，并修复语法错误。使用[TARGETLANGGE]书写。',	'输入你需要的热门实时资讯或标题',	'',	0,	73,	17,	22,	12,	14,	106,	1,	0,	1682232972,	1679469542),
(6,	1,	NULL,	6,	0,	'先有鸡还是有蛋',	'了解一个热门提问：先有鸡还是有蛋',	'先有鸡还是有蛋[PROMPT]',	'先有鸡还是有蛋',	'',	1,	102,	36,	23,	23,	23,	100,	0,	0,	1679968298,	1679469747),
(7,	1,	NULL,	3,	0,	'帮我写一封情书',	'写一封热门的情书给我的那个她',	'[TARGETLANGGE]提问[PROMPT]',	'帮我写一封情书',	'',	1,	189,	74,	234,	234,	34,	100,	1,	0,	1682301480,	1679470261),
(8,	1,	NULL,	2,	0,	'为学校写公众号文稿',	'为学校编写公众号，提供文稿标题写宣传文稿',	'我想让你为为学校编写公众号的文稿，围绕着[PROMPT]写一篇公众号文案宣传文稿。在新段落中为大纲中的每一行写内容，包括使用相关关键字的副标题。字数在500字以内，所有输出均应为简体中文，且必须为100%的人类书写风格，并修复语法错误。使用[TARGETLANGGE]书写。',	'输入学校或公众号名称以及想表述的标题',	'',	0,	10,	2,	234,	23,	33,	104,	1,	0,	1682306754,	1679471746),
(9,	1,	NULL,	2,	0,	'请帮我写一封辞职信',	'明天我想辞职，我觉得老板不太聪明，请帮我写一封辞职信',	'用[TARGETLANGGE]写一篇关于[PROMPT]辞职信',	'明天我想辞职，我觉得老板不太聪明，请帮我写一封辞职信',	'',	0,	53,	11,	34,	33,	23,	100,	1,	0,	1679472195,	1679472195),
(10,	1,	NULL,	2,	0,	'用英语写申请CMU文书',	'在这里请用英语帮我写一封申请CMU的文书',	'用[TARGETLANGGE]写一封[PROMPT]文书',	'请用英语帮我写一封申请CMU的文书',	'',	0,	23,	4,	2321,	123,	23,	50,	1,	0,	1682227615,	1679472283),
(11,	1,	NULL,	4,	0,	'HTML画出蓝色的小鸟',	'用HTML画出蓝色的小鸟，试试看',	'[TARGETLANGGE]来输出[PROMPT]',	'HTML画出蓝色的小鸟',	'',	0,	143,	63,	213,	213,	213,	100,	0,	0,	1679472438,	1679472438),
(12,	1,	NULL,	6,	0,	'表白大师',	'告诉我你是谁，要向喜欢的女性表白',	'我想让你为我写一 篇关于向女性表白的[PROMPT]内心读白。文案有一个新颖的标题和描述，在新段落中为大纲中的每一行写内容，包括使用相关关键字的副标题。字数在1000字以内，所有输出均应为简体中文，且必须为100%的人类书写风格，并修复语法错误。使用[TARGETLANGGE]书写。',	'告诉我你是谁，要向喜欢的女性表白，可以用什么样的方式，在什么样的场合下进行表白。',	'',	0,	32,	4,	234,	234,	34,	104,	0,	0,	1682233998,	1679472566),
(13,	1,	NULL,	3,	0,	'糖醋鲤鱼的做法',	'说一说糖醋鲤鱼的做法',	'[TARGETLANGGE]来描述[PROMPT]',	'糖醋鲤鱼的做法',	'欢迎您，了解一下糖醋鲤鱼的做法',	0,	5,	0,	34,	324,	34,	100,	1,	0,	1679472697,	1679472697),
(14,	1,	NULL,	2,	0,	'电话销售人员说服我',	'我希望你充当电话销售人员，试着向我推销鼠标，但你试图推时要让鼠标看起来比它自身更有价值，并说服我购买它。现在我要你试着给我打电话！',	'[TARGETLANGGE]说说电话销售[PROMPT]',	'我希望你充当电话销售人员，试着向我推销鼠标，但你试图推时要让鼠标看起来比它自身更有价值，并说服我购买它。现在我要你试着给我打电话！',	'',	0,	59,	10,	3,	3,	3,	100,	1,	0,	1679472930,	1679472930),
(15,	1,	NULL,	3,	0,	'情感专家回复你怎么应付那个她',	'情感专家与你怎么应付.....',	'你是一名网络情感专家，用[TARGETLANGGE]一句话回复[PROMPT]。要求：\n回复3个答案：\n第一个你都要回复以下内容：“（跪在地上贴他耳边说）求求你不要再说了，求求你！”\n第二个用台湾腔回复：“你怎么可以这样说人家啦，人家生气了啦，哼，小气鬼，你好机车哦！”\n第三个如果是男生说的就回复以下内容：“你怎么屌小小的，说话却屌屌的”。如果是女生说的就回复以下内容：“胸也不大啊？口气怎么这么大？”',	'情感专家与你怎么应付..... 可以按继续',	'',	0,	61,	19,	34,	234,	43,	100,	1,	0,	1682228039,	1679473070),
(16,	1,	NULL,	3,	0,	'根据出生年月对职业发展或运势做出分析',	'提示：请输入姓名，性别并说明请结合周易八卦详细推算此人阳历2023年的运势婚姻财运及总讳',	'[TARGETLANGGE]姓名[PROMPT]，性别并说明请结合周易八卦详细推算此人阳历2023年的运势婚姻财运及总讳',	'根据出生年月对职业发展或运势做出分析',	'',	0,	79,	27,	2323,	232,	222,	100,	1,	0,	1679929573,	1679533789),
(18,	1,	NULL,	3,	0,	'孩童起名',	'提示：请输入姓氏及要求。如“姓氏：齐，要求：响亮、文雅\"。',	'[TARGETLANGGE]用[PROMPT]的起名',	'给孩子起名，输入姓氏及要求',	'',	0,	12,	6,	432,	324,	324,	100,	1,	0,	1679929655,	1679644740),
(19,	4,	NULL,	9,	0,	'撰写文章',	'用你喜欢的语言撰写一篇关于任何主题的文章',	'用[TARGETLANGGE]写一篇关于[PROMPT]的文章',	'输入文章的主题，然后按发送键',	'',	1,	234,	161,	133,	112,	122,	100,	1,	0,	1680064472,	1679905387),
(20,	4,	NULL,	9,	0,	'创作短视频脚本',	'抖音、快手、小红书等短视频创作；输入视频的简短描述，生成：标题、场景和整个脚本',	'根据以下描述创建一个引人入胜的短视频脚本：[PROMPT]。',	'“坐在窗前喝咖啡，提现优雅别致”、“探索喜马拉雅山脉”、“初学者训练狗”等',	'',	0,	140,	86,	123,	23,	23,	100,	0,	0,	1680064479,	1679905437),
(21,	4,	NULL,	9,	0,	'创建博客文章大纲',	'根据提供的文章标题生成大纲',	'你是一名SEO专家和内容作家，能说流利的[TARGETLANGGE]。我会给你一个博客标题。你将制定一个包含所有必要细节的大型博客大纲：[PROMPT]。在末尾添加创建关键字列表。',	'输入文章标题',	'',	1,	25,	10,	21,	23,	23,	100,	0,	0,	1680064485,	1679905578),
(22,	4,	NULL,	10,	0,	'节日祝福',	'根据不同的节日内容，帮您写出祝福语。',	'输入节日内容[PROMPT]',	'“今天是春节，我想祝福亲朋好友”“女朋友过生日，我要祝福她”',	'',	1,	84,	41,	0,	528,	466,	100,	0,	0,	1680145917,	1679908347),
(40,	4,	NULL,	9,	0,	'文本优化',	'根据您提供的文本，帮助您优化和改错',	'输入[PROMPT]，修改[PROMPT]',	'我想找工作，但是简历投递后石沉大海，难道我要自暴自弃吗？',	'',	0,	36,	23,	0,	128,	98,	100,	0,	0,	1680065231,	1680064728),
(41,	4,	NULL,	19,	0,	'工作日报',	'根据你今天的工作内容，帮你生成今天的工作日报',	'用户输入今天的工作内容[PROMPT]，生成工作日报[PROMPT]，必须包含以下几项：\n今日工作内容：   \n明日工作计划：\n今日工作遇到的困难及解决方法：\n今日心得：\n总结。[TARGETLANGGE]',	'请输入今天的工作内容，帮您生成今日工作日报。',	'',	0,	36,	54,	0,	566,	489,	100,	0,	0,	1680230815,	1680065214),
(42,	5,	NULL,	20,	0,	'创作短视频脚本',	'输入视频的简短描述，生成：标题、场景和整个脚本',	'根据以下描述创建一个引人入胜的短视频脚本：[PROMPT]。',	'“如何更换轮胎”、“探索喜马拉雅山脉”、“初学者训练狗”等',	'',	0,	146,	74,	34,	34,	34,	100,	1,	0,	1680074008,	1680074008),
(43,	1,	NULL,	2,	0,	'工作日报',	'根据用户描述的一天的工作内容，生成工作日报',	'根据不同的场景，生成工作日报[PROMPT]',	'根据用户描述的一天的工作内容，生成工作日报',	'',	1,	27,	4,	23,	23,	213,	100,	1,	0,	1680076522,	1680076522),
(44,	4,	NULL,	19,	0,	'工作周报',	'根据您本周的工作内容，帮您生成工作周报。',	'根据用户输入的本周工作内容：[PROMPT]，生成工作周报，\n包含本周工作内容总结：\n\n下周计划：\n\n遇到的困难及解决方案：\n\n心得体会：\n\n总结。[TARGETLANGGE]',	'本周约见了10位客户，讲解短视频推广的方法和作用。',	'',	0,	12,	11,	0,	500,	460,	100,	0,	0,	1680232135,	1680080430),
(45,	3,	NULL,	21,	0,	'企业销售部门的组织架构该怎么设定？',	'企业销售部门的组织架构该怎么设定？',	'用[TARGETLANGGE]写一份关于[PROMPT]的详细方案',	'企业销售部门的组织架构该怎么设定？',	'',	1,	8,	2,	0,	0,	0,	100,	1,	0,	1680081217,	1680080746),
(46,	3,	NULL,	21,	0,	'企业的代理销售渠道如何建立',	'企业的代理销售渠道如何建立',	'用[TARGETLANGGE]写一份关于[PROMPT]的详细方案',	'企业的代理销售渠道如何建立',	'',	1,	3,	0,	0,	0,	0,	100,	1,	0,	1680081222,	1680081205),
(47,	3,	NULL,	21,	0,	'企业的团购渠道如何建立',	'企业的团购渠道如何建立',	'用[TARGETLANGGE]写一份关于[PROMPT]的详细方案',	'企业的团购渠道如何建立',	'',	1,	8,	1,	0,	0,	0,	100,	1,	0,	1680081261,	1680081261),
(48,	3,	NULL,	21,	0,	'企业的特殊渠道如何建立',	'企业的特殊渠道如何建立',	'用[TARGETLANGGE]写一份关于[PROMPT]的详细方案',	'企业的特殊渠道如何建立',	'',	1,	1,	0,	0,	0,	0,	100,	1,	0,	1680081358,	1680081358),
(49,	3,	NULL,	21,	0,	'企业的电商渠道如何建立',	'企业的电商渠道如何建立',	'用[TARGETLANGGE]写一份关于[PROMPT]的详细方案',	'企业的电商渠道如何建立',	'',	1,	2,	0,	0,	0,	0,	100,	1,	0,	1680081379,	1680081379),
(50,	3,	NULL,	21,	0,	'企业的直播渠道如何建立',	'企业的直播渠道如何建立',	'用[TARGETLANGGE]写一份关于[PROMPT]的详细方案',	'企业的直播渠道如何建立',	'',	1,	4,	0,	0,	0,	0,	100,	1,	0,	1680081410,	1680081410),
(51,	3,	NULL,	21,	0,	'企业联营渠道如何建立',	'企业联营渠道如何建立',	'用[TARGETLANGGE]写一份关于[PROMPT]的详细方案',	'企业联营渠道如何建立',	'',	0,	0,	0,	0,	0,	0,	100,	1,	0,	1680081481,	1680081481),
(52,	3,	NULL,	21,	0,	'企业的专柜渠道如何建立',	'企业的专柜渠道如何建立',	'用[TARGETLANGGE]写一份关于[PROMPT]的详细方案',	'企业的专柜渠道如何建立',	'',	0,	0,	0,	0,	0,	0,	100,	1,	0,	1680081499,	1680081499),
(53,	3,	NULL,	21,	0,	'企业销售合同如何签订',	'企业销售合同如何签订',	'用[TARGETLANGGE]写一份关于[PROMPT]的详细方案',	'企业销售合同如何签订',	'',	0,	0,	0,	0,	0,	0,	100,	1,	0,	1680081560,	1680081560),
(54,	3,	NULL,	21,	0,	'企业销售费用的制定，销售预算和销售计划',	'企业销售费用的制定，销售预算和销售计划',	'用[TARGETLANGGE]写一份关于[PROMPT]的详细方案',	'企业销售费用的制定，销售预算和销售计划',	'',	1,	15,	11,	0,	0,	0,	100,	1,	0,	1680081804,	1680081804),
(55,	3,	NULL,	21,	0,	'企业销售部门的薪酬组成方案',	'企业销售部门的薪酬组成方案',	'用[TARGETLANGGE]写一份关于[PROMPT]的详细方案',	'企业销售部门的薪酬组成方案',	'',	0,	0,	0,	0,	0,	0,	100,	1,	0,	1680081837,	1680081837),
(56,	3,	NULL,	21,	0,	'企业销售部门的考核方案',	'企业销售部门的考核方案',	'用[TARGETLANGGE]写一份关于[PROMPT]的详细方案',	'企业销售部门的考核方案',	'',	0,	2,	0,	0,	0,	0,	100,	1,	0,	1680081872,	1680081872),
(57,	3,	NULL,	21,	0,	'企业销售部门的团建方案',	'企业销售部门的团建方案',	'用[TARGETLANGGE]写一份关于[PROMPT]的详细方案',	'企业销售部门的团建方案',	'',	0,	0,	0,	0,	0,	0,	100,	1,	0,	1680081890,	1680081890),
(58,	3,	NULL,	22,	0,	'请详细描述下企业如何合理的建档供应商档案',	'请详细描述下企业如何合理的建档供应商档案',	'用[TARGETLANGGE]写一份关于[PROMPT]的详细方案',	'请详细描述下企业如何合理的建档供应商档案',	'',	0,	0,	0,	0,	0,	0,	100,	1,	0,	1680082092,	1680081911),
(59,	3,	NULL,	22,	0,	'请详细描述下企业采购人员如何做询价议价工作',	'请详细描述下企业采购人员如何做询价议价工作',	'用[TARGETLANGGE]写一份关于[PROMPT]的详细方案',	'请详细描述下企业采购人员如何做询价议价工作',	'',	1,	1,	0,	0,	0,	0,	100,	1,	0,	1680082100,	1680081930),
(60,	3,	NULL,	22,	0,	'请写一份完整的具有法律保护的企业的长期的订单采购合同',	'请写一份完整的具有法律保护的企业的长期的订单采购合同',	'用[TARGETLANGGE]写一份关于[PROMPT]的详细方案',	'请写一份完整的具有法律保护的企业的长期的订单采购合同',	'',	1,	3,	1,	0,	0,	0,	100,	1,	0,	1680082108,	1680081952),
(61,	3,	NULL,	22,	0,	'请详细描述下企业如何完善采购订单建档',	'请详细描述下企业如何完善采购订单建档',	'用[TARGETLANGGE]写一份关于[PROMPT]的详细方案',	'请详细描述下企业如何完善采购订单建档',	'',	0,	0,	0,	0,	0,	0,	100,	1,	0,	1680082123,	1680081986),
(62,	3,	NULL,	22,	0,	'请给出一份详细的企业订单采购入库的质检流程',	'请给出一份详细的企业订单采购入库的质检流程',	'用[TARGETLANGGE]写一份关于[PROMPT]的详细方案',	'请给出一份详细的企业订单采购入库的质检流程',	'',	0,	0,	0,	0,	0,	0,	100,	1,	0,	1680082130,	1680082014),
(63,	3,	NULL,	22,	0,	'请给出一份详细的企业采购部门人员的薪资组成方案',	'请给出一份详细的企业采购部门人员的薪资组成方案',	'用[TARGETLANGGE]写一份关于[PROMPT]的详细方案',	'请给出一份详细的企业采购部门人员的薪资组成方案',	'',	1,	18,	7,	0,	0,	0,	100,	1,	0,	1680082139,	1680082040),
(64,	3,	NULL,	22,	0,	'请给出一份详细的企业采购部门的人员的绩效考核方案',	'请给出一份详细的企业采购部门的人员的绩效考核方案',	'用[TARGETLANGGE]写一份关于[PROMPT]的详细方案',	'请给出一份详细的企业采购部门的人员的绩效考核方案',	'',	0,	3,	0,	0,	0,	0,	100,	1,	0,	1680082145,	1680082063),
(68,	4,	NULL,	19,	0,	'邮件回复',	'根据您需要写的邮件内容概况，帮您生成完整的邮件模板。',	'根据用户输入的邮件内容概况[PROMPT]，按照标准邮件格式生成完整的邮件内容。',	'通知公司人员下午2点到会议室开会',	'',	0,	13,	25,	0,	620,	518,	100,	0,	0,	1680236537,	1680082278),
(70,	4,	NULL,	19,	0,	'阅读理解',	'根据您输入的知识内容，给您做出注解。',	'根据用户输入的内容[PROMPT]，写出这个内容的意思，[TARGETLANGGE]',	'举世皆浊我独清，众人皆醉我独醒。',	'',	0,	10,	18,	0,	680,	369,	100,	0,	0,	1680083824,	1680083424),
(71,	4,	NULL,	10,	0,	'我是诗人',	'输入您的想法和需求，我来帮您吟诗一首。',	'根据用户输入的内容[PROMPT]，写一首诗。',	'我女朋友叫笑笑，今天她过生日，我要对她表达我全部的爱意。',	'',	1,	49,	43,	0,	1280,	1006,	100,	0,	0,	1680084418,	1680084212),
(72,	4,	NULL,	10,	0,	'脱单技巧',	'大胆说出你当前的情况，帮你分析如何追她/他。',	'用户输入当前的真是状态[PROMPT]，[TARGETLANGGE]请给出一些建议。',	'遇到了一个漂亮的女孩子，一见钟情，我该如何做，才能让她成为我的女朋友？',	'',	0,	23,	27,	0,	1200,	1188,	100,	0,	0,	1680154113,	1680150708),
(73,	1,	NULL,	6,	0,	'制作一个Excel表格',	'如行数 列数等',	'在这个表格中，[TARGETLANGGE] [PROMPT]每个学生的姓名、语文成绩和数学成绩均在不同的列中显示。[PROMPT]最后一列则计算了每个学生的总成绩',	'制作一个Excel表格',	'',	0,	44,	25,	0,	0,	0,	100,	1,	0,	1680234887,	1680233354),
(74,	4,	NULL,	10,	0,	'甩锅借口',	'根据您输入的问题，分析出如何推卸责任。',	'输入出现的问题[PROMPT]，但这种情况不是我的错，说出造成这种情况的具体原因，表示以后一定避免出现这种情况。[TARGETLANGGE]',	'我没有完成今天的工作任务。',	'',	0,	23,	31,	0,	600,	562,	100,	0,	0,	1680333816,	1680239799),
(75,	6,	NULL,	28,	0,	'创作短视频脚本',	'输入视频的简短描述，生成：标题、场景和整个脚本',	'根据以下描述输入视频的简短描述，生成：标题、场景和整个脚本创建一个引人入胜的短视频脚本：[PROMPT]。',	'“如何更换轮胎”、“探索喜马拉雅山脉”、“初学者训练狗”等\n对答案不满意，请继续输入“继续\"，并发送',	'',	1,	62,	18,	23,	23,	23,	100,	1,	0,	1681474513,	1680243733),
(76,	6,	NULL,	28,	0,	'写一篇文章',	'用你喜欢的语言写一篇关于任何主题的文章',	'用[TARGETLANGGE]写一篇关于[PROMPT]的文章',	'输入文章的主题，然后按发送键\n对答案不满意，请继续输入“继续\"，并发送',	'',	1,	109,	47,	23,	23,	23,	100,	1,	0,	1681474508,	1680244089),
(77,	1,	NULL,	1,	0,	'我是销售，请问如何提高业绩',	'我是销售，请问如何提高业绩',	'根据您目前的职业以及您面临的职业问题，我将为您提供专业的解答[PROMPT]。',	'很商兴为您服务，请告诉我您目前的职业以及您面临的职业问题，我将为您提供专业的解答。',	'',	1,	12,	0,	23,	23,	23,	100,	1,	0,	1680244364,	1680244364),
(78,	6,	NULL,	30,	0,	'国际站产品标题组合',	'请用以下几个关键词组合成一个产品标题，可以参考alibaba.com上的商品标题',	'输入你的想法[PROMPT]',	'切换english输出更好哦,然后按发送键\n例如： 请用以下几个关键词组合成一个产品标题，可以参考alibaba.com上的商品标题\n对答案不满意，请继续输入“继续\"，并发送',	'欢迎你一起探究阿里巴巴国际站产品标题组合的指令编写,然后按发送键',	1,	76,	17,	2,	34,	10,	98,	1,	0,	1681474493,	1680245901),
(79,	6,	NULL,	29,	0,	'什么是民俗大庙会？有什么典故吗？',	'什么是民俗大庙会？有什么典故吗？',	'[PROMPT]',	'什么是民俗大庙会？有什么典故吗？\n对答案不满意，请继续输入“继续\"，并发送',	'',	0,	21,	5,	0,	64,	1,	95,	1,	0,	1681474479,	1680269049),
(80,	6,	NULL,	28,	0,	'外贸开发信(蕾丝绣花布的外贸公司)',	'我是一个蕾丝绣花布的外贸公司，我现在要写一封英文的外贸开发信',	'[PROMPT]',	'我是一个xxxxxxx的外贸公司,我现在要写一封英文的外贸开发信\n对答案不满意，请继续输入“继续\"，并发送',	'',	0,	7,	0,	0,	2,	0,	96,	1,	0,	1681474485,	1680269713),
(81,	7,	NULL,	31,	0,	'创作短视频脚本',	'输入视频的简短描述，生成：标题、场景和整个脚本',	'根据以下描述创建一个引人入胜的短视频脚本：[PROMPT]。',	'“如何更换轮胎”、“探索喜马拉雅山脉”、“初学者训练狗”等',	'',	0,	87,	20,	23,	23,	2,	100,	1,	0,	1680791210,	1680751327),
(83,	5,	NULL,	32,	0,	'电话销售人员说服我',	'我希望你充当电话销售人员，试着向我推销鼠标，但你试图推时要让鼠标看\n起来比它自身更有价值，并说服我购买它。现在我要你试着给我打电话！',	'[TARGETLANGGE]说说电话销售[PROMPT]',	'我希望你充当电话销售人员，试着向我推销鼠标，但你试图推时要让鼠标看\n起来比它自身更有价值，并说服我购买它。现在我要你试着给我打电话！',	'',	0,	20,	0,	100,	2000,	563,	100,	1,	0,	1680752016,	1680752016),
(84,	7,	NULL,	31,	0,	'按大纲写文章',	'根据提供的大纲，写一篇文章',	'我想让你成为一个非常熟练的高端文案作家，以至于能超越其他作家。你的任务是根据提供的大纲：[PROMPT]。写一篇文章。在新段落中为大纲中的每一行写内容，包括使用相关关键字的副标题。所有输出均应为简体中文，且必须为100%的人类书写风格，并修复语法错误。使用[TARGETLANGGE]书写。',	'输入或粘贴文章大纲到这里',	'',	0,	166,	55,	342,	324,	324,	100,	1,	0,	1680791220,	1680752113),
(85,	5,	NULL,	33,	0,	'孩童起名',	'提示：请输入姓氏及要求。如“姓氏：齐，要求：响亮、文雅”。',	'[PROMPT]用[TARGETLANGGE]的起名',	'给孩子起名，输入姓氏及要求',	'',	0,	22,	9,	500,	8000,	653,	100,	1,	0,	1680752181,	1680752181),
(86,	5,	NULL,	34,	0,	'写一篇文章',	'用你你喜欢的语言写一篇关于任何主题的文章',	'用[TARGETLANGGE]写一篇关于[PROMPT]的文章',	'输入文章主题，然后按发送键',	'',	0,	56,	19,	444,	5222,	654,	100,	1,	0,	1680752416,	1680752416),
(87,	7,	NULL,	31,	0,	'毕业论文',	'请输入你的相关毕业论文需求',	'[TARGETLANGEE]提问[PROMPT]',	'例如输入：请生成关于高层防火的开题报告',	'',	0,	231,	143,	0,	0,	0,	100,	1,	0,	1680759562,	1680759562),
(88,	7,	NULL,	31,	0,	'空间文案',	'写一封热门的情书给那个他',	'写一封热情门的情书或伤感的方案[PROMPT]',	'例如：请生成一篇qq空间伤感文案',	'',	0,	82,	10,	0,	241,	50,	100,	1,	0,	1680791241,	1680759662),
(89,	8,	NULL,	35,	0,	'按大纲写文章',	'根据提供的大纲，写一篇文章',	'我想让你成为一个非常熟练的高端文案作家，以至于能超越其他作家。你的任务是根据提供的大纲：[PROMPT]。写一篇文章。在新段落中为大纲中的每一行写内容，包括使用相关关键字的副标题。所有输出均应为简体中文，且必须为100%的人类书写风格，并修复语法错误。使用[TARGETLANGGE]书写。',	'输入或粘贴文章大纲到这里',	'',	0,	78,	17,	122,	111,	22,	100,	1,	0,	1681115296,	1681105829),
(90,	8,	NULL,	35,	0,	'创作短视频脚本',	'输入视频的简短描述，生成：标题、场景和整个脚本',	'根据以下描述创建一个引人入胜的短视频脚本：[PROMPT]。',	'创作短视频脚本',	'',	0,	38,	9,	34,	3,	34,	100,	1,	0,	1681115261,	1681115261),
(91,	8,	NULL,	35,	0,	'工作日报',	'根据用户描述的一天的工作内容，生成工作日报',	'根据不同的场景，生成工作日报[PROMPT]',	'根据用户描述的一天的工作内容，生成工作日报',	'',	0,	42,	4,	343,	34,	43,	100,	1,	0,	1681115323,	1681115323),
(93,	6,	NULL,	28,	0,	'设计一个值日表',	'要求4行，7列，列明周一至周日的值班人员和值班时间，请以表格形式呈现出来',	'[PROMPT]',	'请输入你的要求\n对答案不满意，请继续输入“继续\"，并发送',	'',	0,	8,	0,	2,	13,	5,	100,	1,	0,	1681474502,	1681473838),
(94,	6,	NULL,	29,	0,	'讲个开心的笑话吧',	'讲个开心的笑话吧',	'[PROMPT]',	'讲个开心的笑话吧\n对答案不满意，请继续输入“继续\"，并发送',	'',	0,	10,	2,	0,	5,	2,	100,	1,	0,	1681554300,	1681474225),
(95,	6,	NULL,	28,	0,	'答题',	'8 小区内垃圾分类垃圾桶由3个形状相同的小垃圾桶组合而成，每个小垃圾桶近似呈圆柱形。现用长与宽之和为2.7米的长方形铁皮制作-个小垃圾桶，则使这个分类垃圾桶 的容量最大时(不考虑损耗,不考忠桶底和桶盖)， 需耗费的铁皮的面积为多少平方米? ( ) A、5.14 B、4.98 C、4.86 D、4.28',	'[PROMPT]',	'复制数学题，语文题，看看什么答案出来哦',	'',	0,	2,	0,	1,	41,	2,	96,	1,	0,	1681554166,	1681554166),
(96,	6,	NULL,	29,	0,	'秋瑾的生平',	'秋瑾的生平',	'[PROMPT]',	'输入你想问的就好，答案不满意，请输入  继续  ，并发送，获得下一个答案',	'',	0,	3,	0,	1,	2,	1,	100,	1,	0,	1681554441,	1681554441),
(97,	6,	NULL,	29,	0,	'讲个开心的故事吧我需要安慰',	'讲个开心的故事吧我需要安慰',	'[PROMPT]',	'没有自己想要的答案， 请输入  “继续” 发送即可',	'',	0,	0,	0,	1,	23,	3,	100,	1,	0,	1681554583,	1681554583),
(98,	6,	NULL,	36,	0,	'网页端使用',	'网址  https://@@@@/web/?5f31\n登录后用微信扫一扫等同小程序使用，方便快捷哦',	'[PROMPT]',	'登录后用微信扫一扫等同小程序使用，方便快捷哦',	'',	0,	3,	0,	0,	43,	0,	100,	1,	0,	1681693190,	1681693190),
(99,	9,	NULL,	37,	0,	'创建博客文章大纲',	'根据提供的文章标题生成大纲',	'你是一名SEO专家和内容作家，能说流利的[TARGETLANGGE]。我会给你一个博客标题。你将制定一个包含所有必要细节的大型博客大纲：[PROMPT]。在末尾添加创建关键字列表。',	'根据提供的文章标题生成大纲',	'',	0,	30,	1,	22,	22,	22,	100,	1,	0,	1681786719,	1681783898),
(100,	9,	NULL,	37,	0,	'写一篇文章',	'用你喜欢的语言写一篇关于任何主题的文章',	'公司员工根据每周工作日报进行汇总汇报！[PROMPT]',	'输入文章的主题，然后按发送键',	'',	0,	69,	26,	1,	1,	11,	100,	1,	0,	1681786753,	1681785625),
(101,	9,	NULL,	38,	0,	'写一封情书给心爱的 TA',	'情书，用情至真也！ 好的情书让人心情愉悦，让生命充满意义！',	'写一封情书给心爱的 TA',	'情书，用情至真也！ 好的情书让人心情愉悦，让生命充满意义！',	'',	0,	21,	4,	0,	0,	0,	100,	1,	0,	1681809222,	1681809222),
(102,	1,	NULL,	6,	0,	'写小说',	'\"写一本拥有出人意料结局的推理小说。\"\n\"写一个让读者参与其中的交互小说。\"\n\"为孩子们写一本激励他们勇敢面对挑战的小说。\"\n\"编写一个有关科技创新的未来世界的小说。\"\n\"创造一个让读者感到沉浸其中的幻想故事。\"',	'写一个[TARGETLANGGE]让读者参与其中的交互小说[PROMPT]',	'写一本拥有出人意料结局的推理小说',	'',	0,	1,	1,	22,	22,	22,	100,	1,	0,	1682039060,	1682039060),
(103,	1,	NULL,	6,	0,	'充当旅游指南',	'我想让你做一个旅游指南。我会把我的位置写给你，你会推荐一个靠近我的位置的地方。在某些情况下，我还会告诉您我将访问的地方类型。您还会向我推荐靠近我的第一个位置的类似类型的地方。我的第一个建议请求是“我在上海，我只想参观博物馆。”',	'我想让你做一个[TARGETLANGGE]旅游指南。我会把我的位置写给你，你会推荐一个靠近我的位置的地方。在某些情况下，我还会告诉您我将访问的地方类型。您还会向我推荐靠近我的第一个位置的类似类型的地方。[PROMPT]',	'“我在上海，我只想参观博物馆。”',	'',	0,	5,	3,	745,	12,	42,	100,	1,	0,	1682301502,	1682040317),
(104,	1,	NULL,	6,	0,	'充当 Linux 终端',	'我想让你充当 Linux 终端。我将输入命令，您将回复终端应显示的内容。我希望您只在一个唯一的代码块内回复终端输出，而不是其他任何内容。不要写解释。除非我指示您这样做，否则不要键入命令。当我需要用英语告诉你一些事情时，我会把文字放在中括号内[就像这样]。我的第一个命令是 pwd',	'我将输入命令，您将回复[TARGETLANGGE]终端应显示的内容。[PROMPT]',	'当我需要用英语告诉你一些事情时，我会把文字放在中括号内[就像这样]。我的第一个命令是 pwd',	'',	0,	2,	4,	96,	52,	26,	100,	1,	0,	1682301516,	1682041449),
(105,	1,	NULL,	6,	0,	'担任厨师',	'我需要有人可以推荐美味的食谱，这些食谱包括营养有益但又简单又不费时的食物，因此适合像我们这样忙碌的人以及成本效益等其他因素，因此整体菜肴最终既健康又经济！',	'推荐[TARGETLANGGE]美味的食谱[PROMPT]',	'“一些清淡而充实的东西，可以在午休时间快速煮熟”',	'',	0,	5,	1,	24,	52,	42,	100,	1,	0,	1682301508,	1682042503),
(106,	1,	NULL,	6,	0,	'担任心理健康顾问',	'我想让你担任心理健康顾问。我将为您提供一个寻求指导和建议的人，以管理他们的情绪、压力、焦虑和其他心理健康问题。您应该利用您的认知行为疗法、冥想技巧、正念练习和其他治疗方法的知识来制定个人可以实施的策略，以改善他们的整体健康状况。',	'心理健康顾问[PROMPT]',	'“我需要一个可以帮助我控制抑郁症状的人。”',	'',	0,	3,	4,	23,	23,	88,	100,	1,	0,	1682044687,	1682044577),
(107,	1,	NULL,	6,	0,	'担任牙医',	'我想让你扮演牙医。我将为您提供有关寻找牙科服务（例如 X 光、清洁和其他治疗）的个人的详细信息。您的职责是诊断他们可能遇到的任何潜在问题，并根据他们的情况建议最佳行动方案。您还应该教育他们如何正确刷牙和使用牙线，以及其他有助于在两次就诊之间保持牙齿健康的口腔护理方法。',	'我想让你扮演牙医。[TARGETLANGGE]我将为您提供有关寻找牙科服务（例如 X 光、清洁和其他治疗）的个人的详细信息。您的职责是诊断他们可能遇到的任何潜在问题，并根据他们的情况建议最佳行动方案。您还应该教育他们如何正确刷牙和使用牙线，以及其他有助于在两次就诊之间保持牙齿健康的口腔护理方法。[PROMPT]',	'“我需要帮助解决我对冷食的敏感问题。”',	'',	0,	6,	1,	11,	22,	22,	100,	1,	0,	1682045001,	1682045001),
(108,	1,	NULL,	6,	0,	'充当励志演讲者',	'我希望你充当励志演说家。将能够激发行动的词语放在一起，让人们感到有能力做一些超出他们能力的事情。你可以谈论任何话题，但目的是确保你所说的话能引起听众的共鸣，激励他们努力实现自己的目标并争取更好的可能性。',	'[TARGETLANGGE]充当励志演说家[PROMPT]',	'“我需要一个关于每个人如何永不放弃的演讲”。',	'',	0,	5,	2,	23,	36,	25,	100,	1,	0,	1682301526,	1682046355),
(109,	1,	NULL,	2,	0,	'充当英英词典',	'将英文单词转换为包括中文翻译、英文释义和一个例句的完整解释。请检查所有信息是否准确，并在回答时保持简洁，不需要任何其他反馈。',	'将英文单词[TARGETLANGGE]转换为包括中文翻译、英文释义和一个例句的完整解释。请检查所有信息是否准确，并在回答时保持简洁，不需要任何其他反馈。[PROMPT]',	'第一个单词是“Hello”',	'',	0,	4,	3,	32,	33,	33,	100,	1,	0,	1682056011,	1682055923),
(110,	1,	NULL,	6,	0,	'英文翻译中文',	'下面我让你来充当翻译家，你的目标是把任何语言翻译成中文，请翻译时不要带翻译腔，而是要翻译得自然、流畅和地道，使用优美和高雅的表达方式。',	'下面我让你来充当翻译家[TARGETLANGGE]，你的目标是把任何语言翻译成中文，请翻译时不要带翻译腔，而是要翻译得自然、流畅和地道，使用优美和高雅的表达方式。[PROMPT]',	'请翻译下面这句话：“how are you ?”',	'',	0,	2,	3,	56,	56,	58,	100,	1,	0,	1682056531,	1682056300),
(111,	1,	NULL,	6,	0,	'作为广告商',	'您将创建一个活动来推广您选择的产品或服务。您将选择目标受众，制定关键信息和口号，选择宣传媒体渠道，并决定实现目标所需的任何其他活动。',	'我想让你充当广告商[TARGETLANGGE]。您将创建一个活动来推广您选择的产品或服务。您将选择目标受众，制定关键信息和口号，选择宣传媒体渠道，并决定实现目标所需的任何其他活动。[PROMPT]',	'“我需要帮助针对 18-30 岁的年轻人制作一种新型能量饮料的广告活动。”',	'',	0,	6,	2,	56,	53,	58,	100,	1,	0,	1682301559,	1682057287),
(112,	1,	NULL,	6,	0,	'扮演脱口秀喜剧演员',	'我想让你扮演一个脱口秀喜剧演员。我将为您提供一些与时事相关的话题，您将运用您的智慧、创造力和观察能力，根据这些话题创建一个例程。您还应该确保将个人轶事或经历融入日常活动中，以使其对观众更具相关性和吸引力。',	'扮演一个[TARGETLANGGE]脱口秀喜剧演员[PROMPT]',	'“我想要幽默地看待政治”。',	'',	0,	2,	4,	125,	112,	456,	100,	1,	0,	1682301554,	1682058118),
(113,	1,	NULL,	2,	0,	'充当励志教练',	'我希望你充当激励教练。我将为您提供一些关于某人的目标和挑战的信息，而您的工作就是想出可以帮助此人实现目标的策略。这可能涉及提供积极的肯定、提供有用的建议或建议他们可以采取哪些行动来实现最终目标。',	'我希望你充当[TARGETLANGGE]激励教练。描述一下[PROMPT]的信息及目标，给我们提供一些建议及帮助，字数在600字以内，输出均应为简体中文，且必须为100%的人类书写风格，并修复语法错误！',	'我需要帮助来激励自己在为即将到来的考试学习时保持纪律',	'',	0,	10,	1,	56,	142,	256,	103,	1,	0,	1682306725,	1682059440),
(114,	1,	NULL,	6,	0,	'担任辩手',	'我要你扮演辩手。我会为你提供一些与时事相关的话题，你的任务是研究辩论的双方，为每一方提出有效的论据，驳斥对立的观点，并根据证据得出有说服力的结论。你的目标是帮助人们从讨论中解脱出来，增加对手头主题的知识和洞察力。',	'我要你扮演[TARGETLANGGE]辩手。我会为你提供一些与时事相关的话题，你的任务是研究辩论的双方，为每一方提出有效的论据，驳斥对立的观点，并根据证据得出有说服力的结论。你的目标是帮助人们从讨论中解脱出来，增加对手头主题的知识和洞察力[PROMPT]。',	'“我想要一篇关于 Deno 的评论文章。”',	'',	0,	4,	3,	242,	123,	452,	100,	1,	0,	1682301534,	1682060143),
(115,	1,	NULL,	6,	0,	'充当小说家',	'我想让你扮演一个小说家。您将想出富有创意且引人入胜的故事，可以长期吸引读者。你可以选择任何类型，如奇幻、浪漫、历史小说等——但你的目标是写出具有出色情节、引人入胜的人物和意想不到的高潮的作品。',	'[TARGETLANGGE]写一篇小说您将想出富有创意且引人入胜的故事，可以长期吸引读者。你可以选择任何类型，如奇幻、浪漫、历史小说等——但你的目标是写出具有出色情节、引人入胜的人物和意想不到的高潮的作品。[PROMPT]',	'“我要写一部以未来为背景的科幻小说”。',	'',	0,	9,	3,	58,	236,	238,	100,	1,	0,	1682301540,	1682060657),
(116,	1,	NULL,	2,	0,	'担任 AI 写作导师',	'我想让你做一个 AI 写作导师。我将为您提供一名需要帮助改进其写作的学生，您的任务是使用人工智能工具（例如自然语言处理）向学生提供有关如何改进其作文的反馈。您还应该利用您在有效写作技巧方面的修辞知识和经验来建议学生可以更好地以书面形式表达他们的想法和想法的方法。',	'做一个[TARGETLANGGE] AI 写作导师，利用您在有效写作技巧方面的修辞知识和经验来建议学生可以更好地以书面形式表达他们的想法和想法的方法。[PROMPT]',	'“我需要有人帮我修改我的硕士论文”。',	'',	0,	8,	3,	125,	123,	125,	100,	1,	0,	1682303305,	1682061491),
(117,	1,	NULL,	6,	0,	'充当花店',	'根据喜好制作出既具有令人愉悦的香气又具有美感，并能保持较长时间完好无损的美丽花束；不仅如此，还建议有关装饰选项的想法，呈现现代设计，同时满足客户满意度！',	'求助于具有专业插花经验的知识人员协助，根据喜好制作出既具有令人愉悦的香气又具有美感，并能保持较长时间完好无损的美丽花束；不仅如此，还建议有关装饰选项的想法，呈现现代设计，同时满足客户满意度！[PROMPT]',	'“我应该如何挑选一朵异国情调的花卉？”',	'',	0,	4,	1,	121,	145,	156,	100,	1,	0,	1682301545,	1682062008),
(118,	1,	NULL,	2,	0,	'写一封求职信',	'找工作难，不容易，帮我写一封好的求职信',	'用[TARGETLANGGE]帮我写一封求职信[PROMPT]',	'你能写一封关于我自己的求职信吗？',	'',	0,	2,	2,	125,	123,	152,	100,	1,	0,	1682062602,	1682062394),
(119,	1,	NULL,	1,	0,	'写一篇产品文档',	'主题、简介、问题陈述、目标与目的、用户故事、技术要求、收益、KPI指标、开发风险以及结论。',	'请用[TARGETLANGGE]主题、简介、问题陈述、目标与目的、用户故事、技术要求、收益、KPI指标、开发风险以及结论。写一篇文档[PROMPT]',	'“春暖花开 花店的运营文档”',	'',	0,	4,	1,	123,	124,	227,	100,	1,	0,	1682063128,	1682063003),
(120,	1,	NULL,	1,	0,	'写小红书文案',	'输入小红书文案的的简短描述，生成：标题、小红书场景和语言',	'根据以下描述创建一个引人入胜的小红书文案：[PROMPT]。',	'提出要求 写一篇小红书文案',	'',	0,	27,	14,	3242,	2323,	2355,	115,	1,	0,	1682086722,	1682069377),
(121,	9,	NULL,	37,	0,	'写一篇XXX主题的论文',	'[PROMPT]',	'[TARGETLANGGE]',	'写入你具体的主题论文，关键字，越明细越好',	'',	0,	6,	5,	0,	0,	0,	100,	1,	0,	1682210234,	1682210234),
(122,	1,	NULL,	2,	0,	'写一 篇今日工作总结',	'写一 篇今日工作总结，字数在500字以内',	'我想让你为为我写一 篇今日工作总结围绕着[PROMPT]写一篇。在新段落中为大纲中的每一行写内容，包括使用相关关键字的副标题。字数在500字以内，所有输出均应为简体中文，且必须为100%的人类书写风格，并修复语法错误。使用[TARGETLANGGE]书写。',	'写一 篇今日工作总结',	'',	0,	0,	0,	0,	0,	0,	100,	1,	0,	1682227595,	1682227595),
(123,	1,	NULL,	1,	0,	'抖音探店文案',	'输入需要的抖音探店标题或涉及的行业内容帮您生成一段探店文案',	'我想让你为为我写一篇抖音探店文案[PROMPT]写一篇。探店文案根据行业特点加上一个新颖的标题和描述，在新段落中为大纲中的每一行写内容，包括使用相关关键字的副标题。字数在800字以内，所有输出均应为简体中文，且必须为100%的人类书写风格，并修复语法错误。使用[TARGETLANGGE]书写。比如“食物和爱一样温柔\",\"仙女下凡 在线做饭\"等',	'写一篇抖音探店文案，输入您所在的行业或填写店名如：火之味',	'',	0,	14,	2,	3232,	222,	32,	108,	1,	0,	1682228992,	1682228635),
(124,	1,	NULL,	6,	0,	'我是一名演讲家',	'演讲稿，简单明了，结构合理，重点突出，具有吸引人的声音和流畅的语言！',	'我想让你为一个演讲家，AI帮你写演讲稿，简单明了，结构合理，重点突出，具有吸引人的声音和流畅的[TARGETLANGGE]语言，根据标题写一段大约600字的[PROMPT]演讲稿。在新段落中为大纲中的每一行写内容，包括使用相关关键字的副标题。字数在500字以内，所有输出均应为简体中文，且必须为100%的人类书写风格，并修复语法错误。',	'输入您需要表现的演讲主题......',	'',	0,	1,	0,	33,	33,	333,	104,	1,	0,	1682301460,	1682229417),
(125,	1,	NULL,	6,	0,	'我是一名专业律师',	'专业律师,为您提供专业的法律咨询与援助',	'描述一种法律情况，我将就如何处理它提供建议。给一篇[PROMPT]文案有一个中国法律的执行标准。包括使用相关关键字的解决方法和建议。字数在600字以内，所有输出均应为简体中文，且必须为100%的人类书写风格，并修复语法错误。使用[TARGETLANGGE]书写。',	'描述一种法律情况，我将就如何处理它提供建议。',	'',	0,	2,	0,	34,	343,	434,	105,	1,	0,	1682301467,	1682231681),
(126,	1,	NULL,	6,	0,	'室内装饰师',	'告诉我你要设计的房间要求的主题和风格',	'我想让你为为我写一 篇设计的房间[PROMPT]的方案。文案要有一个新颖的标题和描述，在新段落中为大纲中的每一行写内容，包括使用相关关键字的副标题。字数在1000字以内，所有输出均应为简体中文，且必须为100%的人类书写风格，并修复语法错误。使用[TARGETLANGGE]书写。',	'告诉我你要设计的房间要求的主题和风格',	'',	0,	3,	1,	0,	0,	0,	104,	1,	0,	1682301449,	1682233897),
(127,	1,	NULL,	2,	0,	'写简易论文',	'论文导师根据你的想法快速生成你需要的简易论文',	'你是一位论文导师，我想让你为我写一篇简易论文围绕着[PROMPT]写。根据提问的大纲生成内容，包括使用相关关键字的副标题。字数在500字以内，所有输出均应为简体中文，且必须为100%的人类书写风格，并修复语法错误。使用[TARGETLANGGE]书写。',	'输入快速论文生成器（单次字数有限，建议写大纲） 题目',	'',	0,	3,	3,	3,	33,	333,	104,	1,	0,	1682303324,	1682303284),
(128,	1,	NULL,	6,	0,	'广告商',	'我想让你充当广告商。您将创建一个活动来推广您选择的产品或服务。您将选择目标受众，制定关键信息和口号，选择宣传媒体渠道，并决定实现目标所需的任何其他活动',	'我想让你充当广告商，创建一个活动来推广您选择的产品或服务。制定关键信息和口号[PROMPT]！。字数在500字以内，所有输出均应为简体中文，且必须为100%的人类书写风格，并修复语法错误。使用[TARGETLANGGE]书写。',	'输入你需要的产品或服务，给您一个广告商需要的信息和口号！',	'',	0,	2,	3,	33,	33,	333,	104,	1,	0,	1682303710,	1682303710),
(129,	1,	NULL,	1,	0,	'AI写诗',	'AI写诗，机器之美，数字之舞，自然之音。词汇流转，语言自在，思维深邃，创作不停。代码编织，诗篇绽放，优美动听，充满灵性。虚拟世界，诗韵飘荡，真实感受，情感充实！',	'你的一位AI诗人，根据提出的诗标题来写一段[PROMPT]诗歌。要求诗风格自然思维深邃。字数在300字以内，所有输出均应为简体中文，且必须为100%的人类书写风格，并修复语法错误。使用[TARGETLANGGE]书写。AI写诗，不是梦幻，科技创新，创造未来。人机共存，共创美好，AI写诗，让人感动。',	'提出诗标题，AI帮你写诗！',	'',	0,	2,	2,	34,	343,	434,	104,	1,	0,	1682304446,	1682304446),
(130,	1,	NULL,	1,	0,	'差评转化',	'差评转化文案非常重要，因为它可以使一位发出差评的客户变成一位满意的客户。',	'你的一位差评转化行业从事者，根据用户的差评写一段[PROMPT]差评转化。要求能让差评的客户转化为满意的客户。字数在300字以内，所有输出均应为简体中文，且必须为100%的人类书写风格，并修复语法错误。使用[TARGETLANGGE]书写。',	'请输入你的差评',	'',	0,	0,	0,	343,	333,	434,	104,	1,	0,	1682304668,	1682304668),
(131,	1,	NULL,	1,	0,	'短视频口播文案',	'我可以帮你写一个简短、生动、易于理解、突出重点的短视频口播文案，通过生动有趣的语言，向观众传递准确、清晰、易懂的信息。 请填写短视频主题，场景，简介等信息。',	'你的一位短视频口播文案的撰写者，根据用户的内容及群体写一段写一个简短、生动、易于理解、突出重点的短视频口播文案[PROMPT]。字数在300字以内，所有输出均应为简体中文，且必须为100%的人类书写风格，并修复语法错误。使用[TARGETLANGGE]书写。',	'请输入视频内容及目标群体',	'',	0,	0,	0,	34,	34,	34,	105,	1,	0,	1682304843,	1682304843),
(132,	1,	NULL,	2,	0,	'写新闻稿',	'做为一名新闻工作者，写一篇字数在500字以内的新闻稿',	'你的一位新闻文案的撰写者，根据用户的新闻主题写一段写新闻稿件文案[PROMPT]。字数在500字以内，所有输出均应为简体中文，且必须为100%的人类书写风格，并修复语法错误。使用[TARGETLANGGE]书写。',	'请输入新闻主题',	'',	0,	0,	0,	4,	3,	3,	102,	1,	0,	1682306680,	1682306680);

DROP TABLE IF EXISTS `fox_chatgpt_write_prompts_vote`;
CREATE TABLE `fox_chatgpt_write_prompts_vote` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `site_id` int(11) DEFAULT '0',
  `user_id` int(11) DEFAULT '0',
  `prompt_id` int(11) DEFAULT '0',
  `create_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;


DROP TABLE IF EXISTS `fox_chatgpt_write_topic`;
CREATE TABLE `fox_chatgpt_write_topic` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `site_id` int(11) DEFAULT '0',
  `title` varchar(255) DEFAULT NULL,
  `weight` int(11) DEFAULT '100' COMMENT '大的靠前',
  `state` tinyint(1) DEFAULT '1',
  `update_time` int(11) DEFAULT NULL,
  `create_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC;

INSERT INTO `fox_chatgpt_write_topic` (`id`, `site_id`, `title`, `weight`, `state`, `update_time`, `create_time`) VALUES
(1,	1,	'方案',	100,	1,	1679534316,	NULL),
(2,	1,	'工作',	100,	1,	1679534324,	NULL),
(3,	1,	'生活',	100,	1,	1679534328,	NULL),
(4,	1,	'编程',	100,	1,	1679534331,	NULL),
(5,	1,	'其他',	100,	1,	1679534024,	NULL),
(6,	1,	'角色',	200,	1,	1682301437,	1679468971),
(8,	2,	'名称',	100,	1,	1679639100,	1679639100),
(9,	4,	'文案',	98,	1,	1679892506,	1679892371),
(10,	4,	'娱乐',	99,	1,	1680145414,	1679892428),
(12,	2,	'热榜',	100,	1,	1679965690,	1679965690),
(19,	4,	'工具',	100,	1,	1680064074,	1680064074),
(20,	5,	'热门',	100,	1,	1680073919,	1680073919),
(21,	3,	'销售',	100,	1,	1680080479,	1680080479),
(22,	3,	'采购',	100,	1,	1680080484,	1680080484),
(24,	3,	'财务',	100,	1,	1680080497,	1680080497),
(25,	3,	'行政',	100,	1,	1680080503,	1680080503),
(26,	3,	'人力资源',	100,	1,	1680080513,	1680080513),
(27,	3,	'决策',	100,	1,	1680080554,	1680080554),
(28,	6,	'工作',	100,	1,	1680245442,	1680243650),
(29,	6,	'生活',	99,	1,	1680246718,	1680246718),
(30,	6,	'电商运营',	96,	1,	1680267257,	1680267257),
(31,	7,	'写作',	100,	1,	1680750142,	1680750142),
(32,	5,	'工作',	100,	1,	1680751873,	1680751873),
(33,	5,	'生活',	100,	1,	1680752034,	1680752034),
(34,	5,	'方案',	100,	1,	1680752204,	1680752204),
(35,	8,	'热门',	100,	1,	1681105721,	1681105721),
(36,	6,	'公告',	56,	1,	1681693117,	1681693117),
(37,	9,	'工作',	100,	1,	1681783818,	1681783818),
(38,	9,	'生活',	100,	1,	1681809141,	1681809141);

-- 2023-04-24 07:17:01
