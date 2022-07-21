<?
include "config.php";
$title = 'Расход сырья';
include "header.php";

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
			<th colspan="2">Мелкая дробь</th>
			<th colspan="2">Крупная дробь</th>
			<th colspan="2">Окалина</th>
			<th colspan="2">Шлак 0-10</th>
			<th colspan="2">Шлак 10-20</th>
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
			<th>Расход, г</th>
			<th>На деталь, мг</th>
			<th>Расход, г</th>
			<th>На деталь, мг</th>
			<th>Расход, кг</th>
			<th>На деталь, г</th>
			<th>Расход, г</th>
			<th>На деталь, мг</th>
			<th>Расход, кг</th>
			<th>На деталь, г</th>
		</tr>
	</thead>
	<tbody style="text-align: center;" class="nowrap">
		<?
		$query = "
			SELECT CW.item
				,CW.drawing_item
				,SUM((SELECT SUM(PB.in_cassette - underfilling) FROM list__Filling WHERE LB_ID = LB.LB_ID)) details
				,SUM(LB.s_fraction) s_fraction
				,SUM(LB.l_fraction) l_fraction
				,SUM(LB.iron_oxide) iron_oxide
				,SUM(LB.slag10) slag10
				,SUM(LB.slag20) slag20
				,SUM(LB.slag30) slag30
				,SUM(LB.sand) sand
				,SUM(LB.crushed_stone) crushed_stone
				,SUM(LB.cement) cement
				,SUM(LB.plasticizer) * 1000 / 10 plasticizer
				,SUM(LB.water * PB.calcium) calcium
				,CW.reinforcement
			FROM plan__Batch PB
			JOIN CounterWeight CW ON CW.CW_ID = PB.CW_ID
			JOIN list__Batch LB ON LB.PB_ID = PB.PB_ID
			WHERE PB.F_ID = {$_GET["F_ID"]}
				".($_GET["date_from"] ? "AND LB.batch_date >= '{$_GET["date_from"]}'" : "")."
				".($_GET["date_to"] ? "AND LB.batch_date <= '{$_GET["date_to"]}'" : "")."
				".($_GET["CW_ID"] ? "AND PB.CW_ID={$_GET["CW_ID"]}" : "")."
				".($_GET["CB_ID"] ? "AND PB.CW_ID IN (SELECT CW_ID FROM CounterWeight WHERE CB_ID = {$_GET["CB_ID"]})" : "")."
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
			$slag30 += $row["slag30"];
			$sand += $row["sand"];
			$crushed_stone += $row["crushed_stone"];
			$cement += $row["cement"];
			$plasticizer += $row["plasticizer"];
			$calcium += $row["calcium"];
			$reinforcement += $row["reinforcement"] * $row["details"];
			?>
			<tr>
				<td colspan="2" class="nowrap"><span style="font-size: 1.5em; font-weight: bold;"><?=$row["item"]?></span><br><i style="font-size: .8em;"><?=$row["drawing_item"]?></i></td>
				<td><?=number_format($row["details"], 0, '', ' ')?></td>
				<td style="background: #7952eb88;"><?=number_format($row["s_fraction"], 0, ',', ' ')?></td>
				<td style="background: #7952eb88;"><?=number_format($row["s_fraction"] * 1000/$row["details"], 0, ',', ' ')?></td>
				<td style="background: #51d5d788;"><?=number_format($row["l_fraction"], 0, ',', ' ')?></td>
				<td style="background: #51d5d788;"><?=number_format($row["l_fraction"] * 1000/$row["details"], 0, ',', ' ')?></td>
				<td style="background: #a52a2a88;"><?=number_format($row["iron_oxide"], 0, ',', ' ')?></td>
				<td style="background: #a52a2a88;"><?=number_format($row["iron_oxide"] * 1000/$row["details"], 0, ',', ' ')?></td>
				<td style="background: #33333388;"><?=number_format($row["slag10"], 0, ',', ' ')?></td>
				<td style="background: #33333388;"><?=number_format($row["slag10"] * 1000/$row["details"], 0, ',', ' ')?></td>
				<td style="background: #33333388;"><?=number_format($row["slag20"], 0, ',', ' ')?></td>
				<td style="background: #33333388;"><?=number_format($row["slag20"] * 1000/$row["details"], 0, ',', ' ')?></td>
				<td style="background: #33333388;"><?=number_format($row["slag30"], 0, ',', ' ')?></td>
				<td style="background: #33333388;"><?=number_format($row["slag30"] * 1000/$row["details"], 0, ',', ' ')?></td>
				<td style="background: #f4a46088;"><?=number_format($row["sand"], 0, ',', ' ')?></td>
				<td style="background: #f4a46088;"><?=number_format($row["sand"] * 1000/$row["details"], 0, ',', ' ')?></td>
				<td style="background: #8b451388;"><?=number_format($row["crushed_stone"], 0, ',', ' ')?></td>
				<td style="background: #8b451388;"><?=number_format($row["crushed_stone"] * 1000/$row["details"], 0, ',', ' ')?></td>
				<td style="background: #70809088;"><?=number_format($row["cement"], 0, ',', ' ')?></td>
				<td style="background: #70809088;"><?=number_format($row["cement"] * 1000/$row["details"], 0, ',', ' ')?></td>
				<td style="background: #80800080;"><?=number_format($row["plasticizer"], 0, ',', ' ')?></td>
				<td style="background: #80800080;"><?=number_format($row["plasticizer"] * 1000/$row["details"], 0, ',', ' ')?></td>
				<td style="background: #c0c0c088;"><?=number_format($row["calcium"], 0, ',', ' ')?></td>
				<td style="background: #c0c0c088;"><?=number_format($row["calcium"] * 1000/$row["details"], 0, ',', ' ')?></td>
				<td style="background: #ffff6688;"><?=number_format($row["reinforcement"] * $row["details"] / 1000, 0, ',', ' ')?></td>
				<td style="background: #ffff6688;"><?=number_format($row["reinforcement"], 0, ',', ' ')?></td>
			</tr>
			<?
		}
		?>

		<tr class="total">
			<td></td>
			<td>Итог:</td>
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
			<td><?=number_format($reinforcement/1000, 0, ',', ' ')?></td>
			<td></td>
		</tr>
	</tbody>
</table>

<div id="consumption_report_btn" title="Распечатать отчет за выбранный период"><a href="/printforms/consumtion_report.php?F_ID=<?=$_GET["F_ID"]?>&date_from=<?=$_GET["date_from"]?>&date_to=<?=$_GET["date_to"]?>" class="print" style="color: white;"><i class="fas fa-2x fa-print"></i></a></div>

<script>
	$(function() {
		$(".print").printPage();
	});
</script>

<?
include "footer.php";
?>
