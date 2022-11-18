<?php
include "config.php";
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
				// Этикетирование
				if( $data[$i+10] == 1 ) {
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

					//Номер партии
					$ReceiptNumber = $data[$i+76] + ($data[$i+77] << 8) + ($data[$i+78] << 16) + ($data[$i+79] << 24);

					$AddrGoods = $data[$i+96] + ($data[$i+97] << 8) + ($data[$i+98] << 16) + ($data[$i+99] << 24);

					echo $nextID." ";
					echo $deviceID." ";
					echo $transactionDate." ";
					echo $netWeight." ";
					echo $quantity." ";
					echo $goodsID." ";
					echo $ReceiptNumber." ";
					echo $AddrGoods."\r\n";

//					// Игнорируем недопустимый вес
//					if( abs($netWeight) >= 7000 and abs($netWeight) <= 14000 ) {
//						// Отмена регистрации
//						if( $netWeight < 0 ) {
//							$query = "
//								DELETE FROM list__Weight_NBC
//								WHERE weight = ABS({$netWeight})
//									AND WT_ID = {$deviceID}
//									AND goodsID = {$goodsID}
//									AND RN = {$ReceiptNumber}
//								ORDER BY nextID DESC
//								LIMIT 1
//							";
//							mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
//						}
//						else {
//							// Записываем в базу регистрацию
//							$query = "
//								INSERT INTO list__Weight_NBC
//								SET weight = {$netWeight}
//									,nextID = {$nextID}
//									,WT_ID = {$deviceID}
//									,weighing_time = '{$transactionDate}'
//									,goodsID = {$goodsID}
//									,RN = {$ReceiptNumber}
//								ON DUPLICATE KEY UPDATE
//									weighing_time = '{$transactionDate}'
//							";
//							mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
//						}
//					}
				}
				//Закрытие партии
				elseif( $data[$i+10] == 71 ) {
					//Идентификатор
					$nextID = $data[$i] + ($data[$i+1] << 8) + ($data[$i+2] << 16) + ($data[$i+3] << 24);
					//Номер терминала
					$deviceID = $data[$i+6] + ($data[$i+7] << 8) + ($data[$i+8] << 16) + ($data[$i+9] << 24);
					//Дата/время совершения регистрации
					$transactionDate = sprintf("20%02d-%02d-%02d %02d:%02d:%02d", $data[$i+11], $data[$i+12], $data[$i+13], $data[$i+14], $data[$i+15], $data[$i+16]);

					echo $nextID." ";
					echo $deviceID." ";
					echo $transactionDate." ";
					echo "Закрытие партии\r\n";
				}
				//Закрытие партии
				elseif( $data[$i+10] == 72 ) {
					//Идентификатор
					$nextID = $data[$i] + ($data[$i+1] << 8) + ($data[$i+2] << 16) + ($data[$i+3] << 24);
					//Номер терминала
					$deviceID = $data[$i+6] + ($data[$i+7] << 8) + ($data[$i+8] << 16) + ($data[$i+9] << 24);
					//Дата/время совершения регистрации
					$transactionDate = sprintf("20%02d-%02d-%02d %02d:%02d:%02d", $data[$i+11], $data[$i+12], $data[$i+13], $data[$i+14], $data[$i+15], $data[$i+16]);

					echo $nextID." ";
					echo $deviceID." ";
					echo $transactionDate." ";
					echo "Аварийное закрытие партии\r\n";
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
/////////////////////////////////////////////////////////
//	$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
//	if( socket_connect($socket, $from_ip, 5002) ) {
//		echo "OK\n";
//	}
//	else {
//		echo "ERROR\n";
//	}
//	socket_close($socket);

// Записываем текст терминала
//if( ($socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) and (socket_connect($socket, $from_ip, 5020)) ) {
//	$in = "\xF8\x55\xCE\x19\x00\xAA".hex2bin(bin2hex("31/08/21 (1)            "));
//	$crc = crc16(byteStr2byteArray($in));
//	$in .= hex2bin($crc);
//
//	socket_write($socket, $in);
//
//	$result = socket_read($socket, 5);
//	$result = socket_read($socket, 1);
//	$result = bin2hex($result);
//	echo $result."\r\n";
//
//	socket_close($socket);
//}
//die();
/////////////////////////////

if( ($socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) and (socket_connect($socket, $from_ip, 5003)) ) {
//if( ($socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) and (socket_connect($socket, "192.168.0.20", 5001)) ) {
	//read_transaction(52445, 1, $socket, 0, $mysqli);
//////////////////////////////
//	$in = "\xF8\x55\xCE\x02\x00\x91\x04";
//	$crc = crc16(byteStr2byteArray($in));
//	$in .= hex2bin($crc);
//
//	socket_write($socket, $in);
//	$result = socket_read($socket, 8);
//	$data = byteStr2byteArray($result);
//	echo dechex($data[5])."\r\n";
//	//echo $result;
////////////////////////////////////////
	read_transaction(549870, 1, $socket, $mysqli);
	//read_transaction(114735, 1, $socket, $mysqli);
	socket_close($socket);
}

//f855ce //заголовок
//b003 //длина тела сообщения (03B0 = 944)
//52 //CMD_TCP_ACK_TRANSACTION
//03 //номер файла
//0300 //Число частей в файле (0003 = 3)
//0100 //Номер текущей части (0001 = 1)
//a803 //Длина записи (03A8 = 936)
//	//данные (936 байт)
//	b01d0000	//Идентификатор, уникальное значение
//	6200		//Длина записи
//	0d7f0000	//Номер терминала
//	01			//Тип регистрации
//	150415113107//Дата/время совершения регистрации
//	0000
//	702b0000	//Масса нетто
//	702b0000
//	00000000
//	000000000000
//	08000000	//ID товара из файла товаров
//	00000000
//	0000
//	00000000
//	0000
//	0000
//	0000
//	0000
//	202020202020202020202020202020
//	0200
//	c7010000	//Номер партии
//	202020202020202020202020202020
//	7f
//	f2000000
//	00000000
//	b11d000062000d7f0000011504151131250000c02b0000c02b000000000000000000000000080000000000000000000000000000000000000000002020202020202020202020202020200200c70100002020202020202020202020202020207ff200000000000000
//	b21d000062000d7f0000011504151132370000a72b0000a72b000000000000000000000000080000000000000000000000000000000000000000002020202020202020202020202020200200c70100002020202020202020202020202020207ff200000000000000
//	b31d000062000d7f0000011504151133140000fc2b0000fc2b000000000000000000000000080000000000000000000000000000000000000000002020202020202020202020202020200200c70100002020202020202020202020202020207ff200000000000000
//	b41d000062000d7f0000011504151134340000e32b0000e32b000000000000000000000000080000000000000000000000000000000000000000002020202020202020202020202020200200c70100002020202020202020202020202020207ff200000000000000
//	b51d000062000d7f00000115041511351d00000b2c00000b2c000000000000000000000000080000000000000000000000000000000000000000002020202020202020202020202020200200c70100002020202020202020202020202020207ff200000000000000
//	b61d000062000d7f0000471504151135370000000000000000000000000000000000000000000000000000000000000000000000000000000000002020202020202020202020202020200200c80100002020202020202020202020202020207f00e03f0000e03f00
//	b71d000062000d7f00000115041511363700004c2c00004c2c000000000000000000000000080000000000000000000000000000000000000000002020202020202020202020202020200200c80100002020202020202020202020202020207ff200000000000000
//	b81d000062000d7f0000011504151137180000152c0000152c000000000000000000000000080000000000000000000000000000000000000000002020202020202020202020202020200200c80100002020202020202020202020202020207ff200000000000000
//3e25 //CRC
?>
