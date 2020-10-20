<?
$key = $_GET["key"];
$t = $_GET["t"];
$h = $_GET["h"];
include "config.php";
// Проверка доступа
if( $key != $script_key ) die('Access denied!');

$ip = $_SERVER['REMOTE_ADDR'];

// Записываем в базу переданные показания
$query = "
	INSERT INTO Climate
	SET temperature = {$t}, humidity = {$h}, ip = '{$ip}'
";
mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
?>
