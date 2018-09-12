<?php
namespace app\gw\logic;

use \Db;
use CenCMS\ApiController;
use GatewayClient\Gateway;
use app\gw\logic\GetLogic;
use app\common\logic\Upload;

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
        return self::setField(
            $command = "insert",
            $db = "group_message_user",
            $map = '',
            $param = [
                'uid' => $uid,
                'group_id' => $group_id
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

}