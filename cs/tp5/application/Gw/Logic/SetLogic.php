<?php
namespace app\gw\logic;

use \Db;
use CenCMS\ApiController;
use GatewayClient\Gateway;
use app\gw\logic\GetLogic;
use app\common\logic\Upload;
use app\gw\logic\SayLogic;

class SetLogic extends ApiController
{
    
    //绑定 Group组/群组
    public function bindGroup($uid,$group_id = '')
    {
        //用户UID 绑定的连接列表
        $linkList = Gateway::getClientIdByUid($uid);
        
        if(!$linkList) return false;

        //分组列表
        $group = [];
        if($group_id)
        {
            $group[]['group_id'] = $group_id;
        }else{
            //数据获取逻辑层
            $GetLogic = new GetLogic();
            //获取用户 所有群组
            $group = $GetLogic->groupList($uid);
        }
        
        if(!$group) return false;

        foreach($group as $key => $val)
        {
            foreach($linkList as $k => $v)
            {
                //每个 连接都绑定分组
                Gateway::joinGroup($v,"group_".$val['group_id']);
            }
        }
    }

    //用户添加 群消息关联记录
    public function setGroupReadNews($uid,$group_id)
    {
        $lastMsg = self::doQuery(
            $command = "find",
            $db = "group_message",
            $map = [
                'group_id' => $group_id
            ],
            $param = "max(id) as id"
        );

        return self::setField(
            $command = "insert",
            $db = "group_message_user",
            $map = '',
            $param = [
                'uid' => $uid,
                'group_id' => $group_id,
                'ms_id' => $lastMsg['id']
            ]
        );
    }

    //好友所有消息为已读
    public function readFriendMessage($uid,$friend_id)
    {
        $GetLogic = new GetLogic();

        $beforeTime = time() - ($GetLogic->nDay * 86400); //n天前

        //所有消息 为已读
        return Db::execute("UPDATE im_message SET status=1 WHERE addtime>{$beforeTime} and to_uid={$uid} and uid={$friend_id}");

    }

    //群消息 已读到最后
    public function readGroupMessage($uid,$group_id)
    {
        $GetLogic = new GetLogic();

        $beforeTime = time() - ($GetLogic->nDay * 86400); //n天前

        $max = Db::query("SELECT MAX(id) as max FROM im_group_message WHERE addtime>{$beforeTime} and group_id={$group_id}");
        
        //所有消息 为已读
        return Db::execute("UPDATE im_group_message_user SET ms_id=".$max[0]['max'].",uptime=".time()." WHERE uid={$uid} and group_id={$group_id}");
    }

    //上传图片
    public function upImage($movePath)
    {
        //上传图片
        $Upload = new Upload('img',['movePath'=>$movePath]);

        //上传信息
        $upInfo = $Upload->getUpInfo();

        return $upInfo;

    }

    //删除与好友的所有关联数据
    public function deleteFriendData($uid,$friend_id)
    {
        
        //执行存储过程
        $result = Db::query("call user_delete_friend({$uid},{$friend_id})");

        if(!$result) return false;

        if($result[0][0]['err']) return false;

        return true;
        
    }

    //删除群组 并且 所有关联数据
    public function deleteGroupData($uid,$group_id)
    {
        //执行存储过程
        $result = Db::query("call user_delete_group({$uid},{$group_id})");

        if(!$result) return false;

        if($result[0][0]['err']) return false;

        return true;
    }

    /**
     * 提示信息推送
     * type int
     * to_uid int | array
     * hint_key string
     */
    public function pushHint($to_uid,$content,$type = false)
    {
        //消息详情
        $info = [
            'say_type' => 2,
            'content' => $content,
            'addtime' => $_SERVER['REQUEST_TIME']
        ];

        if(!$type)
        {
            Gateway::sendToUid($to_uid,self::returnSuccess(SayLogic::sayData("hint",[
                $info
            ])));
        }else{
            Gateway::sendToUid("group_".$to_uid,self::returnSuccess(SayLogic::sayData("hint",[
                $info
            ])));
        }
    }

    /**
     * 添加好友提示
     */
    public function addFriendHint($uid,$to_uid)
    {
        $GetLogic = new GetLogic();
        //添加好友提示
        $content = $GetLogic->hintConfig("ADD_FRIEND");
        if(!$content) return false;

        $messageList = [];
        $uInfo = [
            'uid' => $uid,
            'to_uid' => $to_uid,
            'type_id' => 2,
            'addtime' => $_SERVER['REQUEST_TIME']
        ];
        //用户信息
        $uData = $GetLogic->getUserInfo($uid);

        $uInfo['content'] = preg_replace('/\$\{(\w*?)\}/',$uData['nickname'],$content);

        $messageList[] = $uInfo;
        
        $tuInfo = [
            'uid' => $to_uid,
            'to_uid' => $uid,
            'type_id' => 2,
            'addtime' => $_SERVER['REQUEST_TIME']
        ];
        //用户信息
        $tuData = $GetLogic->getUserInfo($to_uid);

        $tuInfo['content'] = preg_replace('/\$\{(\w*?)\}/',$tuData['nickname'],$content);

        $messageList[] = $tuInfo;

        //消息入库
        $result = self::setField(
            $command = "insertAll",
            $db = "message",
            $map = '',
            $param = $messageList
        );

        if(!$result) return false;

        $this->pushHint($to_uid,$uInfo['content']);

        $this->pushHint($uid,$tuInfo['content']);
    }


    //公搞信息推送 接口
    public function pushNotice()
    {

    }

}