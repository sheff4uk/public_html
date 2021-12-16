<?
$bc = $_GET["bc"];
$ip = $_SERVER['REMOTE_ADDR'];
include "config.php";
//message_to_telegram($bc, '217756119');

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
				FROM list__Assembling
				ORDER BY assembling_time DESC
				LIMIT 1
			";
			$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			$row = mysqli_fetch_array($res);
			// Если это первое сканирование
			if( $row["cassette"] != $cassette ) {
				// Отлавливаем интересуемые кассеты
//				if( $cassette == 114 ) {
//					$message = "Кассета 114 прибыла на расформовку";
//					message_to_telegram($message, TELEGRAM_CHATID);
//				}

				// Узнаем была ли заливка этой кассеты после предыдущего сканирования
//				$query = "
//					SELECT LF.LF_ID
//					FROM list__Assembling LA
//					LEFT JOIN list__Filling LF ON LF.LA_ID = LA.LA_ID
//					WHERE LA.cassette = {$cassette}
//					ORDER BY LA.assembling_time DESC
//					LIMIT 1
//				";
				// Узнаем, залита ли кассета
				$query = "
					SELECT IF((SELECT filling_time FROM list__Filling WHERE cassette = {$cassette} ORDER BY filling_time DESC LIMIT 1) > (SELECT opening_time FROM list__Opening WHERE cassette = {$cassette} ORDER BY opening_time DESC LIMIT 1), 1, 0) is_filling
				";
				$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
				$row = mysqli_fetch_array($res);
				// Если эта кассета залита
				if( $row["is_filling"] == 1 ) {
					// Делаем запись о сборке
					$query = "
						INSERT INTO list__Assembling
						SET cassette = {$cassette}
					";
					mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

					// Делаем запись о расформовке
					$query = "
						INSERT INTO list__Opening
						SET cassette = {$cassette}
					";
					mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
				}
				else {
					// Обновляем время сборки у кассеты
					$query = "
						UPDATE list__Assembling
						SET assembling_time = now()
						WHERE cassette = {$cassette}
						ORDER BY assembling_time DESC
						LIMIT 1
					";
					mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
				}



				////////////////////////////////////////////////////////
				// функции сбора данных с весовых терминалов
				include "functions_WT.php";
				////////////////////////////////////////////////////////

				// Список весов на конвейере
				$query = "
					SELECT GROUP_CONCAT(WT.WT_ID) WT_IDs
					FROM WeighingTerminal WT
					WHERE WT.type = 1
						AND WT.act = 1
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
						SELECT WT.WT_ID
							,WT.post
							,WT.port
							,WT.last_transaction
						FROM WeighingTerminal WT
						WHERE WT.WT_ID IN ({$WT_IDs})
					";
					$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
					while( $row = mysqli_fetch_array($res) ) {
						// Открываем сокет и запускаем функцию чтения и записывания в БД регистраций
						$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
						if( socket_connect($socket, $from_ip, $row["port"]) ) {
							read_transaction_LW($row["last_transaction"]+1, 1, $socket, $mysqli);
						}
						else {
							message_to_telegram("Пост <b>{$row["post"]}</b>\n<b>Нет связи с весами!</b>", TELEGRAM_CHATID);
						}
						socket_close($socket);
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
				////////////////////////////////////////////////////////////

				// По этому списку терминалов собираем данные по упаковке паллетов
				$query = "
					SELECT WT.WT_ID
						,WT.port
						,WT.last_transaction
					FROM WeighingTerminal WT
					WHERE WT.type = 2
						AND WT.act = 1
				";
				$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
				while( $row = mysqli_fetch_array($res) ) {
					// Открываем сокет и запускаем функцию чтения и записывания в БД регистраций
					$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
					if( socket_connect($socket, $from_ip, $row["port"]) ) {
						// Чтение регистраций поддонов
						read_transaction_LPP($row["last_transaction"]+1, 1, $socket, $mysqli);

						// Узнаем дату заливки последней сканированной кассеты
						$query = "
							SELECT DATE_FORMAT(LF.filling_time - INTERVAL 7 HOUR, '%d/%m/%y') filling_time_format
								,IF(TIME(LF.filling_time) BETWEEN '07:00:00' AND '18:59:59', 1, 2) shift
							FROM list__Opening LO
							JOIN list__Filling LF ON LF.LF_ID = LO.LF_ID
							ORDER BY LO.opening_time DESC
							LIMIT 1
						";
						$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
						$subrow = mysqli_fetch_array($subres);
						$filling_time_format = $subrow["filling_time_format"]." (".$subrow["shift"].")";
						// Запись в терминал дату заливки
						set_terminal_text($filling_time_format, $socket, $mysqli);
					}
					else {
						message_to_telegram("<b>Нет связи с терминалом этикетирования паллетов!</b>", TELEGRAM_CHATID);
					}
					socket_close($socket);
				}
				////////////////////////////////////////////////////////////

				// Предупреждаем если долго нет данных по замесам
				$query = "
					SELECT PB.cycle
						,CW.item
					FROM plan__Batch PB
					JOIN CounterWeight CW ON CW.CW_ID = PB.CW_ID
					WHERE PB.print_time + INTERVAL 24 hour < NOW()
						AND PB.batches > 0
						AND PB.fact_batches = 0
				";
				$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
				while( $row = mysqli_fetch_array($res) ) {
					message_to_telegram("Цикл: <b>{$row["cycle"]}</b>\nКод: <b>{$row["item"]}</b>\n<b>Нет данных по заливкам более 24 часов!</b>", TELEGRAM_CHATID);
				}
				///////////////////////////////////////////////////////////
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
			//message_to_telegram($message, TELEGRAM_CHATID);
			break;
		/////////////////////////////////////
	}
}
?>
