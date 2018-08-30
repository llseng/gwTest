<?php
namespace app\api\controller;
use think\Db;
use think\facade\Session;
use CenCMS\ApiController;

class House extends ApiController
{
    // 查找房源
    public function FindHouse() {
        // TODO  where 条件
        $where = '';
        $price = isset($_GET['price']) ? $_GET['price'] : '';
        $price = isset($_GET['type']) ? $_GET['type'] : '';
        if ($price) {
        	$where['price'] = $_GET['price'];
        	$where['type'] = $_GET['type'];
        }
        // $where['price']=['price','=',1];
        // $where2['status']=['status','=',2];
        // $where=array_merge($where1,$where);
        // 获取分页
        $num = empty(session::get('num')) ? 0 : session::get('num') + 1;
        session::set('num',$num);
        // 点击分页
        $pageNum = $num * 8;
        // 房屋简介信息

        $houseData = self::doQuery(
	        $command  = 'select',
	        $db     = 'common_house',
	        $map    = $where,
	        $param  = 'id,pic_url,type,title,info,price,tags,name,size',
	        $join   = '',
	        $link   = '',
	        $order  = 'id',
	        $sort   = 'desc',
	        $start  = $pageNum,
	        $num    = 8
        );

        $house = array();
        for ($i = 0; $i < count($houseData); $i++) { 
            $tmpHouse = $houseData[$i];
            $tags = ['tags'=>explode(',',$tmpHouse['tags'])];
            $tmpHouse = array_merge($tmpHouse,$tags);
            array_push($house, $tmpHouse);
        }
        
        echo self::returnSuccess(['FindHouse'=>$house]);
    }

    // 获取基础设施键值对
    public function getInfomationEquipments() {
        $equipment = array();
        $equipments = self::doQuery(
            $command  = 'select',
            $db     = 'equipment',
            $map    = '',
            $param  = '*'
        );
        // 遍历出基础设施的中英文
        foreach ($equipments as $value) {
            $equipment[$value['en_equipment']] = $value['ch_equipment'];
        }
        echo self::returnSuccess(["equipment" => $equipment]);
    }

    public function postInfomation() {
        // 获取租房类型
        @$type = self::checkIsset($_GET['type']);
        $type = self::notNullData($_GET['type']); 

        switch ($type) {
            case 'entire':
                $type = 0;
            break;
            case 'shared':
                $type = 1;
            break;
            case 'shop':
                $type = 2;
            break;
            case 'office':
                $type = 3;
            break;
        }

        $house = self::getPost([]);

        // 基础设施
        $equipments = array();
        foreach ($house['equipment'] as $key => $value) {
            if ($value == 1) {
                $equipments[] = self::doQuery(
                    $command  = 'find',
                    $db     = 'equipment',
                    $map    = ['en_equipment'=>$key],
                    $param  = 'id'
                );
            }
        }

        $equipmentid = Array();
        foreach ($equipments as $keys => $values) {
                $equipmentid[] = $values['id'];       
        }
        $equipment = implode(",", $equipmentid);

        $houseType = isset($house['houseType']) ? json_encode($house['houseType']) : '';

        $shareType = isset($house['houseType']) ? json_encode($house['houseType']) : '';

        // 获取当前时间戳
        $addtime = time();
        // 获取客户端IP
        $ip = self::getIp();

        if ($house) {
            if ($type==0) { 
                // 整租
                $entire = self::setField(
                    $command  = 'insert',
                    $db     = 'entire_house',
                    $map    = '',
                    $param  = [
                        'detailAddress'=>$house['detailAddress'],
                        'houseType'=>$houseType,
                        'floor'=>$house['floor']['floor'],
                        'total_floor'=>$house['floor']['total'],
                        'manageFee'=>$house['manageFee'],
                        'payWay'=>$house['payWay'],
                        'direction'=>$house['direction'],
                        'equipment'=> $equipment,
                        'decorationDegree'=>$house['decorationDegree'],
                        'seeHouseDate'=>$house['seeHouseDate'],
                        'liveInDate'=>$house['liveInDate'],
                        'addtime'=>$addtime,
                        'ip'=>$ip,
                        'name'=>$house['name'],
                        'mobile'=>$house['mobile'],
                        'sex'=>$house['sex'],
                        'freeCall'=>$house['freeCall'],
                        'pics_url'=>implode(',',$house['pics_url'])
                    ]
                ); 
                if ($entire) {
                    echo self::returnSuccess([],'插入数据成功');
                    $pointer = self::doQuery(
                        $command  = 'find',
                        $db     = 'entire_house',
                        $map    = '',
                        $param  = 'id',
                        $join   = '',
                        $link   = '',
                        $order  = 'id',
                        $sort   = 'desc',
                        $start  = 0,
                        $num    = 1
                    );
                } else {
                    echo self::returnError('插入数据失败');
                    exit;
                }
            } elseif ($type==1) {
                // 合租
                $shared = self::setField(
                    $command  = 'insert',
                    $db     = 'shared_house',
                    $map    = '',
                    $param  = [
                        'detailAddress'=>$house['detailAddress'],
                        'shareType'=>$shareType,
                        'floor'=>$house['floor']['floor'],
                        'total_floor'=>$house['floor']['total'],
                        'manageFee'=>$house['manageFee'],
                        'payWay'=>$house['payWay'],
                        'direction'=>$house['direction'],
                        'equipment'=> $equipment,
                        'detail'=>'',
                        'decorationDegree'=>$house['decorationDegree'],
                        'seeHouseDate'=>$house['seeHouseDate'],
                        'liveInDate'=>$house['liveInDate'],
                        'addtime'=>$addtime,
                        'ip'=>$ip,
                        'name'=>$house['name'],
                        'mobile'=>$house['mobile'],
                        'sex'=>$house['sex'],
                        'freeCall'=>$house['freeCall'],
                        'pics_url'=>implode(',',$house['pics_url'])
                    ]
                );
                if ($shared) {
                    echo self::returnSuccess([],'插入数据成功');
                    $pointer = self::doQuery(
                        $command  = 'find',
                        $db     = 'shared_house',
                        $map    = '',
                        $param  = 'id',
                        $join   = '',
                        $link   = '',
                        $order  = 'id',
                        $sort   = 'desc',
                        $start  = 0,
                        $num    = 1
                    );
                } else {
                    echo self::returnError('插入数据失败');
                    exit;
                }
            } elseif ($type==2) {
                // 商铺
                $shop = self::setField(
                    $command  = 'insert',
                    $db     = 'shop_house',
                    $map    = '',
                    $param  = [
                        'currentState'=>$house['currentState'],
                        'transfer'=>$house['transfer'],
                        'floor'=>$house['floor']['floor'],
                        'total_floor'=>$house['floor']['total'],
                        'manageFee'=>$house['manageFee'],
                        'payWay'=>$house['payWay'],
                        'direction'=>$house['direction'],
                        'split'=>$house['split'],
                        'equipment'=> $equipment,
                        'decorationDegree'=>$house['decorationDegree'],
                        'manageType'=>$house['manageType'],
                        'seeHouseDate'=>$house['seeHouseDate'],
                        'liveInDate'=>$house['liveInDate'],
                        'addtime'=>$addtime,
                        'ip'=>$ip,
                        'businessLicense'=>$house['businessLicense'],
                        'name'=>$house['name'],
                        'mobile'=>$house['mobile'],
                        'sex'=>$house['sex'],
                        'freeCall'=>$house['freeCall'],
                        'pics_url'=>implode(',',$house['pics_url'])
                    ]
                );
                if ($shop) {
                    echo self::returnSuccess([],'插入数据成功');
                    $pointer = self::doQuery(
                        $command  = 'find',
                        $db     = 'shop_house',
                        $map    = '',
                        $param  = 'id',
                        $join   = '',
                        $link   = '',
                        $order  = 'id',
                        $sort   = 'desc',
                        $start  = 0,
                        $num    = 1
                    );
                } else {
                    echo self::returnError('插入数据失败');
                    exit;
                }
            } elseif ($type==3) {
                // 写字楼
                $office = self::setField(
                    $command  = 'insert',
                    $db     = 'office_house',
                    $map    = '',
                    $param  = [
                        'currentState'=>$house['currentState'],
                        'transfer'=>$house['transfer'],
                        'floor'=>$house['floor']['floor'],
                        'total_floor'=>$house['floor']['total'],
                        'manageFee'=>$house['manageFee'],
                        'payWay'=>$house['payWay'],
                        'direction'=>$house['direction'],
                        'split'=>$house['split'],
                        'equipment'=> $equipment,
                        'decorationDegree'=>$house['decorationDegree'],
                        'manageType'=>$house['manageType'],
                        'seeHouseDate'=>$house['seeHouseDate'],
                        'liveInDate'=>$house['liveInDate'],
                        'addtime'=>$addtime,
                        'ip'=>$ip,
                        'businessLicense'=>$house['businessLicense'],
                        'name'=>$house['name'],
                        'mobile'=>$house['mobile'],
                        'sex'=>$house['sex'],
                        'freeCall'=>$house['freeCall'],
                        'pics_url'=>implode(',',$house['pics_url'])
                    ]
                );
                if ($office) {
                    echo self::returnSuccess([],'插入数据成功');
                    $pointer = self::doQuery(
                        $command  = 'find',
                        $db     = 'office_house',
                        $map    = '',
                        $param  = 'id',
                        $join   = '',
                        $link   = '',
                        $order  = 'id',
                        $sort   = 'desc',
                        $start  = 0,
                        $num    = 1
                    );
                } else {
                    echo self::returnError('插入数据失败');
                    exit;
                }
            }

            // 公共部分
            $common = self::setField(
                $command  = 'insert',
                $db     = 'common_house',
                $map    = '',
                $param  = [
                    'master'=>'',
                    'pointer'=>$pointer['id'],
                    'title'=>$house['title'],
                    'type'=>$type,
                    'name'=>$house['name'],
                    'pic_url'=>$house['pic_url'],
                    'price'=>$house['price'],
                    'size'=>$house['size'],
                    'info'=>$house['info'],
                    'tags'=>'在售'
                ]
            );
        }
    }

}
