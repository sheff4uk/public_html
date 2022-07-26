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
	$query = "
		SELECT DATE_FORMAT(CURDATE(), '%d.%m.%Y') date
			,USR_Name(TS.USR_ID) name
			,SUM(1) cnt
		FROM Timesheet TS
		JOIN TimeReg TR ON TR.TS_ID = TS.TS_ID AND TR.del_time IS NULL
		WHERE TS.ts_date = CURDATE()
			AND TS.F_ID = {$row["F_ID"]}
		GROUP BY TS.USR_ID
		HAVING cnt = 1
	";
	$subres = mysqli_query( $mysqli, $query ) or die("Invalid query1: " .mysqli_error( $mysqli ));
	$text = "";
	while( $subrow = mysqli_fetch_array($subres) ) {
		$text .= "<b>Смена не закрыта!</b> {$subrow["name"]} ({$subrow["date"]})\n";
	}
	if( $text ) {
		//message_to_telegram($text, $row["notification_group"]);
		message_to_telegram($text, '217756119');
	}
}

?>
