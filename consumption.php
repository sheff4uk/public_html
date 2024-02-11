<?
include "config.php";
$title = 'Расход сырья';
include "header.php";

// Проверка прав на доступ к экрану
if( !in_array('consumption', $Rights) ) {
	header($_SERVER['SERVER_PROTOCOL'].' 403 Forbidden');
	die('Недостаточно прав для совершения операции');
}

// Если не выбран участок, берем из сессии
if( !$_GET["F_ID"] ) {
	$_GET["F_ID"] = $_SESSION['F_ID'];
}

// Если в фильтре не установлен период, показываем последние 7 дней
if( !$_GET["date_from"] ) {
	$date = date_create('-6 days');
	$_GET["date_from"] = date_format($date, 'Y-m-d');
}
if( !$_GET["date_to"] ) {
	$date = date_create('-0 days');
	$_GET["date_to"] = date_format($date, 'Y-m-d');
}
?>

<style>
	#consumption_report_btn {
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
	#consumption_report_btn:hover {
		opacity: 1;
	}
</style>

<!--Фильтр-->
<div id="filter">
	<h3>Фильтр</h3>
	<form method="get" style="position: relative;">
		<a href="/consumption.php" style="position: absolute; top: 10px; right: 10px;" class="button">Сброс</a>

		<div class="nowrap" style="margin-bottom: 10px;">
			<span>Участок:</span>
			<select name="F_ID" class="<?=$_GET["F_ID"] ? "filtered" : ""?>" onchange="this.form.submit()">
				<?
				$query = "
					SELECT F_ID
						,f_name
					FROM factory
					ORDER BY F_ID
				";
				$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
				while( $row = mysqli_fetch_array($res) ) {
					$selected = ($row["F_ID"] == $_GET["F_ID"]) ? "selected" : "";
					echo "<option value='{$row["F_ID"]}' {$selected}>{$row["f_name"]}</option>";
				}
				?>
			</select>
		</div>

		<div class="nowrap" style="display: inline-block; margin-bottom: 10px;">
			<span style="display: inline-block; width: 200px;">Дата заливки между:</span>
			<input name="date_from" type="date" value="<?=$_GET["date_from"]?>" class="<?=$_GET["date_from"] ? "filtered" : ""?>">
			<input name="date_to" type="date" value="<?=$_GET["date_to"]?>" class="<?=$_GET["date_to"] ? "filtered" : ""?>">
			<i class="fas fa-question-circle" title="По умолчанию устанавливаются последние 7 дней."></i>
		</div>

<!--
		<div class="nowrap" style="display: inline-block; margin-bottom: 10px; margin-right: 30px;">
			<span>Код противовеса:</span>
			<select name="CW_ID" class="<?=$_GET["CW_ID"] ? "filtered" : ""?>" style="width: 100px;">
				<option value=""></option>
				<?
				$query = "
					SELECT CW.CW_ID, CW.item, CW.drawing_item
					FROM CounterWeight CW
					ORDER BY CW.CW_ID
				";
				$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
				while( $row = mysqli_fetch_array($res) ) {
					$selected = ($row["CW_ID"] == $_GET["CW_ID"]) ? "selected" : "";
					echo "<option value='{$row["CW_ID"]}' {$selected}>{$row["item"]} ({$row["drawing_item"]})</option>";
				}
				?>
			</select>
		</div>

		<div class="nowrap" style="display: inline-block; margin-bottom: 10px; margin-right: 30px;">
			<span>Бренд:</span>
			<select name="CB_ID" class="<?=$_GET["CB_ID"] ? "filtered" : ""?>" style="width: 100px;">
				<option value=""></option>
				<?
				$query = "
					SELECT CB.CB_ID, CB.brand
					FROM ClientBrand CB
					ORDER BY CB.CB_ID
				";
				$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
				while( $row = mysqli_fetch_array($res) ) {
					$selected = ($row["CB_ID"] == $_GET["CB_ID"]) ? "selected" : "";
					echo "<option value='{$row["CB_ID"]}' {$selected}>{$row["brand"]}</option>";
				}
				?>
			</select>
		</div>
-->

		<button style="float: right;">Фильтр</button>
	</form>
</div>

<?
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
			<th rowspan="2" colspan = "2">Противовес</th>
			<th rowspan="2">Кол-во залитых деталей</th>
<?
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
<?
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
		<?
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
				".($_GET["CW_ID"] ? "AND PB.CW_ID={$_GET["CW_ID"]}" : "")."
				".($_GET["CB_ID"] ? "AND CWP.CB_ID = {$_GET["CB_ID"]}" : "")."
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
				<td colspan="2" class="nowrap"><span style="font-size: 1.5em; font-weight: bold;"><?=$row["item"]?></span><br><i style="font-size: .8em;"><?=$row["drawing_item"]?></i></td>
				<td><?=$row["details"]?></td>
			<?
			$query = "
				SELECT SUM(LBM.quantity) * MN.adjustment quantity
					,MN.color
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
				echo "<td style='background: #{$subrow["color"]};'>".round($subrow["quantity"], 3)."</td>";
				echo "<td style='background: #{$subrow["color"]};'>".round($subrow["quantity"] * 1000/$row["details"], 3)."</td>";
				$quantity[$i] += $subrow["quantity"];
				$i++;
			}
			?>
				<td style="background: #c0c0c088;" class="calcium"><?=round($row["calcium"], 3)?></td>
				<td style="background: #c0c0c088;" class="calcium"><?=round($row["calcium"] * 1000/$row["details"], 3)?></td>
				<td style="background: #ffff6688;" class="reinforcement"><?=round($row["reinforcement"] * $row["details"], 3)?></td>
				<td style="background: #ffff6688;" class="reinforcement"><?=round($row["reinforcement"] * 1000, 3)?></td>
			</tr>
			<?
		}
		?>

		<tr class="total">
			<td></td>
			<td>Итог:</td>
			<td><?=$details?></td>
		<?
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

<div id="consumption_report_btn" title="Распечатать отчет за выбранный период"><a href="/printforms/consumtion_report.php?F_ID=<?=$_GET["F_ID"]?>&date_from=<?=$_GET["date_from"]?>&date_to=<?=$_GET["date_to"]?>" class="print" style="color: white;"><i class="fas fa-2x fa-print"></i></a></div>

<script>
	$(function() {
		$(".print").printPage();
	});
</script>

<style>
	<?=($calcium ? "" : ".calcium{ display: none; }")?>
	<?=($reinforcement ? "" : ".reinforcement{ display: none; }")?>
</style>

<?
include "footer.php";
?>
