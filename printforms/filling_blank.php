<?
include "../config.php";
?>

<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<script src="../js/jquery-1.11.3.min.js"></script>
	<script src="https://kit.fontawesome.com/020f21ae61.js" crossorigin="anonymous"></script>

<?
$PB_ID = $_GET["PB_ID"];

$query = "
	SELECT PB.F_ID
		,PB.year
		,PB.cycle
		,PB.CW_ID
		,PB.batches
		,CW.item
		,MF.fillings
		,MF.per_batch
		,MF.cubetests
		,CONCAT(ROUND(CW.min_density/1000, 2), '&ndash;', ROUND(CW.max_density/1000, 2)) spec
	FROM plan__Batch PB
	JOIN CounterWeight CW ON CW.CW_ID = PB.CW_ID
	JOIN MixFormula MF ON MF.CW_ID = CW.CW_ID AND MF.F_ID = PB.F_ID
	WHERE PB_ID = {$PB_ID}
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
$row = mysqli_fetch_array($res);

$F_ID = $row["F_ID"];
$batches = $row["batches"];
$item = $row["item"];
$year = $row["year"];
$cycle = $row["cycle"];
$fillings = $row["fillings"];
$per_batch = $row["per_batch"];
$cubetests = $row["cubetests"];
$CW_ID = $row["CW_ID"];
$spec = $row["spec"];

// Массив с номерами контрольных замесов (кубы)
if( $batches > 1 ) {
	if( $cubetests == 1 ) {
		$tests = array(round($batches/2));
	}
	elseif( $cubetests == 2 ) {
		$tests = array(2, round($batches/2));
	}
	elseif( $cubetests == 3 ) {
		$tests = array(2, round($batches/2), $batches);
	}
}
else {
	$tests = array(1);
}

echo "<title>Чеклист оператора для {$item} цикл {$year}/{$cycle}</title>";
?>
	<style>
		@media print {
			@page {
				size: landscape;
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
		.cassette {
			font-weight: bold;
			border: 1px solid #333;
			border-radius: 5px;
			margin: 0 2px;
			padding: 2px;
			display: inline-block;
			width: 28px;
		}
	</style>

	<script>
		function fontSize(elem, maxFontSize) {
			var fontSize = $(elem).attr('fontSize');
			var width = $(elem).width();
			var bodyWidth = $(elem).parent().width();
			var multiplier = bodyWidth / width;
			fontSize = Math.floor(fontSize * multiplier);
			if( fontSize > maxFontSize ) fontSize = maxFontSize;
			$(elem).css({fontSize: fontSize+'px'});
			$(elem).attr('fontSize', fontSize);
		}
	</script>
</head>
<body>

<!--
	<b style="float: right; width: 55%;"><span style="font-size: 1.5em;">ВНИМАНИЕ!</span> Время замеса не должно выходить за границы текущего года.</b>
<h3>Фактическая дата первого замеса: ________________</h3>
-->

<table>
	<thead>
		<tr>
			<th><img src="/img/logo.png" alt="KONSTANTA" style="width: 200px; margin: 5px;"></th>
			<th width="250" style="position: relative;"><span style="position: absolute; top: 0px; left: 5px;" class="nowrap">деталь</span><n id="item" style="font-size: 3em;" fontSize="40" class="nowrap"><?=$item?></n></th>
			<th width="175" style="position: relative;"><span style="position: absolute; top: 0px; left: 5px;">цикл</span><n style="font-size: 3em;"><?=$year?>-<?=$cycle?></n></th>
			<th width="200" style="position: relative;">
				<img src="../barcode.php?code=<?=$PB_ID?>&w=200&h=60" alt="barcode">
				<span style="position: absolute; background: white; left: calc(50% - 40px); top: 48px; width: 80px;"><?=str_pad($PB_ID, 8, "0", STR_PAD_LEFT)?></span>
			</th>
		</tr>
<?
//	// Формируем список вероятных номеров кассет
//	$query = "
//		SELECT LF.cassette
//		FROM list__Batch LB
//		JOIN list__Filling LF ON LF.LB_ID = LB.LB_ID
//		WHERE LB.PB_ID = (
//			SELECT PB_ID
//			FROM plan__Batch
//			WHERE CW_ID = {$CW_ID}
//				AND F_ID = {$F_ID}
//				AND fact_batches > 0
//				AND PB_ID < {$PB_ID}
//			ORDER BY PB_ID DESC
//			LIMIT 1
//		)
//		ORDER BY LF.cassette
//	";
//	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
//	while( $row = mysqli_fetch_array($res) ) {
//		$cassettes .= "<n class='cassette'>{$row["cassette"]}</n>";
//	}
	// Формируем список зарезервированных кассет
	$query = "
		SELECT cassette
		FROM Cassettes
		WHERE F_ID = {$F_ID}
			AND CW_ID = {$CW_ID}
		ORDER BY cassette
	";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $row = mysqli_fetch_array($res) ) {
		$cassettes .= "<n class='cassette'>{$row["cassette"]}</n>";
	}
?>
		<tr>
			<th colspan="4" style="position: relative; padding-top: 12px;">
				<span style="position: absolute; top: 0px; left: 5px;" class="nowrap">Зарезервированные кассеты:</span>
				<?=$cassettes?>
			</th>
		</tr>
	</thead>
</table>

<?
// Данные рецепта
$query = "
	SELECT IFNULL(CONCAT(MF.s_fraction, ' кг'), 0) s_fraction
		,IFNULL(CONCAT(MF.l_fraction, ' кг'), 0) l_fraction
		,IFNULL(CONCAT(MF.iron_oxide, ' кг'), 0) iron_oxide
		,IFNULL(CONCAT(MF.slag10, ' кг'), 0) slag10
		,IFNULL(CONCAT(MF.slag20, ' кг'), 0) slag20
		,IFNULL(CONCAT(MF.slag020, ' кг'), 0) slag020
		,IFNULL(CONCAT(MF.slag30, ' кг'), 0) slag30
		,IFNULL(CONCAT(MF.sand, ' кг'), 0) sand
		,IFNULL(CONCAT(MF.crushed_stone, ' кг'), 0) crushed_stone
		,IFNULL(CONCAT(MF.cement, ' кг'), 0) cement
		,IFNULL(CONCAT(MF.plasticizer, ' кг'), 0) plasticizer
		,IFNULL(CONCAT('min ', MF.water, ' л'), 0) water
		,COUNT(MF.s_fraction) sf_cnt
		,COUNT(MF.l_fraction) lf_cnt
		,COUNT(MF.iron_oxide) io_cnt
		,COUNT(MF.slag10) sl10_cnt
		,COUNT(MF.slag20) sl20_cnt
		,COUNT(MF.slag020) sl020_cnt
		,COUNT(MF.slag30) sl30_cnt
		,COUNT(MF.sand) sn_cnt
		,COUNT(MF.crushed_stone) cs_cnt
		,COUNT(MF.cement) cm_cnt
		,COUNT(MF.plasticizer) pl_cnt
		,COUNT(MF.water) wt_cnt
	FROM MixFormula MF
	WHERE MF.F_ID = {$F_ID}
		AND MF.CW_ID = {$CW_ID}
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
$row = mysqli_fetch_array($res);
?>

<table>
	<thead style="word-wrap: break-word;">
		<tr>
			<th rowspan="3" width="30">№<br>п/п</th>
			<th rowspan="2"><sup style="float: left;">Дата замеса:</sup><br>__________</th>
			<th  rowspan="2">Масса куба раствора</th>
			<th rowspan="3" width="30" style="border-right: 4px solid;">t, ℃ 22±8</th>
			<?=($row["sf_cnt"] ? "<th>Мелкая дробь</th>" : "")?>
			<?=($row["lf_cnt"] ? "<th>Крупная дробь</th>" : "")?>
			<?=($row["io_cnt"] ? "<th>Окалина</th>" : "")?>
			<?=($row["sl10_cnt"] ? "<th>Шлак 0-10</th>" : "")?>
			<?=($row["sl20_cnt"] ? "<th>Шлак 10-20</th>" : "")?>
			<?=($row["sl020_cnt"] ? "<th>Шлак 0-20</th>" : "")?>
			<?=($row["sl30_cnt"] ? "<th>Шлак 5-30</th>" : "")?>
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
			<?=($row["sf_cnt"] ? "<th style='text-align: left; border: dashed;'><sup>куб:</sup></th>" : "")?>
			<?=($row["lf_cnt"] ? "<th style='text-align: left; border: dashed;'><sup>куб:</sup></th>" : "")?>
			<?=($row["io_cnt"] ? "<th style='text-align: left; border: dashed;'><sup>куб:</sup></th>" : "")?>
			<?=($row["sl10_cnt"] ? "<th style='text-align: left; border: dashed;'><sup>куб:</sup></th>" : "")?>
			<?=($row["sl20_cnt"] ? "<th style='text-align: left; border: dashed;'><sup>куб:</sup></th>" : "")?>
			<?=($row["sl020_cnt"] ? "<th style='text-align: left; border: dashed;'><sup>куб:</sup></th>" : "")?>
			<?=($row["sl30_cnt"] ? "<th style='text-align: left; border: dashed;'><sup>куб:</sup></th>" : "")?>
			<?=($row["sn_cnt"] ? "<th style='text-align: left; border: dashed;'><sup>куб:</sup></th>" : "")?>
			<?=($row["cs_cnt"] ? "<th style='text-align: left; border: dashed;'><sup>куб:</sup></th>" : "")?>
		</tr>
		<tr>
			<th>Время<br>замеса</th>
			<th class="nowrap"><?=$spec?> кг</th>
			<?=($row["sf_cnt"] ? "<th class='nowrap'>{$row["s_fraction"]}</th>" : "")?>
			<?=($row["lf_cnt"] ? "<th class='nowrap'>{$row["l_fraction"]}</th>" : "")?>
			<?=($row["io_cnt"] ? "<th class='nowrap'>{$row["iron_oxide"]}</th>" : "")?>
			<?=($row["sl10_cnt"] ? "<th class='nowrap'>{$row["slag10"]}</th>" : "")?>
			<?=($row["sl20_cnt"] ? "<th class='nowrap'>{$row["slag20"]}</th>" : "")?>
			<?=($row["sl020_cnt"] ? "<th class='nowrap'>{$row["slag020"]}</th>" : "")?>
			<?=($row["sl30_cnt"] ? "<th class='nowrap'>{$row["slag30"]}</th>" : "")?>
			<?=($row["sn_cnt"] ? "<th class='nowrap'>{$row["sand"]}</th>" : "")?>
			<?=($row["cs_cnt"] ? "<th class='nowrap'>{$row["crushed_stone"]}</th>" : "")?>
			<?=($row["cm_cnt"] ? "<th class='nowrap'>{$row["cement"]}</th>" : "")?>
			<?=($row["pl_cnt"] ? "<th class='nowrap'>{$row["plasticizer"]}</th>" : "")?>
			<?=($row["wt_cnt"] ? "<th class='nowrap'>{$row["water"]}</th>" : "")?>
		</tr>
	</thead>
	<tbody>
<?
$fillings_cell = "<td rowspan='{$per_batch}' style='border-left: 4px solid;'></td>";
for ($i = 1; $i <= $fillings; $i++) {
	$fillings_cell .= "<td rowspan='{$per_batch}'></td>";
}

$j = 0;

for ($i = 1; $i <= $batches; $i++) {
	echo "
		<tr>
			<td style='text-align: center;'>{$i}</td>
			<td style='text-align: center;'>__:__</td>
			<td></td>
			<td style='border-right: 4px solid;'></td>
			".($row["sf_cnt"] ? "<td></td>" : "")."
			".($row["lf_cnt"] ? "<td></td>" : "")."
			".($row["io_cnt"] ? "<td></td>" : "")."
			".($row["sl10_cnt"] ? "<td></td>" : "")."
			".($row["sl20_cnt"] ? "<td></td>" : "")."
			".($row["sl020_cnt"] ? "<td></td>" : "")."
			".($row["sl30_cnt"] ? "<td></td>" : "")."
			".($row["sn_cnt"] ? "<td></td>" : "")."
			".($row["cs_cnt"] ? "<td></td>" : "")."
			".($row["cm_cnt"] ? "<td></td>" : "")."
			".($row["pl_cnt"] ? "<td></td>" : "")."
			".($row["wt_cnt"] ? "<td></td>" : "")."
			".($j == 0 ? $fillings_cell : "")."
			<td style='text-align: center;'>".(in_array($i, $tests) ? "<b style='font-size: 1.4em;'>&#10065;</b>" : "&#10065;")."</td>
		</tr>
	";
	$j++;
	$j = ($j == $per_batch ? 0 : $j);
}
?>
	</tbody>
</table>

<script>
	fontSize('#item', 45);
</script>

</body>
</html>
