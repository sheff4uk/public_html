<?
$path = dirname(dirname($argv[0]));
$key = $argv[1];
$CB_ID = $argv[2];
$to = $argv[3];

include $path."/config.php";
// Проверка доступа
if( $key != $script_key ) die('Access denied!');

$date = new DateTime();
$sr_date_format = date_format($date, 'd/m/Y');
$subject = "[KONSTANTA] Shell report on {$sr_date_format}";

$message = "
	<table cellspacing='0' cellpadding='2' border='1' style='table-layout: fixed; width: 100%;'>
		<tr>
			<th><img src='https://konstanta.ltd/assets/images/logo.png' alt='KONSTANTA' style='width: 200px; margin: 5px;'></th>
			<th>Report date: <n style='font-size: 2em;'>{$sr_date_format}</n></th>
		</tr>
	</table>

	<table cellspacing='0' cellpadding='2' border='1' style='table-layout: fixed; width: 100%;'>
		<thead style='word-wrap: break-word;'>
			<tr>
				<th>Part-number</th>
				<th>Number of OK shells</th>
				<th>Peak value of shell in use</th>
				<th>Shortage of shells</th>
			</tr>
		</thead>
		<tbody style='text-align: center;'>
";

$query = "
	SELECT CW.item
		,CW.shell_balance
		,ROUND((WB.batches * CW.fillings * CW.in_cassette) / WR.sr_cnt) `durability`
		,ROUND(WR.sr_cnt / DATEDIFF(CURDATE() - INTERVAL 1 DAY, '2020-12-04'), 1) `sr_avg`
		,ROUND(AVG(IF(PB.fakt = 0 OR WEEKDAY(PB.pb_date) IN (5,6), NULL, PB.fakt))) * CW.fillings * CW.in_cassette `often`
		,MAX(PB.fakt) * CW.fillings * CW.in_cassette `max`
		,MAX(PB.fakt) * CW.fillings * CW.in_cassette - CW.shell_balance `need`
		,ROUND((CW.shell_balance - MAX(PB.fakt) * CW.fillings * CW.in_cassette) / (WR.sr_cnt / DATEDIFF(CURDATE() - INTERVAL 1 DAY, '2020-12-04'))) `days_max`
		,DATE_FORMAT(CURDATE() + INTERVAL ROUND((CW.shell_balance - MAX(PB.fakt) * CW.fillings * CW.in_cassette) / (WR.sr_cnt / DATEDIFF(CURDATE() - INTERVAL 1 DAY, '2020-12-04'))) DAY, '%d/%m/%Y') `date_max`
		,CEIL((CW.shell_balance - IFNULL(ROUND(AVG(IF(PB.fakt = 0 OR WEEKDAY(PB.pb_date) IN (5,6), NULL, PB.fakt))), 0) * CW.fillings * CW.in_cassette) / CW.shell_pallet) `pallets`
	FROM CounterWeight CW
	LEFT JOIN plan__Batch PB ON PB.CW_ID = CW.CW_ID
		#AND PB.pb_date BETWEEN (CURDATE() - INTERVAL 91 DAY) AND (CURDATE() - INTERVAL 1 DAY)
	# Число замесов с 04.12.2020
	LEFT JOIN (
		SELECT CW_ID
			,SUM(fakt) batches
		FROM plan__Batch
		WHERE pb_date BETWEEN '2020-12-04' AND CURDATE() - INTERVAL 1 DAY
		GROUP BY CW_ID
	) WB ON WB.CW_ID = CW.CW_ID
	# Число списаний с 04.12.2020
	LEFT JOIN (
		SELECT CW_ID
			,SUM(sr_cnt) sr_cnt
		FROM ShellReject
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
			<td>{$row["item"]}</td>
			<td>{$row["shell_balance"]}</td>
			<td>{$row["max"]}</td>
			<td style='color: red;'>".($row["need"] > 0 ? $row["need"] : "")."</td>
		</tr>
	";
}

$message .= "
		</tbody>
	</table>
	<p>This letter is generated automatically. Please do not answer it. If you have any questions, you can contact us by e-mail info@konstanta.ltd.</p>
";

$headers  = "Content-type: text/html; charset=utf-8 \r\n";
$headers .= "From: planner@konstanta.ltd\r\n";

mail($to, $subject, $message, $headers);
?>
