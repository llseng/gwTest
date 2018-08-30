<?php
namespace app\gw\controller;

//use Workerman\Workerman;
use Workerman\Worker;
use Workerman\Autoloader;
use GatewayWorker\Register;
use GatewayWorker\BusinessWorker;
use GatewayWorker\Gateway;


class Startserver
{
	
	public function __construct()
	{
		
	}
	
	//直接启动 GatewayWorker 的 3个端口监听
	public function Server()
	{
		
		//定制监听IP端口
		$Register = new Register("text://0.0.0.0:9527");
		
		
		
		//设置Gateway进程
		$Gateway = new Gateway("websocket://127.0.0.1:1997");
		
		//Gateway进程的名称，方便status命令中查看统计
		$Gateway->name = "cenIm";
		
		//Gateway进程数
		$Gateway->count = 4;
		
		//lanIp是Gateway所在服务器的内网IP，默认填写127.0.0.1即可。多服务器分布式部署的时候需要填写真实的内网ip，不能填写127.0.0.1。注意：lanIp只能填写真实ip，不能填写域名或者其它字符串，无论如何都不能写0.0.0.0 .
		$Gateway->lanIp = "127.0.0.1";
		
		//Gateway进程启动后会监听一个本机端口，用来给BusinessWorker提供链接服务，然后Gateway与BusinessWorker之间就通过这个连接通讯。这里设置的是Gateway监听本机端口的起始端口。比如启动了4个Gateway进程，startPort为2000，则每个Gateway进程分别启动的本地端口一般为2000、2001、2002、2003。
		//当本机有多个Gateway/BusinessWorker项目时，需要把每个项目的startPort设置成不同的段
		$Gateway->startPort = 9708;
		
		//registerAddress，注册服务地址，只写格式类似于 '127.0.0.1:1236'
		$Gateway->registerAddress = "127.0.0.1:9527";
		
		//和Worker一样，可以设置Gateway进程启动后的回调函数，一般在这个回调里面初始化一些全局数据
		$Gateway->onWorkerStart = function ($obj){
			
		};
		
		//和Worker一样，可以设置Gateway进程关闭的回调函数，一般在这个回调里面做数据清理或者保存数据工作
		$Gateway->onWorkerStop = function ($obj) {
			
		};
		
		
		
		//设置BusinessWorker进程
		$BusinessWorker = new BusinessWorker();
		
		//和Worker一样，可以设置BusinessWorker进程的名称，方便status命令中查看统计
		$BusinessWorker->name = "BusinessWorkerServer";
		
		//和Worker一样，可以设置BusinessWorker进程的数量，以便充分利用多cpu资源
		$BusinessWorker->count = 4;
		
		//注册服务地址，只写格式类似于 '127.0.0.1:1236'
		$BusinessWorker->registerAddress = "127.0.0.1:9527";
		
		//和Worker一样，可以设置BusinessWorker启动后的回调函数，一般在这个回调里面初始化一些全局数据
		$BusinessWorker->onWorkerStart = function ($obj){
			
		};
		
		//和Worker一样，可以设置BusinessWorker关闭的回调函数，一般在这个回调里面做数据清理或者保存数据工作
		$BusinessWorker->onWorkerStop = function ($obj) {
			
		};
		
		//设置使用哪个类来处理业务，默认值是Events，即默认使用Events.php中的Events类来处理业务。业务类至少要实现onMessage静态方法，onConnect和onClose静态方法可以不用实现。
		$BusinessWorker->eventHandler = "CenCMS\GatewayEvent";
		
		
		
		// 如果不是在根目录启动，则运行runAll方法
		if(!defined('GLOBAL_START'))
		{
			Worker::runAll();
		}
		
	}
	
	public function Register()
	{
		
		//定制监听IP端口  必须是text协议
		$Register = new Register("text://0.0.0.0:9527");
		
		// 如果不是在根目录启动，则运行runAll方法
		if(!defined('GLOBAL_START'))
		{
			Worker::runAll();
		}
	}
	
	
	public function Gateway()
	{
		
		//设置Gateway进程
		$Gateway = new Gateway("websocket://0.0.0.0:1997");
		
		//Gateway进程的名称，方便status命令中查看统计
		$Gateway->name = "cenIm";
		
		//Gateway进程数
		$Gateway->count = 4;
		
		//lanIp是Gateway所在服务器的内网IP，默认填写127.0.0.1即可。多服务器分布式部署的时候需要填写真实的内网ip，不能填写127.0.0.1。注意：lanIp只能填写真实ip，不能填写域名或者其它字符串，无论如何都不能写0.0.0.0 .
		$Gateway->lanIp = "127.0.0.1";
		
		//Gateway进程启动后会监听一个本机端口，用来给BusinessWorker提供链接服务，然后Gateway与BusinessWorker之间就通过这个连接通讯。这里设置的是Gateway监听本机端口的起始端口。比如启动了4个Gateway进程，startPort为2000，则每个Gateway进程分别启动的本地端口一般为2000、2001、2002、2003。
		//当本机有多个Gateway/BusinessWorker项目时，需要把每个项目的startPort设置成不同的段
		$Gateway->startPort = 9608;
		
		//registerAddress，注册服务地址，只写格式类似于 '127.0.0.1:1236'
		$Gateway->registerAddress = "127.0.0.1:9527";
		
		//Gateway 心跳测试
		$Gateway->pingInterval = 60;  //测试间隔 /秒

		$Gateway->pingNotResponseLimit = 1;

		$Gateway->pingData = '{"type":"ping"}';
		
		/*
		//和Worker一样，可以设置Gateway进程启动后的回调函数，一般在这个回调里面初始化一些全局数据
		$Gateway->onWorkerStart = function ($obj){
			
		};
		
		//和Worker一样，可以设置Gateway进程关闭的回调函数，一般在这个回调里面做数据清理或者保存数据工作
		$Gateway->onWorkerStop = function ($obj) {
			
		};
		*/
		// 如果不是在根目录启动，则运行runAll方法
		if(!defined('GLOBAL_START'))
		{
			Worker::runAll();
		}
	}
	
	public function BusinessWorker()
	{
		
		//设置BusinessWorker进程
		$BusinessWorker = new BusinessWorker();
		
		//和Worker一样，可以设置BusinessWorker进程的名称，方便status命令中查看统计
		$BusinessWorker->name = "BusinessWorkerServer";
		
		//和Worker一样，可以设置BusinessWorker进程的数量，以便充分利用多cpu资源
		$BusinessWorker->count = 4;
		
		//注册服务地址，只写格式类似于 '127.0.0.1:1236'
		$BusinessWorker->registerAddress = "127.0.0.1:9527";
		/*
		//和Worker一样，可以设置BusinessWorker启动后的回调函数，一般在这个回调里面初始化一些全局数据
		$BusinessWorker->onWorkerStart = function ($obj){
			
		};
		
		//和Worker一样，可以设置BusinessWorker关闭的回调函数，一般在这个回调里面做数据清理或者保存数据工作
		$BusinessWorker->onWorkerStop = function ($obj) {
			
		};
		*/
		//设置使用哪个类来处理业务，默认值是Events，即默认使用Events.php中的Events类来处理业务。业务类至少要实现onMessage静态方法，onConnect和onClose静态方法可以不用实现。
		$BusinessWorker->eventHandler = "CenCMS\GatewayEvent";
		
		// 如果不是在根目录启动，则运行runAll方法
		if(!defined('GLOBAL_START'))
		{
			Worker::runAll();
		}
	}
	
}

?>