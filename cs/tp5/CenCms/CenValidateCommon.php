<?php

return $validateCommon = 
[
    'rule' => [

        'sex'       => ['sex'       => 'number'],

        'age'       => ['age'       => 'number|between:1,120'],

        'mobile'    => ['mobile'    => 'mobile'],

        'realname'  => ['realname'  => 'require'],

        'idcard_no' => ['idcard_no' => 'idCard'],

        'nickname'  => ['nickname'  => 'require'],

        'email'     => ['email'     => 'email'],

        'username'  => ['username'  => 'require'],

        'password'  => ['password'  => 'require|min:6|max:18'],

        'code'      => ['code'      => 'require'],

        'communityName' => ['communityName' => 'require'],

        'detailAddress' => ['detailAddress' => 'require'],

        'size'          => ['size' => 'number'],

        'floor'         => ['floor' => 'require'],

        'manageFee'     => ['manageFee' => 'number'],

        'payWay'        => ['payWay' => 'number|between:0,11'],

        'direction'     => ['direction' => 'number|between:0,3'],

        'equipment'     => ['equipment' => 'array'],

        'decorationDegree' => ['decorationDegree' => 'number|between:0,2'],

        'title'         => ['title' => 'require'],

        'seeHouseDate'  => ['seeHouseDate' => 'number'],

        'liveInDate'    => ['liveInDate' => 'number'],

        'name'          => ['name' => 'require'],

        'freeCall'      => ['freeCall' => 'number|between:0,1'],

        'shareType'     => ['shareType' => 'number|between:0,3'],

        'houseType'     => ['houseType' => 'require'],

        'currentState'  => ['currentState' => 'number|between:0,2'],

        'transfer'      => ['transfer'  => 'number|between:0,1'],

        'slpit'         => ['slpit'  => 'number|between:0,1'],

        'businessLicense'=> ['businessLicense'  => 'number|between:0,1'],

        //=========

        'friend_id'     => ['friend_id' => 'number'], //好友ID

        'intro'         => ['intro' => 'max:100'], //

        'order_id'      => ['order_id' => 'number'], //订单ID（主键ID）

    ],

    'errMsg' => [

        'sex'       => [
            'sex.number'        => "性别必须是男、女或未知"
        ],

        'age'       => [
            'age.number'        => '年龄必须是数字',
            'age.between'       => '年龄必须在1~120之间'
        ],

        'mobile'    => [
            'mobile.mobile'     => '手机号不合法'
        ],

        'realname'  => [
            'realname.require'  => '用户名不合法'
        ],

        'idcard_no' => [
            'idcard_no.idCard'  => '身份证号不合法'
        ],

        'nickname'  => [
            'nickname.require'  => '昵称不能为空'
        ],

        'email'     => [
            'email.email'       => '邮箱不合法'
        ],

        'username'  => [
            'username.require'  => '请填写用户名'
        ],

        'password'  => [
            'password.require'  => '请填写密码',
            'password.min'      => '密码不能少于6位',
            'password.max'      => '密码不能大于18位'
        ],

        'code'     => [
            'code.require'      => '验证码不能为空'
        ],

        'communityName' => [
            'communityName.require' => '小区名不能为空'
        ],

        'detailAddress' => [
            'detailAddress.require' => '详细地址不能为空'
        ],

        'size'          => [
            'size.number' => '面积必须为数字'
        ],

        'floor'         => [
            'floor.array' => '楼层不能为空'
        ],

        'manageFee'     => [
            'manageFee.number' => '管理费必须为数字'
        ],

        'payWay'        => [
            'payWay.number'    => '支付方式必须为数字',
            'payWay.between'   => '支付方式不能大于11'

        ],

        'direction'     => [
            'direction.number'  => '朝向必须是数字',
            'direction.between' => '朝向不得大于3'
        ],

        'equipment'     => [
            'equipment.array'  => '设备必须为数组'
        ],

        'decorationDegree' => [
            'decorationDegree.number' => '装修程度必须是数字',
            'decorationDegree.between' => '装修程度不得大于2'
        ],

        'title'         => [
            'title.require' => '标题不能为空'
        ],

        'seeHouseDate'  => [
            'seeHouseDate.number' => '不合法看房时间戳'
        ],

        'liveInDate'    => [
            'liveInDate.number'   => '不合法入住时间戳'
        ],

        'name'          => [
            'name.require'        => '姓名不能为空'
        ],

        'freeCall'      => [
            'freeCall.number'     => '免费电话必须以数字形式传输',
            'freeCall.between'    => '免费电话不能是大于2的数字'
        ],

        'shareType'     => [
            'shareType.number'    => '合租类型必须是数字',
            'shareType.between'   => '合租类型不得大于3'
        ],

        'houseType'     => [
            'houseType.require'   => '租房类型不能为空'
        ],

        'currentState'  => [
            'currentState.number'  => '当前状态必须为数字',
            'currentState.between' => '当前状态不得大于2'
        ],

        'transfer'     => [
            'transfer.number' => '是否转让必须为数字',
            'transfer.between' => '是否转让不得大于1'
        ],

        'split'     => [
            'split.number' => '是否转让必须为数字',
            'split.between' => '是否转让不得大于1'
        ],

        'businessLicense'     => [
            'businessLicense.number' => '是否转让必须为数字',
            'businessLicense.between' => '是否转让不得大于1'
        ],

        //==========

        'friend_id' => [
            'friend_id.number' => '用户ID必须为数值'
        ],

        'intro' => [
            'intro.max' => '请求简介不得超过100字符'
        ],

        'order_id' => [
            'order_id.number' => '订单ID必须为数值'
        ]

    ]
];
