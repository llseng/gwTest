<?php
namespace app\gw\logic;

use \Session;
use CenCms\ApiController;
use GatewayClient\Gateway;

class SayLogic extends ApiController
{
    public function __construct($uid,$nickname)
    {
        parent::__construct();

        //为了兼容 与 Gateway服务 （Gateway 里用 session::get("uid") 错误）
        $this->uid = $uid;
        $this->nickname = $nickname;
    }
    
    //用户私聊
    public function say($post)
    {
        
        if(!$post['to_uid'] || !$post['content']) return self::returnError("缺少to_uid,content");

        $ifFriend = self::doQuery(
            $command = "find",
            $db = "friend",
            $map = [
                'uid' => $this->uid,
                'friend_id' => $post['to_uid']
            ],
            $param = "id"
        );

        if(!$ifFriend) return self::returnError("非好友,不可聊天");

        $info = [
            'uid' => $this->uid,
            'to_uid' => $post['to_uid'],
            'content' => $post['content'],
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
        $info['status'] = 1;

        //保存聊天记录
        $save = self::setField(
            $command = "insert",
            $db = "message",
            $map = '',
            $param = $info
        );

        if(!$save) return self::returnError("发送失败");
        
        //消息推送
        Gateway::sendToUid($post['to_uid'],self::returnSuccess(self::sayData("news",[["uid"=>$this->uid,"nickname"=>$this->nickname,"content"=>$post['content']]])));

        return self::returnSuccess([],"发送成功。");
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
