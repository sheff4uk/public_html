<?
include "../config.php";
?>

<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.3.1/css/all.css" integrity="sha384-mzrmE5qonljUremFsqc01SB46JvROS7bZs3IO2EmfFsd15uHvIt+Y8vEf7N7fWAU" crossorigin="anonymous">

<?
$PB_ID = $_GET["PB_ID"];

$query = "
	SELECT DATE_FORMAT(PB.pb_date, '%d.%m.%Y') pb_date_format
		,WEEKDAY(PB.pb_date) + 1 pb_date_weekday
		,RIGHT(YEARWEEK(PB.pb_date, 1), 2) week
		,CONCAT('[', DATE_FORMAT(ADDDATE(PB.pb_date, 0-WEEKDAY(PB.pb_date)), '%e %b'), ' - ', DATE_FORMAT(ADDDATE(PB.pb_date, 6-WEEKDAY(PB.pb_date)), '%e %b'), '] ', LEFT(YEARWEEK(PB.pb_date, 1), 4), ' г') week_range
		,PB.CW_ID
		,PB.batches
		,CW.item
		,CW.fillings
		,CW.cubetests
		,CONCAT(ROUND(CW.min_density/1000, 2), '&ndash;', ROUND(CW.max_density/1000, 2)) spec
	FROM plan__Batch PB
	JOIN CounterWeight CW ON CW.CW_ID = PB.CW_ID
	WHERE PB_ID = {$PB_ID}
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
$row = mysqli_fetch_array($res);

$batches = $row["batches"];
$item = $row["item"];
$pb_date_format = $row["pb_date_format"];
$pb_weekday = $row["pb_date_weekday"];
$week = $row["week"];
$week_range = $row["week_range"];
$fillings = $row["fillings"];
$cubetests = $row["cubetests"];
$CW_ID = $row["CW_ID"];
$spec = $row["spec"];

// Массив с номерами контрольных замесов (кубы)
if( $cubetests == 1 ) {
	$tests = array(round($batches/2));
}
elseif( $cubetests == 3 ) {
	$tests = array(1, round($batches/2), $batches);
}

echo "<title>Чеклист оператора для {$item} от {$pb_date}</title>";
?>
	<style type="text/css" media="print">
		@page { size: landscape; }
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
	</style>
</head>
<body>

<b style="float: right; width: 50%;">ВАЖНО! Время замеса в чек-листе не должен выходить за границы своей недели. Новая неделя всегда должна начинаться с 1-го цикла.</b>
<h3>Фактическая дата первого замеса: ________________</h3>

<table>
	<thead>
		<tr>
			<th><img src="/img/logo.png" alt="KONSTANTA" style="width: 200px; margin: 5px;"></th>
			<th style="font-size: 2em;"><?=$item?></th>
			<th><n style="font-size: 3em;"><?=$week?></n> неделя<br><?=$week_range?></th>
			<th width="40"><n style="font-size: 3em;"><?=$pb_weekday?></n><br>цикл</th>
			<th><img src="../barcode.php?code=<?=$PB_ID?>" alt="barcode"></th>
		</tr>
	</thead>
</table>

<?
// Данные рецепта
$query = "
	SELECT GROUP_CONCAT(CONCAT('<span style=\'font-size: 1.5em;\' class=\'nowrap\'>', MF.letter, '</span>') ORDER BY MF.letter SEPARATOR '<br>') ltr
		,GROUP_CONCAT(CONCAT(ROUND(MF.io_min/1000, 2), '&ndash;', ROUND(MF.io_max/1000, 2)) ORDER BY MF.letter SEPARATOR '<br>') io
		,GROUP_CONCAT(CONCAT(ROUND(MF.sn_min/1000, 2), '&ndash;', ROUND(MF.sn_max/1000, 2)) ORDER BY MF.letter SEPARATOR '<br>') sn
		,GROUP_CONCAT(IFNULL(CONCAT(MF.iron_oxide, ' ±5'), 0) ORDER BY MF.letter SEPARATOR '<br>') iron_oxide
		,GROUP_CONCAT(IFNULL(CONCAT(MF.sand, ' ±5'), 0) ORDER BY MF.letter SEPARATOR '<br>') sand
		,GROUP_CONCAT(IFNULL(CONCAT(MF.crushed_stone, ' ±5'), 0) ORDER BY MF.letter SEPARATOR '<br>') crushed_stone
		,GROUP_CONCAT(IFNULL(CONCAT(MF.cement, ' ±2'), 0) ORDER BY MF.letter SEPARATOR '<br>') cement
		,GROUP_CONCAT(IFNULL(CONCAT('min ', MF.water), 0) ORDER BY MF.letter SEPARATOR '<br>') water
		,COUNT(MF.iron_oxide) io_cnt
		,COUNT(MF.sand) sn_cnt
		,COUNT(MF.crushed_stone) cs_cnt
		,COUNT(MF.cement) cm_cnt
		,COUNT(MF.water) wt_cnt
	FROM MixFormula MF
	WHERE MF.CW_ID = {$CW_ID}
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
$row = mysqli_fetch_array($res);
?>

<table>
	<thead style="word-wrap: break-word;">
		<tr>
			<th rowspan="3" width="30">№<br>п/п</th>
			<th rowspan="3">Время замеса</th>
			<th rowspan="2" width="40" style="word-wrap: break-word;">Рецепт</th>
			<th colspan="<?=(1 + ($row["io"] ? 1 : 0) + ($row["sn"] ? 1 : 0))?>" style="border-right: 4px solid;">Масса куба, кг</th>
			<?=($row["io_cnt"] ? "<th rowspan='2'>Окалина, кг</th>" : "")?>
			<?=($row["sn_cnt"] ? "<th rowspan='2'>КМП, кг</th>" : "")?>
			<?=($row["cs_cnt"] ? "<th rowspan='2'>Отсев, кг</th>" : "")?>
			<?=($row["cm_cnt"] ? "<th rowspan='2'>Цемент, кг</th>" : "")?>
			<?=($row["wt_cnt"] ? "<th rowspan='2'>Вода, кг</th>" : "")?>
			<th rowspan="3" colspan="<?=$fillings?>" width="<?=($fillings * 60)?>" style="border-left: 4px solid;">№ кассеты</th>
			<th rowspan="3" width="40">Недолив</th>
			<th rowspan="3" width="20"><i class="fas fa-cube"></i></th>
			<th rowspan="3">Оператор</th>
		</tr>
		<tr>
			<?=(($row["io"] ? "<th>Окалины</th>" : ""))?>
			<?=(($row["sn"] ? "<th>КМП</th>" : ""))?>
			<th style="border-right: 4px solid;">Раствора</th>
		</tr>
		<tr>
			<th><?=$row["ltr"]?></th>
			<?=(($row["io"] ? "<th class='nowrap'>{$row["io"]}</th>" : ""))?>
			<?=(($row["sn"] ? "<th class='nowrap'>{$row["sn"]}</th>" : ""))?>
			<th class="nowrap" style="border-right: 4px solid;"><?=$spec?></th>
			<?=($row["io_cnt"] ? "<th class='nowrap'>{$row["iron_oxide"]}</th>" : "")?>
			<?=($row["sn_cnt"] ? "<th class='nowrap'>{$row["sand"]}</th>" : "")?>
			<?=($row["cs_cnt"] ? "<th class='nowrap'>{$row["crushed_stone"]}</th>" : "")?>
			<?=($row["cm_cnt"] ? "<th class='nowrap'>{$row["cement"]}</th>" : "")?>
			<?=($row["wt_cnt"] ? "<th class='nowrap'>{$row["water"]}</th>" : "")?>
		</tr>
	</thead>
	<tbody>
<?
$fillings_cell = "<td style='border-left: 4px solid;'></td>";
for ($i = 2; $i <= $fillings; $i++) {
	$fillings_cell .= "<td></td>";
}

for ($i = 1; $i <= $batches; $i++) {
	echo "
		<tr>
			<td style='text-align: center;'>{$i}</td>
			<td style='text-align: center;'>__:__</td>
			<td></td>
			".($row["io"] ? "<td></td>" : "")."
			".($row["sn"] ? "<td></td>" : "")."
			<td style='border-right: 4px solid;'></td>
			".($row["io_cnt"] ? "<td></td>" : "")."
			".($row["sn_cnt"] ? "<td></td>" : "")."
			".($row["cs_cnt"] ? "<td></td>" : "")."
			".($row["cm_cnt"] ? "<td></td>" : "")."
			".($row["wt_cnt"] ? "<td></td>" : "")."
			{$fillings_cell}
			<td></td>
			<td style='text-align: center;'>".(in_array($i, $tests) ? "<i class='far fa-square fa-lg'></i>" : "<i class='far fa-square' style='opacity: .5;'></i>")."</td>
			<td></td>
		</tr>
	";
}
?>
	</tbody>
</table>

</body>
</html>
