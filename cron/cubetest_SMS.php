<?
$path = dirname(dirname($argv[0]));
$key = $argv[1];
include $path."/config.php";
// Проверка доступа
if( $key != $script_key ) die('Access denied!');

// Цикл по производственным участкам
$query = "
	SELECT F_ID
		,cube_test_group
	FROM factory
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	$query = "
		SELECT CW.item
			,DATE_FORMAT(LB.batch_time, '%H:%i') time
			,24 delay
		FROM list__Batch LB
		JOIN plan__Batch PB ON PB.PB_ID = LB.PB_ID
		JOIN CounterWeight CW ON CW.CW_ID = PB.CW_ID
		LEFT JOIN list__CubeTest LCT ON LCT.LB_ID = LB.LB_ID AND LCT.delay = 24
		WHERE LB.test = 1
			AND PB.F_ID = {$row["F_ID"]}
			AND LCT.LCT_ID IS NULL
			AND LB.batch_date + INTERVAL 1 DAY = CURDATE()
			AND HOUR(LB.batch_time) = HOUR(CURTIME())
		UNION ALL
		SELECT CW.item
			,DATE_FORMAT(LB.batch_time, '%H:%i') time
			,72 delay
		FROM list__Batch LB
		JOIN plan__Batch PB ON PB.PB_ID = LB.PB_ID
		JOIN CounterWeight CW ON CW.CW_ID = PB.CW_ID
		LEFT JOIN list__CubeTest LCT ON LCT.LB_ID = LB.LB_ID AND LCT.delay = 72
		WHERE LB.test = 1
			AND PB.F_ID = {$row["F_ID"]}
			AND LCT.LCT_ID IS NULL
			AND LB.batch_date + INTERVAL 3 DAY = CURDATE()
			AND HOUR(LB.batch_time) = HOUR(CURTIME())
		ORDER BY time
	";
	$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$text = "";
	while( $subrow = mysqli_fetch_array($subres) ) {
		$text .= "<b>{$subrow["item"]}</b> {$subrow["time"]} [{$subrow["delay"]}]\n";
	}
	if( $text ) {
		message_to_telegram($text, $row["cube_test_group"]);
	}
}

?>
