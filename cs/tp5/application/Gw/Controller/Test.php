<?php
namespace app\gw\controller;

use GatewayClient\Gateway;
use CenCMS\ApiController;
use think\facade\Session;
use think\Container;

class Test extends ApiController
{
	
	public function __construct()
	{
		Gateway::$registerAddress = '127.0.0.1:9527';
		
		/*
        try{
            $gatewayServer = stream_socket_client("tcp://" . Gateway::$registerAddress, $erron, $errstr, 3);
        }catch(\Exception $e) {
			//var_dump($gatewayServer, $erron, $errstr);
            exit("Service not open");
            return false;
        }

		var_dump($gatewayServer, $erron, $errstr);
		*/

	}
	
	//聊天demo
	public function index()
	{
		return view();
	}
	
	public function login()
	{
		$request = input();

		//var_dump(Gateway::isOnline('12345678901234567890'));die;
		
		//Gateway::bindUid($request["client_id"],$request["uid"]);
		
	}

	//
	public function say()
	{
		$post = self::getPost(['type','to_group','to_uid','content']);

		switch($post['type'])
		{
			case "say":
				if(!$psot['to_uid'] || $post['content']) return false;

				if(!Gateway::isUidOnline($post['to_uid'])) return false;

			break;
		}

	}
	
	//
	public function sign_out()
	{
		Session::clear();
	}
	
	public function test()
	{
		Gateway::sendToUid(1,"99999999999999999999");
	}
	
}