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
					//Проверяем была ли эта кассета уже просканирована
					$query = "
						SELECT cassette
						FROM list__Opening
						ORDER BY opening_time DESC
						LIMIT 1
					";
					$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
					$row = mysqli_fetch_array($res);
					// Если это первое сканирование
					if( $row["cassette"] != $cassette ) {
						// Записываем номер кассеты в базу
						$query = "
							INSERT INTO list__Opening
							SET cassette = {$cassette}
						";
						mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
						$LO_ID = mysqli_insert_id( $mysqli );

						// Запрашиваем регистрации у весов
						include "WTsocket.php";
						$query = "
							SELECT WT.port
								,WT.last_transaction
							FROM WeighingTerminal WT
							WHERE WT.type = 1
						";
						$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
						while( $row = mysqli_fetch_array($res) ) {
							// Открываем сокет и запускаем функцию чтения и записывания в БД регистраций
							if( ($socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) and (socket_connect($socket, $from_ip, $row["port"])) ) {
								read_transaction($row["last_transaction"]+1, 1, $socket, $mysqli);
								socket_close($socket);
							}
						}

						$query = "
							SELECT LO.LO_ID
								,DATE_FORMAT(LF.lf_date, '%d.%m.%Y') lf_date_format
								,DATE_FORMAT(LF.lf_time, '%H:%i') lf_time_format
								,DATE_FORMAT(LO.opening_time, '%H:%i') o_time_format
								,CW.item
								,o_interval(LO.LO_ID) maturation
								,LO.cassette
								,CW.in_cassette - LF.underfilling details
								,SUM(IF(LW.LW_ID, 1, 0)) cnt
								,CW.weight
								,ROUND(AVG(LW.weight)) `avg`
								,MIN(LW.weight) `min`
								,MAX(LW.weight) `max`
							FROM list__Opening LO
							LEFT JOIN list__Weight LW ON LW.LO_ID = LO.LO_ID
							JOIN list__Filling LF ON LF.LF_ID = LO.LF_ID
							JOIN list__Batch LB ON LB.LB_ID = LF.LB_ID
							JOIN plan__Batch PB ON PB.PB_ID = LB.PB_ID
							JOIN CounterWeight CW ON CW.CW_ID = PB.CW_ID
							GROUP BY LO.LO_ID
							ORDER BY LO.opening_time DESC
							LIMIT 3
						";
						$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
						$row = mysqli_fetch_array($res);
						$row = mysqli_fetch_array($res);
						$row = mysqli_fetch_array($res); // Нужна третья с конца
						$LO_ID = $row["LO_ID"];

						// Формируем текст сообщения в Телеграм
						$message = "<b>[{$row["cassette"]}]</b> {$row["item"]}\n<b>{$row["maturation"]}</b>ч <i>{$row["lf_date_format"]} {$row["lf_time_format"]}</i>\nДеталей в кассете: <b>{$row["details"]}</b>\nДеталей в партии: <b>{$row["cnt"]}</b>\nЭталонный вес: <b>{$row["weight"]}</b>\nСредний вес: <b>{$row["avg"]}</b>\nМинимальный вес: <b>{$row["min"]}</b>\nМаксимальный вес: <b>{$row["max"]}</b>\nВремя cканирования: <b>{$row["o_time_format"]}</b>\n";

						// Выводим все веса
						$query = "
							SELECT LW.weight
								,LW.goodsID
								,IF(LW.weight BETWEEN ROUND(CW.min_weight * 1,02) AND ROUND(CW.max_weight * 1,02), 0, IF(LW.weight > ROUND(CW.max_weight * 1,02), LW.weight - ROUND(CW.max_weight * 1,02), LW.weight - ROUND(CW.min_weight * 1,02))) diff
							FROM list__Weight LW
							JOIN list__Opening LO ON LO.LO_ID = LW.LO_ID
							JOIN list__Filling LF ON LF.LF_ID = LO.LF_ID
							JOIN list__Batch LB ON LB.LB_ID = LF.LB_ID
							JOIN plan__Batch PB ON PB.PB_ID = LB.PB_ID
							JOIN CounterWeight CW ON CW.CW_ID = PB.CW_ID
							WHERE LW.LO_ID = {$LO_ID}
							ORDER BY LW.weighing_time
						";
						$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
						while( $row = mysqli_fetch_array($res) ) {
							$message .= $row["weight"];
							$message .= ($row["diff"] ? " <i>".($row["diff"] > 0 ? "+" : "").($row["diff"])."</i>" : "");
							switch ($row["goodsID"]) {
								case 2:
									$message .= " <b>непролив</b>";
									break;
								case 3:
									$message .= " <b>мех. трещина</b>";
									break;
								case 4:
									$message .= " <b>усад. трещина</b>";
									break;
								case 5:
									$message .= " <b>скол</b>";
									break;
								case 6:
									$message .= " <b>дефект формы</b>";
									break;
								case 7:
									$message .= " <b>дефект сборки</b>";
									break;
							}
							$message .= "\n";
						}

						//Телеграм бот отправляет уведомление
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
