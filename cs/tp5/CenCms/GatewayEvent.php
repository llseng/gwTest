<?php
namespace CenCMS;

use \Session;
use CenCMS\ApiController;
use Workerman\Lib\Timer;
use \GatewayWorker\Lib\Gateway;
use app\gw\logic\SayLogic;

class GatewayEvent
{
	//记录链接数
    public static $connectNum = 0;

    //服务器内部 ID
	public static $clientId = 0;
	
	//api实例
	public static $api;

	//构造函数
	public function __construct()
	{

	}

	public static function Api()
	{
		if(!self::$api){

			self::$api = new ApiController();

		}

		return self::$api;

	}
	
	//当businessWorker进程启动时触发。每个进程生命周期内都只会触发一次。
	public static function onWorkerStart($businessWorker)
	{
		
	}
	
	//当客户端连接上gateway完成websocket握手时触发的回调函数。
	public static function onWebSocketConnect($client_id, $data)
	{
		
	}
	
	//当客户端连接上gateway进程时(TCP三次握手完毕时)触发的回调函数。
	public static function onConnect($client_id)
	{
	    static::$connectNum++;//链接数+1
        static::$clientId++;

		$_SESSION['cid'] = static::$clientId;

	    $connect_data = [

	        "type" => "login",

	        "connect_id" => $client_id,

            "client_id" => static::$clientId,

		];

		//返回连接信息
		Gateway::sendToClient($client_id,self::Api()->returnSuccess($connect_data));
		
		var_dump($_SESSION);
		var_dump($_SERVER);
		
	}
	
	//当客户端发来数据(Gateway进程收到数据)后触发的回调函数
	public static function onMessage($client_id, $message)
	{
		$json_data = json_decode($message,1);

		if($json_data)
		{

			switch($json_data['type'])
			{
				//单聊
				case "say":
					//聊天逻辑层
					$SayLogic = new SayLogic($_SESSION['uid'],$_SESSION['nickname']);
					
					Gateway::sendToclient($client_id,$SayLogic->say($json_data));
				break;

				default:
					Gateway::sendToClient($client_id,self::Api()->returnSuccess([],$message));
				break;
			}

		}else{
			echo "ERROR_JSON-";
		}

		echo $client_id . '|' . $_SESSION['uid'] . '|' . $message;

		var_dump($_SESSION);
		var_dump($_SERVER);
	}
	
	/*
	客户端与Gateway进程的连接断开时触发。不管是客户端主动断开还是服务端主动断开，都会触发这个回调。一般在这里做一些数据清理工作。
	注意：onClose回调里无法使用Gateway::getSession()来获得当前用户的session数据，但是仍然可以使用$_SESSION变量获得。
	注意：onClose回调里无法使用Gateway::getUidByClientId()接口来获得uid，解决办法是在Gateway::bindUid()时记录一个$_SESSION['uid']，onClose的时候用$_SESSION['uid']来获得uid。
	*/
	public static function onClose($client_id)
	{
		self::$connectNum--;//连接数-1
		
		

	}
	
	/*当businessWorker进程退出时触发。每个进程生命周期内都只会触发一次。
	可以在这里为每一个businessWorker进程做一些清理工作，例如保存一些重要数据等。
	注意：某些情况将不会触发onWorkerStop，例如业务出现致命错误FatalError，或者进程被强行杀死等情况。*/
	public static function onWorkerStop($businessWorker)
	{
		
	}
	
	
}

?>