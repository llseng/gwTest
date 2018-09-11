<?php
return [

	//好友添加请求记录表
	"im_add_friend" => "CREATE TABLE im_add_friend(
	
		`id` int(11) unsigned not null AUTO_INCREMENT COMMENT '主键ID',
		
		`uid` int(11) unsigned not null COMMENT '发起用户ID',
		
		`to_uid` int(11) unsigned not null COMMENT '添加用户ID',
		
		`intro` varchar(100) COMMENT '添加简介',
		
		`state` tinyint(1) not null default 0 COMMENT '请求状态',

		`status` tinyint(1) not null default 0 COMMENT '处理状态',
		
		`addtime` int(11) unsigned not null default 0 COMMENT '发起时间',
		
		`uptime` int(11) unsigned not null default 0 COMMENT '同意时间',
		
		PRIMARY KEY (`id`)
	
	)ENGINE=MYISAM DEFAULT CHARSET=utf8 COMMENT='好友添加请求记录表'",
	
	//用户好友表
	"im_friend" => "CREATE TABLE im_friend (
	
		`id` int(11) unsigned not null AUTO_INCREMENT COMMENT '',
		
		`uid` int(11) unsigned not null COMMENT '用户ID(外键)',
		
		`friend_id` int(11) unsigned not null COMMENT '好友ID(外键)',

		`top` tinyint(1) unsigned not null comment '用与聊天记录置顶',
		
		`class_id` int(11) unsigned not null default 0 COMMENT '用户分组ID',
		
		`name` varchar(30) COMMENT '好友备注',

		`addtime` int(11) unsigned not null default 0 COMMENT '创建时间',
		
		PRIMARY KEY (`id`)
	
	)ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='用户好友表'",
	
	//用户状态表
	"im_user_state" => "CREATE TABLE im_user_state (
	
		`id` INT(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '',
		
		`uid` INT(11) unsigned NOT NULL COMMENT '用户ID(外键)',
		
		`name` VARCHAR(10) COMMENT '状态名',
		
		`uptime` int(11) unsigned COMMENT '修改时间',
		
		PRIMARY KEY (`id`)
	
	)ENGINE=MyISAM default charset=utf8 COMMENT='用户状态表'",

	//用户好友分组表 Group of friends
	"im_friend_class" => "CREATE TABLE im_friend_class (
	
		`class_id` INT(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '分组ID' ,
		
		`uid` int(11) unsigned not null comment '用户ID(外键)',
		
		`name` varchar(30) not null comment '分组名字',
		
		`addtime` int(11) unsigned not null default 0 comment '添加时间',
		
		`uptime` int(11) unsigned not null default 0 comment '修改时间',
		
		PRIMARY KEY (`class_id`)
		
	)ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='用户好友分组表'",
	
	//用户消息表
	"im_message" => "CREATE TABLE im_message (
	
	`id` INT(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '',
	
	`uid` INT(11) unsigned NOT NULL COMMENT '发送用户ID(外键)',
	
	`to_uid` INT(11) unsigned NOT NULL COMMENT '接受用户ID(外键)',
	
	`type_id` TINYINT(2) NOT NULL DEFAULT 0 COMMENT '消息类型(外键)',
	
	`content` TEXT(5000) NOT NULL COMMENT '消息内容',
	
	`status` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '消息接受状态',
	
	`addtime` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '消息发送时间',

	`cancel` TINYINT(1) NOT NULL default 0 comment '取消显示(撤销的意识)',
	
	PRIMARY KEY (`id`)
	
	)ENGINE=INNODB DEFAULT CHARSET=utf8 COMMENT='用户消息表'",
	
	//用户消息类型
	"im_message_type" => "CREATE TABLE im_message_type(
	
		`type_id` TINYINT(2) NOT NULL AUTO_INCREMENT comment '消息类型ID(主键)',
		
		`name` varchar(20) NOT NULL COMMENT '类型名称',
		
		`addtime` int(11) unsigned not null default 0 comment '创建时间',
		
		PRIMARY KEY (`type_id`)
	
	)ENGINE=MYISAM DEFAULT CHARSET=utf8 COMMENT='用户消息类型'",

	//用户申请加群记录
	"im_add_group" => "CREATE TABLE im_add_group(

		`id` int(11) unsigned not null AUTO_INCREMENT COMMENT '记录ID(主键)',
	
		`uid` int(11) unsigned not null comment '用户ID（外键）',

		`group_id` int(11) unsigned not null comment '群组ID(外键)',

		`intro` varchar(255) not null default '' comment '进群简介',

		`state` tinyint(1) not null default 0 comment '是否通过',

		`status` tinyint(1) not null default 0 comment '请求是否处理',

		`addtime` int(11) unsigned not null default 0 comment '申请时间',

		`uptime` int(11) unsigned not null default 0 comment '请求处理时间',

		PRIMARY KEY (`id`)

	)ENGINE=MYISAM DEFAULT CHARSET=utf8 COMMENT='用户申请加群记录'",
	
	//群组
	"im_group" => "CREATE TABLE im_group (
	
		`group_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '群组ID',
		
		`name` VARCHAR(30) NOT NULL COMMENT '群组名',
		
		`add_uid` int(11) unsigned not null COMMENT '创建人ID(外键)',
		
		`icon` varchar(60) default '' COMMENT '群图标',
		
		`notice` varchar(200) default '' COMMENT '群公告',
		
		`intro` varchar(200) default '' COMMENT '群简介',
		
		`addtime` int(11) unsigned not null default 0 comment '创建时间',
		
		`uptime` int(11) unsigned not null default 0 comment '修改时间',
		
		PRIMARY KEY (`group_id`)
	
	)ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='用户聊天群组'",
	
	//群用户关联表
	"im_group_user" => "CREATE TABLE im_group_user (
	
		`id` int(11) unsigned not null AUTO_INCREMENT COMMENT '',
		
		`uid` int(11) unsigned not null COMMENT '用户id(外键)',
		
		`group_id` int(10) unsigned not null COMMENT '群组ID(外键)',

		`rig` tinyint(1) unsigned not null default 0 comment '管理权限',
		
		`addtime` int(11) unsigned not null default 0 COMMENT '入群时间',
		
		`group_nick` varchar(32) COMMENT '群内昵称',
	
		PRIMARY KEY (`id`)
	
	)ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='群用户关联表'",
	
	//群消息内容表
	"im_group_message" => "CREATE TABLE im_group_message(
	
		`id` int(11) unsigned not null AUTO_INCREMENT comment '群消息ID(主键)',

		`group_id` int(11) unsigned not null comment '群ID(外键)',
		
		`uid` int(11) unsigned not null comment '发送用户ID',

		`say_type` tinyint(2) not null default 0 comment '消息类型',
		
		`content` text(1000) NOT NULL COMMENT '群消息内容',
		
		`unick` varchar(32) NOT NULL COMMENT '发送用户昵称(群昵称/用户名)',
		
		`addtime` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '信息创建时间',
		
		PRIMARY KEY (`id`)
	
	)ENGINE=MYISAM DEFAULT CHARSET=utf8 COMMENT='群消息表'",
	
	//群消息关联表
	"im_group_message_user" => "CREATE TABLE im_group_message_user(
		
		`id` int(11) unsigned not null AUTO_INCREMENT COMMENT '主键ID',
		
		`uid` int(11) unsigned not null COMMENT '接受用户ID(外键)',
		
		`group_id` int(11) unsigned not null COMMENT '群组ID(外键)',

		`ms_id` int(11) unsigned not null default 0 comment '群消息ID(外键)',
		
		`state` tinyint(1) not null DEFAULT 0 COMMENT '消息接受状态',
		
		`addtime` int(11) unsigned not null DEFAULT 0 COMMENT '创建时间',
		
		`uptime` int(11) unsigned not null DEFAULT 0 COMMENT '修改时间',
		
		PRIMARY KEY (`id`)
	
	)ENGINE=MYISAM DEFAULT CHARSET=utf8 COMMENT='群消息关联表(用户已读未读状态表)'",
	
	//群内私聊消息关联表
	"im_group_message_touser" => "CREATE TABLE im_group_message_touser(
		
		`id` int(11) unsigned not null AUTO_INCREMENT COMMENT '主键',
		
		`group_id` int(11) unsigned not null COMMENT '所属群组',
		
		`uid` int(11) unsigned not null COMMENT '发送者ID(外键)',

		`unick` varchar(32) not null COMMENT '发送者群昵称/用户名',
		
		`to_uid` int(11) unsigned not null COMMENT '接受用户ID(外键)',
		
		`content` text(1000) not null COMMENT '私聊信息内容',
		
		`addtime` int(11) unsigned not null DEFAULT 0 COMMENT '发送时间',
		
		`state` tinyint(1) not null DEFAULT 0 COMMENT '消息接受状态',
		
		PRIMARY KEY (`id`)
		
	)ENGINE=MYISAM DEFAULT CHARSET=utf8 COMMENT='群内私聊消息关联表'",
	
];

?>