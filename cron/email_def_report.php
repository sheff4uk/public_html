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
	<table cellspacing='0' cellpadding='2' border='1' style='table-layout: fixed; width: 100%;'>
		<tr>
			<th><img src='https://konstanta.ltd/assets/images/logo.png' alt='KONSTANTA' style='width: 200px; margin: 5px;'></th>
			<th><n style='font-size: 2em;'>Дефекты форм, сборки</n></th>
			<th>{$format_month} {$year}</th>
		</tr>
	</table>

	<table cellspacing='0' cellpadding='2' border='1' style='table-layout: fixed; width: 100%;'>
		<thead style='word-wrap: break-word;'>
			<tr>
				<th>Мастер</th>
				<th>Дефект формы</th>
				<th>Дефект сборки</th>
			</tr>
		</thead>
		<tbody style='text-align: center;'>
";

$query = "
	SELECT USR_Name(LA.assembling_master) `master`
    	,CONCAT(SUM(LOD.def_form), ' (', ROUND(SUM(LOD.def_form) / SUM(PB.in_cassette - LF.underfilling) * 100, 2), '%)') def_shell
    	,CONCAT(SUM(LOD.def_assembly), ' (', ROUND(SUM(LOD.def_assembly) / SUM(PB.in_cassette - LF.underfilling) * 100, 2), '%)') def_assembly
	FROM list__Assembling LA
	JOIN list__Filling LF ON LF.LA_ID = LA.LA_ID
	JOIN list__Opening LO ON LO.LF_ID = LF.LF_ID
	JOIN list__Batch LB ON LB.LB_ID = LF.LB_ID
	JOIN plan__Batch PB ON PB.PB_ID = LB.PB_ID
    LEFT JOIN list__Opening_def LOD ON LOD.LO_ID = LO.LO_ID
	WHERE YEAR(LA.assembling_time) = {$year}
		AND MONTH(LA.assembling_time) = {$month}
		AND LA.assembling_master IS NOT NULL
	GROUP BY LA.assembling_master
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query1: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	$message .= "
		<tr>
			<td>{$row["master"]}</td>
			<td>{$row["def_shell"]}</td>
			<td>{$row["def_assembly"]}</td>
		</tr>
	";
}

$message .= "
		</tbody>
	</table>
	<br>
	<br>
";

$message .= "
	<table cellspacing='0' cellpadding='2' border='1' style='table-layout: fixed; width: 100%;'>
		<tr>
			<th><img src='https://konstanta.ltd/assets/images/logo.png' alt='KONSTANTA' style='width: 200px; margin: 5px;'></th>
			<th><n style='font-size: 2em;'>Непроливы, усадочные трещины</n></th>
			<th>{$format_month} {$year}</th>
		</tr>
	</table>

	<table cellspacing='0' cellpadding='2' border='1' style='table-layout: fixed; width: 100%;'>
		<thead style='word-wrap: break-word;'>
			<tr>
				<th>Оператор</th>
				<th>Непролив</th>
				<th>Усадочная трещина</th>
				<th>Легкие детали</th>
				<th>Тяжелые детали</th>
			</tr>
		</thead>
		<tbody style='text-align: center;'>
";

$query = "
	SELECT USR_Name(LB.operator) operator
		,CONCAT(SUM(IF(LW.goodsID = 2, 1, 0)), ' (', ROUND(SUM(IF(LW.goodsID = 2, 1, 0)) / SUM(1) * 100, 2), '%)') not_spill
		,CONCAT(SUM(IF(LW.goodsID = 4, 1, 0)), ' (', ROUND(SUM(IF(LW.goodsID = 4, 1, 0)) / SUM(1) * 100, 2), '%)') crack_drying
		,CONCAT(SUM(IF(LW.weight < ROUND(CW.min_weight/100*101), 1, NULL)), ' (', ROUND(SUM(IF(LW.weight < ROUND(CW.min_weight/100*101), 1, NULL)) / SUM(1) * 100, 2), '%)') light
		,CONCAT(SUM(IF(LW.weight > ROUND(CW.max_weight/100*101), 1, NULL)), ' (', ROUND(SUM(IF(LW.weight > ROUND(CW.max_weight/100*101), 1, NULL)) / SUM(1) * 100, 2), '%)') heavy
	FROM plan__Batch PB
	JOIN list__Batch LB ON LB.PB_ID = PB.PB_ID
	JOIN list__Filling LF ON LF.LB_ID = LB.LB_ID
	JOIN list__Opening LO ON LO.LF_ID = LF.LF_ID
	JOIN list__Weight LW ON LW.LO_ID = LO.LO_ID
	JOIN CounterWeight CW ON CW.CW_ID = PB.CW_ID
	WHERE YEAR(LB.batch_date) = {$year}
		AND MONTH(LB.batch_date) = {$month}
		AND LB.operator IS NOT NULL
	GROUP BY LB.operator
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query1: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	$message .= "
		<tr>
			<td>{$row["operator"]}</td>
			<td>{$row["not_spill"]}</td>
			<td>{$row["crack_drying"]}</td>
			<td>{$row["light"]}</td>
			<td>{$row["heavy"]}</td>
		</tr>
	";
}

$message .= "
		</tbody>
	</table>
";

$headers  = "MIME-Version: 1.0\r\n";
$headers .= "Content-type: text/html; charset=\"utf-8\"\r\n";
$headers .= "From: planner@konstanta.ltd\r\n";

mail($to, $subject, $message, $headers);
?>
