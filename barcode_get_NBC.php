<?
$ip = $_SERVER['REMOTE_ADDR'];
include "config.php";

$F_ID = 2;
$query = "
	SELECT from_ip
		,notification_group
	FROM factory
	WHERE F_ID = {$F_ID}
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
$row = mysqli_fetch_array($res);
$from_ip = $row["from_ip"];
$notification_group = $row["notification_group"];

// Проверка доступа и корректность кода (не менее 8 символов)
//if( $ip == $from_ip ) {
	////////////////////////////////////////////////////////
	// функции сбора данных с весовых терминалов
	include "functions_WT.php";
	////////////////////////////////////////////////////////

	// Узнаем последний LO_ID
	$query = "
		SELECT LO.LO_ID
		FROM list__Opening LO
		WHERE LO.F_ID = {$F_ID}
		ORDER BY LO.opening_time DESC
		LIMIT 1
	";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$row = mysqli_fetch_array($res);
	$LO_ID_before = $row["LO_ID"];

	// Получаем номер кассеты из весового терминала
	$query = "
		SELECT WT.WT_ID
			,WT.port
			,WT.last_transaction
		FROM WeighingTerminal WT
		WHERE WT.F_ID = {$F_ID}
			AND WT.type = 1
			AND WT.act = 1
	";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$row = mysqli_fetch_array($res);
	// Открываем сокет и запускаем функцию чтения и записи в БД регистраций
	$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
	if( socket_connect($socket, $from_ip, $row["port"]) ) {
		// Чтение регистраций кассет
		read_transaction_LA($row["last_transaction"]+1, 1, $socket, $mysqli);
	}
	else {
		message_to_telegram("<b>Нет связи с терминалом расформовки!</b>", $notification_group);
	}
	socket_close($socket);

	// Узнаем LO_ID и дату заливки сканированной кассеты
	$query = "
		SELECT LO.LO_ID
			,DATE_FORMAT(LF.filling_time - INTERVAL 7 HOUR, '%d/%m/%y') filling_time_format
			,IF(TIME(LF.filling_time) BETWEEN '07:00:00' AND '18:59:59', 1, 2) shift
		FROM list__Opening LO
		WHERE LO.F_ID = {$F_ID}
		ORDER BY LO.opening_time DESC
		LIMIT 1
	";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$row = mysqli_fetch_array($res);
	$LO_ID_after = $row["LO_ID"];
	$filling_time_format = $row["filling_time_format"]." (".$row["shift"].")";

	/////////////////////////////////////////////////////////////////////////////////
	// Если расформована новая кассета, тогда собираем данные с весовых терминалов //
	/////////////////////////////////////////////////////////////////////////////////
	if( $LO_ID_before != $LO_ID_after ) {

//		// Список активных весов на конвейере
//		$query = "
//			SELECT GROUP_CONCAT(WT.WT_ID) WT_IDs
//			FROM WeighingTerminal WT
//			WHERE WT.F_ID = {$F_ID}
//				AND WT.type = 2
//				AND WT.act = 1
//		";
//		$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
//		$row = mysqli_fetch_array($res);
//		$WT_IDs = $row["WT_IDs"];
//		$i = 0;
//
//		// Попытки собрать данные и закрыть кассету
//		while( $WT_IDs and $i < 16) {
//			sleep(15); // Ждем 15 секунд
//
//			// По этому списку весов собираем данные
//			$query = "
//				SELECT WT.WT_ID
//					,WT.post
//					,WT.port
//					,WT.last_transaction
//				FROM WeighingTerminal WT
//				WHERE WT.WT_ID IN ({$WT_IDs})
//			";
//			$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
//			while( $row = mysqli_fetch_array($res) ) {
//				// Открываем сокет и запускаем функцию чтения и записывания в БД регистраций
//				$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
//				if( socket_connect($socket, $from_ip, $row["port"]) ) {
//					read_transaction_LW($row["last_transaction"]+1, 1, $socket, $mysqli);
//				}
//				else {
//					message_to_telegram("Пост <b>{$row["post"]}</b>\n<b>Нет связи с весами!</b>", $notification_group);
//				}
//				socket_close($socket);
//			}
//			// Список весов, с которых получены еще не все данные
//			$query = "
//				SELECT GROUP_CONCAT(SUB.WT_ID) WT_IDs
//				FROM (
//					SELECT WT.WT_ID
//					FROM list__Weight LW
//					JOIN WeighingTerminal WT ON WT.WT_ID = LW.WT_ID
//						AND WT.F_ID = {$F_ID}
//						AND WT.WT_ID NOT IN (
//							SELECT WT_ID
//							FROM list__Weight
//							WHERE LO_ID = (SELECT LO_ID FROM list__Opening ORDER BY opening_time DESC LIMIT 1,1)
//							GROUP BY WT_ID
//						)
//					WHERE LW.weighing_time > (SELECT opening_time FROM list__Opening ORDER BY opening_time DESC LIMIT 1,1)
//						AND LW.weighing_time < (SELECT opening_time FROM list__Opening ORDER BY opening_time DESC LIMIT 0,1)
//						AND LW.LO_ID IS NULL
//					GROUP BY LW.WT_ID
//				) SUB
//			";
//			$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
//			$row = mysqli_fetch_array($res);
//			$WT_IDs = $row["WT_IDs"];
//			$i++;
//		}
//		////////////////////////////////////////////////////////////
//
//		// По этому списку терминалов собираем данные по упаковке паллетов
//		$query = "
//			SELECT WT.WT_ID
//				,WT.port
//				,WT.last_transaction
//			FROM WeighingTerminal WT
//			WHERE WT.F_ID = {$F_ID}
//				AND WT.type = 3
//				AND WT.act = 1
//		";
//		$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
//		while( $row = mysqli_fetch_array($res) ) {
//			// Открываем сокет и запускаем функцию чтения и записывания в БД регистраций
//			$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
//			if( socket_connect($socket, $from_ip, $row["port"]) ) {
//				// Чтение регистраций поддонов
//				read_transaction_LPP($row["last_transaction"]+1, 1, $socket, $mysqli);
//
//				// Запись в терминал даты заливки
//				set_terminal_text($filling_time_format, $socket, $mysqli);
//			}
//			else {
//				message_to_telegram("<b>Нет связи с терминалом этикетирования паллетов!</b>", $notification_group);
//			}
//			socket_close($socket);
//		}
//		////////////////////////////////////////////////////////////

		// Предупреждаем если долго нет данных по заливкам
		$query = "
			SELECT PB.cycle
				,CW.item
			FROM plan__Batch PB
			JOIN CounterWeight CW ON CW.CW_ID = PB.CW_ID
			WHERE PB.F_ID = {$F_ID}
				AND PB.print_time + INTERVAL 24 hour < NOW()
				AND PB.batches > 0
				AND PB.fact_batches = 0
				AND PB.F_ID = 1
		";
		$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		while( $row = mysqli_fetch_array($res) ) {
			message_to_telegram("Цикл: <b>{$row["cycle"]}</b>\nКод: <b>{$row["item"]}</b>\n<b>Нет данных по заливкам более 24 часов!</b>", $notification_group);
		}
	}
//}
?>
