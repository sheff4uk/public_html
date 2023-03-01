<?
$path = dirname(dirname($argv[0]));
$key = $argv[1];
$to = $argv[2];

include $path."/config.php";
// Проверка доступа
if( $key != $script_key ) die('Access denied!');

////////////////////////////////////////
// Прошлый месяц
$query = "
	SELECT YEAR(CURDATE() - INTERVAL 1 MONTH) `year`
		,MONTH(CURDATE() - INTERVAL 1 MONTH) `month`
		,DATE_FORMAT(CURDATE() - INTERVAL 1 MONTH, '%M') format_month
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query1: " .mysqli_error( $mysqli ));
$row = mysqli_fetch_array($res);
$year = $row["year"];
$month = $row["month"];
$format_month = $row["format_month"];
/////////////////////////////////////////

$subject = "=?utf-8?b?". base64_encode("[KONSTANTA] Дефекты {$format_month} {$year}"). "?=";

$message = "
	<table cellspacing='0' cellpadding='2' border='1' style='table-layout: fixed; width: 100%;'>\r\n
		<tr>\r\n
			<th><img src='https://konstanta.ltd/assets/images/logo.png' alt='KONSTANTA' style='width: 200px; margin: 5px;'></th>\r\n
			<th><n style='font-size: 2em;'>Дефекты форм, сборки</n></th>\r\n
			<th>{$format_month} {$year}</th>\r\n
		</tr>\r\n
	</table>\r\n

	<table cellspacing='0' cellpadding='2' border='1' style='table-layout: fixed; width: 100%;'>\r\n
		<thead style='word-wrap: break-word;'>\r\n
			<tr>\r\n
				<th>Мастер</th>\r\n
				<th>Дефект формы</th>\r\n
				<th>Дефект сборки</th>\r\n
			</tr>\r\n
		</thead>\r\n
		<tbody style='text-align: center;'>\r\n
";

$query = "
	SELECT USR_Name(LA.assembling_master) `master`
		,CONCAT(SUM(IF(LW.goodsID = 6, 1, 0)), ' (', ROUND(SUM(IF(LW.goodsID = 6, 1, 0)) / SUM(1) * 100, 2), '%)') def_shell
		,CONCAT(SUM(IF(LW.goodsID = 7, 1, 0)), ' (', ROUND(SUM(IF(LW.goodsID = 7, 1, 0)) / SUM(1) * 100, 2), '%)') def_assembly
	FROM list__Assembling LA
	JOIN list__Filling LF ON LF.LA_ID = LA.LA_ID
	JOIN list__Opening LO ON LO.LF_ID = LF.LF_ID
	JOIN list__Weight LW ON LW.LO_ID = LO.LO_ID
	WHERE YEAR(LA.assembling_time) = {$year}
		AND MONTH(LA.assembling_time) = {$month}
		AND LA.assembling_master IS NOT NULL
	GROUP BY LA.assembling_master";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query1: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	$message .= "
		<tr>\r\n
			<td>{$row["master"]}</td>\r\n
			<td>{$row["def_shell"]}</td>\r\n
			<td>{$row["def_assembly"]}</td>\r\n
		</tr>\r\n
	";
}

$message .= "
		</tbody>\r\n
	</table>\r\n
	<br>\r\n
	<br>\r\n
";

//$message .= "
//	<table cellspacing='0' cellpadding='2' border='1' style='table-layout: fixed; width: 100%;'>\r\n
//		<tr>\r\n
//			<th><img src='https://konstanta.ltd/assets/images/logo.png' alt='KONSTANTA' style='width: 200px; margin: 5px;'></th>\r\n
//			<th><n style='font-size: 2em;'>Непроливы, усадочные трещины</n></th>\r\n
//			<th>{$format_month} {$year}</th>\r\n
//		</tr>\r\n
//	</table>\r\n
//
//	<table cellspacing='0' cellpadding='2' border='1' style='table-layout: fixed; width: 100%;'>\r\n
//		<thead style='word-wrap: break-word;'>\r\n
//			<tr>\r\n
//				<th>Оператор</th>\r\n
//				<th>Непролив</th>\r\n
//				<th>Усадочная трещина</th>\r\n
//				<th>Легкие детали</th>\r\n
//				<th>Тяжелые детали</th>\r\n
//			</tr>\r\n
//		</thead>\r\n
//		<tbody style='text-align: center;'>\r\n
//";
//
//$query = "
//	SELECT USR_Name(LB.operator) operator
//		,CONCAT(SUM(IF(LW.goodsID = 2, 1, 0)), ' (', ROUND(SUM(IF(LW.goodsID = 2, 1, 0)) / SUM(1) * 100, 2), '%)') not_spill
//		,CONCAT(SUM(IF(LW.goodsID = 4, 1, 0)), ' (', ROUND(SUM(IF(LW.goodsID = 4, 1, 0)) / SUM(1) * 100, 2), '%)') crack_drying
//		,CONCAT(SUM(IF(LW.weight < ROUND(CW.min_weight/100*101), 1, NULL)), ' (', ROUND(SUM(IF(LW.weight < ROUND(CW.min_weight/100*101), 1, NULL)) / SUM(1) * 100, 2), '%)') light
//		,CONCAT(SUM(IF(LW.weight > ROUND(CW.max_weight/100*101), 1, NULL)), ' (', ROUND(SUM(IF(LW.weight > ROUND(CW.max_weight/100*101), 1, NULL)) / SUM(1) * 100, 2), '%)') heavy
//	FROM plan__Batch PB
//	JOIN list__Batch LB ON LB.PB_ID = PB.PB_ID
//	JOIN list__Filling LF ON LF.LB_ID = LB.LB_ID
//	JOIN list__Opening LO ON LO.LF_ID = LF.LF_ID
//	JOIN list__Weight LW ON LW.LO_ID = LO.LO_ID
//	JOIN CounterWeight CW ON CW.CW_ID = PB.CW_ID
//	WHERE YEAR(LB.batch_date) = {$year}
//		AND MONTH(LB.batch_date) = {$month}
//		AND LB.operator IS NOT NULL
//	GROUP BY LB.operator
//";
//$res = mysqli_query( $mysqli, $query ) or die("Invalid query1: " .mysqli_error( $mysqli ));
//while( $row = mysqli_fetch_array($res) ) {
//	$message .= "
//		<tr>\r\n
//			<td>{$row["operator"]}</td>\r\n
//			<td>{$row["not_spill"]}</td>\r\n
//			<td>{$row["crack_drying"]}</td>\r\n
//			<td>{$row["light"]}</td>\r\n
//			<td>{$row["heavy"]}</td>\r\n
//		</tr>\r\n
//	";
//}
//
//$message .= "
//		</tbody>\r\n
//	</table>\r\n
//";

//$headers  = "MIME-Version: 1.0\r\n";
$headers .= "Content-type: text/html; charset=\"utf-8\"\r\n";
$headers .= "From: planner@konstanta.ltd\r\n";

mail($to, $subject, $message, $headers);
?>
