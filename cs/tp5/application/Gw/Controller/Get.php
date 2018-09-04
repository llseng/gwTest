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

    public $GetLogic;

    //构造函数
    public function __construct()
    {
        parent::__construct();

        $this->GetLogic = new GetLogic();

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
        //获取未处理请求
        $list = $this->GetLogic->addFriend($this->uid);

        if(!$list) return self::returnSuccess([],"无好友请求");

        return self::returnSuccess($list,'获取成功');
    }

    //获取好友列表
    public function friendList()
    {

        $friendList =  $this->GetLogic->friendList($this->uid);

        if(!$friendList) return self::returnSuccess([],"没有好友,快去添加吧！");

        return self::returnSuccess(["list" => $friendList],"获取成功");

    }

    //获取分组
    public function classList()
    {
        $classList = $this->GetLogic->classList($this->uid);

        if($classList) return self::returnSuccess([],'没有分组,快去添加吧！');

        return self::returnSuccess(['list' => $classList],'获取成功');
    }

    //获取 三维分组 列表
    public function classFriend()
    {
        $friendList = $this->GetLogic->friendList($this->uid);

        $classList = $this->GetLogic->classList($this->uid);

        //排序 好友入栈
        $classFriend = GetLogic::classSort($classList,$friendList);

        return self::returnSuccess(["classFriend" => $classFriend]);

    }

    //
    public function test()
    {
        var_dump($this->friendList(),$this->classList());
    }


}