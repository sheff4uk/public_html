<?
$bc = $_GET["bc"];
$ip = $_SERVER['REMOTE_ADDR'];
include "config.php";
message_to_telegram($_GET["bc"], '217756119');

// Проверка доступа и корректность кода (не менее 8 символов)
if( $ip == $from_ip and strlen($bc) >= 8 ) {
	$bc = ltrim($bc, '0'); //Удаляем нули вначале
	$bc = str_pad($bc, 8, "0", STR_PAD_LEFT); //Достраиваем код нулями слева до 8 символов (для тестовых этикеток)

	$prefix = substr($bc, 0, 2); // Узнаем префикс штрихкода

	switch( $prefix ) {
		case "11":
			$bc = substr($bc, 0, 8);			//Обрезаем до 8 символов
			$cassette = (int)substr($bc, 2);	//Выделяем номер кассеты
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

				include "WTsocket.php"; // Сбор данных с весов

				// Список весов на конвейере
				$query = "
					SELECT GROUP_CONCAT(WT.WT_ID) WT_IDs
					FROM WeighingTerminal WT
					WHERE WT.type = 1
				";
				$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
				$row = mysqli_fetch_array($res);
				$WT_IDs = $row["WT_IDs"];
				$i = 0;

				// Попытки собрать данные и закрыть кассету
				while( $WT_IDs and $i < 16) {
					sleep(15); // Ждем 15 секунд

					// По этому списку весов собираем данные
					$query = "
						SELECT WT.port
							,WT.last_transaction
							,WT.post
						FROM WeighingTerminal WT
						WHERE WT.WT_ID IN ({$WT_IDs})
					";
					$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
					while( $row = mysqli_fetch_array($res) ) {
						// Открываем сокет и запускаем функцию чтения и записывания в БД регистраций
						$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
						if( socket_connect($socket, $from_ip, $row["port"]) ) {
							read_transaction($row["last_transaction"]+1, 1, $socket, $mysqli);
						}
						else {
							message_to_telegram("Post {$row["post"]} is offline!", '217756119');
						}
						socket_close($socket);

//						if( ($socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) and (socket_connect($socket, $from_ip, $row["port"])) ) {
//							read_transaction($row["last_transaction"]+1, 1, $socket, $mysqli);
//							socket_close($socket);
//						}
					}
					// Список весов, с которых получены еще не все данные
					$query = "
						SELECT GROUP_CONCAT(SUB.WT_ID) WT_IDs
						FROM (
							SELECT WT.WT_ID
							FROM list__Weight LW
							JOIN WeighingTerminal WT ON WT.WT_ID = LW.WT_ID
								AND WT.WT_ID NOT IN (
									SELECT WT_ID
									FROM list__Weight
									WHERE LO_ID = (SELECT LO_ID FROM list__Opening ORDER BY opening_time DESC LIMIT 1,1)
									GROUP BY WT_ID
								)
							WHERE LW.weighing_time > (SELECT opening_time FROM list__Opening ORDER BY opening_time DESC LIMIT 1,1)
								AND LW.weighing_time < (SELECT opening_time FROM list__Opening ORDER BY opening_time DESC LIMIT 0,1)
								AND LW.LO_ID IS NULL
							GROUP BY LW.WT_ID
						) SUB
					";
					$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
					$row = mysqli_fetch_array($res);
					$WT_IDs = $row["WT_IDs"];
					$i++;
				}

				// Телеграмм рассылка
				$query = "
					SELECT LO.LO_ID
						,DATE_FORMAT(LF.lf_date, '%d.%m.%Y') lf_date_format
						,DATE_FORMAT(LF.lf_time, '%H:%i') lf_time_format
						,DATE_FORMAT(LO.opening_time, '%H:%i') o_time_format
						,CW.item
						,o_interval(LO.LO_ID) maturation
						,LO.cassette
						,PB.in_cassette - LF.underfilling details
						,SUM(IF(LW.LW_ID, 1, 0)) cnt
						,ROUND(CW.weight + (CW.weight/100*CW.drying_percent)) weight
						,CW.drying_percent
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
					LIMIT 1,1
				";
				$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
				$row = mysqli_fetch_array($res);
				$LO_ID = $row["LO_ID"];

				// Формируем текст сообщения в Телеграм
				$message = "<b>[{$row["cassette"]}]</b> {$row["item"]}\n<b>{$row["maturation"]}</b>ч <i>{$row["lf_date_format"]} {$row["lf_time_format"]}</i>\nДеталей в кассете: <b>{$row["details"]}</b>\nДеталей в партии: <b>{$row["cnt"]}</b>\nЭталонный вес +{$row["drying_percent"]}%: <b>".number_format($row["weight"], 0, '', '\'')."</b>\nСредний вес: <b>".number_format($row["avg"], 0, '', '\'')."</b>\nМинимальный вес: <b>".number_format($row["min"], 0, '', '\'')."</b>\nМаксимальный вес: <b>".number_format($row["max"], 0, '', '\'')."</b>\nВремя cканирования: <b>{$row["o_time_format"]}</b>\n";

				// Выводим все веса
				$query = "
					SELECT LW.weight
						,WT.post
						,LW.goodsID
						,IF(LW.weight BETWEEN ROUND(CW.min_weight + (CW.min_weight/100*CW.drying_percent)) AND ROUND(CW.max_weight + (CW.max_weight/100*CW.drying_percent)), 0, IF(LW.weight > ROUND(CW.max_weight + (CW.max_weight/100*CW.drying_percent)), LW.weight - ROUND(CW.max_weight + (CW.max_weight/100*CW.drying_percent)), LW.weight - ROUND(CW.min_weight + (CW.min_weight/100*CW.drying_percent)))) diff
					FROM list__Weight LW
					JOIN WeighingTerminal WT ON WT.WT_ID = LW.WT_ID
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
					//$message .= "&#101".(21 + $row["post"])."; ".number_format($row["weight"], 0, '', '\'');
					$message .= "({$row["post"]}) ".number_format($row["weight"], 0, '', '\'');
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
				message_to_telegram($message, TELEGRAM_CHATID);
			}
			break;
		/////////////////////////////////////
//		case "22": // Формы
//			$SI_ID = (int)substr($bc, 1);
//			//Проверяем была ли форма уже просканирован в течении часа
//			$query = "
//				SELECT event_time
//				FROM shell__Log
//				WHERE SI_ID = {$SI_ID} AND event_time BETWEEN (NOW() - INTERVAL 1 HOUR) AND NOW()
//			";
//			$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
//			// Если формы не было, записываем её в базу
//			if( mysqli_num_rows($res) == 0 ) {
//				$query = "
//					INSERT INTO shell__Log
//					SET SI_ID = {$SI_ID}
//				";
//				mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
//			}
//			break;
		/////////////////////////////////////
		default: // TEST
			//Телеграм бот отправляет уведомление
			$message = "Test label <b>{$bc}</b>";
			message_to_telegram($message, TELEGRAM_CHATID);
			break;
		/////////////////////////////////////
	}
}
?>
