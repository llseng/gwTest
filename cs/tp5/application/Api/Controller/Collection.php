<?php
namespace app\api\controller;
use think\Db;
use think\facade\Session;
use CenCMS\ApiController;

class Collection extends ApiController
{
	// 收藏信息（action）
	public function collecting() {
		// 获取当前用户id
		$uid = self::isLogin();
		// 获取收藏类型
		$type = isset($_GET['type']) ? $_GET['type'] : '';
		// 获取收藏房屋或资讯id
		$pointer = isset($_GET['id']) ? $_GET['id'] : '';
		// 获取当前时间戳
		$ctime = time();
		if ($type && $pointer) {
			// 将type转化成数字
			switch ($type) {
				case 'rent':
					$type = 1;
					break;

				case 'sell':
					$type = 2;
					break;

				case 'recommendation':
					$type = 3;
					break;
				
				default:
					break;
			}
			// 查询用户是否已收藏该信息
			$check = self::doQuery(
				$command = "find",
				$db 	 = "collection",
				$map 	 = ["uid"=>$uid,'type'=>$type,'pointer'=>$pointer],
				$param 	 = "id"
			);

			// 如果收藏了，则取消收藏
			if ($check['id']) {
				$collection = self::setField(
							$command = "delete",
							$db 	 = "collection",
							$map 	 = ["id"=>$check['id']],
							$param   = ""
						);
				echo self::returnSuccess([],'取消收藏');exit;
			} else {
				switch ($type) {
					// 租房
					case 1:
						$collection = self::setField(
							$command = "insert",
							$db 	 = "collection",
							$map 	 = "",
							$param 	 = ["type"=>1,'pointer'=>$pointer,'uid'=>$uid,'ctime'=>$ctime]
						);
						break;
					// 购房
					case 2:
						$collection = self::setField(
							$command = "insert",
							$db 	 = "collection",
							$map 	 = "",
							$param 	 = ["type"=>2,'pointer'=>$pointer,'uid'=>$uid,'ctime'=>$ctime]
						);
						break;
					// 资讯
					case 3:
						$collection = self::setField(
							$command = "insert",
							$db 	 = "collection",
							$map 	 = "",
							$param 	 = ["type"=>3,'pointer'=>$pointer,'uid'=>$uid,'ctime'=>$ctime]
						);
						break;
					
					default:
						break;
				}
			}
			// 插入成功，返回提示信息
			if ($collection) {
				echo self::returnSuccess([],'收藏成功');
			}

		} else {
			// 插入失败，返回提示信息
			echo self::returnError('收藏失败');
			exit;
		}
	}

	// 收藏列表
	public function collected() {
		// 获取得到的类型
		$type = isset($_GET['type']) ? $_GET['type'] : '';
		if ($type === 'recommendation') {
			// 查询关联字段
			$pointer = self::doQuery(
				$command = "select",
				$db 	 = "collection",
				$map 	 = ["type"=>3],
				$param   = "pointer"
			);
			for ($i=0;$i<count($pointer);$i++) {
				// 根据关联数据查询出对应结果
				$recommendation[] = self::doQuery(
					$command = "find",
					$db      = "content",
					$map 	 = ['id'=>$pointer[$i]['pointer']],
					$param   = "id,title,info,type,ctime,pic_url,location,publisher,read_count",
					$join    = "",
					$link    = "",
					$order   = "ctime",
					$sort    = "desc"
				);
			}
			if ($recommendation) {
				echo self::returnSuccess(['collection'=>$recommendation]);
			}
		} else {
			$pointer = self::doQuery(
				$command = "select",
				$db 	 = "collection",
				$map 	 = [['type','in','1,2']],
				$param   = "pointer,type"
			);
			for ($i=0;$i<count($pointer);$i++) {
				// 根据关联数据查询出对应结果
				switch ($pointer[$i]['type']) {
					case '1':
						// 租房信息
						$rent[] = self::doQuery(
							$command = "find",
							$db      = "common_house",
							$map 	 = ['id'=>$pointer[$i]['pointer']],
							$param   = "id,pic_url,title,info,price,size,tags"
						);

						for ($j=0;$j<count($rent);$j++) {
							$rent[$j]['tags'] = explode(',',$rent[$j]['tags']);
						}

						break;

					case '2':
						// 购房信息
						$sell[] = self::doQuery(
							$command = "find",
							$db      = "common_sell",
							$map 	 = ['id'=>$pointer[$i]['pointer']],
							$param   = "id,pic_url,title,info,price,size,tags"
						);
						for ($j=0;$j<count($sell);$j++) {
							$sell[$j]['tags'] = explode(',',$sell[$j]['tags']);
						}

						break;
				}
			}
			$collection = array_merge($rent,$sell);
			echo self::returnSuccess(['collection'=>$collection]);
		}
	}
}