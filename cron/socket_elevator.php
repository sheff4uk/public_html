<?php
$path = dirname(dirname($argv[0]));
$key = $argv[1];
include $path."/config.php";
// Проверка доступа
if( $key != $script_key ) die('Access denied!');

// Двоичная строка в массив отдельных байт
function byteStr2byteArray($s) {
	return array_slice(unpack("C*", "\0".$s), 1);
}

function crc16($buf) {
	$crc = 0;
	$tab = 5;
	for ($k = $tab; $k < count($buf); $k++) {
		$accumulator = 0;

		$temp = (($crc >> 8) << 8);

		for ($bits = 0; $bits < 8; $bits++) {
			if (($temp ^ $accumulator) & 0x8000) {
				$accumulator = (($accumulator << 1) ^ 0x1021);
				$accumulator = $accumulator & 0xFFFF;
			}
			else {
				$accumulator <<= 1;
				$accumulator = $accumulator & 0xFFFF;
			}
			$temp <<= 1;
			$temp = $temp & 0xFFFF;
		}
		$crc <<= 8;
		$crc = $crc & 0xFFFF;
		$crc = ($accumulator ^ $crc ^ ($buf[$k] & 0xff));
	}
	// Меняем местами байты и преобразуем в шестнадцатеричную строку
	return sprintf("%02x%02x", ($crc & 0xFF), (($crc >> 8) & 0xFF));
}

// Прочитать все регистрации начиная с ID
function read_transaction($ID, $curnum, $socket, $mysqli) {
	$hexID = sprintf("%02x%02x%02x%02x", ($ID & 0xFF), (($ID >> 8) & 0xFF), (($ID >> 16) & 0xFF), (($ID >> 24) & 0xFF));
	$hexcurnum = sprintf("%02x%02x", ($curnum & 0xFF), (($curnum >> 8) & 0xFF));
	$in = "\xF8\x55\xCE\x0C\x00\x92\x03\x00\x00".hex2bin($hexcurnum).hex2bin($hexID)."\x00\x00";
	$crc = crc16(byteStr2byteArray($in));
	$in .= hex2bin($crc);

	socket_write($socket, $in);

	//Заголовок
	$result = socket_read($socket, 3);

	//Длина тела сообщения
	$length = socket_read($socket, 2);
	$result .= $length;
	$length = hexdec(bin2hex($length));
	$length = (($length & 0xFF) << 8) + (($length >> 8) & 0xFF);

	// Тело сообщения
	$result .= socket_read($socket, $length);

	//Читаем CRC
	$crc = socket_read($socket, 2);

	//Ответ в массив
	$data = byteStr2byteArray($result);

	//Сравниваем CRC
	if( crc16($data) == bin2hex($crc) ) {

		//Если ответ 0x52 CMD_TCP_ACK_TRANSACTION
		if( $data[5] == 0x52 ) {

			//Число частей в файле
			$nums = $data[7] + ($data[8] << 8);

			//Номер текущей части
			$curnum = $data[9] + ($data[10] << 8);

			//Длина записи
			$curlen = $data[11] + ($data[12] << 8);

			for( $i=13; $i < $curlen; $i=$i+104) {
				// Прием товара
				if( $data[$i+10] == 2 ) {
					//Идентификатор
					$nextID = $data[$i] + ($data[$i+1] << 8) + ($data[$i+2] << 16) + ($data[$i+3] << 24);
					//Номер терминала
					$deviceID = $data[$i+6] + ($data[$i+7] << 8) + ($data[$i+8] << 16) + ($data[$i+9] << 24);
					//Дата/время совершения регистрации
					$transactionDate = sprintf("20%02d-%02d-%02d %02d:%02d:%02d", $data[$i+11], $data[$i+12], $data[$i+13], $data[$i+14], $data[$i+15], $data[$i+16]);
					//Масса нетто
					$netWeight = $data[$i+19] + ($data[$i+20] << 8) + ($data[$i+21] << 16) + ($data[$i+22] << 24);
					//Если масса отрицательная
					if( ($data[$i+22] >> 7) == 1 ) {
						$netWeight = ((-1 >> 32) << 32) + $netWeight;
					}
					// Количество штук
					$quantity = $data[$i+27] + ($data[$i+28] << 8) + ($data[$i+29] << 16) + ($data[$i+30] << 24);
					//Если количество отрицательное
					if( ($data[$i+30] >> 7) == 1 ) {
						$quantity = ((-1 >> 32) << 32) + $quantity;
					}
					//ID товара
					$goodsID = $data[$i+37] + ($data[$i+38] << 8) + ($data[$i+39] << 16) + ($data[$i+40] << 24);

					//Документ-основание
					$DocumentCode = dechex($data[$i+59])."".dechex($data[$i+60])."".dechex($data[$i+61])."".dechex($data[$i+61])."".dechex($data[$i+62])."".dechex($data[$i+63])."".dechex($data[$i+64])."".dechex($data[$i+65])."".dechex($data[$i+66])."".dechex($data[$i+67])."".dechex($data[$i+68])."".dechex($data[$i+69])."".dechex($data[$i+70])."".dechex($data[$i+71])."".dechex($data[$i+72])."".dechex($data[$i+73]);
					$DocumentCode = hex2bin($DocumentCode);
					$DocumentCode = intval(substr($DocumentCode, 2));

					//Номер партии
					$ReceiptNumber = $data[$i+76] + ($data[$i+77] << 8) + ($data[$i+78] << 16) + ($data[$i+79] << 24);

					$AddrGoods = $data[$i+96] + ($data[$i+97] << 8) + ($data[$i+98] << 16) + ($data[$i+99] << 24);

//					echo $nextID." ";
//					echo $deviceID." ";
//					echo $transactionDate." ";
//					echo $netWeight." ";
//					echo $quantity." ";
//					echo $goodsID." ";
//					echo $DocumentCode." ";
//					echo $ReceiptNumber." ";
//					echo $AddrGoods."\r\n";

					// Узнаем участок терминала
					$query = "
						SELECT F_ID
						FROM WeighingTerminal
						WHERE WT_ID = {$deviceID}
					";
					$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
					$row = mysqli_fetch_array($res);
					$F_ID = $row["F_ID"];

					$new_opening = 0;

					// Игнорируем недопустимый вес
					if( abs($netWeight) >= 16000 and abs($netWeight) <= 80000 ) {
						// Отмена регистрации
						if( $netWeight < 0 ) {
							$query = "
								DELETE FROM list__Weight
								WHERE weight = ABS({$netWeight})
									AND WT_ID = {$deviceID}
									AND goodsID = {$goodsID}
									AND RN = {$ReceiptNumber}
								ORDER BY nextID DESC
								LIMIT 1
							";
							mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
						}
						else {
							// Если кассета была просканирована
							if( $DocumentCode > 0 ) {
								// Получаем LO_ID
								$query = "
									SELECT LO_ID
										,TIMESTAMPDIFF(HOUR, opening_time, '{$transactionDate}') hour_diff
										,opening_time
									FROM list__Opening
									WHERE cassette = {$DocumentCode}
										AND lift = 1
										AND F_ID = {$F_ID}
									ORDER BY opening_time DESC
									LIMIT 1
								";
								$res = mysqli_query( $mysqli, $query );
								if( $row = mysqli_fetch_array($res) ) {
									// Если с момента сканирования этой кассеты прошло больше 10 часов, создаем новую расформовку
									if( $row["hour_diff"] > 10 ) {
										$new_opening = 1;
									}
									else {
										$LO_ID = $row["LO_ID"];
									}
								}
								// Иначе новая расформовка
								else {
									$new_opening = 1;
								}
							}
							// Если кассету не просканироввали
							else {
								// Находим последнюю лифтовую кассету на участке
								$query = "
									SELECT LO_ID
									FROM list__Opening
									WHERE lift = 1
										AND F_ID = {$F_ID}
									ORDER BY opening_time DESC
									LIMIT 1
								";
								$res = mysqli_query( $mysqli, $query );
								if( $row = mysqli_fetch_array($res) ) {
									$LO_ID = $row["LO_ID"];
								}
							}

							// Если новая расформовка
							if( $new_opening == 1 ) {

								// Проверяем, залита ли кассета
								$query = "
									SELECT IF(
										(SELECT filling_time FROM list__Filling WHERE cassette = {$DocumentCode} ORDER BY filling_time DESC LIMIT 1)
										>
										IFNULL((SELECT opening_time FROM list__Opening WHERE cassette = {$DocumentCode} ORDER BY opening_time DESC LIMIT 1), 0)
									, 1, 0) is_filling
								";
								$res = mysqli_query( $mysqli, $query );
								$row = mysqli_fetch_array($res);
								$is_filling = $row["is_filling"];

								// Делаем запись о сборке. Расформовка триггером т.к. кассета залита.
								$query = "
									INSERT INTO list__Assembling
									SET F_ID = {$F_ID}
										,cassette = {$DocumentCode}
										,assembling_time = '{$transactionDate}'
										,lift = 1
								";
								mysqli_query( $mysqli, $query );

								// Если залита
								if( $is_filling == 1 ) {

									// Находим LO_ID добавленой кассеты
									$query = "
										SELECT LO_ID
										FROM list__Opening
										WHERE cassette = {$DocumentCode}
											AND lift = 1
											AND F_ID = {$F_ID}
										ORDER BY opening_time DESC
										LIMIT 1
									";
									$res = mysqli_query( $mysqli, $query );
									$row = mysqli_fetch_array($res);
									$LO_ID = $row["LO_ID"];
								}
								else {
									// Все равно делаем запись о расформовке
									$query = "
										INSERT INTO list__Opening
										SET F_ID = {$F_ID}
											,cassette = {$DocumentCode}
											,opening_time = '{$transactionDate}'
											,lift = 1
									";
									mysqli_query( $mysqli, $query );
									$LO_ID = mysqli_insert_id( $mysqli );
								}
							}

							if( $LO_ID > 0 ) {
								// Записываем в базу регистрацию
								$query = "
									INSERT INTO list__Weight
									SET LO_ID = {$LO_ID}
										,weight = {$netWeight}
										,nextID = {$nextID}
										,WT_ID = {$deviceID}
										,weighing_time = '{$transactionDate}'
										,goodsID = {$goodsID}
										,RN = {$ReceiptNumber}
									ON DUPLICATE KEY UPDATE
										weighing_time = '{$transactionDate}'
								";
								mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

								// Запоминаем ID последней регистрации
								$query = "
									UPDATE WeighingTerminal
									SET last_transaction = {$nextID}
									WHERE WT_ID = {$deviceID}
								";
								mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
							}
						}
					}
				}
				//Закрытие партии
				elseif( $data[$i+10] == 71 or $data[$i+10] == 72 ) {
					//Идентификатор
					$nextID = $data[$i] + ($data[$i+1] << 8) + ($data[$i+2] << 16) + ($data[$i+3] << 24);
					//Номер терминала
					$deviceID = $data[$i+6] + ($data[$i+7] << 8) + ($data[$i+8] << 16) + ($data[$i+9] << 24);
					//Дата/время совершения регистрации
					$transactionDate = sprintf("20%02d-%02d-%02d %02d:%02d:%02d", $data[$i+11], $data[$i+12], $data[$i+13], $data[$i+14], $data[$i+15], $data[$i+16]);

//					echo $nextID." ";
//					echo $deviceID." ";
//					echo $transactionDate." ";
//					echo "Закрытие партии\r\n";

					// Обновляем время закрытия последней партии, запоминаем ID последней регистрации
					$query = "
						UPDATE WeighingTerminal
						SET last_receiptDate = '{$transactionDate}'
							,last_transaction = {$nextID}
						WHERE WT_ID = {$deviceID}
					";
					mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
				}
			}

			// Если это не последняя часть
			if( $nums > $curnum ) {
				read_transaction($ID, ++$curnum, $socket, $mysqli);
			}
		}
	}
	else { //Если CRC не совпали делаем попытку еще
		read_transaction($ID, $curnum, $socket, $mysqli);
	}
}

$F_ID = 1;
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

// По этому списку весов собираем данные
$query = "
	SELECT WT.WT_ID
		,WT.post
		,WT.port
		,WT.last_transaction
	FROM WeighingTerminal WT
	WHERE WT.F_ID = {$F_ID}
		AND WT.type = 4
		AND WT.act = 1
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	// Открываем сокет и запускаем функцию чтения и записывания в БД регистраций
	$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
	if( socket_connect($socket, $from_ip, $row["port"]) ) {
		read_transaction($row["last_transaction"]+1, 1, $socket, $mysqli);
	}
//	else {
//		message_to_telegram("Пост <b>{$row["post"]}</b>\n<b>Нет связи с весами!</b>", $notification_group);
//	}
	socket_close($socket);
}
?>
