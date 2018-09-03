<?php
namespace app\gw\logic;

use \Session;
use CenCms\ApiController;
use GatewayClient\Gateway;

class SayLogic extends ApiController
{
    
    public function say($post)
    {
        
        if(!$post['to_uid'] || !$post['content']) return self::returnError("缺少to_uid,content");

        $ifFriend = self::doQuery(
            $command = "find",
            $db = "friend",
            $map = [
                'uid' => session::get("uid"),
                'friend_id' => $post['to_uid']
            ],
            $param = "id"
        );

        if(!$ifFriend) return self::returnError("非好友,不可聊天");

        $info = [
            'uid' => Session::get('uid'),
            'to_uid' => $post['to_uid'],
            'content' => $post['content'],
            'addtime' => $_SERVER['REQUEST_TIME']
        ];

        //用户不在线
        if(!Gateway::isUidOnline($post['to_uid'])) {
            
            $save = self::setField(
                $command = "insert",
                $db = "message",
                $map = '',
                $param = $info
            );

            if(!$save) return self::returnError("发送失败");

            return self::returnSuccess([],"发送成功");
        }

        $info['status'] = 1;

        
        $save = self::setField(
            $command = "insert",
            $db = "message",
            $map = '',
            $param = $info
        );

        if(!$save) return self::returnError("发送失败");

        Gateway::sendToUid($post['to_uid'],self::returnSuccess(["type"=>"say","uid"=>session::get('uid'),"nickname"=>session::get("nickname")],$post['content']));

        return self::returnSuccess([],"发送成功");
    }

}
