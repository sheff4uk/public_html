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
}
?>
