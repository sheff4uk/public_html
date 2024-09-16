<?php
include "config.php";
$title = 'Статистика по браку';
include "header.php";

// Проверка прав на доступ к экрану
if( !in_array('reject', $Rights) ) {
	header($_SERVER['SERVER_PROTOCOL'].' 403 Forbidden');
	die('Недостаточно прав для совершения операции');
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

<!--Фильтр-->
<div id="filter">
	<h3>Фильтр</h3>
	<form method="get" style="position: relative;">
		<a href="/statistic.php" style="position: absolute; top: 10px; right: 10px;" class="button">Сброс</a>

		<div class="nowrap" style="margin-bottom: 10px;">
			<span style="display: inline-block; width: 200px;">Дата между:</span>
			<input name="date_from" type="date" value="<?=$_GET["date_from"]?>" class="<?=$_GET["date_from"] ? "filtered" : ""?>">
			<input name="date_to" type="date" value="<?=$_GET["date_to"]?>" class="<?=$_GET["date_to"] ? "filtered" : ""?>">
			<i class="fas fa-question-circle" title="По умолчанию устанавливаются последние 7 дней."></i>
		</div>

		<div class="nowrap" style="display: inline-block; margin-bottom: 10px; margin-right: 30px;">
			<span>Код противовеса:</span>
			<select name="CW_ID" class="<?=$_GET["CW_ID"] ? "filtered" : ""?>">
				<option value=""></option>
				<?php
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
			<span>Клиент:</span>
			<select name="CB_ID" class="<?=$_GET["CB_ID"] ? "filtered" : ""?>">
				<option value=""></option>
				<?php
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

		<div class="nowrap" style="display: inline-block; margin-bottom: 10px; margin-right: 30px;">
			<span>Участок:</span>
			<select name="F_ID" class="<?=$_GET["F_ID"] ? "filtered" : ""?>">
				<option value=""></option>
				<?php
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

		<button style="float: right;">Фильтр</button>
	</form>
</div>

<?php
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
			<th>Дата расформовки</th>
			<th>Код противовеса</th>
			<th>Кол-во кассет</th>
			<th>Ранняя расформовка</th>
<!--			<th>Несоответствие по весу</th>-->
			<th>Непролив</th>
			<th>Мех. трещина</th>
			<th>Усад. трещина</th>
			<th>Скол</th>
			<th>Дефект формы</th>
			<th>Дефект сборки</th>
			<th>Брак</th>
			<th>Всего брака</th>
			<th>Деталей произведено</th>
			<th>% брака</th>
		</tr>
	</thead>
	<tbody style="text-align: center;">

<?php
// Получаем список дат и список расформованных деталей на эти даты
$query = "
	SELECT DATE(LO.opening_time) opening_date
		,DATE_FORMAT(LO.opening_time, '%d.%m.%Y') date
		,COUNT(distinct(PB.CW_ID)) item_cnt
		,SUM(IFNULL(LOD.not_spill,0)) not_spill
		,SUM(IFNULL(LOD.crack,0)) crack
		,SUM(IFNULL(LOD.crack_drying,0)) crack_drying
		,SUM(IFNULL(LOD.chipped,0)) chipped
		,SUM(IFNULL(LOD.def_form,0)) def_form
		,SUM(IFNULL(LOD.def_assembly,0)) def_assembly
		,SUM(IFNULL(LOD.reject,0)) reject
		,SUM(1) cnt
		,SUM(IF(o_interval(LO.LO_ID) < 24, (PB.in_cassette - LF.underfilling), NULL)) o_interval
		#,SUM(IF(NOT WeightSpec(PB.CW_ID, LO.weight1) OR NOT WeightSpec(PB.CW_ID, LO.weight2) OR NOT WeightSpec(PB.CW_ID, LO.weight3), 1, NULL)) not_spec
		,SUM(PB.in_cassette - LF.underfilling) fact
	FROM list__Batch LB
	JOIN plan__Batch PB ON PB.PB_ID = LB.PB_ID
	JOIN CounterWeight CW ON CW.CW_ID = PB.CW_ID
	JOIN list__Filling LF ON LF.LB_ID = LB.LB_ID
	JOIN list__Opening LO ON LO.LF_ID = LF.LF_ID
	LEFT JOIN list__Opening_def LOD ON LOD.LO_ID = LO.LO_ID
	WHERE 1
		".($_GET["date_from"] ? "AND DATE(LO.opening_time) >= '{$_GET["date_from"]}'" : "")."
		".($_GET["date_to"] ? "AND DATE(LO.opening_time) <= '{$_GET["date_to"]}'" : "")."
		".($_GET["CW_ID"] ? "AND PB.CW_ID={$_GET["CW_ID"]}" : "")."
		".($_GET["CB_ID"] ? "AND CW.CB_ID = {$_GET["CB_ID"]}" : "")."
		".($_GET["F_ID"] ? "AND PB.F_ID = {$_GET["F_ID"]}" : "")."
	GROUP BY DATE(LO.opening_time)
	ORDER BY DATE(LO.opening_time) DESC
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	$item_cnt = $row["item_cnt"];

	$query = "
		SELECT CW.item
			,PB.CW_ID
			,IFNULL(SUM(LOD.not_spill), '-') not_spill
			,IFNULL(SUM(LOD.crack), '-') crack
			,IFNULL(SUM(LOD.crack_drying), '-') crack_drying
			,IFNULL(SUM(LOD.chipped), '-') chipped
			,IFNULL(SUM(LOD.def_form), '-') def_form
			,IFNULL(SUM(LOD.def_assembly), '-') def_assembly
			,IFNULL(SUM(LOD.reject), '-') reject
			,SUM(1) cnt
			,SUM(IF(o_interval(LO.LO_ID) < 24, (PB.in_cassette - LF.underfilling), NULL)) o_interval
			#,SUM(IF(NOT WeightSpec(PB.CW_ID, LO.weight1) OR NOT WeightSpec(PB.CW_ID, LO.weight2) OR NOT WeightSpec(PB.CW_ID, LO.weight3), 1, NULL)) not_spec
			,SUM(PB.in_cassette - LF.underfilling) fact
		FROM list__Batch LB
		JOIN plan__Batch PB ON PB.PB_ID = LB.PB_ID
		JOIN CounterWeight CW ON CW.CW_ID = PB.CW_ID
		JOIN list__Filling LF ON LF.LB_ID = LB.LB_ID
		JOIN list__Opening LO ON LO.LF_ID = LF.LF_ID
		LEFT JOIN list__Opening_def LOD ON LOD.LO_ID = LO.LO_ID
		WHERE DATE(LO.opening_time) LIKE '{$row["opening_date"]}'
			".($_GET["CW_ID"] ? "AND PB.CW_ID={$_GET["CW_ID"]}" : "")."
			".($_GET["CB_ID"] ? "AND CW.CB_ID = {$_GET["CB_ID"]}" : "")."
			".($_GET["F_ID"] ? "AND PB.F_ID = {$_GET["F_ID"]}" : "")."
		GROUP BY PB.CW_ID
		ORDER BY PB.CW_ID
	";
	$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $subrow = mysqli_fetch_array($subres) ) {
		// Выводим общую ячейку с датой расформовки
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
		echo "<td>{$subrow["o_interval"]}</td>";
		//echo "<td>{$subrow["not_spec"]}</td>";
		echo "<td>{$subrow["not_spill"]}</td>";
		echo "<td>{$subrow["crack"]}</td>";
		echo "<td>{$subrow["crack_drying"]}</td>";
		echo "<td>{$subrow["chipped"]}</td>";
		echo "<td>{$subrow["def_form"]}</td>";
		echo "<td>{$subrow["def_assembly"]}</td>";
		echo "<td>{$subrow["reject"]}</td>";

		$total = (int)$subrow["not_spill"] + (int)$subrow["crack"] + (int)$subrow["crack_drying"] + (int)$subrow["chipped"] + (int)$subrow["def_form"] + (int)$subrow["def_assembly"] + (int)$subrow["reject"];
		$percent_total = round($total / $subrow["fact"] * 100, 2);
		echo "<td>{$total}</td>";
		echo "<td>{$subrow["fact"]}</td>";
		echo "<td>{$percent_total} %</td>";
		echo "</tr>";
	}
	echo "<tr class='summary'>";
	echo "<td>Итог:</td>";
	echo "<td>{$row["cnt"]}</td>";
	echo "<td>{$row["o_interval"]}</td>";
	//echo "<td>{$row["not_spec"]}</td>";
	echo "<td>{$row["not_spill"]}</td>";
	echo "<td>{$row["crack"]}</td>";
	echo "<td>{$row["crack_drying"]}</td>";
	echo "<td>{$row["chipped"]}</td>";
	echo "<td>{$row["def_form"]}</td>";
	echo "<td>{$row["def_assembly"]}</td>";
	echo "<td>{$row["reject"]}</td>";
	$total = $row["not_spill"] + $row["crack"] + $row["crack_drying"] + $row["chipped"] + $row["def_form"] + $row["def_assembly"] + $row["reject"];
	$percent_total = round($total / $row["fact"] * 100, 2);
	echo "<td>{$total}</td>";
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
			,SUM(IFNULL(LOD.not_spill,0)) not_spill
			,SUM(IFNULL(LOD.crack,0)) crack
			,SUM(IFNULL(LOD.crack_drying,0)) crack_drying
			,SUM(IFNULL(LOD.chipped,0)) chipped
			,SUM(IFNULL(LOD.def_form,0)) def_form
			,SUM(IFNULL(LOD.def_assembly,0)) def_assembly
			,SUM(IFNULL(LOD.reject,0)) reject
			,SUM(1) cnt
			,SUM(IF(o_interval(LO.LO_ID) < 24, (PB.in_cassette - LF.underfilling), NULL)) o_interval
			#,SUM(IF(NOT WeightSpec(PB.CW_ID, LO.weight1) OR NOT WeightSpec(PB.CW_ID, LO.weight2) OR NOT WeightSpec(PB.CW_ID, LO.weight3), 1, NULL)) not_spec
			,SUM(PB.in_cassette - LF.underfilling) fact
		FROM list__Batch LB
		JOIN plan__Batch PB ON PB.PB_ID = LB.PB_ID
		JOIN CounterWeight CW ON CW.CW_ID = PB.CW_ID
		JOIN list__Filling LF ON LF.LB_ID = LB.LB_ID
		JOIN list__Opening LO ON LO.LF_ID = LF.LF_ID
		LEFT JOIN list__Opening_def LOD ON LOD.LO_ID = LO.LO_ID
		WHERE 1
			".($_GET["date_from"] ? "AND DATE(LO.opening_time) >= '{$_GET["date_from"]}'" : "")."
			".($_GET["date_to"] ? "AND DATE(LO.opening_time) <= '{$_GET["date_to"]}'" : "")."
			".($_GET["CW_ID"] ? "AND PB.CW_ID={$_GET["CW_ID"]}" : "")."
			".($_GET["CB_ID"] ? "AND CW.CB_ID = {$_GET["CB_ID"]}" : "")."
			".($_GET["F_ID"] ? "AND PB.F_ID = {$_GET["F_ID"]}" : "")."
	";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $row = mysqli_fetch_array($res) ) {
		$item_cnt = $row["item_cnt"];

		$query = "
			SELECT CW.item
				,PB.CW_ID
				,IFNULL(SUM(LOD.not_spill), 0) not_spill
				,IFNULL(SUM(LOD.crack), 0) crack
				,IFNULL(SUM(LOD.crack_drying), 0) crack_drying
				,IFNULL(SUM(LOD.chipped), 0) chipped
				,IFNULL(SUM(LOD.def_form), 0) def_form
				,IFNULL(SUM(LOD.def_assembly), 0) def_assembly
				,IFNULL(SUM(LOD.reject), 0) reject
				,SUM(1) cnt
				,SUM(IF(o_interval(LO.LO_ID) < 24, (PB.in_cassette - LF.underfilling), NULL)) o_interval
				#,SUM(IF(NOT WeightSpec(PB.CW_ID, LO.weight1) OR NOT WeightSpec(PB.CW_ID, LO.weight2) OR NOT WeightSpec(PB.CW_ID, LO.weight3), 1, NULL)) not_spec
				,SUM(PB.in_cassette - LF.underfilling) fact
			FROM list__Batch LB
			JOIN plan__Batch PB ON PB.PB_ID = LB.PB_ID
			JOIN CounterWeight CW ON CW.CW_ID = PB.CW_ID
			JOIN list__Filling LF ON LF.LB_ID = LB.LB_ID
			JOIN list__Opening LO ON LO.LF_ID = LF.LF_ID
			LEFT JOIN list__Opening_def LOD ON LOD.LO_ID = LO.LO_ID
			WHERE 1
				".($_GET["date_from"] ? "AND DATE(LO.opening_time) >= '{$_GET["date_from"]}'" : "")."
				".($_GET["date_to"] ? "AND DATE(LO.opening_time) <= '{$_GET["date_to"]}'" : "")."
				".($_GET["CW_ID"] ? "AND PB.CW_ID={$_GET["CW_ID"]}" : "")."
				".($_GET["CB_ID"] ? "AND CW.CB_ID = {$_GET["CB_ID"]}" : "")."
				".($_GET["F_ID"] ? "AND PB.F_ID = {$_GET["F_ID"]}" : "")."
			GROUP BY PB.CW_ID
			ORDER BY PB.CW_ID
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
			echo "<td>{$subrow["o_interval"]}</td>";
			//echo "<td>{$subrow["not_spec"]}</td>";
			echo "<td>{$subrow["not_spill"]}</td>";
			echo "<td>{$subrow["crack"]}</td>";
			echo "<td>{$subrow["crack_drying"]}</td>";
			echo "<td>{$subrow["chipped"]}</td>";
			echo "<td>{$subrow["def_form"]}</td>";
			echo "<td>{$subrow["def_assembly"]}</td>";
			echo "<td>{$subrow["reject"]}</td>";

			$total = $subrow["not_spill"] + $subrow["crack"] + $subrow["crack_drying"] + $subrow["chipped"] + $subrow["def_form"] + $subrow["def_assembly"] + $subrow["reject"];
			$percent_total = round($total / $subrow["fact"] * 100, 2);
			echo "<td>{$total}</td>";
			echo "<td>{$subrow["fact"]}</td>";
			echo "<td>{$percent_total} %</td>";
			echo "</tr>";
		}
		echo "<tr class='summary total'>";
		echo "<td>Итог:</td>";
		echo "<td>{$row["cnt"]}</td>";
		echo "<td>{$row["o_interval"]}</td>";
		//echo "<td>{$row["not_spec"]}</td>";
		echo "<td>{$row["not_spill"]}</td>";
		echo "<td>{$row["crack"]}</td>";
		echo "<td>{$row["crack_drying"]}</td>";
		echo "<td>{$row["chipped"]}</td>";
		echo "<td>{$row["def_form"]}</td>";
		echo "<td>{$row["def_assembly"]}</td>";
		echo "<td>{$row["reject"]}</td>";
		$total = $row["not_spill"] + $row["crack"] + $row["crack_drying"] + $row["chipped"] + $row["def_form"] + $row["def_assembly"] + $row["reject"];
		$percent_total = round($total / $row["fact"] * 100, 2);
		echo "<td>{$total}</td>";
		echo "<td>{$row["fact"]}</td>";
		echo "<td>{$percent_total} %</td>";
		echo "</tr>";
	}
}
?>
	</tbody>
</table>

<?php
include "footer.php";
?>
