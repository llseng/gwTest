<?php
namespace app\gw\controller;

use GatewayClient\Gateway;
use CenCMS\ApiController;
use think\facade\Session;
use think\Container;
use app\gw\logic\GetLogic;

use app\gw\logic\SayLogic;

class Test extends ApiController
{

	//用户聊天逻辑层
	public $SayLogic;
	
	public function __construct()
	{
		parent::__construct();

		Gateway::$registerAddress = '127.0.0.1:9527';

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
		$this->SayLogic = new SayLogic(Session::get('uid'),Session::get('nickname'));
		switch($post['type'])
		{
			case "say":
				return $this->SayLogic->say($post);

			break;
			case "group_say":
				if(!$psot['to_group'] || !$post['content']) return false;

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
		var_dump($_SESSION);
	}
	
}