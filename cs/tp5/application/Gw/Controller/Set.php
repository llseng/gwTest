<?php
namespace app\gw\controller;

use \Session;
use CenCMS\ApiController;
use GatewayClient\Gateway;
use app\gw\logic\SetLogic;

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
        /*
        $uid = Session::get('uid');
        if($uid && Gateway::isUidOnline($uid))
            return Gateway::sendToUid($uid,self::returnSuccess(['uid'=>$uid,'nickname'=>Session::get("nickname")],"已登录"));
        */
        
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
            echo self::returnError("连接断开");
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
            
            Gateway::sendToUid($res['id'].'',self::returnSuccess($uSession,"登录成功"));

        }else{
            
            Gateway::sendToCLient($post['connect_id'],self::returnError("登录验证失败"));
            Gateway::closeClient($post['connect_id']);
        }


        /*
        $post = self::getPost(['username','password','client_id']);

        $password = md5($post['password']);

        $res = self::doQuery(
            $command = "find",
            $db = 'users',
            $map = [
                'username' => $post['username'],
                'password' => $password
            ],
            $param = 'id,nickname'
        );

        //用户不在线
        if(!Gateway::isOnline($post['client_id']))
        {
            echo self::returnError("连接断开");
        }

        if($res)
        {

            //用户连接 绑定 用户ID & 用户数据保存
            Gateway::bindUid($post['client_id'],$res['id']);
            $uSession = [
                'uid' => $res['uid'],
                'nickname' => $res['nickname']
            ];
            Gateway::updateSesssion($post['client_id'],$uSession);

            //保存当前用户数据
            session::set('nickname',$res['nickname']);
            session::set('uid',$res['id']);

            Gateway::sendToUid($res['id'],self::returnSuccess($uSession,"登录成功"));

        }else{
            
            Gateway::sendToCLient($post['client_id'],self::returnError("登录失败"));
        }
        */

    }

    //请求添加好友
    public function addFriend()
    {
        //$uid = Session::get("uid");
        
        $post = self::getPost(['friend_id','intro']);

        if($post['friend_id'] == $this->uid) 
            exit(self::returnError("不可添加自己"));

        if(self::doQuery(
            $command = "find",
            $db = "friend",
            $map = [
                'uid' => $this->uid,
                'friend_id'=> $post['friend_id']
            ],
            $param = "id"
        )) exit(self::returnError("用户已是您的好友"));

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
            Gateway::sendToUid($post['friend_id'],json_encode(['type'=>'add_friend','uid'=>$this->uid,'nickname'=>session::get('nickname'),'intro'=>$post['intro']]));
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
            $map = "(uid=".$post['friend_id']." and to_uid=".$this->uid.") OR (uid=".$this->uid." and to_uid=".$post['friend_id'].")",
            $param = [
                'state' => $post['state'] ? 1 : 0,
                'status' => 1,
                'uptime' => $_SERVER['REQUEST_TIME']
            ]
        );

        if(!$result) exit(self::returnError("操作失败."));

        if(!$post['state'])
        {
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

        if(Gateway::isUidOnline($res['uid'])) Gateway::sendToUid($res['uid'],json_encode(['type'=>'add_friend','uid'=>$this->uid,'nickname'=>session::get('nickname')]));

        exit(self::returnSuccess([],"操作成功"));
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