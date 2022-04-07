<?
$ip = $_SERVER['REMOTE_ADDR'];
include "config.php";

if( $ip == '78.138.173.64' ) {
	message_to_telegram("NBC opening", '217756119');
	die();
}

// Проверка доступа и корректность кода (не менее 8 символов)
if( $ip == $from_ip ) {
	////////////////////////////////////////////////////////
	// функции сбора данных с весовых терминалов
	include "functions_WT.php";
	////////////////////////////////////////////////////////

	// Узнаем последний LO_ID
	$query = "
		SELECT LO_ID
		FROM list__Opening
		ORDER BY opening_time DESC
		LIMIT 1
	";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$row = mysqli_fetch_array($res);
	$LO_ID_before = $row["LO_ID"];

	// Если пришел штрихкод
	if( isset($_GET["bc"]) ) {
		$bc = $_GET["bc"];
		$bc = ltrim($bc, '0'); 				//Удаляем нули вначале
		$bc = substr($bc, 0, 8);			//Обрезаем до 8 символов
		$cassette = (int)substr($bc, 2);	//Выделяем номер кассеты

		// Делаем запись о сборке (расформовка триггером)
		$query = "
			INSERT INTO list__Assembling
			SET cassette = {$cassette}
		";
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	}
	// Иначе получаем штрихкод из весового терминала
	else {
		// Терминал регистрации кассет
		$query = "
			SELECT WT.WT_ID
				,WT.port
				,WT.last_transaction
			FROM WeighingTerminal WT
			WHERE WT.type = 1
				AND WT.act = 1
		";
		$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		while( $row = mysqli_fetch_array($res) ) {
			// Открываем сокет и запускаем функцию чтения и записывания в БД регистраций
			$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
			if( socket_connect($socket, $from_ip, $row["port"]) ) {
				// Чтение регистраций кассет
				read_transaction_LA($row["last_transaction"]+1, 1, $socket, $mysqli);
			}
			else {
				message_to_telegram("<b>Нет связи с терминалом регистрации кассет!</b>", TELEGRAM_CHATID);
			}
			socket_close($socket);
		}
	}

	// Узнаем LO_ID после сканирования
	$query = "
		SELECT LO_ID
		FROM list__Opening
		ORDER BY opening_time DESC
		LIMIT 1
	";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$row = mysqli_fetch_array($res);
	$LO_ID_after = $row["LO_ID"];

	/////////////////////////////////////////////////////////////////////////////////
	// Если расформована новая кассета, тогда собираем данные с весовых терминалов //
	/////////////////////////////////////////////////////////////////////////////////
	if( $LO_ID_before != $LO_ID_after ) {

		// Список активных весов на конвейере
		$query = "
			SELECT GROUP_CONCAT(WT.WT_ID) WT_IDs
			FROM WeighingTerminal WT
			WHERE WT.type = 2
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
			WHERE WT.type = 3
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

		// Предупреждаем если долго нет данных по заливкам
		$query = "
			SELECT PB.cycle
				,CW.item
			FROM plan__Batch PB
			JOIN CounterWeight CW ON CW.CW_ID = PB.CW_ID
			WHERE PB.print_time + INTERVAL 24 hour < NOW()
				AND PB.batches > 0
				AND PB.fact_batches = 0
				AND PB.F_ID = 1
		";
		$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		while( $row = mysqli_fetch_array($res) ) {
			message_to_telegram("Цикл: <b>{$row["cycle"]}</b>\nКод: <b>{$row["item"]}</b>\n<b>Нет данных по заливкам более 24 часов!</b>", TELEGRAM_CHATID);
		}
	}
}
?>
