<?php
namespace app\gw\logic;

use \Db;
use CenCMS\ApiController;

class GetLogic extends ApiController
{

    //消息缓存天数
    public $nDay = 30;

    //用户私聊未读消息
    public function unreadMessage($uid)
    {
        $message = self::doQuery(
            $command = 'select',
            $db = "message m",
            $map = [
                'm.to_uid' => $uid,
                'm.status' => 0,
                "m.cancel" => 0
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

    //好友对我会话记录
    public function friendMessage($uid){

        $beforeTime = time() - ($this->nDay * 86400); //n天前
        
        $sql = "SELECT a.*,f.top,f.name as mark
        FROM im_friend f
        INNER JOIN (SELECT m.uid as id,m.content,m.type_id as say_type,m.status as state,m.addtime,u.nickname as name,u.avatar FROM im_message m INNER JOIN im_users u ON m.to_uid={$uid} and m.addtime>{$beforeTime} and m.uid=u.id and m.cancel=0 ORDER BY `addtime` DESC) a
        ON f.uid={$uid} and f.friend_id=a.id
        GROUP BY a.id
        ORDER BY f.top DESC,a.state DESC,a.addtime DESC";

        //用户会话记录
        $friendMessage = Db::query($sql);

        return $friendMessage;

    }

    //我和好友回话记录
    public function friendMessageU($uid)
    {
        $beforeTime = time() - ($this->nDay * 86400); //n天前
        
        /*
        $sql = "SELECT a.*,f.top,f.name as mark
        FROM im_friend f
        INNER JOIN (SELECT m.uid as id,m.content,m.type_id as say_type,m.status as state,m.addtime,u.nickname as name,u.avatar FROM im_message m INNER JOIN im_users u ON m.to_uid={$uid} and m.addtime>{$beforeTime} and m.uid=u.id ORDER BY `addtime` DESC) a
        ON f.uid={$uid} and f.friend_id=a.id
        GROUP BY a.id
        ORDER BY f.top DESC,a.state DESC,a.addtime DESC";
        */

        $sql = "SELECT a.id,a.uid,a.to_uid,a.content,a.type_id as say_type,a.status as state,a.addtime,a.nickname as name,a.avatar,f.name as mark,f.class_id,f.top 
        FROM (SELECT l.*,u.nickname,u.avatar FROM (SELECT *,uid+to_uid as mid FROM im_message WHERE addtime>{$beforeTime} and uid={$uid} and cancel=0 UNION ALL SELECT *,uid+to_uid as mid FROM im_message WHERE addtime>{$beforeTime} and to_uid={$uid} and cancel=0) l LEFT JOIN im_users u ON u.id=l.mid-{$uid} ORDER BY l.status DESC,l.addtime DESC) a 
        LEFT JOIN im_friend f
        ON f.uid={$uid} and a.uid=f.friend_id
        GROUP BY a.mid";

        //用户会话记录
        $friendMessage = Db::query($sql);

        return $friendMessage;
    }

    //单聊未读消息数分组
    public function firendUnreadNum($uid)
    {

        $beforeTime = time() - ($this->nDay * 86400); //n天前

        $sql = "SELECT uid,count(*) as num 
        FROM im_message 
        WHERE addtime>{$beforeTime} and to_uid={$uid} and status=0 and cancel=0
        GROUP BY uid";

        $unreadNumList = Db::query($sql);

        return $unreadNumList;
    }

    //群会话记录
    public function groupMessage($uid)
    {

        $beforeTime = time() - ($this->nDay * 86400); //n天前

        $sql = "SELECT * 
        FROM (SELECT gu.uid,gu.group_id,g.name as group_name,g.icon as gavatar FROM im_group_user gu INNER JOIN im_group g ON gu.uid={$uid} and gu.group_id=g.group_id ) b
        INNER JOIN (SELECT gm.group_id as id,gm.uid,gm.content,gm.addtime,gm.say_type,gm.unick,gu.group_nick FROM im_group_message gm INNER JOIN im_group_user gu ON gm.addtime>{$beforeTime} and gm.uid=gu.uid ORDER BY gm.addtime DESC) a
        ON a.id=b.group_id
        GROUP BY a.id
        ORDER BY a.addtime DESC";

        $groupMessage = Db::query($sql);

        return $groupMessage;
    }

    //群未读信息数列表
    public function groupUnreadNum($uid)
    {
        $beforeTime = time() - ($this->nDay * 86400); //n天前

        $sql = "SELECT a.group_id,count(*) as num 
        FROM (SELECT gu.uid,gu.group_id,gmu.ms_id FROM im_group_user gu INNER JOIN im_group_message_user gmu ON gu.uid={$uid} and gu.group_id=gmu.group_id and gu.uid=gmu.uid) a
        INNER JOIN im_group_message m
        ON m.addtime>{$beforeTime} and m.group_id=a.group_id and m.id>a.ms_id
        GROUP BY m.group_id";

        $unreadNumList = Db::query($sql);

        return $unreadNumList;
        
    }

    //所有回话最后一条记录
    public function unreadMessages($uid)
    {
        //$GetLogic = new GetLogic();
        //单聊最后一条记录
        //$friendMessage = $this->friendMessage($uid);
        $friendMessage = $this->friendMessageU($uid);
        
        $fml = ["unread"=>[],"read"=>[]];

        if($friendMessage)
        {
            //单聊未读记录
            $fmlUnread = $this->firendUnreadNum($uid);
            $unreadNum = [];
            foreach($fmlUnread as $key => $val)
            {
                $unreadNum[$val['uid']] = $val['num'];
            }
    
            foreach($friendMessage as $key => $val)
            {
                $row = [
                    "type" => 0,
                    "id" => $val['uid'] == $uid ? $val['to_uid'] : $val['uid'], //发送ID为自己的话 消息就是 发给对方
                    "name" => $val['name'],
                    "mark" => $val['mark'],
                    "avatar" => $val['avatar'],
                    "lastmsg" => [
                        "id" => $val['id'],
                        "say_type"=>$val['say_type'],
                        "state" => $val['state'],
                        "content" => $val['content'],
                        "addtime" => $val['addtime']
                    ],
                    "unreadNum" => $unreadNum[$val['id']] ?: 0,
                    "top" => $val['top']
                ];

                if($val['uid'] == $uid)
                {
                    $row['lastmsg']['uid'] = $uid;
                }

                /*
                //信息未读 证明有未读消息 获取未读条数
                if(!$val['state'])
                {
                    $row['unreadNum'] = (int)$unreadNum[$val['id']];
                }
                */
                //未读消息
                if($unreadNum[$val['id']])
                {
                    $fml["unread"][] = $row;
                }else{
                    $fml["read"][] = $row;
                }
                //$fml[] = $row;
                
            }

        }

        //群聊最后一条记录
        $groupMessage = $this->groupMessage($uid);

        $gml = ["unread"=>[],"read"=>[]];

        if($groupMessage)
        {
            //群聊未读数
            $gmlUnread = $this->groupUnreadNum($uid);
            $unreadNum = [];
            foreach($gmlUnread as $key => $val)
            {
                $unreadNum[$val['group_id']] = $val['num'];
            }

            foreach($groupMessage as $key => $val)
            {
                $row = [
                    "type" => 1,
                    "id" => $val['id'],
                    "name" => $val['group_name'],
                    "mark" => $val['mark'],
                    "avatar" => $val['avatar'],
                    "lastmsg" => [
                        "uid" => $val['uid'],
                        "name" => $val['unick'],
                        "say_type"=>$val['say_type'],
                        "content" => $val['content'],
                        "addtime" => $val['addtime'],
                        "state" => $unreadNum[$val['id']] ? 0 : 1, //有未读 证明当前未读
                    ],
                    "unreadNum" => $unreadNum[$val['id']] ?: 0
                ];
    
                //
                if($unreadNum[$val['id']])
                {
                    $gml["unread"][] = $row;
                }else{
                    $gml["read"][] = $row;
                }

                //$gml[] = $row;
            }
        }

        //var_dump($groupMessage,$friendMessage);
        //var_dump($fml,$gml);
        $list = array_merge($fml['unread'],$gml['unread'],$fml['read'],$gml['read']);

        return $list;
        
    }

    //用户单聊消息记录
    public function friendMessageList($uid,$friend_id,$ms_id = 0,$limitNum = 10)
    {
        $beforeTime = time() - ($this->nDay * 86400); //n天前

        $msWhere = '';
        if($ms_id) $msWhere .= "and id<{$ms_id}";

        $sqlUnion = "SELECT * FROM im_message WHERE addtime>{$beforeTime} {$msWhere} and to_uid={$uid} and uid={$friend_id} and cancel=0
        UNION ALL
        SELECT * FROM im_message WHERE addtime>{$beforeTime} {$msWhere} and to_uid={$friend_id} and uid={$uid} and cancel=0";

        $sql = "SELECT a.*,a.type_id as type,a.type_id as say_type,a.status as state
        FROM ( {$sqlUnion} ) a
        ORDER BY a.addtime DESC
        LIMIT 0,$limitNum";

        $list = Db::query($sql);

        $SetLogic = new SetLogic();
        //所有消息 为已读
        $SetLogic->readFriendMessage($uid,$friend_id);
        //Db::execute("UPDATE im_message SET status=1 WHERE addtime>{$beforeTime} and to_uid={$uid} and uid={$friend_id}");

        return $list;
    }

    //用户群聊记录
    public function groupMessageList($uid,$group_id,$ms_id = 0,$limitNum = 10)
    {
        $beforeTime = time() - ($this->nDay * 86400); //n天前

        $msWhere = '';
        if($ms_id) $msWhere .= "and id<{$ms_id}";

        $sql = "SELECT gm.say_type as type,gm.content,gm.uid,gm.addtime,guu.group_nick as nick,guu.nickname as name,guu.avatar
        FROM im_group_message gm
        INNER JOIN (SELECT gu.uid,gu.group_nick,u.nickname,u.avatar FROM im_group_user gu INNER JOIN im_users u ON gu.group_id={$group_id} and gu.uid=u.id ) guu
        ON gm.addtime>{$beforeTime} and gm.group_id={$group_id} {$msWhere} and gm.uid=guu.uid
        ORDER BY gm.addtime DESC
        LIMIT 0,$limitNum";

        $list = Db::query($sql);

        $SetLogic = new SetLogic();
        //所有消息 为已读
        $SetLogic->readGroupMessage($uid,$group_id);
        //Db::execute("UPDATE im_message SET status=1 WHERE addtime>{$beforeTime} and to_uid={$uid} and uid={$friend_id}");

        return $list;
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
                'status' => 0,
            ],
            $param = 'id,uid,intro,addtime',
            $join = '',
            $link = '',
            $order = 'addtime',
            $sort = "desc"
        );
    }

    //获取用户信息
    public function getUserInfo($uid)
    {
        return self::doQuery(
            $command = "find",
            $db = "users",
            $map = [
                'id' => $uid
            ],
            $param = 'id,id as uid,nickname,mobile,email,avatar,username,sex,city,addtime'
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
            $param = "add_uid as uid"
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
            $param = "friend_id as id,class_id,nickname as name,name as mark,avatar",
            $join = "im_users",
            $link = "im_friend.friend_id=im_users.id",
            $order = "name",
            $sort = "desc"
        );
    }

    //获取用户群信息列表
    public function userGroupList($uid)
    {
        $sql = "SELECT a.*,b.mannum 
        FROM (SELECT g.group_id,g.name as group_name,g.icon as avatar,gu.group_nick as nick,gu.addtime FROM im_group g INNER JOIN im_group_user gu ON gu.uid={$uid} and gu.group_id=g.group_id) a
        LEFT JOIN (SELECT count(uid) as mannum,group_id FROM im_group_user GROUP BY group_id) b
        ON a.group_id=b.group_id
        ORDER BY a.addtime DESC";

        $list = Db::query($sql);

        return $list;
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

    //获取用户关联群ID列表
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
    
    //获取群用户列表
    public function groupUserList($group_id,$num = 10)
    {
        $num <= 0 && $num = 10;

        $sql = "SELECT u.nickname,u.username,u.avatar,gu.uid,gu.group_nick as nick,gu.addtime 
        FROM im_users u
        INNER JOIN im_group_user gu
        ON gu.group_id={$group_id} and u.id=gu.uid
        ORDER BY gu.rig DESC,gu.addtime
        LIMIT 0,$num";

        //执行
        $list = Db::query($sql);

        return $list;

    }

    //获取用户好友会话记录
    //public function 

}