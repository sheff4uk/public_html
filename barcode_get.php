<?
$bc = $_GET["bc"];
$ip = $_SERVER['REMOTE_ADDR'];
include "config.php";

// Проверка доступа
if( $ip == "91.144.175.13" ) {
	// Узнаем префикс штрихкода
	$prefix = substr($bc, 0, 1);
	// Для префикса форм
	if( $prefix == "2" ) {
		$SI_ID = (int)substr($bc, 1);
		echo $SI_ID;
		// Записываем в базу переданные данные
		$query = "
			INSERT INTO shell__Log
			SET SI_ID = {$SI_ID}
		";
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	}
}
?>
