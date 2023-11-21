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
//$subject = "[KONSTANTA] Shell/Pallets report on {$sr_date_format}";
$subject = "[KONSTANTA] Shell/Pallets report on ";

$message = "Report date: <n style='font-size: 2em;'></n>";
// $message = "
// 	<table cellspacing='0' cellpadding='2' border='1' style='table-layout: fixed; width: 100%;'>\n
// 		<tr>\n
// 			<th><img src='https://konstanta.ltd/assets/images/logo.png' alt='KONSTANTA' style='width: 200px; margin: 5px;'></th>\n
// 			<th><n style='font-size: 2em;'>Shell report</n><br><a href='https://kis.konstanta.ltd/online_shell_report.php'>Click here to open the online report</a></th>\n
// 			<th>Report date: <n style='font-size: 2em;'>{$sr_date_format}</n></th>\n
// 		</tr>\n
// 	</table>\n
// ";
// $message = "
// 	<table cellspacing='0' cellpadding='2' border='1' style='table-layout: fixed; width: 100%;'>\n
// 		<thead style='word-wrap: break-word;'>\n
// 			<tr>\n
// 				<th>Part-number</th>\n
// 				<th>Number of OK shells</th>\n
// 				<th>Shell scrap on the past day</th>\n
// 				<th>Current need for shells</th>\n
// 				<th>Shortage of shells</th>\n
// 			</tr>\n
// 		</thead>\n
// 		<tbody style='text-align: center;'>\n
// ";

// $query = "
// 	SELECT CW.drawing_item
// 		,CW.shell_balance
// 		#,ROUND((WB.fillings * PB.in_cassette) / WR.sr_cnt) `durability`
// 		#,ROUND(WR.sr_cnt / DATEDIFF(CURDATE() - INTERVAL 1 DAY, '2020-12-04'), 1) `sr_avg`
// 		#,ROUND(AVG(PB.fact_batches * PB.fillings / PB.per_batch * PB.in_cassette)) `often`
// 		#,MAX(ROUND(PB.fact_batches * PB.fillings / PB.per_batch) * PB.in_cassette) `max`
// 		,(
// 			SELECT SUM(MixFormula.in_cassette)
// 			FROM Cassettes
// 			JOIN MixFormula ON MixFormula.F_ID = Cassettes.F_ID
// 				AND MixFormula.CW_ID = Cassettes.CW_ID
// 			WHERE MixFormula.CW_ID = CW.CW_ID
// 		) current_need
// 		#,MAX(ROUND(PB.fact_batches * PB.fillings / PB.per_batch) * PB.in_cassette) - CW.shell_balance `need`
// 		#,ROUND((CW.shell_balance - MAX(PB.fact_batches * PB.fillings / PB.per_batch * PB.in_cassette)) / (WR.sr_cnt / DATEDIFF(CURDATE() - INTERVAL 1 DAY, '2020-12-04'))) `days_max`
// 		#,DATE_FORMAT(CURDATE() + INTERVAL ROUND((CW.shell_balance - MAX(PB.fact_batches * PB.fillings / PB.per_batch * PB.in_cassette)) / (WR.sr_cnt / DATEDIFF(CURDATE() - INTERVAL 1 DAY, '2020-12-04'))) DAY, '%d/%m/%Y') `date_max`
// 		,SR.sr_cnt
// 	FROM CounterWeight CW
// 	LEFT JOIN (
// 		SELECT CW_ID
// 			,SUM(sr_cnt) sr_cnt
// 		FROM shell__Reject
// 		WHERE sr_date = CURDATE() - INTERVAL 1 DAY
// 		GROUP BY CW_ID
// 	) SR ON SR.CW_ID = CW.CW_ID
// 	LEFT JOIN plan__Batch PB ON PB.CW_ID = CW.CW_ID
// 	# Число заливок с 04.12.2020
// 	LEFT JOIN (
// 		SELECT PB.CW_ID
// 			,SUM(1) fillings
// 		FROM list__Filling LF
// 		JOIN list__Batch LB ON LB.LB_ID = LF.LB_ID
// 		JOIN plan__Batch PB ON PB.PB_ID = LB.PB_ID
// 		WHERE DATE(LF.filling_time) BETWEEN '2020-12-04' AND CURDATE() - INTERVAL 1 DAY
// 		GROUP BY PB.CW_ID
// 	) WB ON WB.CW_ID = CW.CW_ID
// 	# Число списаний с 04.12.2020
// 	LEFT JOIN (
// 		SELECT CW_ID
// 			,SUM(sr_cnt) sr_cnt
// 		FROM shell__Reject
// 		WHERE sr_date BETWEEN '2020-12-04' AND CURDATE() - INTERVAL 1 DAY
// 		GROUP BY CW_ID
// 	) WR ON WR.CW_ID = CW.CW_ID
// 	WHERE CW.CB_ID = {$CB_ID}
// 	GROUP BY CW.CW_ID
// 	ORDER BY CW.CW_ID
// ";
// $res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
// while( $row = mysqli_fetch_array($res) ) {
// 	$need = $row["current_need"] - $row["shell_balance"];
// 	$message .= "
// 		<tr>\n
// 			<td>{$row["drawing_item"]}</td>\n
// 			<td>{$row["shell_balance"]}</td>\n
// 			<td>{$row["sr_cnt"]}</td>\n
// 			<td>{$row["current_need"]}</td>\n
// 			<td style='color: red;'>".($need > 0 ? $need : "")."</td>\n
// 		</tr>\n
// 	";
// }

// $message .= "
// 		</tbody>\n
// 	</table>\n
// 	<br>\n
// 	<br>\n
// ";

// $message .= "
// 	<table cellspacing='0' cellpadding='2' border='1' style='table-layout: fixed; width: 100%;'>
// 		<tr>
// 			<th><img src='https://konstanta.ltd/assets/images/logo.png' alt='KONSTANTA' style='width: 200px; margin: 5px;'></th>
// 			<th><n style='font-size: 2em;'>Pallets report</n><br><a href='https://kis.konstanta.ltd/online_pallet_report.php'>Click here to open the online report</a></th>
// 			<th>Report date: <n style='font-size: 2em;'>{$sr_date_format}</n></th>
// 		</tr>
// 	</table>

// 	<table cellspacing='0' cellpadding='2' border='1' style='table-layout: fixed; width: 100%;'>
// 		<thead style='word-wrap: break-word;'>
// 			<tr>
// 				<!--<th>Number of pallets shipped today</th>-->
// 				<!--<th>Pallets returned today</th>-->
// 				<!--<th>Broken pallets found today</th>-->
// 				<th>Debt in pallets (Vesta)</th>
// 				<th>Debt in rubles</th>
// 			</tr>
// 		</thead>
// 		<tbody style='text-align: center;'>
// 			<tr>
// ";

// // Узнаем актуальную стоимость поддона (дерево)
// $query = "
// 	SELECT PA.pallet_cost
// 	FROM pallet__Arrival PA
// 	JOIN pallet__Supplier PS ON PS.PS_ID = PA.PS_ID AND PS.PN_ID = 1
// 	WHERE PA.pallet_cost > 0
// 	ORDER BY PA.pa_date DESC, PA.PA_ID DESC
// 	LIMIT 1
// ";
// $res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
// $row = mysqli_fetch_array($res);
// $actual_pallet_cost = $row["pallet_cost"];

// // Узнаем долг в поддонах (дерево)
// $query = "
// 	SELECT PCB.pallet_balance
// 	FROM pallet__ClientBalance PCB
// 	WHERE PCB.CB_ID = {$CB_ID}
// 		AND PCB.PN_ID = 1
// ";
// $res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
// $row = mysqli_fetch_array($res);
// $message .= "
// 	<td style='color: red;'>{$row["pallet_balance"]}</td>
// 	<td style='color: red;'><b>&#8381;".number_format(( $row["pallet_balance"] * $actual_pallet_cost ), 0, '', ' ')."</b></td>
// ";

// $message .= "
// 			</tr>
// 		</tbody>
// 	</table>
// ";

// $message .= "
// 	<p>This letter is generated automatically. Please do not answer it. If you have any questions, you can contact us by e-mail info@konstanta.ltd.</p>
// ";

$headers  = "Content-type: text/html; charset=utf-8 \r\n";
$headers .= "From: planner@konstanta.ltd\r\n";

mail($to, $subject, $message, $headers);
?>
