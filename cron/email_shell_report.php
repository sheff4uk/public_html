<?
$path = dirname(dirname($argv[0]));
$key = $argv[1];
$CB_ID = $argv[2];
$to = $argv[3];

include $path."/config.php";
// Проверка доступа
if( $key != $script_key ) die('Access denied!');

$date = date_create();
$sr_date_format = date_format($date, 'd/m/Y');
$subject = "[KONSTANTA] Shell/Pallets report on {$sr_date_format}";

$message = "
	<table cellspacing='0' cellpadding='2' border='1' style='table-layout: fixed; width: 100%;'>
		<tr>
			<th><img src='https://konstanta.ltd/assets/images/logo.png' alt='KONSTANTA' style='width: 200px; margin: 5px;'></th>
			<th><n style='font-size: 2em;'>Shell report</n><br><a href='https://kis.konstanta.ltd/online_shell_report.php'>Click here to open the online report</a></th>
			<th>Report date: <n style='font-size: 2em;'>{$sr_date_format}</n></th>
		</tr>
	</table>

	<table cellspacing='0' cellpadding='2' border='1' style='table-layout: fixed; width: 100%;'>
		<thead style='word-wrap: break-word;'>
			<tr>
				<th>Part-number</th>
				<th>Number of OK shells</th>
				<th>Shell scrap on the past day</th>
				<th>Peak value of shell in use</th>
				<th>Shortage of shells</th>
			</tr>
		</thead>
		<tbody style='text-align: center;'>
";

$query = "
	SELECT CW.drawing_item
		,CW.shell_balance
		,ROUND((WB.fillings * PB.in_cassette) / WR.sr_cnt) `durability`
		,ROUND(WR.sr_cnt / DATEDIFF(CURDATE() - INTERVAL 1 DAY, '2020-12-04'), 1) `sr_avg`
		,ROUND(AVG(PB.fact_batches * PB.fillings / PB.per_batch * PB.in_cassette)) `often`
		,MAX(ROUND(PB.fact_batches * PB.fillings / PB.per_batch) * PB.in_cassette) `max`
		,MAX(ROUND(PB.fact_batches * PB.fillings / PB.per_batch) * PB.in_cassette) - CW.shell_balance `need`
		,ROUND((CW.shell_balance - MAX(PB.fact_batches * PB.fillings / PB.per_batch * PB.in_cassette)) / (WR.sr_cnt / DATEDIFF(CURDATE() - INTERVAL 1 DAY, '2020-12-04'))) `days_max`
		,DATE_FORMAT(CURDATE() + INTERVAL ROUND((CW.shell_balance - MAX(PB.fact_batches * PB.fillings / PB.per_batch * PB.in_cassette)) / (WR.sr_cnt / DATEDIFF(CURDATE() - INTERVAL 1 DAY, '2020-12-04'))) DAY, '%d/%m/%Y') `date_max`
		,SR.sr_cnt
	FROM CounterWeight CW
	LEFT JOIN (
		SELECT CW_ID
			,SUM(sr_cnt) sr_cnt
		FROM shell__Reject
		WHERE sr_date = CURDATE() - INTERVAL 1 DAY
		GROUP BY CW_ID
	) SR ON SR.CW_ID = CW.CW_ID
	LEFT JOIN plan__Batch PB ON PB.CW_ID = CW.CW_ID
	# Число заливок с 04.12.2020
	LEFT JOIN (
		SELECT PB.CW_ID
			,SUM(1) fillings
		FROM list__Filling LF
		JOIN list__Batch LB ON LB.LB_ID = LF.LB_ID
		JOIN plan__Batch PB ON PB.PB_ID = LB.PB_ID
		WHERE DATE(LF.filling_time) BETWEEN '2020-12-04' AND CURDATE() - INTERVAL 1 DAY
		GROUP BY PB.CW_ID
	) WB ON WB.CW_ID = CW.CW_ID
	# Число списаний с 04.12.2020
	LEFT JOIN (
		SELECT CW_ID
			,SUM(sr_cnt) sr_cnt
		FROM shell__Reject
		WHERE sr_date BETWEEN '2020-12-04' AND CURDATE() - INTERVAL 1 DAY
		GROUP BY CW_ID
	) WR ON WR.CW_ID = CW.CW_ID
	WHERE CW.CW_ID IN (SELECT CW_ID FROM CounterWeight WHERE CB_ID = {$CB_ID})
	GROUP BY CW.CW_ID
	ORDER BY CW.CW_ID
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	$message .= "
		<tr>
			<td>{$row["drawing_item"]}</td>
			<td>{$row["shell_balance"]}</td>
			<td>{$row["sr_cnt"]}</td>
			<td>{$row["max"]}</td>
			<td style='color: red;'>".($row["need"] > 0 ? $row["need"] : "")."</td>
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
			<th><n style='font-size: 2em;'>Pallets report</n><br><a href='https://kis.konstanta.ltd/online_pallet_report.php'>Click here to open the online report</a></th>
			<th>Report date: <n style='font-size: 2em;'>{$sr_date_format}</n></th>
		</tr>
	</table>

	<table cellspacing='0' cellpadding='2' border='1' style='table-layout: fixed; width: 100%;'>
		<thead style='word-wrap: break-word;'>
			<tr>
				<!--<th>Number of pallets shipped today</th>-->
				<!--<th>Pallets returned today</th>-->
				<!--<th>Broken pallets found today</th>-->
				<th>Debt in pallets (Vesta)</th>
				<th>Debt in rubles</th>
			</tr>
		</thead>
		<tbody style='text-align: center;'>
			<tr>
";

//$query = "
//	SELECT SUM(pallets) shipped
//	FROM list__Shipment LS
//	JOIN CounterWeight CW ON CW.CW_ID = LS.CW_ID
//	WHERE LS.ls_date = CURDATE()
//		AND CW.CB_ID = {$CB_ID}
//";
//$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
//$row = mysqli_fetch_array($res);
//$message .= "
//	<td>{$row["shipped"]}</td>
//";
//
//// Возвращенные поддоны
//$query = "
//	SELECT SUM(PR.pr_cnt) pr_cnt
//	FROM pallet__Return PR
//	WHERE PR.pr_date = CURDATE()
//		AND PR.CB_ID = {$CB_ID}
//";
//$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
//$row = mysqli_fetch_array($res);
//$message .= "
//	<td>{$row["pr_cnt"]}</td>
//";
//
//// Сломанные поддоны
//$query = "
//	SELECT SUM(PD.pd_cnt) pd_cnt
//	FROM pallet__Disposal PD
//	WHERE PD.pd_date = CURDATE()
//		AND PD.CB_ID = {$CB_ID}
//";
//$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
//$row = mysqli_fetch_array($res);
//$message .= "
//	<td>{$row["pd_cnt"]}</td>
//";

// Узнаем актуальную стоимость поддона (дерево)
$query = "
	SELECT PA.pallet_cost
	FROM pallet__Arrival PA
	JOIN pallet__Supplier PS ON PS.PS_ID = PA.PS_ID AND PS.PN_ID = 1
	WHERE PA.pallet_cost > 0
	ORDER BY PA.pa_date DESC, PA.PA_ID DESC
	LIMIT 1
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
$row = mysqli_fetch_array($res);
$actual_pallet_cost = $row["pallet_cost"];

// Узнаем долг в поддонах (дерево)
$query = "
	SELECT PCB.pallet_balance
	FROM pallet__ClientBalance PCB
	WHERE PCB.CB_ID = {$CB_ID}
		AND PCB.PN_ID = 1
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
$row = mysqli_fetch_array($res);
$message .= "
	<td style='color: red;'>{$row["pallet_balance"]}</td>
	<td style='color: red;'><b>&#8381;".number_format(( $row["pallet_balance"] * $actual_pallet_cost ), 0, '', ' ')."</b></td>
";

$message .= "
			</tr>
		</tbody>
	</table>
";

$message .= "
	<p>This letter is generated automatically. Please do not answer it. If you have any questions, you can contact us by e-mail info@konstanta.ltd.</p>
";

$headers  = "Content-type: text/html; charset=utf-8 \r\n";
$headers .= "From: planner@konstanta.ltd\r\n";

mail($to, $subject, $message, $headers);
?>
