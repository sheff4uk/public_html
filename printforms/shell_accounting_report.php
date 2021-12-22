<?
include "../config.php";
?>

<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">

<?
$date = date_create();
$sr_date_format = date_format($date, 'd/m/Y');

echo "<title>Shells report on {$sr_date_format}</title>";
?>
	<style type="text/css" media="print">
		@page { size: portrait; }
	</style>

	<style>
		body, td {
			font-family: Trebuchet MS, Tahoma, Verdana, Arial, sans-serif;
			font-size: 10pt;
		}
		table {
			table-layout: fixed;
			width: 100%;
			border-collapse: collapse;
			border-spacing: 0px;
		}
		.thead {
			text-align: center;
			font-weight: bold;
		}
		td, th {
			padding: 3px;
			border: 1px solid black;
			line-height: 1em;
		}
		.nowrap {
			white-space: nowrap;
		}
		.total {
			font-weight: bold;
		}
	</style>
</head>
<body>

<table>
	<thead>
		<tr>
			<th><img src="/img/logo.png" alt="KONSTANTA" style="width: 200px; margin: 5px;"></th>
			<th>Report date: <n style="font-size: 2em;"><?=$sr_date_format?></n></th>
		</tr>
	</thead>
</table>

<table>
	<thead style="word-wrap: break-word;">
		<tr>
			<th>Part-number</th>
			<th>Number of OK shells</th>
<!--			<th>Average durability of shells in filling cycles</th>-->
<!--			<th>Daily average shell scrap</th>-->
			<th>Shell scrap on the past day</th>
			<th>Peak value of shell in use</th>
			<th>Shortage of shells</th>
<!--			<th>Days to shortage of shells to peak value based on current scrap level</th>-->
		</tr>
	</thead>
	<tbody style="text-align: center;">
		<?
		$query = "
			SELECT CW.drawing_item
				,CW.shell_balance
				,ROUND((WB.fillings * PB.in_cassette) / WR.sr_cnt) `durability`
				,ROUND(WR.sr_cnt / DATEDIFF(CURDATE() - INTERVAL 1 DAY, '2020-12-04'), 1) `sr_avg`
				,ROUND(AVG(IF(PB.fact_batches = 0, NULL, PB.fact_batches) * PB.fillings / PB.per_batch * PB.in_cassette)) `often`
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
			WHERE 1
				".($_GET["CB_ID"] ? "AND CW.CW_ID IN (SELECT CW_ID FROM CounterWeight WHERE CB_ID = {$_GET["CB_ID"]})" : "")."
			GROUP BY CW.CW_ID
			ORDER BY CW.CW_ID
		";
		$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		while( $row = mysqli_fetch_array($res) ) {
			//$pallets += $row["pallets"];
			?>
				<tr>
					<td><?=$row["drawing_item"]?></td>
					<td><?=$row["shell_balance"]?></td>
<!--					<td><?=$row["durability"]?></td>-->
<!--					<td><?=$row["sr_avg"]?></td>-->
					<td><?=$row["sr_cnt"]?></td>
					<td><?=$row["max"]?></td>
					<td style="color: red;"><?=($row["need"] > 0 ? $row["need"] : "")?></td>
<!--					<td><?=($row["days_max"] < 0 ? "" : "{$row["days_max"]} <sub>{$row["date_max"]}</sub>")?></td>-->
				</tr>
			<?
		}
		?>
	</tbody>
</table>

</body>
</html>
