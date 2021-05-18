<?
$bc = $_GET["bc"];
$bc = str_pad($bc, 8, "0", STR_PAD_LEFT);
$ip = $_SERVER['REMOTE_ADDR'];
include "config.php";

// Проверка доступа
if( $ip == $from_ip ) {
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
					// Если кассеты не было, записываем её в базу
					if( mysqli_num_rows($res) == 0 ) {
						$query = "
							INSERT INTO cassette__Opening
							SET cassette = {$cassette}
						";
						mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

						//Узнаем время заливки и код
						$query = "
							SELECT DATE_FORMAT(LF.lf_date, '%d.%m.%Y') lf_date_format
								,DATE_FORMAT(LF.lf_time, '%H:%i') lf_time_format
								,CW.item
								,TIMESTAMPDIFF(HOUR, CAST(CONCAT(LF.lf_date, ' ', LF.lf_time) AS DATETIME), NOW()) maturation
							FROM list__Filling LF
							JOIN list__Batch LB ON LB.LB_ID = LF.LB_ID
							JOIN plan__Batch PB ON PB.PB_ID = LB.PB_ID
							JOIN CounterWeight CW ON CW.CW_ID = PB.CW_ID
							WHERE LF.cassette = {$cassette}
							ORDER BY LF.lf_date DESC, LF.lf_time DESC
							LIMIT 1
						";
						$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
						$row = mysqli_fetch_array($res);
						//Телеграм бот отправляет уведомление
						$message = "<b>[{$cassette}]</b> {$row["item"]}\n<b>{$row["maturation"]}</b>ч <i>{$row["lf_date_format"]} {$row["lf_time_format"]}</i>";
						message_to_telegram($message);
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
		default: // TEST
			$test = (int)$bc;
			//Телеграм бот отправляет уведомление
			$message = "Test label <b>{$bc}</b>";
			message_to_telegram($message);
			break;
		/////////////////////////////////////
	}
}
?>
