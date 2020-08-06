<?
include "config.php";
$title = 'Статистика по браку';
include "header.php";

// Если в фильтре не установлен период, показываем последние 7 дней
if( !$_GET["date_from"] ) {
	$date = new DateTime('-6 days');
	$_GET["date_from"] = date_format($date, 'Y-m-d');
}
if( !$_GET["date_to"] ) {
	$date = new DateTime('-0 days');
	$_GET["date_to"] = date_format($date, 'Y-m-d');
}
?>

<style>
	.summary td {
		background-color: rgba(0, 0, 0, 0.2);
	}
	.total {
		background: #333333 url(js/ui/images/ui-bg_diagonals-thick_8_333333_40x40.png) 50% 50% repeat !important;
	}
	.total td {
		color: #fff;
	}
</style>

<!--Фильтр-->
<div id="filter">
	<h3>Фильтр</h3>
	<form method="get" style="position: relative;">
		<a href="/statistic.php" style="position: absolute; top: 10px; right: 10px;" class="button">Сброс</a>

		<div class="nowrap" style="margin-bottom: 10px;">
			<span style="display: inline-block; width: 200px;">Дата заливки между:</span>
			<input name="date_from" type="date" value="<?=$_GET["date_from"]?>" class="<?=$_GET["date_from"] ? "filtered" : ""?>">
			<input name="date_to" type="date" value="<?=$_GET["date_to"]?>" class="<?=$_GET["date_to"] ? "filtered" : ""?>">
		</div>

		<div class="nowrap" style="display: inline-block; margin-bottom: 10px; margin-right: 30px;">
			<span>Код противовеса:</span>
			<select name="CW_ID" class="<?=$_GET["CW_ID"] ? "filtered" : ""?>" style="width: 100px;">
				<option value=""></option>
				<?
				$query = "
					SELECT CW.CW_ID, CW.item
					FROM CounterWeight CW
					ORDER BY CW.CW_ID
				";
				$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
				while( $row = mysqli_fetch_array($res) ) {
					$selected = ($row["CW_ID"] == $_GET["CW_ID"]) ? "selected" : "";
					echo "<option value='{$row["CW_ID"]}' {$selected}>{$row["item"]}</option>";
				}
				?>
			</select>
		</div>

		<div class="nowrap" style="display: inline-block; margin-bottom: 10px; margin-right: 30px;">
			<span>Брэнд:</span>
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
	});
</script>

<table class="main_table">
	<thead>
		<tr>
			<th rowspan="2">Дата заливки</th>
			<th rowspan="2">Код противовеса</th>
			<th rowspan="2">Кол-во заливок</th>
			<th rowspan="2">Ранняя расформовка</th>
			<th rowspan="2">Ранняя упаковка</th>
			<th rowspan="2">Несоответствие по весу</th>
			<th rowspan="2">Непролив</th>
			<th rowspan="2">Трещина</th>
			<th rowspan="2">Скол</th>
			<th rowspan="2">Дефект форм</th>
			<th rowspan="2">Всего брака</th>
			<th colspan="2">Заливка</th>
			<th rowspan="2">% брака</th>
		</tr>
		<tr>
			<th>План</th>
			<th>Факт</th>
		</tr>
	</thead>
	<tbody style="text-align: center;">

<?
// Получаем список дат и список залитых деталей на эти даты
$query = "
	SELECT
		LB.batch_date,
		DATE_FORMAT(LB.batch_date, '%d.%m.%y') date,
		COUNT(distinct(LB.CW_ID)) item_cnt,
		SUM(IFNULL(LO.o_not_spill,0)) + SUM(IFNULL(LP.p_not_spill,0)) not_spill,
		SUM(IFNULL(LO.o_crack,0)) + SUM(IFNULL(LP.p_crack,0)) crack,
		SUM(IFNULL(LO.o_chipped,0)) + SUM(IFNULL(LP.p_chipped,0)) chipped,
		SUM(IFNULL(LO.o_def_form,0)) + SUM(IFNULL(LP.p_def_form,0)) def_form,
		SUM(1) cnt,
		SUM(IF(o_interval(LO.LO_ID) < 24, 1, NULL)) o_interval,
		SUM(IF(p_interval(LP.LP_ID) < 120, 1, NULL)) p_interval,
		SUM(IF(LO.w1_error OR LO.w2_error OR LO.w3_error, 1, NULL)) not_spec,
		SUM(CW.in_cassette) - ROUND(SUM(LB.underfilling/CW.fillings)) fakt
	FROM list__Batch LB
	JOIN CounterWeight CW ON CW.CW_ID = LB.CW_ID
	JOIN list__Filling LF ON LF.LB_ID = LB.LB_ID
	LEFT JOIN list__Opening LO ON LO.LF_ID = LF.LF_ID
	LEFT JOIN list__Packing LP ON LP.LF_ID = LF.LF_ID
	WHERE 1
		".($_GET["date_from"] ? "AND LB.batch_date >= '{$_GET["date_from"]}'" : "")."
		".($_GET["date_to"] ? "AND LB.batch_date <= '{$_GET["date_to"]}'" : "")."
		".($_GET["CW_ID"] ? "AND LB.CW_ID={$_GET["CW_ID"]}" : "")."
		".($_GET["CB_ID"] ? "AND LB.CW_ID IN (SELECT CW_ID FROM CounterWeight WHERE CB_ID = {$_GET["CB_ID"]})" : "")."
	GROUP BY LB.batch_date
	ORDER BY LB.batch_date DESC
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	$item_cnt = $row["item_cnt"];

	$query = "
		SELECT
			CW.item,
			LB.CW_ID,
			IFNULL(SUM(LO.o_not_spill), '-') o_not_spill,
			IFNULL(SUM(LO.o_crack), '-') o_crack,
			IFNULL(SUM(LO.o_chipped), '-') o_chipped,
			IFNULL(SUM(LO.o_def_form), '-') o_def_form,
			IFNULL(SUM(LP.p_not_spill), '-') p_not_spill,
			IFNULL(SUM(LP.p_crack), '-') p_crack,
			IFNULL(SUM(LP.p_chipped), '-') p_chipped,
			IFNULL(SUM(LP.p_def_form), '-') p_def_form,
			SUM(1) cnt,
			SUM(IF(o_interval(LO.LO_ID) < 24, 1, NULL)) o_interval,
			SUM(IF(p_interval(LP.LP_ID) < 120, 1, NULL)) p_interval,
			SUM(IF(LO.w1_error OR LO.w2_error OR LO.w3_error, 1, NULL)) not_spec,
			SUM(CW.in_cassette) - ROUND(SUM(LB.underfilling/CW.fillings)) fakt
		FROM list__Batch LB
		JOIN CounterWeight CW ON CW.CW_ID = LB.CW_ID
		JOIN list__Filling LF ON LF.LB_ID = LB.LB_ID
		LEFT JOIN list__Opening LO ON LO.LF_ID = LF.LF_ID
		LEFT JOIN list__Packing LP ON LP.LF_ID = LF.LF_ID
		WHERE LB.batch_date LIKE '{$row["batch_date"]}'
			".($_GET["CW_ID"] ? "AND LB.CW_ID={$_GET["CW_ID"]}" : "")."
			".($_GET["CB_ID"] ? "AND LB.CW_ID IN (SELECT CW_ID FROM CounterWeight WHERE CB_ID = {$_GET["CB_ID"]})" : "")."
		GROUP BY LB.batch_date, LB.CW_ID
		ORDER BY LB.batch_date DESC, LB.CW_ID
	";
	$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $subrow = mysqli_fetch_array($subres) ) {
		// Выводим общую ячейку с датой заливки
		if( $item_cnt ) {
			$item_cnt++;
			echo "<tr style='border-top: 2px solid #333;'>";
			echo "<td rowspan='{$item_cnt}' style='background-color: rgba(0, 0, 0, 0.2);'>{$row["date"]}</td>";
			$item_cnt = 0;
		}
		else {
			echo "<tr>";
		}

		echo "<td>{$subrow["item"]}</td>";
		echo "<td>{$subrow["cnt"]}</td>";
		echo "<td style='color:red;'><a href='opening.php?batch_date_from={$row["batch_date"]}&amp;batch_date_to={$row["batch_date"]}&amp;CW_ID={$subrow["CW_ID"]}&amp;int24=1' target='_blank'>{$subrow["o_interval"]}</a></td>";
		echo "<td><a href='packing.php?batch_date_from={$row["batch_date"]}&amp;batch_date_to={$row["batch_date"]}&amp;CW_ID={$subrow["CW_ID"]}&amp;int120=1' target='_blank'>{$subrow["p_interval"]}</a></td>";
		echo "<td><a href='opening.php?batch_date_from={$row["batch_date"]}&amp;batch_date_to={$row["batch_date"]}&amp;CW_ID={$subrow["CW_ID"]}&amp;not_spec=1' target='_blank'>{$subrow["not_spec"]}</a></td>";
		echo "<td><a href='opening.php?batch_date_from={$row["batch_date"]}&amp;batch_date_to={$row["batch_date"]}&amp;CW_ID={$subrow["CW_ID"]}&amp;not_spill=1' target='_blank'>{$subrow["o_not_spill"]}</a> / <a href='packing.php?batch_date_from={$row["batch_date"]}&amp;batch_date_to={$row["batch_date"]}&amp;CW_ID={$subrow["CW_ID"]}&amp;not_spill=1' target='_blank'>{$subrow["p_not_spill"]}</a></td>";
		echo "<td><a href='opening.php?batch_date_from={$row["batch_date"]}&amp;batch_date_to={$row["batch_date"]}&amp;CW_ID={$subrow["CW_ID"]}&amp;crack=1' target='_blank'>{$subrow["o_crack"]}</a> / <a href='packing.php?batch_date_from={$row["batch_date"]}&amp;batch_date_to={$row["batch_date"]}&amp;CW_ID={$subrow["CW_ID"]}&amp;crack=1' target='_blank'>{$subrow["p_crack"]}</a></td>";
		echo "<td><a href='opening.php?batch_date_from={$row["batch_date"]}&amp;batch_date_to={$row["batch_date"]}&amp;CW_ID={$subrow["CW_ID"]}&amp;chipped=1' target='_blank'>{$subrow["o_chipped"]}</a> / <a href='packing.php?batch_date_from={$row["batch_date"]}&amp;batch_date_to={$row["batch_date"]}&amp;CW_ID={$subrow["CW_ID"]}&amp;chipped=1' target='_blank'>{$subrow["p_chipped"]}</a></td>";
		echo "<td><a href='opening.php?batch_date_from={$row["batch_date"]}&amp;batch_date_to={$row["batch_date"]}&amp;CW_ID={$subrow["CW_ID"]}&amp;def_form=1' target='_blank'>{$subrow["o_def_form"]}</a> / <a href='packing.php?batch_date_from={$row["batch_date"]}&amp;batch_date_to={$row["batch_date"]}&amp;CW_ID={$subrow["CW_ID"]}&amp;def_form=1' target='_blank'>{$subrow["p_def_form"]}</a></td>";

		$total = $subrow["o_not_spill"] + $subrow["p_not_spill"] + $subrow["o_crack"] + $subrow["p_crack"] + $subrow["o_chipped"] + $subrow["p_chipped"] + $subrow["o_def_form"] + $subrow["p_def_form"];
		$percent_total = round($total / $subrow["fakt"] * 100, 2);
		echo "<td>{$total}</td>";
		echo "<td>?</td>";
		echo "<td>{$subrow["fakt"]}</td>";
		echo "<td>{$percent_total} %</td>";
		echo "</tr>";
	}
	echo "<tr class='summary'>";
	echo "<td>Итог:</td>";
	echo "<td>{$row["cnt"]}</td>";
	echo "<td>{$row["o_interval"]}</td>";
	echo "<td>{$row["p_interval"]}</td>";
	echo "<td>{$row["not_spec"]}</td>";
	echo "<td>{$row["not_spill"]}</td>";
	echo "<td>{$row["crack"]}</td>";
	echo "<td>{$row["chipped"]}</td>";
	echo "<td>{$row["def_form"]}</td>";
	$total = $row["not_spill"] + $row["crack"] + $row["chipped"] + $row["def_form"];
	$percent_total = round($total / $row["fakt"] * 100, 2);
	echo "<td>{$total}</td>";
	echo "<td>?</td>";
	echo "<td>{$row["fakt"]}</td>";
	echo "<td>{$percent_total} %</td>";
	echo "</tr>";
}
////////////////////////////////////////////////
// Выводим итог, если есть фильтр
if( $filter ) {
	$query = "
		SELECT
			COUNT(distinct(LB.CW_ID)) item_cnt,
			SUM(IFNULL(LO.o_not_spill,0)) + SUM(IFNULL(LP.p_not_spill,0)) not_spill,
			SUM(IFNULL(LO.o_crack,0)) + SUM(IFNULL(LP.p_crack,0)) crack,
			SUM(IFNULL(LO.o_chipped,0)) + SUM(IFNULL(LP.p_chipped,0)) chipped,
			SUM(IFNULL(LO.o_def_form,0)) + SUM(IFNULL(LP.p_def_form,0)) def_form,
			SUM(1) cnt,
			SUM(IF(o_interval(LO.LO_ID) < 24, 1, NULL)) o_interval,
			SUM(IF(p_interval(LP.LP_ID) < 120, 1, NULL)) p_interval,
			SUM(IF(LO.w1_error OR LO.w2_error OR LO.w3_error, 1, NULL)) not_spec,
			SUM(CW.in_cassette) - ROUND(SUM(LB.underfilling/CW.fillings)) fakt
		FROM list__Batch LB
		JOIN CounterWeight CW ON CW.CW_ID = LB.CW_ID
		JOIN list__Filling LF ON LF.LB_ID = LB.LB_ID
		LEFT JOIN list__Opening LO ON LO.LF_ID = LF.LF_ID
		LEFT JOIN list__Packing LP ON LP.LF_ID = LF.LF_ID
		WHERE 1
			".($_GET["date_from"] ? "AND LB.batch_date >= '{$_GET["date_from"]}'" : "")."
			".($_GET["date_to"] ? "AND LB.batch_date <= '{$_GET["date_to"]}'" : "")."
			".($_GET["CW_ID"] ? "AND LB.CW_ID={$_GET["CW_ID"]}" : "")."
			".($_GET["CB_ID"] ? "AND LB.CW_ID IN (SELECT CW_ID FROM CounterWeight WHERE CB_ID = {$_GET["CB_ID"]})" : "")."
	";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $row = mysqli_fetch_array($res) ) {
		$item_cnt = $row["item_cnt"];

		$query = "
			SELECT
				CW.item,
				LB.CW_ID,
				IFNULL(SUM(LO.o_not_spill), '-') o_not_spill,
				IFNULL(SUM(LO.o_crack), '-') o_crack,
				IFNULL(SUM(LO.o_chipped), '-') o_chipped,
				IFNULL(SUM(LO.o_def_form), '-') o_def_form,
				IFNULL(SUM(LP.p_not_spill), '-') p_not_spill,
				IFNULL(SUM(LP.p_crack), '-') p_crack,
				IFNULL(SUM(LP.p_chipped), '-') p_chipped,
				IFNULL(SUM(LP.p_def_form), '-') p_def_form,
				SUM(1) cnt,
				SUM(IF(o_interval(LO.LO_ID) < 24, 1, NULL)) o_interval,
				SUM(IF(p_interval(LP.LP_ID) < 120, 1, NULL)) p_interval,
				SUM(IF(LO.w1_error OR LO.w2_error OR LO.w3_error, 1, NULL)) not_spec,
				SUM(CW.in_cassette) - ROUND(SUM(LB.underfilling/CW.fillings)) fakt
			FROM list__Batch LB
			JOIN CounterWeight CW ON CW.CW_ID = LB.CW_ID
			JOIN list__Filling LF ON LF.LB_ID = LB.LB_ID
			LEFT JOIN list__Opening LO ON LO.LF_ID = LF.LF_ID
			LEFT JOIN list__Packing LP ON LP.LF_ID = LF.LF_ID
			WHERE 1
				".($_GET["date_from"] ? "AND LB.batch_date >= '{$_GET["date_from"]}'" : "")."
				".($_GET["date_to"] ? "AND LB.batch_date <= '{$_GET["date_to"]}'" : "")."
				".($_GET["CW_ID"] ? "AND LB.CW_ID={$_GET["CW_ID"]}" : "")."
				".($_GET["CB_ID"] ? "AND LB.CW_ID IN (SELECT CW_ID FROM CounterWeight WHERE CB_ID = {$_GET["CB_ID"]})" : "")."
			GROUP BY LB.CW_ID
			ORDER BY LB.CW_ID
		";
		$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		while( $subrow = mysqli_fetch_array($subres) ) {
			// Выводим общую ячейку с датой заливки
			if( $item_cnt ) {
				$item_cnt++;
				echo "<tr style='border-top: 2px solid #333;' class='total'>";
				echo "<td rowspan='{$item_cnt}' style='background-color: rgba(0, 0, 0, 0.2);'><h2>Σ</h2></td>";
				$item_cnt = 0;
			}
			else {
				echo "<tr class='total'>";
			}

			echo "<td>{$subrow["item"]}</td>";
			echo "<td>{$subrow["cnt"]}</td>";
			echo "<td style='color:red;'><a href='opening.php?batch_date_from={$_GET["date_from"]}&amp;batch_date_to={$_GET["date_to"]}&amp;CW_ID={$subrow["CW_ID"]}&amp;int24=1' target='_blank'>{$subrow["o_interval"]}</a></td>";
			echo "<td><a href='packing.php?batch_date_from={$_GET["date_from"]}&amp;batch_date_to={$_GET["date_to"]}&amp;CW_ID={$subrow["CW_ID"]}&amp;int120=1' target='_blank'>{$subrow["p_interval"]}</a></td>";
			echo "<td><a href='opening.php?batch_date_from={$_GET["date_from"]}&amp;batch_date_to={$_GET["date_to"]}&amp;CW_ID={$subrow["CW_ID"]}&amp;not_spec=1' target='_blank'>{$subrow["not_spec"]}</a></td>";
			echo "<td><a href='opening.php?batch_date_from={$_GET["date_from"]}&amp;batch_date_to={$_GET["date_to"]}&amp;CW_ID={$subrow["CW_ID"]}&amp;not_spill=1' target='_blank'>{$subrow["o_not_spill"]}</a> / <a href='packing.php?batch_date_from={$_GET["date_from"]}&amp;batch_date_to={$_GET["date_to"]}&amp;CW_ID={$subrow["CW_ID"]}&amp;not_spill=1' target='_blank'>{$subrow["p_not_spill"]}</a></td>";
			echo "<td><a href='opening.php?batch_date_from={$_GET["date_from"]}&amp;batch_date_to={$_GET["date_to"]}&amp;CW_ID={$subrow["CW_ID"]}&amp;crack=1' target='_blank'>{$subrow["o_crack"]}</a> / <a href='packing.php?batch_date_from={$_GET["date_from"]}&amp;batch_date_to={$_GET["date_to"]}&amp;CW_ID={$subrow["CW_ID"]}&amp;crack=1' target='_blank'>{$subrow["p_crack"]}</a></td>";
			echo "<td><a href='opening.php?batch_date_from={$_GET["date_from"]}&amp;batch_date_to={$_GET["date_to"]}&amp;CW_ID={$subrow["CW_ID"]}&amp;chipped=1' target='_blank'>{$subrow["o_chipped"]}</a> / <a href='packing.php?batch_date_from={$_GET["date_from"]}&amp;batch_date_to={$_GET["date_to"]}&amp;CW_ID={$subrow["CW_ID"]}&amp;chipped=1' target='_blank'>{$subrow["p_chipped"]}</a></td>";
			echo "<td><a href='opening.php?batch_date_from={$_GET["date_from"]}&amp;batch_date_to={$_GET["date_to"]}&amp;CW_ID={$subrow["CW_ID"]}&amp;def_form=1' target='_blank'>{$subrow["o_def_form"]}</a> / <a href='packing.php?batch_date_from={$_GET["date_from"]}&amp;batch_date_to={$_GET["date_to"]}&amp;CW_ID={$subrow["CW_ID"]}&amp;def_form=1' target='_blank'>{$subrow["p_def_form"]}</a></td>";

			$total = $subrow["o_not_spill"] + $subrow["p_not_spill"] + $subrow["o_crack"] + $subrow["p_crack"] + $subrow["o_chipped"] + $subrow["p_chipped"] + $subrow["o_def_form"] + $subrow["p_def_form"];
			$percent_total = round($total / $subrow["fakt"] * 100, 2);
			echo "<td>{$total}</td>";
			echo "<td>?</td>";
			echo "<td>{$subrow["fakt"]}</td>";
			echo "<td>{$percent_total} %</td>";
			echo "</tr>";
		}
		echo "<tr class='summary total'>";
		echo "<td>Итог:</td>";
		echo "<td>{$row["cnt"]}</td>";
		echo "<td>{$row["o_interval"]}</td>";
		echo "<td>{$row["o_interval"]}</td>";
		echo "<td>{$row["not_spec"]}</td>";
		echo "<td>{$row["not_spill"]}</td>";
		echo "<td>{$row["crack"]}</td>";
		echo "<td>{$row["chipped"]}</td>";
		echo "<td>{$row["def_form"]}</td>";
		$total = $row["not_spill"] + $row["crack"] + $row["chipped"] + $row["def_form"];
		$percent_total = round($total / $row["fakt"] * 100, 2);
		echo "<td>{$total}</td>";
		echo "<td>?</td>";
		echo "<td>{$row["fakt"]}</td>";
		echo "<td>{$percent_total} %</td>";
		echo "</tr>";
	}
}
?>
	</tbody>
</table>

<?
include "footer.php";
?>
