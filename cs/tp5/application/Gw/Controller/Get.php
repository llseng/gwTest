<?php
namespace app\gw\controller;

use \Db;
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

    //用户会话未读消息
    public function unreadMessage()
    {
        $unreadMessages = $this->GetLogic->unreadMessages($this->uid);

        if(!$unreadMessages) return self::returnError("无会话消息");

        return self::returnSuccess(["list"=>$unreadMessages],"会话消息");
    }

    //获取用户详情
    public function userInfo()
    {
        $post = self::getPost(['uid']);
        //用户ID
        $uid = (int)$post['uid'];
        //用户详情
        $userInfo = $this->GetLogic->getUserInfo($uid);
        if(!$userInfo) return self::returnError("用户不存在");
        //是否是好友
        $isFriendData = $this->GetLogic->getFriend($this->uid,$uid);
        $isFriend = $isFriendData ? 1 : 0;

        return self::returnSuccess(["info"=>$userInfo,"isFriend"=>$isFriend],"获取成功");

    }

    //用户单聊消息记录
    public function friendMessage()
    {
        $post = self::getPost(['friend_id','ms_id']);
        //好友ID
        $friend_id = (int)$post['friend_id'];
        //是否是好友
        $friendInfo = $this->GetLogic->getFriend($this->uid,$friend_id);
        if(!$friendInfo) return self::returnError("非好友，获取失败");

        $ms_id = (int)$post['ms_id']; //消息ID

        $messageList = $this->GetLogic->friendMessageList($this->uid,$friend_id,$ms_id);

        if(!$messageList) return self::returnError("消息倒头了");

        return self::returnSuccess(["list"=>$messageList],"获取成功");

    }

    /**
     * 群聊消息记录
     */
    public function groupMessage()
    {
        $post = self::getPost(['group_id','ms_id']);
        //好友ID
        $group_id = (int)$post['group_id'];
        //是否有加群组
        $isInGroup = $this->GetLogic->isInGroup($this->uid,$group_id);
        if(!$isInGroup) return self::returnError("未添加群组，获取失败");
        
        $ms_id = $post['ms_id']; //消息ID
        
        $messageList = $this->GetLogic->groupMessageList($this->uid,$group_id,$ms_id);

        if(!$messageList) return self::returnError("消息倒头了");

        return self::returnSuccess(["list"=>$messageList],"获取成功");
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

    //获取群组列表
    public function groupList()
    {
        $groupList = $this->GetLogic->userGroupList($this->uid);

        if(!$groupList) return self::returnSuccess([],"没有群组,快去添加吧！");

        return self::returnSuccess(["list" => $groupList],"获取成功");
    }

    //获取好友和群组列表
    public function FGList()
    {
        //获取好友列表
        $friendList =  $this->GetLogic->friendList($this->uid);
        //获取群组列表
        $groupList = $this->GetLogic->userGroupList($this->uid);

        return self::returnSuccess(["friendList" => $friendList,"groupList"=>$groupList],"获取成功");

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

    /**
     * 获取群详请 群信息 群管理 群成员
     */
    public function groupInfo()
    {
        //
        $post = self::getPost(['group_id']);
        $group_id = (int)$post['group_id'];

        //获取群信息
        $groupInfo = $this->GetLogic->getGroupInfo($group_id);
        if(!$groupInfo) return self::returnError("群不存在,获取失败");
        
        //获取管理 [uid=>1,...]
        $groupAdmin = $this->GetLogic->getGroupAdmin($group_id);
        $adminList = []; //群管理 ID 列表
        foreach($groupAdmin as $key => $val)
        {
            $adminList[] = $val;
        }

        //群用户列表
        $groupUserList = $this->GetLogic->groupUserList($group_id);

        foreach($groupUserList as $key => &$val)
        {
            //是否是管理
            $val['isAdmin'] = 0;
            if(in_array($val['uid'],$adminList)) $val['isAdmin'] = 1; 
        }

        //是否在群里
        $isInGroup = $this->GetLogic->isInGroup($this->uid,$group_id);

        $list = [
            "info" => $groupInfo,
            "userList" => $groupUserList,
            "isInGroup" => $isInGroup ? 1 : 0,
        ];

        return self::returnSuccess($list,"获取成功");
        
    }

    //
    public function test()
    {
        var_dump($this->friendList(),$this->classList());
    }


}