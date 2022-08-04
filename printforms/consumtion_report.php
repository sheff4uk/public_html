<?
include "../config.php";
?>

<!DOCTYPE html>
<html lang="ru">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">

<?
$date_from = date("d.m.Y", strtotime($_GET["date_from"]));
$date_to = date("d.m.Y", strtotime($_GET["date_to"]));

$date = date_create();
$date_format = date_format($date, 'd.m.Y');

// Название участка
$query = "SELECT f_name FROM factory WHERE F_ID = {$_GET["F_ID"]}";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
$row = mysqli_fetch_array($res);
$f_name = $row["f_name"];

echo "<title>Расход сырья {$date_format}</title>";
?>
	<style type="text/css" media="print">
		@page { size: landscape; }
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

<br>
<br>
<br>
<br>
<br>
<br>
<br>
<table>
	<thead>
		<tr>
			<th rowspan="1"><img src="/img/logo.png" alt="KONSTANTA" style="width: 200px; margin: 5px;"></th>
			<th rowspan="1" style="font-size: 2em;">Расход сырья на производство продукции</th>
			<th style="position: relative;">
				<span style="position: absolute; top: 0px; left: 5px;" class="nowrap">участок</span>
				<n style="font-size: 2em;"><?=$f_name?></n></th>
			<th style="position: relative;">
				<span style="position: absolute; top: 0px; left: 5px;" class="nowrap">период</span>
				<n style="font-size: 2em;"><?=$date_from?> - <?=$date_to?></n>
			</th>
		</tr>
<!--
		<tr>
			<th>Дата документа: <n style="font-size: 1.5em;"><?=$date_format?></n></th>
		</tr>
-->
	</thead>
</table>

<table class="main_table">
	<thead>
		<tr>
			<th rowspan="2" colspan="3">Противовес</th>
			<th rowspan="2">Кол-во залитых деталей</th>
			<th colspan="2">Мелкая дробь</th>
			<th colspan="2">Крупная дробь</th>
			<th colspan="2">Окалина</th>
			<th colspan="2">Шлак 0-10</th>
			<th colspan="2">Шлак 10-20</th>
			<th colspan="2">Шлак 0-20</th>
			<th colspan="2">Шлак 5-30</th>
			<th colspan="2">КМП</th>
			<th colspan="2">Отсев</th>
			<th colspan="2">Цемент</th>
			<th colspan="2">Пластификатор</th>
			<th colspan="2">Кальций</th>
			<th colspan="2">Арматура</th>
		</tr>
		<tr>
			<th>Расход, кг</th>
			<th>На деталь, г</th>
			<th>Расход, кг</th>
			<th>На деталь, г</th>
			<th>Расход, кг</th>
			<th>На деталь, г</th>
			<th>Расход, кг</th>
			<th>На деталь, г</th>
			<th>Расход, кг</th>
			<th>На деталь, г</th>
			<th>Расход, кг</th>
			<th>На деталь, г</th>
			<th>Расход, кг</th>
			<th>На деталь, г</th>
			<th>Расход, кг</th>
			<th>На деталь, г</th>
			<th>Расход, кг</th>
			<th>На деталь, г</th>
			<th>Расход, кг</th>
			<th>На деталь, г</th>
			<th>Расход, г</th>
			<th>На деталь, мг</th>
			<th>Расход, г</th>
			<th>На деталь, мг</th>
			<th>Расход, кг</th>
			<th>На деталь, г</th>
		</tr>
	</thead>
	<tbody style="text-align: center;" class="nowrap">
		<?
		$query = "
			SELECT CW.drawing_item
				,CW.item
				,SUM((SELECT SUM(PB.in_cassette - underfilling) FROM list__Filling WHERE LB_ID = LB.LB_ID)) details
				,SUM(LB.s_fraction) s_fraction
				,SUM(LB.l_fraction) l_fraction
				,SUM(LB.iron_oxide) iron_oxide
				,SUM(LB.slag10) slag10
				,SUM(LB.slag20) slag20
				,SUM(LB.slag020) slag020
				,SUM(LB.slag30) slag30
				,SUM(LB.sand) sand
				,SUM(LB.crushed_stone) crushed_stone
				,SUM(LB.cement) cement
				,SUM(LB.plasticizer) * 1000 / 10 plasticizer
				,SUM(LB.water * PB.calcium) calcium
				,CW.reinforcement
				,IF(PB.F_ID = 1, 1, 0) krv
			FROM plan__Batch PB
			JOIN CounterWeight CW ON CW.CW_ID = PB.CW_ID
			JOIN list__Batch LB ON LB.PB_ID = PB.PB_ID
			WHERE PB.F_ID = {$_GET["F_ID"]}
				".($_GET["date_from"] ? "AND LB.batch_date >= '{$_GET["date_from"]}'" : "")."
				".($_GET["date_to"] ? "AND LB.batch_date <= '{$_GET["date_to"]}'" : "")."
			GROUP BY PB.CW_ID
			ORDER BY PB.CW_ID
		";
		$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		while( $row = mysqli_fetch_array($res) ) {
			$details += $row["details"];
			$s_fraction += $row["s_fraction"];
			$l_fraction += $row["l_fraction"];
			$iron_oxide += $row["iron_oxide"];
			$slag10 += $row["slag10"];
			$slag20 += $row["slag20"];
			$slag020 += $row["slag020"];
			$slag30 += $row["slag30"];
			$sand += $row["sand"] ? round($row["sand"] + (0.05 * $row["krv"]) * $row["details"]) : 0;
			$crushed_stone += $row["crushed_stone"];
			$cement += $row["cement"] ? round($row["cement"] + (0.1 * $row["krv"]) * $row["details"]) : 0;
			$plasticizer += $row["plasticizer"];
			$calcium += $row["calcium"];
			$reinforcement += $row["reinforcement"] * $row["details"];
			?>
			<tr>
				<td colspan="3"><b><?=$row["drawing_item"]?></b><br><i style="font-size: .8em;"><?=$row["item"]?></i></td>
				<td><?=number_format($row["details"], 0, '', ' ')?></td>
				<td><?=number_format($row["s_fraction"], 0, ',', ' ')?></td>
				<td><?=number_format($row["s_fraction"] * 1000/$row["details"], 0, ',', ' ')?></td>
				<td><?=number_format($row["l_fraction"], 0, ',', ' ')?></td>
				<td><?=number_format($row["l_fraction"] * 1000/$row["details"], 0, ',', ' ')?></td>
				<td><?=number_format($row["iron_oxide"], 0, ',', ' ')?></td>
				<td><?=number_format($row["iron_oxide"] * 1000/$row["details"], 0, ',', ' ')?></td>
				<td><?=number_format($row["slag10"], 0, ',', ' ')?></td>
				<td><?=number_format($row["slag10"] * 1000/$row["details"], 0, ',', ' ')?></td>
				<td><?=number_format($row["slag20"], 0, ',', ' ')?></td>
				<td><?=number_format($row["slag20"] * 1000/$row["details"], 0, ',', ' ')?></td>
				<td><?=number_format($row["slag020"], 0, ',', ' ')?></td>
				<td><?=number_format($row["slag020"] * 1000/$row["details"], 0, ',', ' ')?></td>
				<td><?=number_format($row["slag30"], 0, ',', ' ')?></td>
				<td><?=number_format($row["slag30"] * 1000/$row["details"], 0, ',', ' ')?></td>
				<td><?=number_format($row["sand"] ? round($row["sand"] + (0.05 * $row["krv"]) * $row["details"]) : 0, 0, ',', ' ')?></td>
				<td><?=number_format($row["sand"] ? ($row["sand"] * 1000/$row["details"] + (50 * $row["krv"])) : 0, 0, ',', ' ')?></td>
				<td><?=number_format($row["crushed_stone"], 0, ',', ' ')?></td>
				<td><?=number_format($row["crushed_stone"] * 1000/$row["details"], 0, ',', ' ')?></td>
				<td><?=number_format($row["cement"] ? round($row["cement"] + (0.1 * $row["krv"]) * $row["details"]) : 0, 0, ',', ' ')?></td>
				<td><?=number_format($row["cement"] ? ($row["cement"] * 1000/$row["details"] + (100 * $row["krv"])) : 0, 0, ',', ' ')?></td>
				<td><?=number_format($row["plasticizer"], 0, ',', ' ')?></td>
				<td><?=number_format($row["plasticizer"] * 1000/$row["details"], 0, ',', ' ')?></td>
				<td><?=number_format($row["calcium"], 0, ',', ' ')?></td>
				<td><?=number_format($row["calcium"] * 1000/$row["details"], 0, ',', ' ')?></td>
				<td><?=number_format($row["reinforcement"] * $row["details"] / 1000, 3, ',', ' ')?></td>
				<td><?=number_format($row["reinforcement"], 0, ',', ' ')?></td>
			</tr>
			<?
		}
		?>

		<tr class="total">
			<td colspan="3">Итог:</td>
			<td><?=number_format($details, 0, '', ' ')?></td>
			<td><?=number_format($s_fraction, 0, ',', ' ')?></td>
			<td></td>
			<td><?=number_format($l_fraction, 0, ',', ' ')?></td>
			<td></td>
			<td><?=number_format($iron_oxide, 0, ',', ' ')?></td>
			<td></td>
			<td><?=number_format($slag10, 0, ',', ' ')?></td>
			<td></td>
			<td><?=number_format($slag20, 0, ',', ' ')?></td>
			<td></td>
			<td><?=number_format($slag020, 0, ',', ' ')?></td>
			<td></td>
			<td><?=number_format($slag30, 0, ',', ' ')?></td>
			<td></td>
			<td><?=number_format($sand, 0, ',', ' ')?></td>
			<td></td>
			<td><?=number_format($crushed_stone, 0, ',', ' ')?></td>
			<td></td>
			<td><?=number_format($cement, 0, ',', ' ')?></td>
			<td></td>
			<td><?=number_format($plasticizer, 0, ',', ' ')?></td>
			<td></td>
			<td><?=number_format($calcium, 0, ',', ' ')?></td>
			<td></td>
			<td><?=number_format($reinforcement/1000, 3, ',', ' ')?></td>
			<td></td>
		</tr>
	</tbody>
</table>

<br>
<h3>Начальник производства: ____________________________/____________</h3>
<br>
<h3>Начальник отдела качества: ____________________________/____________</h3>

</body>
</html>
