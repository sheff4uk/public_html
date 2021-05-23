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
						SELECT opening_time
						FROM list__Opening
						WHERE cassette = {$cassette} AND opening_time BETWEEN (NOW() - INTERVAL 1 HOUR) AND NOW()
					";
					$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
					// Если это первое сканирование
					if( mysqli_num_rows($res) == 0 ) {
						// Записываем номе кассеты в базу
						$query = "
							INSERT INTO list__Opening
							SET cassette = {$cassette}
						";
						mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
						$LO_ID = mysqli_insert_id( $mysqli );

						// Запрашиваем регистрации у весов
						include "socket.php";
						$query = "
							SELECT WT.port
								,WT.last_transaction
								#,LW.LO_ID
							FROM WeighingTerminal WT
							#JOIN list__Weight LW ON LW.WT_ID = WT.WT_ID AND LW.nextID = WT.last_transaction
							WHERE WT.type = 1
						";
						$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
						while( $row = mysqli_fetch_array($res) ) {
							// Открываем сокет и запускаем функцию чтения и записывания в БД регистраций
							if( ($socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) and (socket_connect($socket, $from_ip, $row["port"])) ) {
								read_transaction($row["last_transaction"]+1, 1, $socket, $row["LO_ID"], $mysqli);
								socket_close($socket);
							}
						}

						//Узнаем время заливки и код
						$query = "
							SELECT DATE_FORMAT(LF.lf_date, '%d.%m.%Y') lf_date_format
								,DATE_FORMAT(LF.lf_time, '%H:%i') lf_time_format
								,CW.item
								,o_interval(LO.LO_ID) maturation
							FROM list__Opening LO
							JOIN list__Filling LF ON LF.LF_ID = LO.LF_ID
							JOIN list__Batch LB ON LB.LB_ID = LF.LB_ID
							JOIN plan__Batch PB ON PB.PB_ID = LB.PB_ID
							JOIN CounterWeight CW ON CW.CW_ID = PB.CW_ID
							WHERE LO.LO_ID = {$LO_ID}
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
