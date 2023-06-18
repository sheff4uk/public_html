<?
$path = dirname(dirname($argv[0]));
$key = $argv[1];
include $path."/config.php";
// Проверка доступа
if( $key != $script_key ) die('Access denied!');

// Генерация табеля на следующий месяц
$query = "
	INSERT INTO TariffMonth(year, month, USR_ID, F_ID)
	SELECT YEAR(CURDATE()), MONTH(CURDATE()), TM.USR_ID, TM.F_ID
	FROM TariffMonth TM
	WHERE TM.year = YEAR(CURDATE() - INTERVAL 1 MONTH)
		AND TM.month = MONTH(CURDATE() - INTERVAL 1 MONTH)
		AND TM.USR_ID IN (SELECT USR_ID FROM Users WHERE act = 1 AND IFNULL(cardcode, '') != '' )
	ON DUPLICATE KEY UPDATE
		F_ID = TM.F_ID
";
mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));


/////////////////////////////////////////
// Заполняем таблицу с выходными днями //
/////////////////////////////////////////

$year = date('Y');
$month = date('m');
// Узнаем кол-во дней в выбранном месяце
$strdate = '01.'.$month.'.'.$year;
$timestamp = strtotime($strdate);
$days = date('t', $timestamp);

// Получаем производственный календарь на выбранный год
$xml = simplexml_load_file("http://xmlcalendar.ru/data/ru/".$year."/calendar.xml");
$json = json_encode($xml);
$data = json_decode($json,TRUE);

$i = 1;
while ($i <= $days) {
	$date = $year.'-'.$month.'-'.$i;
	$day_of_week = date('N', strtotime($date));	// День недели 1..7
	$day = date('d', strtotime($date));			// День месяца

	// Перебираем массив и если находим дату то проверяем ее тип (тип дня: 1 - выходной день, 2 - рабочий и сокращенный (может быть использован для любого дня недели), 3 - рабочий день (суббота/воскресенье))
	$t = 0;
	foreach( $data["days"]["day"] as $key=>$value ) {
		if( $value["@attributes"]["d"] == $month.".".$day) {
			$t = $value["@attributes"]["t"];
		}
	}

	if ( (($day_of_week >= 6 and $t != "3" and $t != "2") or ($t == "1")) ) { // Выделяем цветом выходные дни
		$query = "
			INSERT INTO holidays
			SET holiday = '{$date}'
		";
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	}
	$i++;
}
?>
