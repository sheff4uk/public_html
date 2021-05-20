<?
include "config.php";
$title = 'Статистика по браку';
include "header.php";

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

<!--Фильтр-->
<div id="filter">
	<h3>Фильтр</h3>
	<form method="get" style="position: relative;">
		<a href="/statistic.php" style="position: absolute; top: 10px; right: 10px;" class="button">Сброс</a>

		<div class="nowrap" style="margin-bottom: 10px;">
			<span style="display: inline-block; width: 200px;">Дата заливки между:</span>
			<input name="date_from" type="date" value="<?=$_GET["date_from"]?>" class="<?=$_GET["date_from"] ? "filtered" : ""?>">
			<input name="date_to" type="date" value="<?=$_GET["date_to"]?>" class="<?=$_GET["date_to"] ? "filtered" : ""?>">
			<i class="fas fa-question-circle" title="По умолчанию устанавливаются последние 7 дней."></i>
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
			<th rowspan="2">Дата заливки</th>
			<th rowspan="2">Код противовеса</th>
			<th rowspan="2">Кол-во заливок</th>
			<th rowspan="2">Ранняя расформовка</th>
			<th rowspan="2">Несоответствие по весу</th>
			<th rowspan="2">Непролив</th>
			<th rowspan="2">Трещина</th>
			<th rowspan="2">Скол</th>
			<th rowspan="2">Дефект форм</th>
			<th rowspan="2">Всего брака</th>
			<th colspan="2">Деталей</th>
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
	SELECT PB.pb_date
		,DATE_FORMAT(PB.pb_date, '%d.%m.%y') date
		,COUNT(distinct(PB.CW_ID)) item_cnt
		,SUM(IFNULL(LO.not_spill,0)) not_spill
		,SUM(IFNULL(LO.crack,0)) crack
		,SUM(IFNULL(LO.chipped,0)) chipped
		,SUM(IFNULL(LO.def_form,0)) def_form
		,SUM(1) cnt
		,SUM(IF(o_interval(LO.LO_ID) < 24, 1, NULL)) o_interval
		,SUM(IF(NOT WeightSpec(PB.CW_ID, LO.weight1) OR NOT WeightSpec(PB.CW_ID, LO.weight2) OR NOT WeightSpec(PB.CW_ID, LO.weight3), 1, NULL)) not_spec
		,SUM(CW.in_cassette - LF.underfilling) fact
	FROM list__Batch LB
	JOIN plan__Batch PB ON PB.PB_ID = LB.PB_ID
	JOIN CounterWeight CW ON CW.CW_ID = PB.CW_ID
	JOIN list__Filling LF ON LF.LB_ID = LB.LB_ID
	LEFT JOIN list__Opening LO ON LO.LF_ID = LF.LF_ID
	WHERE 1
		".($_GET["date_from"] ? "AND PB.pb_date >= '{$_GET["date_from"]}'" : "")."
		".($_GET["date_to"] ? "AND PB.pb_date <= '{$_GET["date_to"]}'" : "")."
		".($_GET["CW_ID"] ? "AND PB.CW_ID={$_GET["CW_ID"]}" : "")."
		".($_GET["CB_ID"] ? "AND PB.CW_ID IN (SELECT CW_ID FROM CounterWeight WHERE CB_ID = {$_GET["CB_ID"]})" : "")."
	GROUP BY PB.pb_date
	ORDER BY PB.pb_date DESC
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	$item_cnt = $row["item_cnt"];

	$query = "
		SELECT CW.item
			,PB.CW_ID
			,IFNULL(SUM(LO.not_spill), '-') not_spill
			,IFNULL(SUM(LO.crack), '-') crack
			,IFNULL(SUM(LO.chipped), '-') chipped
			,IFNULL(SUM(LO.def_form), '-') def_form
			,SUM(1) cnt
			,SUM(IF(o_interval(LO.LO_ID) < 24, 1, NULL)) o_interval
			,SUM(IF(NOT WeightSpec(PB.CW_ID, LO.weight1) OR NOT WeightSpec(PB.CW_ID, LO.weight2) OR NOT WeightSpec(PB.CW_ID, LO.weight3), 1, NULL)) not_spec
			,SUM(CW.in_cassette - LF.underfilling) fact
			,PB.batches * IFNULL(PB.fillings_per_batch, CW.fillings) * CW.in_cassette plan
		FROM list__Batch LB
		JOIN plan__Batch PB ON PB.PB_ID = LB.PB_ID
		JOIN CounterWeight CW ON CW.CW_ID = PB.CW_ID
		JOIN list__Filling LF ON LF.LB_ID = LB.LB_ID
		LEFT JOIN list__Opening LO ON LO.LF_ID = LF.LF_ID
		WHERE PB.pb_date LIKE '{$row["pb_date"]}'
			".($_GET["CW_ID"] ? "AND PB.CW_ID={$_GET["CW_ID"]}" : "")."
			".($_GET["CB_ID"] ? "AND PB.CW_ID IN (SELECT CW_ID FROM CounterWeight WHERE CB_ID = {$_GET["CB_ID"]})" : "")."
		GROUP BY PB.pb_date, PB.CW_ID
		ORDER BY PB.pb_date DESC, PB.CW_ID
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
		echo "<td style='color:red;'><a href='opening.php?pb_date_from={$row["pb_date"]}&amp;pb_date_to={$row["pb_date"]}&amp;CW_ID={$subrow["CW_ID"]}&amp;int24=1' target='_blank'>{$subrow["o_interval"]}</a></td>";
		echo "<td><a href='opening.php?pb_date_from={$row["pb_date"]}&amp;pb_date_to={$row["pb_date"]}&amp;CW_ID={$subrow["CW_ID"]}&amp;not_spec=1' target='_blank'>{$subrow["not_spec"]}</a></td>";
		echo "<td><a href='opening.php?pb_date_from={$row["pb_date"]}&amp;pb_date_to={$row["pb_date"]}&amp;CW_ID={$subrow["CW_ID"]}&amp;not_spill=1' target='_blank'>{$subrow["not_spill"]}</a></td>";
		echo "<td><a href='opening.php?pb_date_from={$row["pb_date"]}&amp;pb_date_to={$row["pb_date"]}&amp;CW_ID={$subrow["CW_ID"]}&amp;crack=1' target='_blank'>{$subrow["crack"]}</a></td>";
		echo "<td><a href='opening.php?pb_date_from={$row["pb_date"]}&amp;pb_date_to={$row["pb_date"]}&amp;CW_ID={$subrow["CW_ID"]}&amp;chipped=1' target='_blank'>{$subrow["chipped"]}</a></td>";
		echo "<td><a href='opening.php?pb_date_from={$row["pb_date"]}&amp;pb_date_to={$row["pb_date"]}&amp;CW_ID={$subrow["CW_ID"]}&amp;def_form=1' target='_blank'>{$subrow["def_form"]}</a></td>";

		$total = $subrow["not_spill"] + $subrow["crack"] + $subrow["chipped"] + $subrow["def_form"];
		$percent_total = round($total / $subrow["fact"] * 100, 2);
		echo "<td>{$total}</td>";
		echo "<td>{$subrow["plan"]}</td>";
		echo "<td>{$subrow["fact"]}</td>";
		echo "<td>{$percent_total} %</td>";
		echo "</tr>";

		$total_plan += $subrow["plan"];
	}
	echo "<tr class='summary'>";
	echo "<td>Итог:</td>";
	echo "<td>{$row["cnt"]}</td>";
	echo "<td>{$row["o_interval"]}</td>";
	echo "<td>{$row["not_spec"]}</td>";
	echo "<td>{$row["not_spill"]}</td>";
	echo "<td>{$row["crack"]}</td>";
	echo "<td>{$row["chipped"]}</td>";
	echo "<td>{$row["def_form"]}</td>";
	$total = $row["not_spill"] + $row["crack"] + $row["chipped"] + $row["def_form"];
	$percent_total = round($total / $row["fact"] * 100, 2);
	echo "<td>{$total}</td>";
	echo "<td>{$total_plan}</td>";
	echo "<td>{$row["fact"]}</td>";
	echo "<td>{$percent_total} %</td>";
	echo "</tr>";

	unset($total_plan);
}
////////////////////////////////////////////////
// Выводим итог, если есть фильтр
if( $filter ) {
	$query = "
		SELECT COUNT(distinct(PB.CW_ID)) item_cnt
			,SUM(IFNULL(LO.not_spill,0)) not_spill
			,SUM(IFNULL(LO.crack,0)) crack
			,SUM(IFNULL(LO.chipped,0)) chipped
			,SUM(IFNULL(LO.def_form,0)) def_form
			,SUM(1) cnt
			,SUM(IF(o_interval(LO.LO_ID) < 24, 1, NULL)) o_interval
			,SUM(IF(NOT WeightSpec(PB.CW_ID, LO.weight1) OR NOT WeightSpec(PB.CW_ID, LO.weight2) OR NOT WeightSpec(PB.CW_ID, LO.weight3), 1, NULL)) not_spec
			,SUM(CW.in_cassette - LF.underfilling) fact
		FROM list__Batch LB
		JOIN plan__Batch PB ON PB.PB_ID = LB.PB_ID
		JOIN CounterWeight CW ON CW.CW_ID = PB.CW_ID
		JOIN list__Filling LF ON LF.LB_ID = LB.LB_ID
		LEFT JOIN list__Opening LO ON LO.LF_ID = LF.LF_ID
		WHERE 1
			".($_GET["date_from"] ? "AND PB.pb_date >= '{$_GET["date_from"]}'" : "")."
			".($_GET["date_to"] ? "AND PB.pb_date <= '{$_GET["date_to"]}'" : "")."
			".($_GET["CW_ID"] ? "AND PB.CW_ID={$_GET["CW_ID"]}" : "")."
			".($_GET["CB_ID"] ? "AND PB.CW_ID IN (SELECT CW_ID FROM CounterWeight WHERE CB_ID = {$_GET["CB_ID"]})" : "")."
	";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $row = mysqli_fetch_array($res) ) {
		$item_cnt = $row["item_cnt"];

		$query = "
			SELECT CW.item
				,PB.CW_ID
				,IFNULL(SUM(LO.not_spill), '-') not_spill
				,IFNULL(SUM(LO.crack), '-') crack
				,IFNULL(SUM(LO.chipped), '-') chipped
				,IFNULL(SUM(LO.def_form), '-') def_form
				,SUM(1) cnt
				,SUM(IF(o_interval(LO.LO_ID) < 24, 1, NULL)) o_interval
				,SUM(IF(NOT WeightSpec(PB.CW_ID, LO.weight1) OR NOT WeightSpec(PB.CW_ID, LO.weight2) OR NOT WeightSpec(PB.CW_ID, LO.weight3), 1, NULL)) not_spec
				,SUM(CW.in_cassette - LF.underfilling) fact
			FROM list__Batch LB
			JOIN plan__Batch PB ON PB.PB_ID = LB.PB_ID
			JOIN CounterWeight CW ON CW.CW_ID = PB.CW_ID
			JOIN list__Filling LF ON LF.LB_ID = LB.LB_ID
			LEFT JOIN list__Opening LO ON LO.LF_ID = LF.LF_ID
			WHERE 1
				".($_GET["date_from"] ? "AND PB.pb_date >= '{$_GET["date_from"]}'" : "")."
				".($_GET["date_to"] ? "AND PB.pb_date <= '{$_GET["date_to"]}'" : "")."
				".($_GET["CW_ID"] ? "AND PB.CW_ID={$_GET["CW_ID"]}" : "")."
				".($_GET["CB_ID"] ? "AND PB.CW_ID IN (SELECT CW_ID FROM CounterWeight WHERE CB_ID = {$_GET["CB_ID"]})" : "")."
			GROUP BY PB.CW_ID
			ORDER BY PB.CW_ID
		";
		$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		while( $subrow = mysqli_fetch_array($subres) ) {
			// Узнаем план
			$query = "
				SELECT SUM(PB.batches * IFNULL(PB.fillings_per_batch, CW.fillings) * CW.in_cassette) plan
				FROM plan__Batch PB
				JOIN CounterWeight CW ON CW.CW_ID = PB.CW_ID
				WHERE PB.fact_batches
					AND PB.CW_ID = {$subrow["CW_ID"]}
					".($_GET["date_from"] ? "AND PB.pb_date >= '{$_GET["date_from"]}'" : "")."
					".($_GET["date_to"] ? "AND PB.pb_date <= '{$_GET["date_to"]}'" : "")."
			";
			$subsubres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			$subsubrow = mysqli_fetch_array($subsubres);

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
			echo "<td style='color:red;'><a href='opening.php?pb_date_from={$_GET["date_from"]}&amp;pb_date_to={$_GET["date_to"]}&amp;CW_ID={$subrow["CW_ID"]}&amp;int24=1' target='_blank'>{$subrow["o_interval"]}</a></td>";
			echo "<td><a href='opening.php?pb_date_from={$_GET["date_from"]}&amp;pb_date_to={$_GET["date_to"]}&amp;CW_ID={$subrow["CW_ID"]}&amp;not_spec=1' target='_blank'>{$subrow["not_spec"]}</a></td>";
			echo "<td><a href='opening.php?pb_date_from={$_GET["date_from"]}&amp;pb_date_to={$_GET["date_to"]}&amp;CW_ID={$subrow["CW_ID"]}&amp;not_spill=1' target='_blank'>{$subrow["not_spill"]}</a></td>";
			echo "<td><a href='opening.php?pb_date_from={$_GET["date_from"]}&amp;pb_date_to={$_GET["date_to"]}&amp;CW_ID={$subrow["CW_ID"]}&amp;crack=1' target='_blank'>{$subrow["crack"]}</a></td>";
			echo "<td><a href='opening.php?pb_date_from={$_GET["date_from"]}&amp;pb_date_to={$_GET["date_to"]}&amp;CW_ID={$subrow["CW_ID"]}&amp;chipped=1' target='_blank'>{$subrow["chipped"]}</a></td>";
			echo "<td><a href='opening.php?pb_date_from={$_GET["date_from"]}&amp;pb_date_to={$_GET["date_to"]}&amp;CW_ID={$subrow["CW_ID"]}&amp;def_form=1' target='_blank'>{$subrow["def_form"]}</a></td>";

			$total = $subrow["not_spill"] + $subrow["crack"] + $subrow["chipped"] + $subrow["def_form"];
			$percent_total = round($total / $subrow["fact"] * 100, 2);
			echo "<td>{$total}</td>";
			echo "<td>{$subsubrow["plan"]}</td>";
			echo "<td>{$subrow["fact"]}</td>";
			echo "<td>{$percent_total} %</td>";
			echo "</tr>";

			$total_plan += $subsubrow["plan"];
		}
		echo "<tr class='summary total'>";
		echo "<td>Итог:</td>";
		echo "<td>{$row["cnt"]}</td>";
		echo "<td>{$row["o_interval"]}</td>";
		echo "<td>{$row["not_spec"]}</td>";
		echo "<td>{$row["not_spill"]}</td>";
		echo "<td>{$row["crack"]}</td>";
		echo "<td>{$row["chipped"]}</td>";
		echo "<td>{$row["def_form"]}</td>";
		$total = $row["not_spill"] + $row["crack"] + $row["chipped"] + $row["def_form"];
		$percent_total = round($total / $row["fact"] * 100, 2);
		echo "<td>{$total}</td>";
		echo "<td>{$total_plan}</td>";
		echo "<td>{$row["fact"]}</td>";
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
