<?
$bc = $_GET["bc"];
$ip = $_SERVER['REMOTE_ADDR'];
include "config.php";

// Проверка доступа
if( $ip == "91.144.175.13" ) {
	// Узнаем префикс штрихкода
	$prefix1 = substr($bc, 0, 1);
	$prefix2 = substr($bc, 0, 2);

	switch( $prefix1 ) {
		case "1":
			switch( $prefix2 ) {
				case "11":
					$cassette = (int)substr($bc, 2);
					// Записываем в базу переданные данные
					$query = "
						INSERT INTO cassette__Opening
						SET cassette = {$cassette}
					";
					mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
					break;
				/////////////////////////////////////
			}
			break;
		/////////////////////////////////////
		case "2": // Формы
			$SI_ID = (int)substr($bc, 1);
			// Записываем в базу переданные данные
			$query = "
				INSERT INTO shell__Log
				SET SI_ID = {$SI_ID}
			";
			mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			break;
		/////////////////////////////////////
	}
}
?>
