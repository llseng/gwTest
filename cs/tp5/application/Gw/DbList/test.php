<?php
error_reporting(E_ALL || ~E_NOTICE);

$dblist = include("./dbList.php");

//var_dump($dblist);

$mysqli = new mysqli("localhost","root","qq1300904522","test");

if($mysqli->connect_erron || $mysqli->connect_error)
{
	die("MYSQL ERROR[".$mysqli->connect_erron."] :" . $mysqli->connect_error);
}

function query($mysqli,$query)
{
	if($mysqli->query($query)){
		return true;
	}else{
		if($mysqli->error)
		{
			return "MYSQL ERROR[".$mysqli->erron."] :" . $mysqli->error;
		}else{
			return false;
		}
	};
}

$i = 0;
foreach($dblist as $key => $val)
{
	echo $key . "==>";
	//die($val);
	var_export(query($mysqli,$val));
	//echo "MYSQL ERROR[".$mysqli->erron."] :" . $mysqli->error . "\r\n";
	echo "\r\n";

	$i++;
}
?>