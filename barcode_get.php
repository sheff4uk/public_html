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
					//Проверяем была ли эта кассета уже просканирован в течении часа
					$query = "
						SELECT event_time
						FROM cassette__Opening
						WHERE cassette = {$cassette} AND event_time BETWEEN (NOW() - INTERVAL 1 HOUR) AND NOW()
					";
					$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
					// Если была кассета, то обновляем время
					if( mysqli_num_rows($res) ) {
						$row = mysqli_fetch_array($res);
						$query = "
							UPDATE cassette__Opening
							SET event_time = NOW()
							WHERE event_time = {$row["event_time"]} AND cassette = {$cassette}
						";
						mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
					}
					// Иначе добавляем кассету
					else {
						$query = "
							INSERT INTO cassette__Opening
							SET cassette = {$cassette}
						";
						mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
					}
					break;
				/////////////////////////////////////
			}
			break;
		/////////////////////////////////////
		case "2": // Формы
			$SI_ID = (int)substr($bc, 1);
			//Проверяем была ли форма уже просканирован в течении часа
			$query = "
				SELECT event_time
				FROM shell__Log
				WHERE SI_ID = {$SI_ID} AND event_time BETWEEN (NOW() - INTERVAL 1 HOUR) AND NOW()
			";
			$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			// Если формы не было, записываем её в базу
			if( mysqli_num_rows($res) == 0 ) {
				$query = "
					INSERT INTO shell__Log
					SET SI_ID = {$SI_ID}
				";
				mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			}
			break;
		/////////////////////////////////////
	}
}
?>
