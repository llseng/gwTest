<?php
namespace app\gw\controller;

use \Session;
use CenCMS\ApiController;
use GatewayClient\Gateway;
use app\gw\logic\SetLogic;
use app\gw\logic\GetLogic;
use app\gw\logic\SayLogic;

class Set extends ApiController
{

    /**
     * GatewayCLiet 客户端开启
     */
    public function GatewayClientStart()
    {
        Gateway::$registerAddress = "127.0.0.1:9527";

        /*
        try{
            $gatewayServer = stream_socket_client("tcp://" . Gateway::$registerAddress, $erron, $errstr, 3);
        }catch(\Exception $e) {
            exit("Service not open");
            return false;
        }

        var_dump(Gateway::$registerAddress, $erron, $errstr);
        //return true;
        */

    }

    //用户当前请求 方法
    private $action;
    //用户ID
    public $uid;

    public function __construct()
    {
        //调用父级构造函数
        parent::__construct();

        $this->action = request()->action();

        //无需前置的方法 
        $this->front = ['userlogin'];

        //前置方法
        if(!in_array(strtolower($this->action),$this->front))
        {
            $this->isLogin();
        }
    }

    //是否登录
    public function isLogin($r = 0)
    {
        $this->uid = Session::get('uid');
        if($this->uid && Gateway::isUidOnline($this->uid))
            return $this->uid;
        
        //删除当前所有session
        Session::clear(null);
        
        if($r) return false;
        exit(self::returnError('未登录'));
    }

    /**
     * 用户登录
     */
    public function userLogin()
    {

        $post = self::getPost(['uid','client_id']);

        $isLogin = self::isLogin(1);
        if($isLogin !== false)
            return exit(self::returnSuccess(['uid'=>$isLogin,'nickname'=>session::get("nickname")],"已登录"));
        
        $res = self::doQuery(
            $command = "find",
            $db = "users",
            $map = [
                'id' => $post['uid']
            ],
            $param = "id,nickname"
        );

        //用户不在线
        if(!Gateway::isOnline($post['connect_id']))
        {
            echo self::returnError("IM连接断开,登录失败");
        }

        if($res)
        {

            //用户连接 绑定 用户ID & 用户数据保存
            Gateway::bindUid($post['connect_id'],$res['id']);
            $uSession = [
                'uid' => $res['id'],
                'nickname' => $res['nickname']
            ];
            Gateway::updateSession($post['connect_id'],$uSession);

            //保存当前用户数据
            Session::set('nickname',$res['nickname']);
            Session::set('uid',$res['id']);
            
            $success = self::returnSuccess($uSession,"登录成功");
            Gateway::sendToUid($res['id'],$success);

            exit($success);

        }else{
            $error = self::returnError("登录验证失败");

            Gateway::sendToCLient($post['connect_id'],$error);
            Gateway::closeClient($post['connect_id']);

            exit($error);
        }

    }

    //未读消息消息推送
    public function messagePush()
    {
        //数据获取逻辑层
        $GetLogic = new GetLogic();
        //未读私聊信息
        $news = $GetLogic->unreadMessage($this->uid);
        //用户未读消息推送
        Gateway::sendToUid($this->uid,self::returnSuccess(SayLogic::sayData("news",$news),"有未读信息"));

        exit(self::returnSuccess([],"成功"));
    }

    //请求添加好友
    public function addFriend()
    {
        
        $post = self::getPost(['friend_id','intro']);

        if($post['friend_id'] == $this->uid) 
            exit(self::returnError("不可添加自己"));

        //数据获取逻辑层
        $GetLogic = new GetLogic();

        if($GetLogic->getFriend($this->uid,$post['friend_id'])) exit(self::returnError("用户已是您的好友"));

        //获取用户信息
        $res = self::doQuery(
            $command = "find",
            $db = "users",
            $map = [
                'id' => $post['friend_id']
            ],
            $param = 'id,nickname'
        );

        //是否有用户
        if(!$res)
            exit(self::returnError("用户不存在"));

        //是否已有请求记录
        if(self::doQuery(
            $command = "find",
            $db = "add_friend",
            $map = [
                'uid' => $this->uid,
                'to_uid' => $post['friend_id'],
                'status' => 0
            ],
            $param = "id"
        )) {
           exit(self::returnError("请求已发送，无需重复提交")); 
        }

        //记录数据
        $paramData = [
            'uid' => $this->uid,
            'to_uid' => $res['id'],
            'intro' => $post['intro'],
            'addtime' => $_SERVER['REQUEST_TIME']
        ];
        
        //保存记录
        $result = self::setField(
            $command = "insert",
            $db = "add_friend",
            $map = '',
            $param = $paramData
        );

        if(!$result)
        {
            exit(self::returnError("请求失败"));
        }

        //用户IM在线 推送信息
        if(Gateway::isUidOnline($post['friend_id']))
        {
            Gateway::sendToUid($post['friend_id'],self::returnSuccess(SayLogic::sayData("req_add_friend",[
                ['uid'=>$this->uid,'nickname'=>session::get('nickname'),'intro'=>$post['intro']]
            ])));
        }

        exit(self::returnSuccess([],"请求成功"));

    }

    /**
     * 同意添加好友
     */
    public function verifyAddFriend()
    {

        $post = self::getPost(['order_id','state']);

        //$uid = Session::get("uid");

        //请求记录
        $res = self::doQuery(
            $command = 'find',
            $db = 'add_friend',
            $map = [
                'im_add_friend.id' => $post['order_id'],
                'im_add_friend.to_uid' => $this->uid, //添加自己的订单
                'im_add_friend.status' => 0, //未处理订单
            ],
            $param = "nickname,uid",
            $join = "im_users ",
            $link = "im_add_friend.uid=im_users.id"
        );

        if(!$res)
        {
            exit(self::returnError("没有请求信息"));
        }
        //修改请求状态
        $result = self::setField(
            $command = "update",
            $db = "add_friend",
            $map = "(uid=".$res['uid']." and to_uid=".$this->uid.") OR (uid=".$this->uid." and to_uid=".$res['uid'].")",
            $param = [
                'state' => $post['state'] ? 1 : 0,
                'status' => 1,
                'uptime' => $_SERVER['REQUEST_TIME']
            ]
        );

        if(!$result) exit(self::returnError("操作失败."));

        if(!$post['state'])
        {
            //添加好友成功 给对方推送提示信息
            if(Gateway::isUidOnline($res['uid'])) Gateway::sendToUid($res['uid'],self::returnSuccess(SayLogic::sayData("ref_add_friend",[
                ['uid'=>$this->uid,'nickname'=>session::get('nickname'),'intro'=>"拒绝了您的好友请求"]
            ])));

            exit(self::returnError("操作成功"));
        }

        //给双方添加好友
        $addFriend = self::setField(
            $command = "insertAll",
            $db = "friend",
            $map = '',
            $param = [
                ['uid' => $this->uid,
                'friend_id' => $res['uid'],
                'addtime'=>$_SERVER['REQUEST_TIME']],
                ['uid' => $res['uid'],
                'friend_id' => $this->uid,
                'addtime'=>$_SERVER['REQUEST_TIME']]
            ]
        );

        if(!$addFriend) exit(self::returnError("操作失败.."));

        //添加好友成功 给对方推送提示信息
        if(Gateway::isUidOnline($res['uid'])) Gateway::sendToUid($res['uid'],self::returnSuccess(SayLogic::sayData("add_friend",[
            ['uid'=>$this->uid,'nickname'=>session::get('nickname')]
        ])));

        exit(self::returnSuccess([],"操作成功"));
    }

    //修改好友备注/分组
    public function editFriendInfo()
    {
        $post = self::getPost(['friend_id','class_id','name']);

        $post['friend_id'] = (int)$post['friend_id'];
        $post['class_id'] = (int)$post['class_id'];

        //数据获取逻辑层
        $GetLogic = new GetLogic();
        if(!$GetLogic->getFriend($this->uid,$post['friend_id']))
        {
            exit(self::returnError("非好友,不可操作"));
        }

        if($post['class_id'] && !$GetLogic->getClass($this->uid,$post['class_id']))
        {
            exit(self::returnError("没有当前分组"));
        }

        //信息
        $info = [
            'class_id' => $post['class_id'],
        ];
        if($post['name']) $info['name'] = $post['name'];

        //保存
        $result = self::setField(
            $command = "update",
            $db = "friend",
            $map = [
                'uid' => $this->uid,
                'friend_id' => $post['friend_id']
            ],
            $param = $info
        );

        if(!$result) return self::returnError("修改失败");

        return self::returnSuccess([],"修改成功");

    }

    //添加/新建 分组
    public function addClass()
    {
        $post = self::getPost(['className']);

        if(!$post['className']) exit(self::returnError("请填写分组名"));

        //新建分组
        $res = self::setField(
            $command = "insert",
            $db = "friend_class",
            $map = '',
            $param = [
                'uid' => $this->uid,
                'name' => $post['className'],
                'addtime' => $_SERVER['REQUEST_TIME']
            ]
        );

        if(!$res) exit(self::returnError("创建失败"));

        exit(self::returnSuccess([],"创建成功"));
    }

    //创建 群组
    public function createGroup()
    {
        $post = self::getPost(['groupName','icon','notice','intro']);

        //数据获取逻辑层
        $GetLogic = new GetLogic();
        //是否有创建权限
        if(!$GetLogic->ifCreateGroupPer($this->uid)) exit(self::returnError("无权群创建权限"));
        //群组基本信息
        $info = [
            'add_uid' => $this->uid,
            'name' => $post['groupName'],
            'icon' => $post['icon'],
            'notice' => $post['notice'],
            'intro' => $post['intro'],
            'addtime' => $_SERVER['REQUEST_TIME']
        ];

        //入库 & 获取群ID
        $group_id = self::setField(
            $command = "insertGetId",
            $db = "group",
            $map = '',
            $param = $info
        );

        if(!$group_id) exit(self::returnError("创建失败"));

        //用户信息
        $userInfo = [
            'uid' => $this->uid,
            'group_id' => $group_id,
            'group_nick' => Session::get('nickname'),
            'addtime' => $_SERVER['REQUEST_TIME']
        ];

        //入群
        $result = self::setField(
            $command = "insert",
            $db = "group_user",
            $map = '',
            $param = $userInfo
        );

        if(!$result) exit(self::returnError("入群失败"));
        $info['group_id'] = $group_id;

        //设置逻辑层
        $SetLogic = new SetLogic();
        //绑定群组
        $SetLogic->bindGroup($this->uid,$group_id);
        //用户添加 群消息关联记录
        $setGroupReadNews = $SetLogic->setGroupReadNews($this->uid,$group_id);
        
        exit(self::returnSuccess($info,"创建成功" . ($setGroupReadNews ? '。' : '！')));
    }

    //上传图片
    public function uploadImage()
    {
        $post = self::getPost(['type']);

        $dirList = ["say/","group_say/"];
        $type = (int)$post['type'];
        if($type >= count($dirList)) $type = 0;

        //设置逻辑层
        $SetLogic = new SetLogic();
        $info = $SetLogic->upImage($dirList[$type]);

        if($info['status'] !== true) exit(self::returnError($info['status']));

        exit(self::returnSuccess($info,"上传成功"));
        
    }

    public function test()
    {
        //$uid = session::get("uid");
        $friend = self::doQuery(
            $command = "select",
            $db = "friend",
            $map = [
                "uid" => $this->uid,
            ],
            $param = "friend_id,class_id,nickname,name",
            $join = "im_users",
            $link = "im_friend.friend_id=im_users.id",
            $order = "name",
            $sort = "desc"
        );

        var_dump($friend);

        var_dump(session::get());
        var_dump($_SERVER);
    }
}