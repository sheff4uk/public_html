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
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	$F_ID = $row["F_ID"];
	$query = "
		SELECT DATE_FORMAT(CURDATE(), '%d.%m.%Y') date
			,USR_Name(TS.USR_ID) name
			,SUM(1) cnt
		FROM Timesheet TS
		JOIN TimeReg TR ON TR.TS_ID = TS.TS_ID AND TR.del_time IS NULL
		WHERE TS.ts_date = CURDATE()
			AND TS.F_ID = {$F_ID}
		GROUP BY TS.USR_ID
		HAVING cnt = 1
	";
	$subres = mysqli_query( $mysqli, $query ) or die("Invalid query1: " .mysqli_error( $mysqli ));
	$text = "";
	while( $subrow = mysqli_fetch_array($subres) ) {
		$text .= "<b>Смена не закрыта!</b> {$subrow["name"]} ({$subrow["date"]})\n";
	}
	if( $text ) {
		message_to_telegram($text, $row["notification_group"]);
	}

	//////////////////////////////////////////////
	// Автоматически помечаем выходные в табеле //
	//////////////////////////////////////////////
	$year = date('Y');
	$month = date('m');
	$xml = simplexml_load_file("http://xmlcalendar.ru/data/ru/".$year."/calendar.xml");
	$json = json_encode($xml);
	$data = json_decode($json,TRUE);
	$day_of_week = date('N');	// День недели 1..7
	$day = date('d');			// День месяца
	// Перебираем массив и если находим дату то проверяем ее тип (тип дня: 1 - выходной день, 2 - рабочий и сокращенный (может быть использован для любого дня недели), 3 - рабочий день (суббота/воскресенье))
	$t = 0;
	foreach( $data["days"]["day"] as $key=>$value ) {
		if( $value["@attributes"]["d"] == $month.".".$day) {
			$t = $value["@attributes"]["t"];
		}
	}
	// Если пришелся выходной день
	if ( (($day_of_week >= 6 and $t != "3" and $t != "2") or ($t == "1")) ) {
		// Цикл по работникам
		$query = "
			SELECT USR.USR_ID
			FROM Users USR
			JOIN TariffMonth TM ON TM.year = {$year}
				AND TM.month = {$month}
				AND TM.USR_ID = USR.USR_ID
				AND TM.F_ID = {$F_ID}
		";
		$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		while( $subrow = mysqli_fetch_array($subres) ) {
			// Проставляем выходные в табеле
			$query = "
				INSERT INTO Timesheet
				SET ts_date = '{$year}-{$month}-{$day}'
					,USR_ID = {$subrow["USR_ID"]}
					,F_ID = {$F_ID}
					,status = 4 #выходной
				ON DUPLICATE KEY UPDATE
					status = IF(duration IS NULL AND pay IS NULL AND status IS NULL, 4, status)
			";
			mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		}
	}
}

?>
