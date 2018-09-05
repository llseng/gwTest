<?php
namespace CenCMS;

use think\Db;
use think\facade\Session;
use CenCMS\CenValidate;

require_once('CenCMS/CenValidate.php');

class ApiController
{

	protected $cenValidate;

	public function __construct() {
		
		$this->cenValidate = new CenValidate();
	}

	/**
	 * 状态判断返回值
	 * @param int    $status 状态码
	 * @param string $data   状态提示
	 */

	private function returnData($status, $data) {
        $echoData['status'] = $status;
        $echoData['data'] = $data;
		return $echoData;
        // return json_encode($returnData);
	}

	public function returnError($error) {
		return json_encode($this->returnData(0, $error));
	}

	public function returnSuccess($arr, $msg='') {
		$echoData = self::returnData(1, $msg);
		return json_encode(array_merge($arr, $echoData));
	}

	// 验证字段是否为空
	protected function checkNull($param) {
		if (empty($param)) {
			self::returnData(0,'请求失败');
		} else {
			return;
		}
	}

	protected function notNullData($param) {
		if (empty($param)) {
			self::returnData(0,'请求失败');
		} else {
			return $param;
		}
	}

	protected function checkIsset($param) {
		$a = isset($param) ? $param : '';
		return $a;
		// echo $_GET($param) == true ? "t" : "w";
	}
  
	/** 
	 * 判断字符串是否为json格式
	 * @param string  $data  json 字符串
	 * @param bool    $assoc 是否返回关联数组
	 */

	private function is_json($data, $assoc) {
		$decodeData = json_decode($data, $assoc);
		if (is_array($decodeData) && !empty(current($decodeData))) {
			return $decodeData;
		}
		return ;
	}

	/**
	 * 数据库操作 查询语句 
	 * @param string  	  $db 			数据表名
     * @param string 	  $join 		关联表名 别名 联表条件
	 * @param array 	  $map 		    查询条件
	 * @param string 	  $order 		条件 排序
	 * @param integer 	  $start|$num   起始条数 条数限制
	 * @param string 	  $param        查询字段名
	 * @param 			  $needDetail   判断条件
	 */

	public function doQuery($command, $db, $map='', $param='', $join='', $link='', $order='', $sort='', $start='', $num='', $compare='', $value='', $needDetail=(1!=1)) {
		if (is_array($map)) {
			switch ($command) {
				case 'find':
					$data = Db::name($db)->join($join,$link)->where($map)->order($order ,$sort)->limit($start, $num)->field($param)->find();
					break;
				case 'select':
					$data = Db::name($db)->join($join,$link)->where($map)->order($order ,$sort)->limit($start, $num)->field($param)->select();
					break;
				case 'count':
					$data = Db::name($db)->join($join,$link)->where($map)->order($order ,$sort)->limit($start, $num)->field($param)->count();
				default:
					break;
			}
		} elseif (is_string($map)) {
			switch ($command) {
				case 'find':
					$data = Db::name($db)->join($join,$link)->where($map,$compare,$value)->order($order ,$sort)->limit($start, $num)->field($param)->find();
					break;
				case 'select':
					$data = Db::name($db)->join($join,$link)->where($map,$compare,$value)->order($order ,$sort)->limit($start, $num)->field($param)->select();
					break;
				case 'count':
					$data = Db::name($db)->join($join,$link)->where($map,$compare,$num)->order($order ,$sort)->limit($start, $num)->field($param)->count();
				default:
					break;
			}
		}
		
		return $data;
    }

    /**
     * 数据库操作 增删改
     * @param array 		$param 修改字段和值
     * @param array|string  $map   查询条件
     * @param string 		$db    表名
     */

    protected function setField($command, $db, $map="", $param) {
        // 数据验证
        // $validate;
        // if ($validate) {
	        switch ($command) {
	        	case 'update':
					$update = Db::name($db)->where($map)->update($param);
					return $update;
	        		break;
	        	case 'insert':
					$insert = Db::name($db)->insert($param);
					return $insert;
	        		break;
				case 'insertAll':
					$insert = Db::name($db)->insertAll($param);
					return $insert;
					break;
				case 'insertGetId':
						$insert = Db::name($db)->insertGetId($param);
						return $insert;
						break;
	        	case 'delete':
					$delete = Db::name($db)->where($map)->delete();
					return $delete;
	        		break;
				default:
				return false;
	        		break;
	        }
    }

    /*

    * params 要验证的所有字段数组，只有一个也要是数组

    * 返回 错误信息

    */
    protected function getPost($params){
        
		//$post = json_decode(file_get_contents("php://input"),true);
		$post = input("post.");
		
        if (!$params) {
        	// 如果没有传入 params，则默认检测所有 POST 中的 keys
        	$params = array_keys($post);
        }
        foreach ($params as $key) {

            // 是否有这个字段
            if (!isset($post[$key])) {
                // echo $key.'不存在'; // 对 key 本地化
                echo self::returnError($key.'不存在');
                exit; // 终止本次访问
			}
			
			$value = $post[$key];
			
			$result = $this->cenValidate->dataValidate($key,$value);
			
            if ($result !== true) {
                echo self::returnError($result);
                exit;
            }
        }

        // 一切通过，返回该数据
        return $post;
    }

    //不同环境下获取真实的IP
    public function getIp(){
	    //判断服务器是否允许$_SERVER
	    if(isset($_SERVER)){    
	        if(isset($_SERVER['HTTP_X_FORWARDED_FOR'])){
	            $realip = $_SERVER['HTTP_X_FORWARDED_FOR'];
	        }elseif(isset($_SERVER['HTTP_CLIENT_IP'])) {
	            $realip = $_SERVER['HTTP_CLIENT_IP'];
	        }else{
	            $realip = $_SERVER['REMOTE_ADDR'];
	        }
	    }else{
	        //不允许就使用getenv获取  
	        if(getenv("HTTP_X_FORWARDED_FOR")){
	              $realip = getenv( "HTTP_X_FORWARDED_FOR");
	        }elseif(getenv("HTTP_CLIENT_IP")) {
	              $realip = getenv("HTTP_CLIENT_IP");
	        }else{
	              $realip = getenv("REMOTE_ADDR");
	        }
	    }

	    return $realip;
	}

    // 判断是否登录，已经登录返回 userid，未登录返回 false
    public function isLogin() {
        $userid = Session::get('userid');

        if (empty($userid)) {
            echo self::returnError('未登录');
            exit;
        }

        return $userid;
    }

}
