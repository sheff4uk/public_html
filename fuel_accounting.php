<?php
include "config.php";
$title = 'Учет дизтоплива';
include "header.php";

// Проверка прав на доступ к экрану
if( !in_array('fuel_accounting', $Rights) ) {
	header($_SERVER['SERVER_PROTOCOL'].' 403 Forbidden');
	die('Недостаточно прав для совершения операции');
}

$FT_ID = 3; // Номер топливной цистерны

// Если в фильтре не установлен период, показываем последние 7 дней
if( !$_GET["date_from"] ) {
	$date = date_create('-6 days');
	$_GET["date_from"] = date_format($date, 'Y-m-d');
}
if( !$_GET["date_to"] ) {
	$date = date_create('-0 days');
	$_GET["date_to"] = date_format($date, 'Y-m-d');
}

include "./forms/fuel_accounting_form.php";

// Узнаем баланс топлива
//$query = "
//	SELECT SUM(FT.ft_balance) ft_balance
//	FROM fuel__Tank FT
//";
$query = "
	SELECT FT.ft_balance
	FROM fuel__Tank FT
	WHERE FT.FT_ID = {$FT_ID}
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
$row = mysqli_fetch_array($res);
$ft_balance = $row["ft_balance"];
// Выводим на экран
echo "<h3>Баланс дизтоплива: <span style='font-size: 2em; color: brown;'>{$ft_balance}</span> л</h3>";
?>

<style>
	#fuel_report_btn {
		text-align: center;
		line-height: 68px;
		color: #fff;
		bottom: 250px;
		cursor: pointer;
		width: 56px;
		height: 56px;
		opacity: .4;
		position: fixed;
		right: 20px;
		z-index: 9;
		border-radius: 50%;
		background-color: #db4437;
		box-shadow: 0 0 4px rgba(0,0,0,.14), 0 4px 8px rgba(0,0,0,.28);
	}
	#fuel_arrival_btn {
		text-align: center;
		line-height: 68px;
		color: #fff;
		bottom: 175px;
		cursor: pointer;
		width: 56px;
		height: 56px;
		opacity: .4;
		position: fixed;
		right: 20px;
		z-index: 9;
		border-radius: 50%;
		background-color: #16A085;
		box-shadow: 0 0 4px rgba(0,0,0,.14), 0 4px 8px rgba(0,0,0,.28);
	}
	#fuel_filling_btn {
		text-align: center;
		line-height: 68px;
		color: #fff;
		bottom: 100px;
		cursor: pointer;
		width: 56px;
		height: 56px;
		opacity: .4;
		position: fixed;
		right: 20px;
		z-index: 9;
		border-radius: 50%;
		background-color: #db4437;
		box-shadow: 0 0 4px rgba(0,0,0,.14), 0 4px 8px rgba(0,0,0,.28);
	}
	#fuel_report_btn:hover, #fuel_arrival_btn:hover, #fuel_filling_btn:hover {
		opacity: 1;
	}
</style>

<!--Фильтр-->
<div id="filter">
	<h3>Фильтр</h3>
	<form method="get" style="position: relative;">
		<a href="/fuel_accounting.php" style="position: absolute; top: 10px; right: 10px;" class="button">Сброс</a>

		<div class="nowrap" style="margin-bottom: 10px;">
			<span style="display: inline-block; width: 200px;">Дата между:</span>
			<input name="date_from" type="date" value="<?=$_GET["date_from"]?>" class="<?=$_GET["date_from"] ? "filtered" : ""?>">
			<input name="date_to" type="date" value="<?=$_GET["date_to"]?>" class="<?=$_GET["date_to"] ? "filtered" : ""?>">
			<i class="fas fa-question-circle" title="По умолчанию устанавливаются последние 7 дней."></i>
		</div>

		<div class="nowrap" style="display: inline-block; margin-bottom: 10px; margin-right: 30px;">
			<span>Техника:</span>
			<select name="FD_ID" class="<?=$_GET["FD_ID"] ? "filtered" : ""?>">
				<option value=""></option>
				<?php
				$query = "
					SELECT FD.FD_ID
						,FD.fuel_device
						,CONCAT(' (пробег: ', FD.last_hour_meter_value, ')') last_hour_meter_value
					FROM fuel__Device FD
					ORDER BY FD.FD_ID
				";
				$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
				while( $row = mysqli_fetch_array($res) ) {
					$selected = ($row["FD_ID"] == $_GET["FD_ID"]) ? "selected" : "";
					echo "<option value='{$row["FD_ID"]}' {$selected}>{$row["fuel_device"]}{$row["last_hour_meter_value"]}</option>";
				}
				?>
			</select>
		</div>

		<button style="float: right;">Фильтр</button>
	</form>
</div>

<?php
// Узнаем есть ли фильтр
$filter = 0;
foreach ($_GET as &$value) {
	if( $value ) $filter = 1;
}
?>

<script>
	$(document).ready(function() {
		$( "#filter" ).accordion({
			active: <?=($filter ? "0" : "false")?>,
			collapsible: true,
			heightStyle: "content"
		});

		// При скроле сворачивается фильтр
		$(window).scroll(function(){
			$( "#filter" ).accordion({
				active: "false"
			});
		});
	});
</script>

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
			<th>Автор</th>
			<th></th>
		</tr>
	</thead>
	<tbody style="text-align: center;">

<?php
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
		,IF(FF.USR_ID, USR_Icon(FF.USR_ID), '') USR_Icon
		,DATE_FORMAT(FF.last_edit, '%d.%m.%Y в %H:%i:%s') last_edit
		,IFNULL(FT.FT_ID, 0) last
	FROM fuel__Filling FF
	JOIN fuel__Device FD ON FD.FD_ID = FF.FD_ID
	LEFT JOIN fuel__Tank FT ON FT.FT_ID = FF.FT_ID AND FT.last_fuel_meter_value = FF.fuel_meter_value
	WHERE 1
		".($_GET["date_from"] ? "AND FF.ff_date >= '{$_GET["date_from"]}'" : "")."
		".($_GET["date_to"] ? "AND FF.ff_date <= '{$_GET["date_to"]}'" : "")."
		".($_GET["FD_ID"] ? "AND FF.FD_ID={$_GET["FD_ID"]}" : "")."

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
		,IF(FA.USR_ID, USR_Icon(FA.USR_ID), '')
		,DATE_FORMAT(FA.last_edit, '%d.%m.%Y в %H:%i:%s')
		,IF((SELECT FA_ID FROM fuel__Arrival ORDER BY FA_ID DESC LIMIT 1) = FA.FA_ID, 1, 0) last
	FROM fuel__Arrival FA
	WHERE 1
		".($_GET["date_from"] ? "AND FA.fa_date >= '{$_GET["date_from"]}'" : "")."
		".($_GET["date_to"] ? "AND FA.fa_date <= '{$_GET["date_to"]}'" : "")."
		".($_GET["FD_ID"] ? "AND 0" : "")."

	ORDER BY date_time, type
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	$fa_cnt += $row["fa_cnt"];
	$ff_cnt += $row["ff_cnt"];
	$hours_cnt += $row["hours_cnt"];
	?>
	<tr id="<?=$row["type"]?><?=$row["ID"]?>" class="<?=($row["fa_cnt"] ? "row_arrival" : "")?>">
		<td><?=$row["date_format"]?></td>
		<td><?=$row["time_format"]?></td>
		<td><b><?=$row["fa_cnt"]?></b></td>
		<td><?=$row["fuel_meter_before"]?></td>
		<td><?=$row["fuel_meter_value"]?></td>
		<td><b><?=$row["ff_cnt"]?></b></td>
		<td><span class="nowrap"><?=$row["fuel_device"]?></span></td>
		<td><?=$row["hour_meter_value"]?></td>
		<td><?=$row["hours_cnt"]?></td>
		<td><?=$row["USR_Icon"]?><?=($row["last_edit"] ? "<i class='fas fa-clock' title='Сохранено ".$row["last_edit"]."'.></i>" : "")?></td>
		<td><a href="#" <?=($row["last"] ? ($row["type"] == "A" ? "class='add_arrival' FA_ID='{$row["ID"]}'" : "class='add_filling' FF_ID='{$row["ID"]}'") : "style='display: none;'")?> title="Редактировать"><i class="fa fa-pencil-alt fa-lg"></i></a></td>
	</tr>
	<?php
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
			<td></td>
			<td></td>
		</tr>
	</tbody>
</table>

<div id="fuel_report_btn" title="Распечатать отчет за прошлый месяц"><a href="/printforms/fuel_accounting_report.php" class="print" style="color: white;"><i class="fas fa-2x fa-print"></i></a></div>
<div id="fuel_arrival_btn" class="add_arrival" title="Приобретение дизтоплива"><i class="fas fa-2x fa-plus"></i></div>
<div id="fuel_filling_btn" class="add_filling" LFMV="<?=$last_fuel_meter_value?>" title="Заправка техники дизтопливом"><i class="fas fa-2x fa-gas-pump"></i></div>

<script>
	$(function() {
		$(".print").printPage();
	});
</script>

<?php
include "footer.php";
?>
