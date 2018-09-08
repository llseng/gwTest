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

        if(!$post['cond']) return self::returnError("搜索值不可为空");
        
        $list = $this->GetLogic->findFriend($post['cond'],$post['page']);

        return self::returnSuccess(["list"=>$list],"搜索完成");
    }

    /**
     * 查找群组
     */
    public function findGroup()
    {
        $post = self::getPost(['cond']);

        if(!$post['cond']) return self::returnError("搜索值不可为空");

        $list = $this->GetLogic->findGroup($post['cond'],$post['page']);

        return self::returnSuccess(["list"=>$list],"搜索完成");

    }

    /**
     * 搜索 好友|群
     */
    public function findAll()
    {
        $post = self::getPost(['cond','page']);

        if(!$post['cond']) return self::returnError("搜索值不可为空");
        
        $friendList = $this->GetLogic->findFriend($post['cond'],$post['page']);

        $groupList = $this->GetLogic->findGroup($post['cond'],$post['page']);

        return self::returnSuccess(["friendList"=>$friendList,"groupList"=>$groupList],"搜索完成");

    }

    /**
     * 获取所有好友请求
     */
    public function addFriend()
    {
        //获取未处理请求
        $list = $this->GetLogic->addFriend($this->uid);

        if(!$list) return self::returnSuccess([],"无好友请求");

        return self::returnSuccess(["list"=>$list],'获取成功');
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

    /**
     * 获取进群请求
     */
    public function addGroup()
    {
        //可管理的群列表 return [group_id,group_id2,...]
        $UidGroup = $this->GetLogic->getUidGroupAamin($this->uid);

        $addGroupList = self::doQuery(
            $command = "select",
            $db = "group g",
            $map = '',
            $param = "a.id,a.uid,a.intro,a.addtime,a.group_id,a.nickname,g.name as group_name",
            $join = "(select ag.*,u.nickname from im_add_group ag join im_users u on ag.group_id in(" .join(",",$UidGroup). ") and ag.uid=u.id) a",
            $link = "g.group_id in(" .join(",",$UidGroup). ") and a.group_id=g.group_id and a.status=0"
        );

        if(!$addGroupList) return self::returnSuccess([],"没有加群请求");

        return self::returnSuccess(["list"=>$addGroupList],"获取成功");

    }

    //
    public function test()
    {
        var_dump($this->friendList(),$this->classList());
    }


}