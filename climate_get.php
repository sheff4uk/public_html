<?
$t1 = $_GET["t1"];
$h1 = $_GET["h1"];
$t2 = $_GET["t2"];
$h2 = $_GET["h2"];
$ip = $_SERVER['REMOTE_ADDR'];
include "config.php";

// Проверка доступа
if( !(strpos($ip, "92.255") === 0) ) die('Access denied!');


// Записываем в базу переданные показания
$query = "
	INSERT INTO Climate
	SET t1 = IF({$t1} != 0 AND {$h1} != 0, {$t1}, NULL)
		,h1 = IF({$t1} != 0 AND {$h1} != 0, {$h1}, NULL)
		,t2 = IF({$t2} != 0 AND {$h2} != 0, {$t2}, NULL)
		,h2 = IF({$t2} != 0 AND {$h2} != 0, {$h2}, NULL)
		,ip = '{$ip}'
";
mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
?>
