<?php
//GatewayWorker 服务器开启
namespace think;

use think\facade\Route;


/*if(strpos(strtolower(PHP_OS), 'win') === 0){
	exit("start.php not support windows.\n");
}*/

require_once __DIR__ . "/thinkphp/base.php";

// 执行应用并响应
Container::get('app')->bind("gw/startserver/server")->run()->send();

?>