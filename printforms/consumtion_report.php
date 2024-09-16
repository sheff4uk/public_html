<?php
include "../config.php";
?>

<!DOCTYPE html>
<html lang="ru">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">

<?php
$date_from = date("d.m.Y", strtotime($_GET["date_from"]));
$date_to = date("d.m.Y", strtotime($_GET["date_to"]));

$date = date_create();
$date_format = date_format($date, 'd.m.Y');

// Название участка и должностные лица
$query = "
	SELECT f_name
		,job_title_1
		,full_name_1
		,job_title_2
		,full_name_2
	FROM factory
	WHERE F_ID = {$_GET["F_ID"]}
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
$row = mysqli_fetch_array($res);
$f_name = $row["f_name"];
$job_title_1 = $row["job_title_1"];
$full_name_1 = $row["full_name_1"];
$job_title_2 = $row["job_title_2"];
$full_name_2 = $row["full_name_2"];

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
<?php
	$query = "
		SELECT MN.MN_ID
			,MN.material_name
		FROM plan__Batch PB
		JOIN list__Batch LB ON LB.PB_ID = PB.PB_ID
		JOIN list__BatchMaterial LBM ON LBM.LB_ID = LB.LB_ID
		JOIN material__Name MN ON MN.MN_ID = LBM.MN_ID
		WHERE LBM.quantity
			AND PB.F_ID = {$_GET["F_ID"]}
			".($_GET["date_from"] ? "AND LB.batch_date >= '{$_GET["date_from"]}'" : "")."
			".($_GET["date_to"] ? "AND LB.batch_date <= '{$_GET["date_to"]}'" : "")."
		GROUP BY MN.material_name
		ORDER BY MN.material_name
	";
	$MN_IDs = "0";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $row = mysqli_fetch_array($res) ) {
		echo "<th colspan='2'>{$row["material_name"]}</th>";
		$MN_IDs .= ",".$row["MN_ID"];
	}
?>
			<th colspan="2" class="calcium">Кальций</th>
			<th colspan="2" class="reinforcement">Арматура</th>
		</tr>
		<tr>
<?php
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $row = mysqli_fetch_array($res) ) {
		echo "<th>Расход, кг</th>";
		echo "<th>На деталь, г</th>";
	}
?>
			<th class="calcium">Расход, кг</th>
			<th class="calcium">На деталь, г</th>
			<th class="reinforcement">Расход, кг</th>
			<th class="reinforcement">На деталь, г</th>
		</tr>
	</thead>
	<tbody style="text-align: center;" class="nowrap">
		<?php
		$query = "
			SELECT CW.CW_ID
				,CW.item
				,CW.drawing_item
				,SUM((SELECT SUM(PB.in_cassette - underfilling) FROM list__Filling WHERE LB_ID = LB.LB_ID)) details
				,LB.LB_ID
				,SUM(LB.water * PB.calcium / 1000) calcium
				,MAX(CW.reinforcement / 1000) reinforcement
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
			$calcium += $row["calcium"];
			$reinforcement += $row["reinforcement"] * $row["details"];
			?>
			<tr>
				<td colspan="3"><b><?=$row["drawing_item"]?></b><br><i style="font-size: .8em;"><?=$row["item"]?></i></td>
				<td><?=$row["details"]?></td>
			<?php
			$query = "
				SELECT SUM(LBM.quantity) * MN.adjustment quantity
				FROM material__Name MN
				LEFT JOIN list__BatchMaterial LBM ON LBM.MN_ID = MN.MN_ID
					AND LBM.LB_ID IN (
						SELECT LB_ID
						FROM list__Batch
						WHERE PB_ID IN (SELECT PB_ID FROM plan__Batch WHERE F_ID = {$_GET["F_ID"]} AND CW_ID = {$row["CW_ID"]})
							".($_GET["date_from"] ? "AND batch_date >= '{$_GET["date_from"]}'" : "")."
							".($_GET["date_to"] ? "AND batch_date <= '{$_GET["date_to"]}'" : "")."
					)
				WHERE MN.MN_ID IN ({$MN_IDs})
				GROUP BY MN.material_name
				ORDER BY MN.material_name
			";
			$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			$i = 0;
			while( $subrow = mysqli_fetch_array($subres) ) {
				echo "<td>".round($subrow["quantity"], 3)."</td>";
				echo "<td>".round($subrow["quantity"] * 1000/$row["details"], 3)."</td>";
				$quantity[$i] += $subrow["quantity"];
				$i++;
			}
			?>
				<td class="calcium"><?=round($row["calcium"], 3)?></td>
				<td class="calcium"><?=round($row["calcium"] * 1000/$row["details"], 3)?></td>
				<td class="reinforcement"><?=round($row["reinforcement"] * $row["details"], 3)?></td>
				<td class="reinforcement"><?=round($row["reinforcement"] * 1000, 3)?></td>
			</tr>
			<?php
		}
		?>

		<tr class="total">
			<td colspan="3">Итог:</td>
			<td><?=$details?></td>
		<?php
		foreach ($quantity as $subvalue) {
			echo "<td>".round($subvalue, 3)."</td>";
			echo "<td></td>";
		}
		?>
			<td class="calcium"><?=round($calcium, 3)?></td>
			<td class="calcium"></td>
			<td class="reinforcement"><?=round($reinforcement, 3)?></td>
			<td class="reinforcement"></td>
		</tr>
	</tbody>
</table>

<br>
<h3 style="float: left;"><?=$job_title_1?>: ____________________ / <?=$full_name_1?></h3>
<h3 style="float: right;"><?=$job_title_2?>: ____________________ / <?=$full_name_2?></h3>

<style>
	<?=($calcium ? "" : ".calcium{ display: none; }")?>
	<?=($reinforcement ? "" : ".reinforcement{ display: none; }")?>
</style>

</body>
</html>
