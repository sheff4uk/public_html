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
	SELECT PB.year
		,PB.cycle
		,PB.CW_ID
		,PB.batches
		,SUBSTRING(CW.item, -3, 3) item
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
$year = $row["year"];
$cycle = $row["cycle"];
$fillings = $row["fillings"];
$cubetests = $row["cubetests"];
$CW_ID = $row["CW_ID"];
$spec = $row["spec"];

// Массив с номерами контрольных замесов (кубы)
if( $cubetests == 1 ) {
	$tests = array(round($batches/2)+1);
}
elseif( $cubetests == 2 ) {
	$tests = array(2, round($batches/2)+1);
}
elseif( $cubetests == 3 ) {
	$tests = array(2, round($batches/2)+1, $batches);
}

echo "<title>Чеклист оператора для {$item} цикл {$year}/{$cycle}</title>";
?>
	<style>
		@media print {
			@page {
				size: portrait;
/*				padding: 0;*/
/*				margin: 0;*/
			}
		}

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

	<b style="float: right; width: 55%;"><span style="font-size: 1.5em;">ВНИМАНИЕ!</span> Время замеса не должно выходить за границы текущего года.</b>
<h3>Фактическая дата первого замеса: ________________</h3>

<table>
	<thead>
		<tr>
			<th><img src="/img/logo.png" alt="KONSTANTA" style="width: 200px; margin: 5px;"></th>
			<th width="75" style="position: relative;"><span style="position: absolute; top: 0px; left: 5px;">код</span><n style="font-size: 3em;"><?=$item?></n></th>
			<th width="100" style="position: relative;"><span style="position: absolute; top: 0px; left: 5px;">год</span><n style="font-size: 3em;"><?=$year?></n></th>
			<th width="75" style="position: relative;"><span style="position: absolute; top: 0px; left: 5px;">цикл</span><n style="font-size: 3em;"><?=$cycle?></n></th>
			<th width="200" style="position: relative;">
				<img src="../barcode.php?code=<?=$PB_ID?>&w=200&h=60" alt="barcode">
				<span style="position: absolute; background: white; left: calc(50% - 40px); top: 48px; width: 80px;"><?=str_pad($PB_ID, 8, "0", STR_PAD_LEFT)?></span>
			</th>
		</tr>
	</thead>
</table>

<?
// Формируем список вероятных номеров кассет
$query = "
	SELECT LF.cassette
	FROM list__Batch LB
	JOIN list__Filling LF ON LF.LB_ID = LB.LB_ID
	WHERE LB.PB_ID = (SELECT PB_ID FROM plan__Batch WHERE CW_ID = {$CW_ID} AND fact_batches > 0 AND PB_ID < {$PB_ID} ORDER BY PB_ID DESC LIMIT 1)
	ORDER BY LF.cassette
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	$cassettes .= "<b style='border: 2px solid #333; border-radius: 5px; margin: 0 2px; display: inline-block;'>{$row["cassette"]}</b>";
}
echo "<div style='border: 1px solid; padding: 10px;'><span>Вероятные номера кассет:</span> {$cassettes}<br><b>Пожалуйста указывайте номера кассет разборчиво.</b></div>";

// Данные рецепта
$query = "
	SELECT IFNULL(CONCAT(MF.iron_oxide, ' ±5 кг'), 0) iron_oxide
		,IFNULL(CONCAT(MF.sand, ' ±5 кг'), 0) sand
		,IFNULL(CONCAT(MF.crushed_stone, ' ±5 кг'), 0) crushed_stone
		,IFNULL(CONCAT(MF.cement, ' ±2 кг'), 0) cement
		,IFNULL(CONCAT(MF.plasticizer, ' ±0.1 кг'), 0) plasticizer
		,IFNULL(CONCAT('min ', MF.water, ' л'), 0) water
		,COUNT(MF.iron_oxide) io_cnt
		,COUNT(MF.sand) sn_cnt
		,COUNT(MF.crushed_stone) cs_cnt
		,COUNT(MF.cement) cm_cnt
		,COUNT(MF.plasticizer) pl_cnt
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
			<th  rowspan="2">Масса куба раствора</th>
			<th rowspan="3" width="30" style="border-right: 4px solid;">t, ℃ 22±8</th>
			<?=($row["io_cnt"] ? "<th>Окалина</th>" : "")?>
			<?=($row["sn_cnt"] ? "<th>КМП</th>" : "")?>
			<?=($row["cs_cnt"] ? "<th>Отсев</th>" : "")?>
			<?=($row["cm_cnt"] ? "<th rowspan='2'>Цемент</th>" : "")?>
			<?=($row["pl_cnt"] ? "<th rowspan='2'>Пластификатор</th>" : "")?>
			<?=($row["wt_cnt"] ? "<th rowspan='2'>Вода</th>" : "")?>
			<th rowspan="3" colspan="<?=$fillings?>" width="<?=($fillings * 50)?>" style="border-left: 4px solid;">№ кассеты</th>
			<th rowspan="3" width="40">Недолив</th>
			<th rowspan="3" width="20"><i class="fas fa-cube"></i></th>
		</tr>
		<tr>
			<?=($row["io_cnt"] ? "<th style='text-align: left; border: dashed;'><sup>куб:</sup></th>" : "")?>
			<?=($row["sn_cnt"] ? "<th style='text-align: left; border: dashed;'><sup>куб:</sup></th>" : "")?>
			<?=($row["cs_cnt"] ? "<th style='text-align: left; border: dashed;'><sup>куб:</sup></th>" : "")?>
		</tr>
		<tr>
			<th class="nowrap"><?=$spec?> кг</th>
			<?=($row["io_cnt"] ? "<th class='nowrap'>{$row["iron_oxide"]}</th>" : "")?>
			<?=($row["sn_cnt"] ? "<th class='nowrap'>{$row["sand"]}</th>" : "")?>
			<?=($row["cs_cnt"] ? "<th class='nowrap'>{$row["crushed_stone"]}</th>" : "")?>
			<?=($row["cm_cnt"] ? "<th class='nowrap'>{$row["cement"]}</th>" : "")?>
			<?=($row["pl_cnt"] ? "<th class='nowrap'>{$row["plasticizer"]}</th>" : "")?>
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
			<td style='border-right: 4px solid;'></td>
			".($row["io_cnt"] ? "<td></td>" : "")."
			".($row["sn_cnt"] ? "<td></td>" : "")."
			".($row["cs_cnt"] ? "<td></td>" : "")."
			".($row["cm_cnt"] ? "<td></td>" : "")."
			".($row["pl_cnt"] ? "<td></td>" : "")."
			".($row["wt_cnt"] ? "<td></td>" : "")."
			{$fillings_cell}
			<td></td>
			<td style='text-align: center;'>".(in_array($i, $tests) ? "<b style='font-size: 1.4em;'>&#10065;</b>" : "&#10065;")."</td>
		</tr>
	";
}
?>
	</tbody>
</table>

</body>
</html>
