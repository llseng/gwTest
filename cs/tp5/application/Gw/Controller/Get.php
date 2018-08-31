<?php
namespace app\gw\controller;

use \Session;
use CenCMS\ApiController;
use GatewayClient\Gateway;

use app\gw\logic\GetLogic;

/**
 * 获取信息 的 控制器
 */
class Get extends ApiController
{
    //用户ID
    public $uid;

    //构造函数
    public function __construct()
    {
        parent::__construct();

        //验证登录
        $this->isLogin();

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
     * 查找好友
     */
    public function findFriend()
    {
        $post = self::getPost(['cond']);
        //MYSql 模糊查询防注入
        $cond = addcslashes($post['cond'],"%_");
        
        $list = self::doQuery(
            $command = "select",
            $db = 'users',
            $map = "concat(`id`,`nickname`,`username`) like '%".$cond."%'",
            $param = "id as uid,nickname,username"
        );

        return self::returnSuccess($list,"搜索完成");
    }

    /**
     * 查找群组
     */
    public function findGroup()
    {
        $post = self::getPost(['cond']);
        //MYSql 模糊查询防注入
        $cond = addcslashes($post['cond'],"%_");

        $list = self::doQuery(
            $command = "select",
            $db = 'group',
            $map = "concat(`group_id`,`name`) like '%".$cond."%'",
            $param = "group_id,name as group_name"
        );

        return self::returnSuccess($list,"搜索完成");

    }

    /**
     * 获取所有好友请求
     */
    public function addFriend()
    {

        $list = self::doQuery(
            $command = "select",
            $db = "add_friend",
            $map = [
                'to_uid' => $this->uid,
                //'status' => 0,
            ],
            $param = 'id,uid,intro,addtime',
            $join = '',
            $link = '',
            $order = 'addtime',
            $sort = "desc"
        );

        if(!$list) return self::returnSuccess([],"无好友请求");

        return self::returnSuccess($list,'获取成功');
    }

    //获取好友列表
    public function friendList()
    {
        $friendList = self::doQuery(
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

        if(!$friendList) return self::returnSuccess([],"没有好友,快去添加吧！");

        return self::returnSuccess($friendList,"获取成功");

    }

    //获取分组
    public function classList()
    {
        $classList = self::doQuery(
            $command = "select",
            $db = "friend_class",
            $map = ['uid' => $this->uid],
            $param = "class_id,name,addtime"
        );

        if($classList) return self::returnSuccess([],'没有分组,快去添加吧！');

        return self::returnSuccess($classList,'没有分组,快去添加吧！');
    }

    //获取 三维分组 列表
    public function classFriend()
    {
        $friendList = self::doQuery(
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

        $classList = self::doQuery(
            $command = "select",
            $db = "friend_class",
            $map = ['uid' => $this->uid],
            $param = "class_id,name,addtime"
        );

        //排序 好友入栈
        $classFriend = GetLogic::classSort($classList,$friendList);

        return self::returnSuccess($classFriend);

    }

    //
    public function test()
    {
        var_dump($this->friendList(),$this->classList());
    }


}