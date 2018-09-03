<?php
namespace app\gw\logic;

use CenCMS\ApiController;

class GetLogic extends ApiController
{

    //用户私聊未读消息
    public function unreadMessage($uid)
    {
        return self::doQuery(
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
            $sort = "desc"
        );
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

    //是否有分组
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

}