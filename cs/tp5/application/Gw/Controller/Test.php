<?php
namespace app\gw\controller;

use \Db;
use \Cache;
use GatewayClient\Gateway;
use CenCMS\ApiController;
use think\facade\Session;
use think\Container;
use app\gw\logic\GetLogic;

use app\common\logic\Upload;

use app\gw\logic\SayLogic;
use app\gw\logic\SetLogic;

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
	
	//聊天demo
	public function index_k()
	{
		return view();
	}

	//
	public function tests()
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
		$post = self::getPost(['type','to_group','to_uid','say_type','content']);
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
        //设置逻辑层
        $SetLogic = new SetLogic();
        //入群后提示信息
		var_dump($SetLogic->addGroupHint(3,4));
		
		var_dump(Gateway::getAllGroupIdList());
		
		var_dump(Gateway::getUidListByGroup('group_4'));
	}
	
}