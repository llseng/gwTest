<?php
namespace app\gw\logic;

use CenCMS\ApiController;

class GetLogic extends ApiController
{

    //用户私聊未读消息
    public function unreadMessage($uid)
    {
        $message = self::doQuery(
            $command = 'select',
            $db = "message m",
            $map = [
                'm.to_uid' => $uid,
                'm.status' => 0
            ],
            $param = "m.type_id,m.uid,m.content,m.addtime,u.username,u.nickname",
            $join = "im_users u",
            $link = "u.id=m.uid",
            $order = "addtime",
            $sort = "desc",
            $start = 0,
            $num = 20
        );

        if($message) self::setField(
            $command = "update",
            $db = "message",
            $map = [
                'to_uid' => $uid,
                'status' => 0
            ],
            $param = [
                'status' => 1
            ]
        );

        return $message;

    }

    //查找用户
    public function findFriend($cond,$page = 1)
    {
        //MYSql 模糊查询防注入
        $cond = addcslashes($cond,"%_");

        $page < 1 && $page = 1;
        $pageNum = 20;
        $limitStart = ($page - 1) * $pageNum;

        $list = self::doQuery(
            $command = "select",
            $db = 'users',
            $map = "concat(`id`,`nickname`,`username`) like '%".$cond."%'",
            $param = "id as uid,nickname,username,avatar",
            $join = '',
            $link = '',
            $order = "id",
            $sort = "",
            $start = $limitStart,
            $num = $pageNum
        );

        return $list;
    }

    //查找群组
    public function findGroup($cond,$page = 0)
    {
        //MYSql 模糊查询防注入
        $cond = addcslashes($cond,"%_");

        $page < 1 && $page = 1; //页数不可小于
        $pageNum = 20;
        $limitStart = ($page - 1) * $pageNum;

        $list = self::doQuery(
            $command = "select",
            $db = 'group g',
            $map = "concat(g.group_id,g.name) like '%".$cond."%'",
            $param = "g.group_id,g.name as group_name,gu.mannum",
            $join = "(select count(uid) as mannum,group_id from im_group_user group by group_id) gu",
            $link = "g.group_id=gu.group_id",
            $order = "g.group_id",
            $sort = "",
            $start = $limitStart,
            $num = $pageNum
        );

        return $list;
    }

    //获取好友请求
    public function addFriend($uid)
    {
        return self::doQuery(
            $command = "select",
            $db = "add_friend",
            $map = [
                'to_uid' => $uid,
                //'status' => 0,
            ],
            $param = 'id,uid,intro,addtime',
            $join = '',
            $link = '',
            $order = 'addtime',
            $sort = "desc"
        );
    }

    //获取用户详情
    public function getUserInfo($uid)
    {
        return self::doQuery(
            $command = "find",
            $db = "users",
            $map = [
                'id' => $uid
            ],
            $param = 'id,nickname,avatar'
        );
    }

    //获取群组详情
    public function getGroupInfo($group_id)
    {
        return self::doQuery(
            $command = 'find',
            $db = "group",
            $map = [
                'group_id' => $group_id
            ],
            $param = 'group_id,name as group_name,add_uid,icon,notice,intro,addtime'
        );
    }

    //获取群管理列表 return ['uid'=>'1',...]
    public function getGroupAdmin($group_id)
    {
        $admin = self::doQuery(
            $command = "find",
            $db = "group",
            $map = [
                "group_id" => $group_id
            ],
            $param = "add_uid"
        );

        return $admin;
    }

    //获取用户可管理的群 return [grput_id_1,group_id_2,...]
    public function getUidGroupAamin($uid)
    {
        $group = self::doQuery(
            $command = "select",
            $db = "group",
            $map = [
                "add_uid" => $uid
            ],
            $param = "group_id"
        );

        $groupList = [];
        foreach ($group as $key => $val)
        {
            $groupList[] = $val['group_id'];
        }

        return $groupList;
    }

    //获取好友
    public function getFriend($uid,$friend_id)
    {
        return self::doQuery(
            $command = "find",
            $db = "friend",
            $map = [
                'uid' => $uid,
                'friend_id'=> $friend_id
            ],
            $param = "friend_id,class_id,name"
        );
    }

    //获取好友列表
    public function friendList($uid)
    {
        
        return self::doQuery(
            $command = "select",
            $db = "friend",
            $map = [
                "uid" => $uid,
            ],
            $param = "friend_id,class_id,nickname,name",
            $join = "im_users",
            $link = "im_friend.friend_id=im_users.id",
            $order = "name",
            $sort = "desc"
        );
    }

    //是否有该分组
    public function getClass($uid,$class_id)
    {
        return self::doQuery(
            $command = "select",
            $db = "friend_class",
            $map = [
                'uid' => $uid,
                'class_id'=>$class_id
            ],
            $param = "class_id,name,addtime"
        );
    }


    //获取好友分组
    public function classList($uid)
    {
        return self::doQuery(
            $command = "select",
            $db = "friend_class",
            $map = ['uid' => $uid],
            $param = "class_id,name,addtime"
        );
    }

    //用户分组列表排序
    public static function classSort(array $classSort,array $friendList)
    {
        $CLASS = []; //分组

        $CLASS[0]['list'] = []; //无分组
        $CLASS[0]['name'] = '无分组'; //

        $FRIEND = [];//临时数据

        //无分组信息
        if(!$classSort && !$friendList) return $CLASS;
        
        foreach($friendList as $key => $val)
        {
            $FRIEND[$val['class_id']][] = $val;
        }

        unset($friendList);
        
        foreach ($classSort as $key => $val)
        {
            $CLASS[$val['class_id']] = $val;
            $CLASS[$val['class_id']]['list'] = $FRIEND[$val['class_id']];

            unset($FRIEND[$val['class_id']]);
        }
        
        if($FRIEND)
        {
            foreach ($FRIEND as $key => $val)
            {
                $CLASS[0]['list'] = array_merge($CLASS[0]['list'],$val);
            }
        }


        return $CLASS;

    }

    //是否有创建群的权限
    public function ifCreateGroupPer($uid)
    {
        //
        return self::doQuery(
            $command = "find",
            $db = "users",
            $map = [
                "id"=>$uid
            ],
            $param = "id"
        );
    }

    //是否有群管理权限 /并返回 群信息
    public function ifManageGroupPer($uid,$group_id)
    {
        return self::doQuery(
            $command = "find",
            $db = "group",
            $map = [
                'group_id' => $group_id,
                'add_uid' => $uid
            ],
            $param = "group_id,name as group_name"
        );
    }

    //获取群用户信息
    public function isInGroup($uid,$group_id){
        return self::doQuery(
            $command = "find",
            $db = "group_user",
            $map = [
                "uid" => $uid,
                "group_id" => $group_id
            ],
            $param = "uid,group_id,group_nick,addtime"
        );
    }

    //获取群列表
    public function groupList($uid)
    {
        return self::doQuery(
            $command = "select",
            $db = "group_user",
            $map = [
                'uid' => $uid,
            ],
            $param = "group_id,group_nick"
        );
    }

    //获取用户好友会话记录
    //public function 

}