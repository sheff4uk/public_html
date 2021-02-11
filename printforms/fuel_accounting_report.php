<?
include "../config.php";
?>

<!DOCTYPE html>
<html lang="ru">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">

<?
$date = date_create('-1 month');
$year = date_format($date, 'Y');
$month = date_format($date, 'm');

echo "<title>Shells report on {$sr_date_format}</title>";
?>
	<style type="text/css" media="print">
		@page { size: portrait; }
	</style>

	<style>
		body, td {
			font-family: Trebuchet MS, Tahoma, Verdana, Arial, sans-serif;
			font-size: 8pt;
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
			<th style="font-size: 2em;">Журнал учета дизельного топлива</th>
			<th>За: <n style="font-size: 2em;"><?=$month?>.<?=$year?></n></th>
		</tr>
	</thead>
</table>

<table class="main_table">
	<thead>
		<tr>
			<th>Дата заправки / приобретения</th>
			<th>Время заправки / приобретения</th>
			<th>Кол-во приобретенного топлива</th>
			<th>Показания счетчика до заправки</th>
			<th>Показания счетчика после заправки</th>
			<th>Кол-во заправленного топлива</th>
			<th>Техника</th>
			<th>Показания счетчика пробега на момент заправки</th>
			<th>Пробег с последней заправки</th>
		</tr>
	</thead>
	<tbody style="text-align: center;">

<?
$query = "
	SELECT 'F' type
		,FF.FF_ID ID
		,STR_TO_DATE(CONCAT(FF.ff_date, ' ', FF.ff_time), '%Y-%m-%d %H:%i:%s') date_time
		,DATE_FORMAT(FF.ff_date, '%d.%m.%Y')	date_format
		,DATE_FORMAT(FF.ff_time, '%H:%i')		time_format
		,NULL fa_cnt
		,FD.fuel_device
		,FF.fuel_meter_value - FF.ff_cnt		fuel_meter_before
		,FF.fuel_meter_value
		,FF.ff_cnt
		,FF.hour_meter_value
		,FF.hours_cnt
		,DATE_FORMAT(FF.last_edit, '%d.%m.%Y в %H:%i:%s') last_edit
	FROM fuel__Filling FF
	JOIN fuel__Device FD ON FD.FD_ID = FF.FD_ID
	WHERE YEAR(FF.ff_date) = {$year} AND MONTH(FF.ff_date) = {$month}

	UNION

	SELECT 'A'
		,FA.FA_ID
		,STR_TO_DATE(CONCAT(FA.fa_date, ' ', FA.fa_time), '%Y-%m-%d %H:%i:%s')
		,DATE_FORMAT(FA.fa_date, '%d.%m.%Y')
		,DATE_FORMAT(FA.fa_time, '%H:%i')
		,FA.fa_cnt
		,NULL
		,NULL
		,NULL
		,NULL
		,NULL
		,NULL
		,DATE_FORMAT(FA.last_edit, '%d.%m.%Y в %H:%i:%s')
	FROM fuel__Arrival FA
	WHERE YEAR(FA.fa_date) = {$year} AND MONTH(FA.fa_date) = {$month}

	ORDER BY date_time, type
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	$fa_cnt += $row["fa_cnt"];
	$ff_cnt += $row["ff_cnt"];
	$hours_cnt += $row["hours_cnt"];
	?>
	<tr>
		<td><?=$row["date_format"]?></td>
		<td><?=$row["time_format"]?></td>
		<td><b><?=$row["fa_cnt"]?></b></td>
		<td><?=$row["fuel_meter_before"]?></td>
		<td><?=$row["fuel_meter_value"]?></td>
		<td><b><?=$row["ff_cnt"]?></b></td>
		<td><span class="nowrap"><?=$row["fuel_device"]?></span></td>
		<td><?=$row["hour_meter_value"]?></td>
		<td><?=$row["hours_cnt"]?></td>
	</tr>
	<?
}
?>
		<tr class="total">
			<td></td>
			<td>Итог:</td>
			<td><b><?=$fa_cnt?></b></td>
			<td></td>
			<td></td>
			<td><b><?=$ff_cnt?></b></td>
			<td></td>
			<td></td>
			<td><b><?=$hours_cnt?></b></td>
		</tr>
	</tbody>
</table>

<?
// Узнаем баланс топлива на начало и конец месяца
$query = "
	SELECT SUM(FT.ft_balance) ft_balance
	FROM fuel__Tank FT
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
$row = mysqli_fetch_array($res);
$ft_balance = $row["ft_balance"];

$query = "
	SELECT SUM(FF.ff_cnt) ff_cnt
	FROM fuel__Filling FF
	WHERE FF.ff_date >= '{$year}-{$month}-01' + INTERVAL 1 MONTH
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
$row = mysqli_fetch_array($res);
$ff_cnt_after = $row["ff_cnt"];

$query = "
	SELECT SUM(FA.fa_cnt) fa_cnt
	FROM fuel__Arrival FA
	WHERE FA.fa_date >= '{$year}-{$month}-01' + INTERVAL 1 MONTH
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
$row = mysqli_fetch_array($res);
$fa_cnt_after = $row["fa_cnt"];

echo "<h3 style='float: right;'>Баланс топлива на конец месяца: <span style='font-size: 2em;'>".($ft_balance - $fa_cnt_after + $ff_cnt_after)."</span> л</h3>";
echo "<h3>Баланс топлива на начало месяца: <span style='font-size: 2em;'>".($ft_balance - $fa_cnt + $ff_cnt - $fa_cnt_after + $ff_cnt_after)."</span> л</h3>";
echo "<h3>Материально ответственное лицо: ____________________________/____________</h3>";
?>

</body>
</html>
