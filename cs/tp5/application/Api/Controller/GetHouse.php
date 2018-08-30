<?php
namespace app\api\controller;
use think\Db;
use think\facade\Session;
use CenCMS\ApiController;

class GetHouse extends ApiController
{
    // 获取租房房源详细信息
    public function GetHouse() {
        @$type = self::checkIsset($_GET['type']);
        @$id = self::checkIsset($_GET['id']);
        $type = self::notNullData($type); 
        $id = self::notNullData($id); 
        switch ($type) {
            // 购房
            case 'sell':
           		$sell_common = self::doQuery(
           			$command = 'find',
           			$db 	 = 'common_sell',
           			$map 	 = ['id'=>$id],
           			$param 	 = 'housename,pic_url,price,size,info,pointer'
           		);
           		$house = self::doQuery(
		                	$command = 'find',
		                    $db     = 'sell_house',
		                    $map    = ['id'=>$sell_common['pointer']],
		                    $param  = 'pics_url,addtime,payWay,houseType,detailAddress,floor,total,direction,decorationDegree,seeHouseDate,seeHouseInfo,seeHouseinvoke,liveInDate,equipment,username,mobile'
		                );
           		// 基础设施
                $equipments = explode(',', $house['equipment']);
                $equipment = self::doQuery(
		                	$command = 'select',
		                	$db 	= 'equipment',
		                	$map 	= '',
		                	$param  = 'en_equipment'
		                );
                foreach ($equipment as $key => $value) {
                	$equipmentData[$value['en_equipment']] = 0;
                }
                foreach ($equipments as $val) {
	                $equipment = self::doQuery(
	                	$command = 'find',
	                	$db 	= 'equipment',
	                	$map 	= ['id'=>$val],
	                	$param  = 'en_equipment'
	                );
	        		$equipmentData[$equipment['en_equipment']] = 1;
                }
                $house['equipment'] = $equipmentData;
                // 获取楼层信息
                $floor['floor'] = $house['floor'];
                $floor['total'] = $house['total'];
                $house['floor'] = $floor;
                // 同价楼房
                $similarHouse = self::doQuery(
                	$command = 'select',
                	$db 	 = 'common_sell',
                	$map 	 = ['type'=>0,'price'=>$sell_common['price']],
                	$param   = 'id'
                );
                if ($house) {
                	echo self::returnSuccess(['houseInfo'=>['sellHouseInfo'=>array_merge($sell_common,$house),'similarHouse'=>$similarHouse]]);
                } else {
                	echo self::returnError('请求数据失败');
                }
            break;
            // 租房
            case 'rent':
                $house_common = self::doQuery(
                	$command = 'find',
                    $db     = 'common_house',
                    $map    = ['id'=>$id],
                    $param  = 'pointer,type,info,price,name,size'
                );

                switch ($house_common['type']) {
                	// 整租
                	case '0':
                		$house = self::doQuery(
		                	$command = 'find',
		                    $db     = 'entire_house',
		                    $map    = ['id'=>$house_common['pointer']],
		                    $param  = 'pics_url,addtime,payWay,houseType,detailAddress,floor,total_floor,direction,decorationDegree,seeHouseDate,liveInDate,equipment,latitude,longitude,name,mobile,uid'
		                );
		                // 基础设施
		                $equipments = explode(',', $house['equipment']);
		                $equipment = self::doQuery(
				                	$command = 'select',
				                	$db 	= 'equipment',
				                	$map 	= '',
				                	$param  = 'en_equipment'
				                );
		                foreach ($equipment as $key => $value) {
		                	$equipmentData[$value['en_equipment']] = 0;
		                }
		                foreach ($equipments as $val) {
			                $equipment = self::doQuery(
			                	$command = 'find',
			                	$db 	= 'equipment',
			                	$map 	= ['id'=>$val],
			                	$param  = 'en_equipment'
			                );
			        		$equipmentData[$equipment['en_equipment']] = 1;
		                }
                		$house['equipment'] = $equipmentData;
		                // 获取楼层信息
		                $floor['floor'] = $house['floor'];
		                $floor['total'] = $house['total_floor'];
                        $house['floor'] = $floor;
                        // 获取房主信息
		                $user = self::doQuery(
		                	$command = 'find',
		                	$db 	 = 'users',
		                	$map 	 = ['id'=>$house['uid']],
		                	$param   = 'avatar,username,nickname,idcard,id,mobile',
		                	$join    = 'cn_users_info',
		                	$link    = 'cn_users.id = cn_users_info.uid'
		                );
                        // 同价楼房
		                $similarHouse = self::doQuery(
		                	$command = 'select',
		                	$db 	 = 'common_house',
		                	$map 	 = ['type'=>0,'price'=>$house_common['price']],
		                	$param   = 'id'
		                );
                		break;
                	// 合租
                	case '1':
                		$house = self::doQuery(
		                	$command = 'find',
		                    $db     = 'shared_house',
		                    $map    = ['id'=>$house_common['pointer']],
		                    $param  = 'pics_url,addtime,payWay,shareType,detailAddress,floor,total_floor,direction,decorationDegree,seeHouseDate,liveInDate,equipment,latitude,longitude,name,mobile,uid'
		                );
		                // 基础设施
		                $equipments = explode(',', $house['equipment']);
		                $equipment = self::doQuery(
				                	$command = 'select',
				                	$db 	= 'equipment',
				                	$map 	= '',
				                	$param  = 'en_equipment'
				                );
		                foreach ($equipment as $key => $value) {
		                	$equipmentData[$value['en_equipment']] = 0;
		                }
		                foreach ($equipments as $val) {
			                $equipment = self::doQuery(
			                	$command = 'find',
			                	$db 	= 'equipment',
			                	$map 	= ['id'=>$val],
			                	$param  = 'en_equipment'
			                );
			        		$equipmentData[$equipment['en_equipment']] = 1;
		                }
                		$house['equipment'] = $equipmentData;
		                // 楼层信息
		                $floor['floor'] = $house['floor'];
		                $floor['total'] = $house['total_floor'];
                        $house['floor'] = $floor;
                        // 发布者信息
		                $user = self::doQuery(
		                	$command = 'find',
		                	$db 	 = 'users',
		                	$map 	 = ['id'=>$house['uid']],
		                	$param   = 'avatar,username,nickname,idcard,id,mobile',
		                	$join    = 'cn_users_info',
		                	$link    = 'cn_users.id = cn_users_info.uid'
		                );
                        // 同价楼房
		                $similarHouse = self::doQuery(
		                	$command = 'select',
		                	$db 	 = 'common_house',
		                	$map 	 = ['type'=>0,'price'=>$house_common['price']],
		                	$param   = 'id'
		                );
                		break;
                	// 商铺
                	case '2':
                		$house = self::doQuery(
		                	$command = 'find',
		                    $db     = 'shop_house',
		                    $map    = ['id'=>$house_common['pointer']],
		                    $param  = 'pics_url,addtime,payWay,currentState,floor,total_floor,transfer,manageFee,split,direction,decorationDegree,seeHouseDate,liveInDate,equipment,businessLicense,name,mobile,uid'
		                );
		                // 基础设施
		                $equipments = explode(',', $house['equipment']);
		                $equipment = self::doQuery(
				                	$command = 'select',
				                	$db 	= 'equipment',
				                	$map 	= '',
				                	$param  = 'en_equipment'
				                );
		                foreach ($equipment as $key => $value) {
		                	$equipmentData[$value['en_equipment']] = 0;
		                }
		                foreach ($equipments as $val) {
			                $equipment = self::doQuery(
			                	$command = 'find',
			                	$db 	= 'equipment',
			                	$map 	= ['id'=>$val],
			                	$param  = 'en_equipment'
			                );
			        		$equipmentData[$equipment['en_equipment']] = 1;
		                }
                		$house['equipment'] = $equipmentData;
		                // 楼层信息
		                $floor['floor'] = $house['floor'];
		                $floor['total'] = $house['total_floor'];
                        $house['floor'] = $floor;
                        // 发布者
		                $user = self::doQuery(
		                	$command = 'find',
		                	$db 	 = 'users',
		                	$map 	 = ['id'=>$house['uid']],
		                	$param   = 'avatar,username,nickname,idcard,id,mobile',
		                	$join    = 'cn_users_info',
		                	$link    = 'cn_users.id = cn_users_info.uid'
		                );
                        // 同价楼房
		                $similarHouse = self::doQuery(
		                	$command = 'select',
		                	$db 	 = 'common_house',
		                	$map 	 = ['type'=>0,'price'=>$house_common['price']],
		                	$param   = 'id'
		                );
                		break;
                	// 写字楼
                	case '3':
                		$house = self::doQuery(
		                	$command = 'find',
		                    $db     = 'shop_house',
		                    $map    = ['id'=>$house_common['pointer']],
		                    $param  = 'pics_url,addtime,payWay,currentState,floor,total_floor,transfer,manageFee,split,direction,decorationDegree,seeHouseDate,liveInDate,equipment,businessLicense,name,mobile,uid'
		                );
		                // 基础设施
		                $equipments = explode(',', $house['equipment']);
		                $equipment = self::doQuery(
				                	$command = 'select',
				                	$db 	= 'equipment',
				                	$map 	= '',
				                	$param  = 'en_equipment'
				                );
		                foreach ($equipment as $key => $value) {
		                	$equipmentData[$value['en_equipment']] = 0;
		                }
		                foreach ($equipments as $val) {
			                $equipment = self::doQuery(
			                	$command = 'find',
			                	$db 	= 'equipment',
			                	$map 	= ['id'=>$val],
			                	$param  = 'en_equipment'
			                );
			        		$equipmentData[$equipment['en_equipment']] = 1;
		                }
                		$house['equipment'] = $equipmentData;
		                // 楼层信息
		                $floor['floor'] = $house['floor'];
		                $floor['total'] = $house['total_floor'];
                        $house['floor'] = $floor;
		                $user = self::doQuery(
		                	$command = 'find',
		                	$db 	 = 'users',
		                	$map 	 = ['id'=>$house['uid']],
		                	$param   = 'avatar,username,nickname,idcard,id,mobile',
		                	$join    = 'cn_users_info',
		                	$link    = 'cn_users.id = cn_users_info.uid'
		                );
                        // 同价楼房
		                $similarHouse = self::doQuery(
		                	$command = 'select',
		                	$db 	 = 'common_house',
		                	$map 	 = ['type'=>0,'price'=>$house_common['price']],
		                	$param   = 'id'
		                );
                		break;
                	default:
                		echo self::returnError('请求数据失败');exit;
                		break;
                }
                if ($house) {
                	echo self::returnSuccess(['houseInfo'=>['mainHouseInfo'=>array_merge($house_common,$house),'floor'=>$floor,'user'=>$user,'similarHouse'=>$similarHouse]]);
                } else {
                	echo self::returnError('请求数据失败');
                }
                
            break;
            default:
                echo self::returnError('请求数据失败');
                break;
        }
    }
}