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
		,PB.CW_ID
		,PB.batches
		,CW.item
		,CW.fillings
		,CONCAT(ROUND(CW.min_density/1000, 2), '&ndash;', ROUND(CW.max_density/1000, 2)) spec
	FROM plan__Batch PB
	JOIN CounterWeight CW ON CW.CW_ID = PB.CW_ID
	WHERE PB_ID = {$PB_ID}
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
$row = mysqli_fetch_array($res);

$batches = $row["batches"];
$item = $row["item"];
$pb_date = $row["pb_date_format"];
$fillings = $row["fillings"];
$CW_ID = $row["CW_ID"];
$spec = $row["spec"];

echo "<title>Чеклист оператора для {$item} от {$pb_date}</title>";
?>
	<style type="text/css" media="print">
		@page { size: landscape; }
	</style>

	<style>
		body, td {
			font-family: Trebuchet MS, Tahoma, Verdana, Arial, sans-serif;
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

<table>
	<thead>
		<tr>
			<th><img src="/img/logo.png" alt="KONSTANTA" style="width: 200px; margin: 5px;"></th>
			<th style="font-size: 2em;"><?=$item?></th>
			<th style="font-size: 2em;"><?=$pb_date?></th>
		</tr>
	</thead>
</table>

<?
// Данные рецепта
$query = "
	SELECT GROUP_CONCAT(CONCAT('<span style=\'font-size: 1.5em;\'>', letter, '</span>') ORDER BY letter SEPARATOR '<br>') ltr
		,GROUP_CONCAT(CONCAT(ROUND(io_min/1000, 2), '&ndash;', ROUND(io_max/1000, 2)) ORDER BY letter SEPARATOR '<br>') io
		,GROUP_CONCAT(CONCAT(ROUND(sn_min/1000, 2), '&ndash;', ROUND(sn_max/1000, 2)) ORDER BY letter SEPARATOR '<br>') sn
		,GROUP_CONCAT(CONCAT(ROUND(cs_min/1000, 2), '&ndash;', ROUND(cs_max/1000, 2)) ORDER BY letter SEPARATOR '<br>') cs
		,GROUP_CONCAT(distinct CONCAT(iron_oxide, ' ±5') ORDER BY letter SEPARATOR '<br>') iron_oxide
		,GROUP_CONCAT(distinct CONCAT(sand, ' ±5') ORDER BY letter SEPARATOR '<br>') sand
		,GROUP_CONCAT(distinct CONCAT(crushed_stone, ' ±5') ORDER BY letter SEPARATOR '<br>') crushed_stone
		,GROUP_CONCAT(distinct CONCAT(cement, ' ±2') ORDER BY letter SEPARATOR '<br>') cement
		,GROUP_CONCAT(distinct CONCAT('min ', water) ORDER BY letter SEPARATOR '<br>') water
	FROM MixFormula
	WHERE CW_ID = {$CW_ID}
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
$row = mysqli_fetch_array($res);
?>

<table>
	<thead style="word-wrap: break-word;">
		<tr>
			<th rowspan="3" width="30">№<br>п/п</th>
			<th rowspan="3">Время замеса</th>
			<th rowspan="2" width="30" style="word-wrap: break-word;">Рецепт</th>
			<th colspan="<?=(1 + ($row["io"] ? 1 : 0) + ($row["sn"] ? 1 : 0) + ($row["cs"] ? 1 : 0))?>">Масса куба, кг</th>
			<?=($row["iron_oxide"] ? "<th rowspan='2'>Окалина, кг</th>" : "")?>
			<?=($row["sand"] ? "<th rowspan='2'>КМП, кг</th>" : "")?>
			<?=($row["crushed_stone"] ? "<th rowspan='2'>Отсев, кг</th>" : "")?>
			<?=($row["cement"] ? "<th rowspan='2'>Цемент, кг</th>" : "")?>
			<?=($row["water"] ? "<th rowspan='2'>Вода, кг</th>" : "")?>
			<th rowspan="3" colspan="<?=$fillings?>" width="<?=($fillings * 60)?>">№ кассеты<h2>Замес на <?=$fillings?> кассеты</h2></th>
			<th rowspan="3" width="40">Недолив</th>
			<th rowspan="3" width="20"><i class="fas fa-cube"></i></th>
			<th rowspan="3">Оператор</th>
		</tr>
		<tr>
			<?=(($row["io"] ? "<th>Окалины</th>" : ""))?>
			<?=(($row["sn"] ? "<th>КМП</th>" : ""))?>
			<?=(($row["cs"] ? "<th>Отсева</th>" : ""))?>
			<th>Раствора</th>
		</tr>
		<tr>
			<th><?=$row["ltr"]?></th>
			<?=(($row["io"] ? "<th class='nowrap'>{$row["io"]}</th>" : ""))?>
			<?=(($row["sn"] ? "<th class='nowrap'>{$row["sn"]}</th>" : ""))?>
			<?=(($row["cs"] ? "<th class='nowrap'>{$row["cs"]}</th>" : ""))?>
			<th class="nowrap"><?=$spec?></th>
			<?=($row["iron_oxide"] ? "<th class='nowrap'>{$row["iron_oxide"]}</th>" : "")?>
			<?=($row["sand"] ? "<th class='nowrap'>{$row["sand"]}</th>" : "")?>
			<?=($row["crushed_stone"] ? "<th class='nowrap'>{$row["crushed_stone"]}</th>" : "")?>
			<?=($row["cement"] ? "<th class='nowrap'>{$row["cement"]}</th>" : "")?>
			<?=($row["water"] ? "<th class='nowrap'>{$row["water"]}</th>" : "")?>
		</tr>
	</thead>
	<tbody>
<?
for ($i = 1; $i <= $fillings; $i++) {
	$fillings_cell .= "<td></td>";
}

for ($i = 1; $i <= $batches; $i++) {
	echo "
		<tr>
			<td style='text-align: center;'>{$i}</td>
			<td></td>
			<td></td>
			".($row["io"] ? "<td></td>" : "")."
			".($row["sn"] ? "<td></td>" : "")."
			".($row["cs"] ? "<td></td>" : "")."
			<td></td>
			".($row["iron_oxide"] ? "<td></td>" : "")."
			".($row["sand"] ? "<td></td>" : "")."
			".($row["crushed_stone"] ? "<td></td>" : "")."
			".($row["cement"] ? "<td></td>" : "")."
			".($row["water"] ? "<td></td>" : "")."
			{$fillings_cell}
			<td></td>
			<td style='text-align: center;'><i class='far fa-square'></i></td>
			<td></td>
		</tr>
	";
}
?>
	</tbody>
</table>

</body>
</html>
