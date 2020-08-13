<?
include "config.php";
$title = 'Статистика по браку';
include "header.php";
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
		RS.filling_date,
		DATE_FORMAT(RS.filling_date, '%d.%m.%y') date,
		COUNT(distinct(RS.CW_ID)) item_cnt,
		SUM(IFNULL(o_not_spill,0)) + SUM(IFNULL(b_not_spill,0)) not_spill,
		SUM(IFNULL(o_crack,0)) + SUM(IFNULL(b_crack,0)) crack,
		SUM(IFNULL(o_chipped,0)) + SUM(IFNULL(b_chipped,0)) chipped,
		SUM(IFNULL(o_def_form,0)) + SUM(IFNULL(b_def_form,0)) def_form,
		SUM(1) cnt,
		SUM(IF(interval1 < 24, 1, 0)) interval1,
		SUM(IF(interval2 < 120, 1, 0)) interval2,
		SUM(IF(WeightSpec(RS.CW_ID, RS.weight1) AND WeightSpec(RS.CW_ID, RS.weight2) AND WeightSpec(RS.CW_ID, RS.weight3), 0, 1)) not_spec,
		SUM(RS.amount) fakt
	FROM RouteSheet RS
	WHERE 1
		".($_GET["date_from"] ? "AND RS.filling_date >= '{$_GET["date_from"]}'" : "")."
		".($_GET["date_to"] ? "AND RS.filling_date <= '{$_GET["date_to"]}'" : "")."
		".($_GET["CW_ID"] ? "AND RS.CW_ID={$_GET["CW_ID"]}" : "")."
		".($_GET["CB_ID"] ? "AND RS.CW_ID IN (SELECT CW_ID FROM CounterWeight WHERE CB_ID = {$_GET["CB_ID"]})" : "")."
	GROUP BY filling_date
	ORDER BY RS.filling_date DESC
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	$item_cnt = $row["item_cnt"];

	$query = "
		SELECT
			CW.item,
			RS.CW_ID,
			IFNULL(SUM(RS.o_not_spill), '-') o_not_spill,
			IFNULL(SUM(RS.o_crack), '-') o_crack,
			IFNULL(SUM(RS.o_chipped), '-') o_chipped,
			IFNULL(SUM(RS.o_def_form), '-') o_def_form,
			IFNULL(SUM(RS.b_not_spill), '-') b_not_spill,
			IFNULL(SUM(RS.b_crack), '-') b_crack,
			IFNULL(SUM(RS.b_chipped), '-') b_chipped,
			IFNULL(SUM(RS.b_def_form), '-') b_def_form,
			SUM(1) cnt,
			SUM(IF(interval1 < 24, 1, NULL)) interval1,
			SUM(IF(interval2 < 120, 1, NULL)) interval2,
			SUM(IF(WeightSpec(RS.CW_ID, RS.weight1) AND WeightSpec(RS.CW_ID, RS.weight2) AND WeightSpec(RS.CW_ID, RS.weight3), NULL, 1)) not_spec,
			SUM(RS.amount) fakt
		FROM RouteSheet RS
		JOIN CounterWeight CW ON CW.CW_ID = RS.CW_ID
		WHERE filling_date LIKE '{$row["filling_date"]}'
			".($_GET["CW_ID"] ? "AND RS.CW_ID={$_GET["CW_ID"]}" : "")."
			".($_GET["CB_ID"] ? "AND RS.CW_ID IN (SELECT CW_ID FROM CounterWeight WHERE CB_ID = {$_GET["CB_ID"]})" : "")."
		GROUP BY RS.filling_date, RS.CW_ID
		ORDER BY RS.filling_date DESC, RS.CW_ID
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
		echo "<td style='color:red;'><a href='route_sheet.php?filling_date_from={$row["filling_date"]}&amp;filling_date_to={$row["filling_date"]}&amp;CW_ID={$subrow["CW_ID"]}&amp;int24=1' target='_blank'>{$subrow["interval1"]}</a></td>";
		echo "<td><a href='route_sheet.php?filling_date_from={$row["filling_date"]}&amp;filling_date_to={$row["filling_date"]}&amp;CW_ID={$subrow["CW_ID"]}&amp;int120=1' target='_blank'>{$subrow["interval2"]}</a></td>";
		echo "<td><a href='route_sheet.php?filling_date_from={$row["filling_date"]}&amp;filling_date_to={$row["filling_date"]}&amp;CW_ID={$subrow["CW_ID"]}&amp;weight=1' target='_blank'>{$subrow["not_spec"]}</a></td>";
		echo "<td><a href='route_sheet.php?filling_date_from={$row["filling_date"]}&amp;filling_date_to={$row["filling_date"]}&amp;CW_ID={$subrow["CW_ID"]}&amp;not_spill=1' target='_blank'>{$subrow["o_not_spill"]} / {$subrow["b_not_spill"]}</a></td>";
		echo "<td><a href='route_sheet.php?filling_date_from={$row["filling_date"]}&amp;filling_date_to={$row["filling_date"]}&amp;CW_ID={$subrow["CW_ID"]}&amp;crack=1' target='_blank'>{$subrow["o_crack"]} / {$subrow["b_crack"]}</a></td>";
		echo "<td><a href='route_sheet.php?filling_date_from={$row["filling_date"]}&amp;filling_date_to={$row["filling_date"]}&amp;CW_ID={$subrow["CW_ID"]}&amp;chipped=1' target='_blank'>{$subrow["o_chipped"]} / {$subrow["b_chipped"]}</a></td>";
		echo "<td><a href='route_sheet.php?filling_date_from={$row["filling_date"]}&amp;filling_date_to={$row["filling_date"]}&amp;CW_ID={$subrow["CW_ID"]}&amp;def_form=1' target='_blank'>{$subrow["o_def_form"]} / {$subrow["b_def_form"]}</a></td>";
		$total = $subrow["o_not_spill"] + $subrow["b_not_spill"] + $subrow["o_crack"] + $subrow["b_crack"] + $subrow["o_chipped"] + $subrow["b_chipped"] + $subrow["o_def_form"] + $subrow["b_def_form"];
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
	echo "<td>{$row["interval1"]}</td>";
	echo "<td>{$row["interval2"]}</td>";
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
			COUNT(distinct(RS.CW_ID)) item_cnt,
			SUM(IFNULL(o_not_spill,0)) + SUM(IFNULL(b_not_spill,0)) not_spill,
			SUM(IFNULL(o_crack,0)) + SUM(IFNULL(b_crack,0)) crack,
			SUM(IFNULL(o_chipped,0)) + SUM(IFNULL(b_chipped,0)) chipped,
			SUM(IFNULL(o_def_form,0)) + SUM(IFNULL(b_def_form,0)) def_form,
			SUM(1) cnt,
			SUM(IF(interval1 < 24, 1, 0)) interval1,
			SUM(IF(interval2 < 120, 1, 0)) interval2,
			SUM(IF(WeightSpec(RS.CW_ID, RS.weight1) AND WeightSpec(RS.CW_ID, RS.weight2) AND WeightSpec(RS.CW_ID, RS.weight3), 0, 1)) not_spec,
			SUM(RS.amount) fakt
		FROM RouteSheet RS
		WHERE 1
			".($_GET["date_from"] ? "AND RS.filling_date >= '{$_GET["date_from"]}'" : "")."
			".($_GET["date_to"] ? "AND RS.filling_date <= '{$_GET["date_to"]}'" : "")."
			".($_GET["CW_ID"] ? "AND RS.CW_ID={$_GET["CW_ID"]}" : "")."
			".($_GET["CB_ID"] ? "AND RS.CW_ID IN (SELECT CW_ID FROM CounterWeight WHERE CB_ID = {$_GET["CB_ID"]})" : "")."
	";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $row = mysqli_fetch_array($res) ) {
		$item_cnt = $row["item_cnt"];

		$query = "
			SELECT
				CW.item,
				RS.CW_ID,
				IFNULL(SUM(RS.o_not_spill), '-') o_not_spill,
				IFNULL(SUM(RS.o_crack), '-') o_crack,
				IFNULL(SUM(RS.o_chipped), '-') o_chipped,
				IFNULL(SUM(RS.o_def_form), '-') o_def_form,
				IFNULL(SUM(RS.b_not_spill), '-') b_not_spill,
				IFNULL(SUM(RS.b_crack), '-') b_crack,
				IFNULL(SUM(RS.b_chipped), '-') b_chipped,
				IFNULL(SUM(RS.b_def_form), '-') b_def_form,
				SUM(1) cnt,
				SUM(IF(interval1 < 24, 1, NULL)) interval1,
				SUM(IF(interval2 < 120, 1, NULL)) interval2,
				SUM(IF(WeightSpec(RS.CW_ID, RS.weight1) AND WeightSpec(RS.CW_ID, RS.weight2) AND WeightSpec(RS.CW_ID, RS.weight3), NULL, 1)) not_spec,
				SUM(RS.amount) fakt
			FROM RouteSheet RS
			JOIN CounterWeight CW ON CW.CW_ID = RS.CW_ID
			WHERE 1
				".($_GET["date_from"] ? "AND RS.filling_date >= '{$_GET["date_from"]}'" : "")."
				".($_GET["date_to"] ? "AND RS.filling_date <= '{$_GET["date_to"]}'" : "")."
				".($_GET["CW_ID"] ? "AND RS.CW_ID={$_GET["CW_ID"]}" : "")."
				".($_GET["CB_ID"] ? "AND RS.CW_ID IN (SELECT CW_ID FROM CounterWeight WHERE CB_ID = {$_GET["CB_ID"]})" : "")."
			GROUP BY RS.CW_ID
			ORDER BY RS.CW_ID
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
			echo "<td style='color:red;'><a href='route_sheet.php?filling_date_from={$_GET["date_from"]}&amp;filling_date_to={$_GET["date_to"]}&amp;CW_ID={$subrow["CW_ID"]}&amp;int24=1' target='_blank'>{$subrow["interval1"]}</a></td>";
			echo "<td><a href='route_sheet.php?filling_date_from={$_GET["date_from"]}&amp;filling_date_to={$_GET["date_to"]}&amp;CW_ID={$subrow["CW_ID"]}&amp;int120=1' target='_blank'>{$subrow["interval2"]}</a></td>";
			echo "<td><a href='route_sheet.php?filling_date_from={$_GET["date_from"]}&amp;filling_date_to={$_GET["date_to"]}&amp;CW_ID={$subrow["CW_ID"]}&amp;weight=1' target='_blank'>{$subrow["not_spec"]}</a></td>";
			echo "<td><a href='route_sheet.php?filling_date_from={$_GET["date_from"]}&amp;filling_date_to={$_GET["date_to"]}&amp;CW_ID={$subrow["CW_ID"]}&amp;not_spill=1' target='_blank'>{$subrow["o_not_spill"]} / {$subrow["b_not_spill"]}</a></td>";
			echo "<td><a href='route_sheet.php?filling_date_from={$_GET["date_from"]}&amp;filling_date_to={$_GET["date_to"]}&amp;CW_ID={$subrow["CW_ID"]}&amp;crack=1' target='_blank'>{$subrow["o_crack"]} / {$subrow["b_crack"]}</a></td>";
			echo "<td><a href='route_sheet.php?filling_date_from={$_GET["date_from"]}&amp;filling_date_to={$_GET["date_to"]}&amp;CW_ID={$subrow["CW_ID"]}&amp;chipped=1' target='_blank'>{$subrow["o_chipped"]} / {$subrow["b_chipped"]}</a></td>";
			echo "<td><a href='route_sheet.php?filling_date_from={$_GET["date_from"]}&amp;filling_date_to={$_GET["date_to"]}&amp;CW_ID={$subrow["CW_ID"]}&amp;def_form=1' target='_blank'>{$subrow["o_def_form"]} / {$subrow["b_def_form"]}</a></td>";
			$total = $subrow["o_not_spill"] + $subrow["b_not_spill"] + $subrow["o_crack"] + $subrow["b_crack"] + $subrow["o_chipped"] + $subrow["b_chipped"] + $subrow["o_def_form"] + $subrow["b_def_form"];
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
		echo "<td>{$row["interval1"]}</td>";
		echo "<td>{$row["interval2"]}</td>";
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
