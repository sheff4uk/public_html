<?
// Скрипт выполняется каждый час
$path = dirname(dirname($argv[0]));
$key = $argv[1];
include $path."/config.php";
// Проверка доступа
if( $key != $script_key ) die('Access denied!');

// Цикл по производственным участкам
$query = "
	SELECT F_ID
		,notification_group
	FROM factory
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	$F_ID = $row["F_ID"];

	// Находим все открытые смены
	$query = "
		SELECT TSS.TSS_ID
			,SUM(1) cnt
			,MIN(TR.tr_minute) tr_minute
			,TSS.shift_num
			,USR_Name(TS.USR_ID) name
			,(
				SELECT shift_end
				FROM WorkingShift
				WHERE TS.ts_date BETWEEN valid_from AND IFNULL(valid_to, CURDATE())
					AND F_ID = TS.F_ID
					AND shift_num = TSS.shift_num
			) shift_end
			,HOUR(NOW()) * 60 + MINUTE(NOW()) + TIMESTAMPDIFF(MINUTE, TS.ts_date, CURDATE()) passed
		FROM Timesheet TS
		JOIN TimesheetShift TSS ON TSS.TS_ID = TS.TS_ID
		JOIN TimeReg TR ON TR.TSS_ID = TSS.TSS_ID
			AND TR.del_time IS NULL
		WHERE TS.F_ID = {$F_ID}
		GROUP BY TSS.TSS_ID
		HAVING cnt = 1
	";
	$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$text = "";
	while( $subrow = mysqli_fetch_array($subres) ) {
		if( $subrow["passed"] >= $subrow["shift_end"] + 120 ) {
			// Если смена превышена на 2 часа, закрываем ее
			$query = "
				INSERT INTO TimeReg
				SET TSS_ID = {$subrow["TSS_ID"]}
					,prefix = 0
					,tr_minute = {$subrow["tr_minute"]}
					,add_time = NOW()
			";
			mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

			$text .= "{$subrow["name"]} не отметил закрытие. <b>Смена не засчитана!</b>\n";
		}
		elseif( $subrow["passed"] >= $subrow["shift_end"] + 60 ) {
				// Если смена превышена на час, предупреждаем о скором закрытии
			$text .= "{$subrow["name"]} не отметил закрытие. <b>Один час до автоматической отмены!</b>\n";
		}
	}
	if( $text ) {
		message_to_telegram($text, $row["notification_group"]);
	}

//	//////////////////////////////////////////////
//	// Автоматически помечаем выходные в табеле //
//	//////////////////////////////////////////////
//
//	// Узнаем время окончания самой последней смены
//	$query = "
//		SELECT IFNULL(GREATEST(MAX(shift_end) - 120, 0), 1440) shift_end
//		FROM WorkingShift
//		WHERE CURDATE() BETWEEN valid_from AND IFNULL(valid_to, CURDATE())
//			AND F_ID = {$F_ID}
//	";
//	$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
//	$subrow = mysqli_fetch_array($subres);
//	$shift_end = $subrow["shift_end"];
//
//	// Получаем время в минутах прошедшее с начала суток
//	$hour = date('H');			// Часы
//	$minute = date('i');		// Минуты
//	$minute_time = $hour * 60 + $minute;
//	$interval_day = 0;
//	if( $shift_end >= 1440 ) {
//		$minute_time = $minute_time + 1440;
//		$interval_day = 1;
//	}
//
//	// Если все смены производственного дня закончились
//	if( $minute_time >= $shift_end ) {
//
//		// Узнаем статус дня из производственного календаря
//		$query = "
//			SELECT YEAR(CURDATE() - INTERVAL {$interval_day} DAY) year
//				,MONTH(CURDATE() - INTERVAL {$interval_day} DAY) month
//				,WEEKDAY(CURDATE() - INTERVAL 1 DAY) + {$interval_day} day_of_week
//				,DAY(CURDATE() - INTERVAL {$interval_day} DAY) day
//		";
//		$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
//		$subrow = mysqli_fetch_array($subres);
//		$year = $subrow["year"];
//		$month = $subrow["month"];
//		$day_of_week = $subrow["day_of_week"];
//		$day = $subrow["day"];
//
//		$xml = simplexml_load_file("http://xmlcalendar.ru/data/ru/".$year."/calendar.xml");
//		$json = json_encode($xml);
//		$data = json_decode($json,TRUE);
//		// Перебираем массив и если находим дату то проверяем ее тип (тип дня: 1 - выходной день, 2 - рабочий и сокращенный (может быть использован для любого дня недели), 3 - рабочий день (суббота/воскресенье))
//		$t = 0;
//		foreach( $data["days"]["day"] as $key=>$value ) {
//			if( $value["@attributes"]["d"] == $month.".".$day) {
//				$t = $value["@attributes"]["t"];
//			}
//		}
//		// Если пришелся выходной день
//		if ( (($day_of_week >= 6 and $t != "3" and $t != "2") or ($t == "1")) ) {
//			// Цикл по работникам
//			$query = "
//				SELECT TM.USR_ID
//				FROM TariffMonth TM
//				WHERE TM.year = {$year}
//					AND TM.month = {$month}
//					AND TM.F_ID = {$F_ID}
//			";
//			$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
//			while( $subrow = mysqli_fetch_array($subres) ) {
//				// Проставляем выходные в табеле
//				$query = "
//					INSERT INTO Timesheet
//					SET ts_date = DATE('{$year}-{$month}-{$day}')
//						,USR_ID = {$subrow["USR_ID"]}
//						,F_ID = {$F_ID}
//						,status = 4 #выходной
//					ON DUPLICATE KEY UPDATE
//						status = IF((SELECT SUM(1) FROM TimesheetShift TSS WHERE TSS.TS_ID = TS_ID) IS NULL AND status IS NULL, 4, status)
//				";
//				mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
//			}
//		}
//	}
}

?>
