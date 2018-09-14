<?php
namespace app\gw\logic;

use \Session;
use CenCms\ApiController;
use GatewayClient\Gateway;
use app\gw\logic\GetLogic;

class SayLogic extends ApiController
{
    public function __construct($session)
    {
        parent::__construct();

        //为了兼容 与 Gateway服务 （Gateway 里用 session::get("uid") 错误）
        $this->uid = $session['uid'];
        $this->nickname = $session['nickname'];
        $this->avatar = $session['avatar'];
    }

    //是否是好友
    private function ifFriend($friend_id)
    {
        return self::doQuery(
            $command = "find",
            $db = "friend",
            $map = [
                'uid' => $this->uid,
                'friend_id' => $friend_id
            ],
            $param = "id"
        );
    }
    
    //用户私聊
    public function say($post)
    {
        
        if(!$post['to_uid'] || !$post['content']) return self::returnError("缺少to_uid,content");

        //是否是好友
        $ifFriend = $this->ifFriend($post['to_uid']);

        if(!$ifFriend) return self::returnError("非好友,不可聊天");

        //信息类型
        $say_type = (int)$post['say_type'];

        //信息记录
        $info = [
            'uid' => $this->uid,
            'to_uid' => $post['to_uid'],
            'content' => $post['content'],
            'type_id' => $say_type,
            //Gateway服务 无 $_SERVER['REQUEST_TIME'] 数据
            'addtime' => time()
        ];

        //用户不在线
        if(!Gateway::isUidOnline($post['to_uid'])) {
            //保存聊天记录
            $save = self::setField(
                $command = "insert",
                $db = "message",
                $map = '',
                $param = $info
            );

            if(!$save) return self::returnError("发送失败");

            return self::returnSuccess([],"发送成功");
        }

        //用户在线 信息已读
        //$info['status'] = 1;

        //保存聊天记录
        $save = self::setField(
            $command = "insert",
            $db = "message",
            $map = '',
            $param = $info
        );

        if(!$save) return self::returnError("发送失败");
        
        //消息推送
        Gateway::sendToUid($post['to_uid'],self::returnSuccess(self::sayData("news",[
            ["uid"=>$this->uid,"say_type"=>$say_type,"nickname"=>$this->nickname,"avatar"=>$this->avatar,"content"=>$post['content'],"addtime"=>$info['addtime']]
        ])));

        return self::returnSuccess(["rec"=>$post['rec']],"发送成功。");
    }

    //是否添加了群
    private function ifUidGroup($group_id,$uid = '')
    {
        $uid = (int)$uid;

        return self::doQuery(
            $command = "find",
            $db = "group_user",
            $map = [
                'uid' => $uid ?: $this->uid,
                'group_id' => $group_id
            ],
            $param = "id,uid,group_nick,addtime"
        );
    }

    /**
     * 用户群聊
     */
    public function group_say($post)
    {
        if(!$post['to_group'] || !$post['content']) return self::returnError("缺少to_group,content");

        $post['to_group'] = (int)$post['to_group']; 
        $to_uid = (int)$post['to_uid']; 
        $say_type = (int)$post['say_type'];

        //是否是添加了群
        $ifUidGroup = $this->ifUidGroup($post['to_group']);
        if(!$ifUidGroup) return self::returnError("未加入群|已被踢出群");

        //消息详情
        $info = [
            'group_id' => $post['to_group'],
            'uid' => $this->uid,
            'content' => $post['content'],
            'say_type' => $say_type,
            'unick' => $ifUidGroup['group_nick'],
            'addtime' => time()
        ];

        if($to_uid)
        {
            //群内私聊
            $info['to_uid'] = $to_uid;

            //用户是否在群中
            if($thsi->ifUidGroup($post['to_group'],$to_uid)) return self::returnError("发送失败，用户不在群内");

            //是否在线 & 在线推送
            if(Gateway::isUidOnline($to_uid))
            {
                $info['state'] = 1; //消息接受状态
                //消息推送
                Gateway::sendToUid($post['to_uid'],self::retrunSuccess(self::sayData("group_news",[
                    $info
                ]),"有人在群内私聊您"));

            }

            //消息入库
            $res = self::setField(
                $command = "insertGetId",
                $db = "group_message_touser",
                $map = '',
                $param = $info
            );

        }else{
            //群聊
            //消息入库
            $res = self::setField(
                $command = "insertGetId",
                $db = "group_message",
                $map = '',
                $param = $info
            );

            if(!$res) return self::returnError("发送失败");

            //消息推送
            Gateway::sendToGroup("group_".$post['to_group'],self::returnSuccess(self::sayData("group_news",[
                $info
            ]),"您有一条群消息"),[$info['uid']]);

            /* //群消息已读改为 其他模式
            //在线用户列表
            $onlineUid = Gateway::getUidListByGroup("group_".$post['to_group']);
            
            //有人在线 并把已读记录改为最新
            if($onlineUid){
                //在线用户已读数据更新
                $update = self::setField(
                    $command = "update",
                    $db = "group_message_user",
                    $map = "uid in(".join(',',$onlineUid).")",
                    $param = [
                        "ms_id" => $res
                    ]
                );

            }
            */

        }

        return self::returnSuccess(["rec"=>$post['rec']],"发送成功");

    }

    /**
     * type 值简介
     */
    public static function sayData($type,$message)
    {
        $data = [
            "type" => $type,
            "list" => $message
        ];

        return $data;
    }

}
