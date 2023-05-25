<?
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
	WHERE F_ID = 2
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {

	// Получаем список активных смен
	$query = "
		SELECT TS.ts_date
			,DATE_FORMAT(TS.ts_date, '%d.%m.%Y') ts_date_format
			,TSS.shift_num
		FROM TimeReg TR
		JOIN TimesheetShift TSS ON TSS.TSS_ID = TR.TSS_ID
			AND TSS.duration IS NULL
		JOIN Timesheet TS ON TS.TS_ID = TSS.TS_ID
			AND TS.F_ID = {$row["F_ID"]}
		WHERE TR.del_time IS NULL
		GROUP BY TS.ts_date, TSS.shift_num
		ORDER BY TS.ts_date, TSS.shift_num
	";
	$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $subrow = mysqli_fetch_array($subres) ) {

		// Выводим список работников на смене
		$query = "
			SELECT USR_Name(TS.USR_ID) `name`
				,TR.tr_photo
				,CONCAT(LPAD((TR.tr_minute DIV 60) % 24, 2, '0'), ':', LPAD(TR.tr_minute % 60, 2, '0')) tr_time
				,USR.post
				,USR.user_type
				,USR.USR_ID
			FROM TimeReg TR
			JOIN TimesheetShift TSS ON TSS.TSS_ID = TR.TSS_ID
				AND TSS.duration IS NULL
				AND TSS.shift_num = {$subrow["shift_num"]}
			JOIN Timesheet TS ON TS.TS_ID = TSS.TS_ID
				AND TS.F_ID = {$row["F_ID"]}
				AND TS.ts_date = '{$subrow["ts_date"]}'
			JOIN Users USR ON USR.USR_ID = TS.USR_ID
			WHERE TR.del_time IS NULL
			ORDER BY `name`
		";
		$subsubres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		$total_by_post = array();
		$total = 0;
		$aut_list = "0";
		while( $subsubrow = mysqli_fetch_array($subsubres) ) {
			if( $subsubrow["post"] != null ) {
				$total_by_post[$subsubrow["post"]]++;
				$total++;
			}
			if( $subsubrow["user_type"] == "Аутсорсер" ) {
				$aut_list .= ",{$subsubrow["USR_ID"]}";
			}
		}

		if( $total > 0 ) {
			$text = "<b>Смена {$subrow["shift_num"]}</b>\n";
			foreach ($total_by_post as $key => &$value) {
				$text .= "{$key}: {$value}\n";
			}
			$text .= "<b>Всего: {$total}</b>\n";
			message_to_telegram($text, $row["notification_group"]);
		}

		// Выводим список невышедших аутсорсеров
		if( $aut_list != "0" ) {
			$text = "<b>Не вышли:</b>\n";
			$query = "
				SELECT USR_Name(USR.USR_ID) `name`
				FROM Users USR
				WHERE USR.USR_ID NOT IN ({$aut_list})
					AND USR.F_ID = {$row["F_ID"]}
					AND USR.act = 1
					AND USR.user_type LIKE 'Аутсорсер'
				ORDER BY `name`
			";
			$subsubres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			while( $subsubrow = mysqli_fetch_array($subsubres) ) {
				$text .= "{$subsubrow["name"]}\n";
			}
			message_to_telegram($text, $row["notification_group"]);
		}
	}
}

?>
