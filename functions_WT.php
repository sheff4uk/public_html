<?
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

// Функция читает регистрации с терминалов на конвейере
function read_transaction_LW($ID, $curnum, $socket, $mysqli) {
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
					//ID товара
					$goodsID = $data[$i+37] + ($data[$i+38] << 8) + ($data[$i+39] << 16) + ($data[$i+40] << 24);
					$goodsID = ($goodsID == 8 ? 1 : $goodsID);
					//Номер партии
					$ReceiptNumber = $data[$i+76] + ($data[$i+77] << 8) + ($data[$i+78] << 16) + ($data[$i+79] << 24);

					// Игнорируем недопустимый вес
					if( abs($netWeight) >= 7000 and abs($netWeight) <= 14000 ) {
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

							// Меняем ID последней регистрации
							$query = "
								UPDATE WeighingTerminal
								SET last_transaction = (SELECT nextID FROM list__Weight WHERE WT_ID = {$deviceID} ORDER BY nextID DESC LIMIT 1)
								WHERE WT_ID = {$deviceID}
							";
							mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
						}
						else {
							// Записываем в базу регистрацию
							$query = "
								INSERT INTO list__Weight
								SET weight = {$netWeight}
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
				//Закрытие партии
				elseif( $data[$i+10] == 71 or $data[$i+10] == 72 ) {
					//Идентификатор
					$nextID = $data[$i] + ($data[$i+1] << 8) + ($data[$i+2] << 16) + ($data[$i+3] << 24);
					//Номер терминала
					$deviceID = $data[$i+6] + ($data[$i+7] << 8) + ($data[$i+8] << 16) + ($data[$i+9] << 24);
					//Дата/время совершения регистрации
					$receipt_end = sprintf("20%02d-%02d-%02d %02d:%02d:%02d", $data[$i+11], $data[$i+12], $data[$i+13], $data[$i+14], $data[$i+15], $data[$i+16]);

					// Узнаем время предыдущего закрытия и участок терминала
					$query = "
						SELECT last_receiptDate receipt_start
							,F_ID
						FROM WeighingTerminal
						WHERE WT_ID = {$deviceID}
					";
					$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
					$row = mysqli_fetch_array($res);
					$receipt_start = $row["receipt_start"];
					$F_ID = $row["F_ID"];

					// Узнаем chatID участка
					$query = "
						SELECT notification_group
						FROM factory
						WHERE F_ID = {$F_ID}
					";
					$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
					$row = mysqli_fetch_array($res);
					$notification_group = $row["notification_group"];

					// Узнаем номер закрытой партии
					$query = "
						SELECT IFNULL(MAX(RN), 0) RN
						FROM list__Weight
						WHERE weighing_time BETWEEN '{$receipt_start}' AND '{$receipt_end}'
							AND WT_ID = {$deviceID}
							AND LO_ID IS NULL
					";
					$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
					$row = mysqli_fetch_array($res);
					$RN = $row["RN"];

					// Если закрытая партия не была пустой
					if( $RN > 0 ) {
						// Из пересечения временных интервалов находим наиболее подходящую кассету
						$query = "
							SELECT SUB.LO_ID
								,IFNULL((SELECT SUM(1) FROM list__Weight WHERE RN = {$RN} AND WT_ID = {$deviceID} AND weighing_time BETWEEN (SELECT opening_time FROM list__Opening WHERE LO_ID = SUB.LO_ID) AND SUB.end_time), 0) CW_cnt
								,(SELECT cassette FROM list__Opening WHERE LO_ID = SUB.LO_ID) cassette
								,(
									SELECT CW.item
									FROM list__Opening LO
									JOIN list__Filling LF ON LF.LF_ID = LO.LF_ID
									JOIN list__Batch LB ON LB.LB_ID = LF.LB_ID
									JOIN plan__Batch PB ON PB.PB_ID = LB.PB_ID
									JOIN CounterWeight CW ON CW.CW_ID = PB.CW_ID
									WHERE LO.LO_ID = SUB.LO_ID
								) item
							FROM (
								SELECT (SELECT LO_ID FROM list__Opening WHERE F_ID = {$F_ID} AND opening_time < LO.opening_time AND lift IS NULL ORDER BY opening_time DESC LIMIT 1) LO_ID
									,LO.opening_time end_time
								FROM list__Opening LO
								WHERE LO.F_ID = {$F_ID}
									AND LO.opening_time > '{$receipt_start}'
									AND (SELECT opening_time FROM list__Opening WHERE F_ID = {$F_ID} AND opening_time < LO.opening_time AND lift IS NULL ORDER BY opening_time DESC LIMIT 1) <= '{$receipt_end}'
									AND '{$receipt_start}' <= LO.opening_time
									AND LO.lift IS NULL
								) SUB
							ORDER BY CW_cnt DESC
							#LIMIT 1
						";
						$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
						$row = mysqli_fetch_array($res);
						$LO_ID = $row["LO_ID"];
						$CW_cnt = $row["CW_cnt"];
						$cassette = $row["cassette"];
						$item = $row["item"];

						// Связываем регистрации закрытой партии с подходящей по времени кассетой
						$query = "
							UPDATE list__Weight
							SET LO_ID = {$LO_ID}
							WHERE WT_ID = {$deviceID}
								AND LO_ID IS NULL
						";
						mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

						$receipt_err = 0;
						// Попытка прочитать вторую строку запроса, чтобы выявить не закрытую партию
						if( $row = mysqli_fetch_array($res) ) {
							if( $row["CW_cnt"] / $CW_cnt >= 1/2 ) $receipt_err = 1;
						}

						// Выявляем повторяющийся брак (3 и более подряд)
						$query = "
							SELECT goodsID
							FROM list__Weight
							WHERE WT_ID = {$deviceID}
								AND RN = {$RN}
						";
						$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
						$reject_cnt = 0;
						$reject_err = 0;
						$prev_goodsID = 0;
						while( $row = mysqli_fetch_array($res) ) {
							if( $prev_goodsID == $row["goodsID"] and $row["goodsID"] > 1 ) {
								++$reject_cnt;
							}
							else {
								$reject_cnt = 0;
							}
							if( $reject_cnt > 2 ) $reject_err = 1;
							$prev_goodsID == $row["goodsID"];
						}

						// Выявляем трещины и сколы на посту
						$query = "
							SELECT WT.post
								,SUM(IF(goodsID = 3, 1, 0)) crack
								,SUM(IF(goodsID = 5, 1, 0)) chip
							FROM list__Weight LW
							JOIN WeighingTerminal WT ON WT.WT_ID = LW.WT_ID
							WHERE LW.RN = {$RN}
								AND LW.WT_ID = {$deviceID}
						";
						$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
						$row = mysqli_fetch_array($res);
						$post = $row["post"];
						$crack = $row["crack"];
						$chip = $row["chip"];
						// Если в партии были трещины или сколы или незакрытие или повторения брака сообщаем в телеграм
						if( $crack or $chip or $receipt_err or $reject_err ) {
							$message = "Пост <b>{$post}</b>, кассета: <b>{$cassette}</b>, код: <b>{$item}</b>, партия <b>{$RN}</b>\n".($crack ? "трещина: <b>{$crack}</b>\n" : "").($chip ? "скол: <b>{$chip}</b>\n" : "").($receipt_err ? "<b>Пропущено закрытие партии!</b>\n" : "").($reject_err ? "<b>Подряд идущий одинаковый брак (3 и более)!</b>" : "");
							message_to_telegram($message, $notification_group);
						}
					}

					// Обновляем время закрытия последней партии
					$query = "
						UPDATE WeighingTerminal
						SET last_receiptDate = '{$receipt_end}'
						WHERE WT_ID = {$deviceID}
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

			// Если это не последняя часть
			if( $nums > $curnum ) {
				read_transaction_LW($ID, ++$curnum, $socket, $mysqli);
			}
		}
	}
	else { //Если CRC не совпали делаем попытку еще
		read_transaction_LW($ID, $curnum, $socket, $mysqli);
	}
}

// Функция читает регистрации с терминала этикеток на паллеты
function read_transaction_LPP($ID, $curnum, $socket, $mysqli) {
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
				// Этикетирование паллета
				if( $data[$i+10] == 1 ) {
					//Идентификатор
					$nextID = $data[$i] + ($data[$i+1] << 8) + ($data[$i+2] << 16) + ($data[$i+3] << 24);
					//Номер терминала
					$deviceID = $data[$i+6] + ($data[$i+7] << 8) + ($data[$i+8] << 16) + ($data[$i+9] << 24);
					//Дата/время совершения регистрации
					$transactionDate = sprintf("20%02d-%02d-%02d %02d:%02d:%02d", $data[$i+11], $data[$i+12], $data[$i+13], $data[$i+14], $data[$i+15], $data[$i+16]);
					// Количество штук
					$quantity = $data[$i+27] + ($data[$i+28] << 8) + ($data[$i+29] << 16) + ($data[$i+30] << 24);
					//Если количество отрицательное
					if( ($data[$i+30] >> 7) == 1 ) {
						$quantity = ((-1 >> 32) << 32) + $quantity;
					}
					//ID товара
					$goodsID = $data[$i+37] + ($data[$i+38] << 8) + ($data[$i+39] << 16) + ($data[$i+40] << 24);

					// Если количество положительное
					if( $quantity > 0 ) {
						// Записываем в базу регистрацию
						$query = "
							INSERT INTO list__PackingPallet
							SET packed_time = '{$transactionDate}'
								,nextID = {$nextID}
								,WT_ID = {$deviceID}
								,CWP_ID = {$goodsID}
						";
						mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
					}
					else {
						// Иначе сторнируем
						$query = "
							UPDATE list__PackingPallet
							SET removal_time = '{$transactionDate}'
							WHERE CWP_ID = {$goodsID}
								AND WT_ID = {$deviceID}
							ORDER BY nextID DESC
							LIMIT 1
						";
						mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
					}

					// Запоминаем ID последней регистрации
					$query = "
						UPDATE WeighingTerminal
						SET last_transaction = {$nextID}
						WHERE WT_ID = {$deviceID}
					";
					mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
				}
				//Закрытие партии
				elseif( $data[$i+10] == 71 or $data[$i+10] == 72 ) {
					//Идентификатор
					$nextID = $data[$i] + ($data[$i+1] << 8) + ($data[$i+2] << 16) + ($data[$i+3] << 24);
					//Номер терминала
					$deviceID = $data[$i+6] + ($data[$i+7] << 8) + ($data[$i+8] << 16) + ($data[$i+9] << 24);
					//Дата/время совершения регистрации
					$receipt_end = sprintf("20%02d-%02d-%02d %02d:%02d:%02d", $data[$i+11], $data[$i+12], $data[$i+13], $data[$i+14], $data[$i+15], $data[$i+16]);

					// Обновляем время закрытия последней партии
					$query = "
						UPDATE WeighingTerminal
						SET last_receiptDate = '{$receipt_end}'
						WHERE WT_ID = {$deviceID}
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

			// Если это не последняя часть
			if( $nums > $curnum ) {
				read_transaction_LPP($ID, ++$curnum, $socket, $mysqli);
			}
		}
	}
	else { //Если CRC не совпали делаем попытку еще
		read_transaction_LPP($ID, $curnum, $socket, $mysqli);
	}
}

// Функция читает регистрации кассет на расформовке
function read_transaction_LA($ID, $curnum, $socket, $mysqli) {
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
				// Регистрация кассеты на карусели (прием товара)
				if( $data[$i+10] == 2 ) {
					//Идентификатор
					$nextID = $data[$i] + ($data[$i+1] << 8) + ($data[$i+2] << 16) + ($data[$i+3] << 24);
					//Номер терминала
					$deviceID = $data[$i+6] + ($data[$i+7] << 8) + ($data[$i+8] << 16) + ($data[$i+9] << 24);
					//Дата/время совершения регистрации
					$transactionDate = sprintf("20%02d-%02d-%02d %02d:%02d:%02d", $data[$i+11], $data[$i+12], $data[$i+13], $data[$i+14], $data[$i+15], $data[$i+16]);
					// Количество штук
					$quantity = $data[$i+27] + ($data[$i+28] << 8) + ($data[$i+29] << 16) + ($data[$i+30] << 24);
					//Если количество отрицательное
					if( ($data[$i+30] >> 7) == 1 ) {
						$quantity = ((-1 >> 32) << 32) + $quantity;
					}
					//ID товара
					$goodsID = $data[$i+37] + ($data[$i+38] << 8) + ($data[$i+39] << 16) + ($data[$i+40] << 24);

					// Если количество положительное
					if( $quantity > 0 ) {
						// Узнаем участок терминала
						$query = "
							SELECT F_ID
							FROM WeighingTerminal
							WHERE WT_ID = {$deviceID}
						";
						$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
						$row = mysqli_fetch_array($res);
						$F_ID = $row["F_ID"];

						// Узнаем участок просканированной кассеты
						$query = "
							SELECT F_ID
							FROM Cassettes
							WHERE cassette = {$goodsID}
						";
						$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
						$row = mysqli_fetch_array($res);
						$cF_ID = $row["F_ID"];

						// Если участки различаются, обновляем участок у кассеты и переносим в резерв
						if( $F_ID != $cF_ID ) {
							$query = "
								UPDATE Cassettes
								SET F_ID = {$F_ID}
									,CW_ID = NULL
								WHERE cassette = {$goodsID}
							";
							mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
						}

						// Делаем запись в таблицу сборки кассет (расформовка триггером)
						$query = "
							INSERT INTO list__Assembling
							SET assembling_time = '{$transactionDate}'
								,cassette = {$goodsID}
								,F_ID = {$F_ID}
						";
						mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
					}

					// Запоминаем ID последней регистрации
					$query = "
						UPDATE WeighingTerminal
						SET last_transaction = {$nextID}
						WHERE WT_ID = {$deviceID}
					";
					mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
				}
				//Закрытие партии
				elseif( $data[$i+10] == 71 or $data[$i+10] == 72 ) {
					//Идентификатор
					$nextID = $data[$i] + ($data[$i+1] << 8) + ($data[$i+2] << 16) + ($data[$i+3] << 24);
					//Номер терминала
					$deviceID = $data[$i+6] + ($data[$i+7] << 8) + ($data[$i+8] << 16) + ($data[$i+9] << 24);
					//Дата/время совершения регистрации
					$receipt_end = sprintf("20%02d-%02d-%02d %02d:%02d:%02d", $data[$i+11], $data[$i+12], $data[$i+13], $data[$i+14], $data[$i+15], $data[$i+16]);

					// Обновляем время закрытия последней партии
					$query = "
						UPDATE WeighingTerminal
						SET last_receiptDate = '{$receipt_end}'
						WHERE WT_ID = {$deviceID}
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

			// Если это не последняя часть
			if( $nums > $curnum ) {
				read_transaction_LPP($ID, ++$curnum, $socket, $mysqli);
			}
		}
	}
	else { //Если CRC не совпали делаем попытку еще
		read_transaction_LPP($ID, $curnum, $socket, $mysqli);
	}
}

// Функция обновляет текст терминала
function set_terminal_text($text, $socket, $mysqli) {
	$text = str_pad($text, 24);
	$in = "\xF8\x55\xCE\x19\x00\xAA".hex2bin(bin2hex($text));
	$crc = crc16(byteStr2byteArray($in));
	$in .= hex2bin($crc);

	socket_write($socket, $in);
}
?>
