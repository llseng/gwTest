<?php
namespace app\api\controller;
use think\Db;
use think\facade\Session;
use CenCMS\ApiController;

Class Information extends ApiController
{
	public function information() {
		// 计算总条数
		$re_count = self::doQuery(
			$command = 'count',
			$db 	 = 'content'
		);
		
		// 分页
		$page = isset($_GET['page']) ? $_GET['page'] : 0;
		if ($page) {
			$pageNumber = 8;
			$pageStartNumber = ($page-1) * $pageNumber;
			if ($pageStartNumber > $re_count) {
				$page = ceil($re_count/$pageNumber);
				$pageStartNumber = ($page-1) * $pageNumber;
			}
		} else {
			$pageNumber = '';
			$pageStartNumber = 0;
		}
		// 获取资讯
		$everyPageData = self::recommendation($pageStartNumber,$pageNumber);
		// 获取广告
		// 1. get recommendatio nbetween 0~8
		// 2. query adversing where sort between 0~8
		// 3. 遍历 2 中的结果，插入 1 中结果对应 sort の 位置
		if ($page) {
			$advertisings = self::advertising('sort','in',"({$page}*8,({$page}+1)*8");
		} else {
			$advertisings = self::advertising();
		}
		for ($i=0;$i<count($advertisings);$i++) {
        	$advertising = $advertisings[$i];
        	$sort = $advertising['sort'];

        	$everyPageData = self::insertEle($everyPageData, [$sort => $advertising], $sort);
    	}
    	// 输出
    	echo self::returnSuccess(['information' => $everyPageData],'获取成功');
	}

	// 资讯
	public function recommendation($pageStartNumber,$pageNumber) {

		$recommendation = self::doQuery(
			$command  = "select",
			$db 	  = "content",
			$map 	  = "",
			$param 	  = "id,type,title,info,ctime,pic_url,location,read_count,publisher",
			$join 	  = "",
			$link     = "",
			$order    = "id",
			$sort     = "desc",
			$start 	  = $pageStartNumber,
			$num      = $pageNumber
		);

		return $recommendation;
	}

	// 广告
	public function advertising($maps='',$compareTo='',$values='') {
		$advertising = self::doQuery(
			$command  = "select",
			$db 	  = "advertising",
			$map 	  = $maps,
			$param 	  = "id,type,ctime,pic_url,video_url,sort",
			$join     = "",
			$link	  = "",
			$order	  = "",
			$sort 	  = "",
			$start 	  = "",
			$num 	  = "",
			$compare  = $compareTo,
			$value   = $values
		);

		return $advertising;
	}

	// arr - 要插入的数组
	// ele - 要插入的数据(广告)
	// postion 要插入的位置
	private function insertEle($arr, $ele, $position) {

		// 处理 $arr
    	array_splice($arr, $position, 0, $ele);

    	return $arr;
	}

	public function content() {
		// 获取GET
		$id = isset($_GET['id']) ? $_GET['id'] : '0';

		$content = self::doQuery(
			$command = "find",
			$db 	 = "content",
			$map 	 = ['id'=>$id],
			$param   = "uid,title,text,tag,ctime"
		);
		if ($content['tag']) {
			$content['tag'] = implode(',',$content['tag']);
		} else {
			$content['tag'] = array();
		}


		$user = self::doQuery(
			$command = "find",
			$db 	 = "users",
			$map 	 = ['id'=>$content['uid']],
			$param   = "id,nickname,avatar,tag",
			$join 	 = "cn_users_info",
			$link 	 = "cn_users.id=cn_users_info.uid"
		);
		if ($user['tag']) {
			$user['tag'] = implode(',',$user['tag']);
		} else {
			$user['tag'] = array();
		}

		$recommendation = self::doQuery(
			$command = "select",
			$db 	 = "content",
			$map 	 = "",
			$param 	 = "title,info,type,ctime,pic_url,location,publisher,read_count",
			$join 	 = "",
			$link 	 = "",
			$order 	 = "id",
			$sort 	 = "desc",
			$start 	 = 0,
			$num 	 = 4
		);

		$advertising = self::doQuery(
			$command = "select",
			$db 	 = "advertising",
			$map 	 = "",
			$param   = "id,type,ctime,pic_url,video_url,sort",
			$join 	 = "",
			$link 	 = "",
			$order 	 = "id",
			$sort 	 = "desc",
			$start 	 = 0,
			$num 	 = 2
		);

		echo self::returnSuccess(['content'=>$content,'user'=>$user,'recommendation'=>$recommendation,'advertising'=>$advertising]);
	}

}