<?php
namespace app\gw\logic;


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

    //上传图片
    public function upImage($movePath)
    {
        //上传图片
        $Upload = new Upload('img',['movePath'=>$movePath]);

        //上传信息
        $upInfo = $Upload->getUpInfo();

        return $upInfo;

    }

}