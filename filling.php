<?
include "config.php";
$title = 'Заливка';
include "header.php";
include "./forms/filling_form.php";
//die("<h1>Ведутся работы</h1>");

// Если в фильтре не установлена неделя, показываем текущую
if( !$_GET["week"] ) {
	$query = "SELECT YEARWEEK(CURDATE(), 1) week";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$row = mysqli_fetch_array($res);
	$_GET["week"] = $row["week"];
}
?>

<style>
	#batch_plan {
		position: relative;
	}
	#batch_plan:hover > div {
		height: 300px;
		opacity: 1;
	}
	#batch_plan > div {
		background: #fff;
		height: 0px;
		border: 1px solid #bbb;
		padding: 10px;
		border-radius: 5px;
		margin-top: 10px;
		z-index: 2;
		position: absolute;
		top: -10px;
		left: 0px;
		width: 100%;
		overflow: auto;
		opacity: 0;
		transition: .3s;
		-webkit-transition: .3s;
		box-shadow: 5px 5px 8px #666;
	}
	#batch_plan > div table {
		width: 100%;
		table-layout: fixed;
	}
</style>

<!--Фильтр-->
<div id="filter">
	<h3>Фильтр</h3>
	<form method="get" style="position: relative;">
		<a href="/filling.php" style="position: absolute; top: 10px; right: 10px;" class="button">Сброс</a>

		<div class="nowrap" style="margin-bottom: 10px;">
			<span>Неделя:</span>
			<select name="week" class="<?=$_GET["week"] ? "filtered" : ""?>" onchange="this.form.submit()">
				<?
				$query = "
					SELECT LEFT(YEARWEEK(CURDATE(), 1), 4) year
					UNION
					SELECT LEFT(YEARWEEK(batch_date, 1), 4) year
					FROM list__Batch
					ORDER BY year DESC
				";
				$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
				while( $row = mysqli_fetch_array($res) ) {
					echo "<optgroup label='{$row["year"]}'>";
					$query = "
						SELECT SUB.week
							,SUB.week_format
							,SUB.WeekStart
							,SUB.WeekEnd
						FROM (
							SELECT LEFT(YEARWEEK(CURDATE(), 1), 4) year
								,YEARWEEK(CURDATE(), 1) week
								,RIGHT(YEARWEEK(CURDATE(), 1), 2) week_format
								,DATE_FORMAT(ADDDATE(CURDATE(), 0-WEEKDAY(CURDATE())), '%e %b') WeekStart
								,DATE_FORMAT(ADDDATE(CURDATE(), 6-WEEKDAY(CURDATE())), '%e %b') WeekEnd
							UNION
							SELECT LEFT(YEARWEEK(batch_date, 1), 4) year
								,YEARWEEK(batch_date, 1) week
								,RIGHT(YEARWEEK(batch_date, 1), 2) week_format
								,DATE_FORMAT(ADDDATE(batch_date, 0-WEEKDAY(batch_date)), '%e %b') WeekStart
								,DATE_FORMAT(ADDDATE(batch_date, 6-WEEKDAY(batch_date)), '%e %b') WeekEnd
							FROM list__Batch
							WHERE LEFT(YEARWEEK(batch_date, 1), 4) = {$row["year"]}
							GROUP BY week
						) SUB
						WHERE SUB.year = {$row["year"]}
						ORDER BY SUB.week DESC
					";
					$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
					while( $subrow = mysqli_fetch_array($subres) ) {
						$selected = ($subrow["week"] == $_GET["week"]) ? "selected" : "";
						echo "<option value='{$subrow["week"]}' {$selected}>{$subrow["week_format"]} [{$subrow["WeekStart"]} - {$subrow["WeekEnd"]}]</option>";
					}
					echo "</optgroup>";
				}
				?>
			</select>
			<i class="fas fa-question-circle" title="По умолчанию устанавливается текущая неделя."></i>
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

<!--
		<div class="nowrap" style="display: inline-block; margin-bottom: 10px; margin-right: 30px;">
			<span>№ кассеты:</span>
			<input type="number" min="1" max="<?=$cassettes?>" name="cassette" value="<?=$_GET["cassette"]?>" class="<?=$_GET["cassette"] ? "filtered" : ""?>" style="width: 80px;">
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
			<th>Год/Цикл</th>
			<th>Дата время замеса</th>
			<th>Рецепт</th>
			<th>Куб раствора, кг</th>
			<th>t, ℃ 19±4</th>
			<th>Окалина,<br>кг ±5</th>
			<th>КМП,<br>кг ±5</th>
			<th>Отсев,<br>кг ±5</th>
			<th>Цемент,<br>кг ±2</th>
			<th>Пластификатор,<br>кг ±0.1</th>
			<th>Вода, л</th>
			<th colspan="2">№ кассеты</th>
			<th>Недолив</th>
			<th>Оператор</th>
			<th></th>
		</tr>
	</thead>

<?
// Получаем список дат и противовесов и кол-во замесов на эти даты
$query = "
	SELECT PB.PB_ID
		,PB.year
		,PB.cycle
		,CW.item
		,PB.CW_ID
		,PB.batches
		,PB.fact_batches
		,MIN(TIMESTAMP(LB.batch_date, LB.batch_time)) time
		,IF(COUNT(LCT24.LCT_ID)=COUNT(IF(LB.test = 1, LB.LB_ID, NULL)), CEIL(COUNT(LCT24.LCT_ID) * 2 / 3), 0) `24tests`
		,IF(COUNT(LCT72.LCT_ID)=COUNT(IF(LB.test = 1, LB.LB_ID, NULL)), CEIL(COUNT(LCT72.LCT_ID) * 2 / 3), 0) `72tests`
	FROM plan__Batch PB
	JOIN CounterWeight CW ON CW.CW_ID = PB.CW_ID
	JOIN list__Batch LB ON LB.PB_ID = PB.PB_ID
	LEFT JOIN list__CubeTest LCT24 ON LCT24.LB_ID = LB.LB_ID AND LCT24.delay = 24
	LEFT JOIN list__CubeTest LCT72 ON LCT72.LB_ID = LB.LB_ID AND LCT72.delay = 72
	WHERE PB.PB_ID IN (SELECT PB_ID FROM list__Batch WHERE YEARWEEK(batch_date, 1) LIKE '{$_GET["week"]}' GROUP BY PB_ID)
		".($_GET["CW_ID"] ? "AND PB.CW_ID={$_GET["CW_ID"]}" : "")."
		".($_GET["CB_ID"] ? "AND PB.CW_ID IN (SELECT CW_ID FROM CounterWeight WHERE CB_ID = {$_GET["CB_ID"]})" : "")."
	GROUP BY PB.PB_ID
	ORDER BY PB.cycle, time
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	// Находим средний результат испытаний кубиков
	$query = "
		SELECT ROUND(AVG(SUB.pressure)) `pressure`
		FROM (
			SELECT LCT.pressure
			FROM list__CubeTest LCT
			JOIN list__Batch LB ON LB.LB_ID = LCT.LB_ID AND LB.PB_ID = {$row['PB_ID']}
			WHERE LCT.delay = 24
			ORDER BY LCT.pressure DESC
			LIMIT {$row['24tests']}
		) SUB
		UNION ALL
		SELECT ROUND(AVG(SUB.pressure)) `pressure`
		FROM (
			SELECT LCT.pressure
			FROM list__CubeTest LCT
			JOIN list__Batch LB ON LB.LB_ID = LCT.LB_ID AND LB.PB_ID = {$row['PB_ID']}
			WHERE LCT.delay = 72
			ORDER BY LCT.pressure DESC
			LIMIT {$row['72tests']}
		) SUB
	";
	$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$test24 = mysqli_result($subres,0,'pressure');
	$test72 = mysqli_result($subres,1,'pressure');

	$cnt = $row["fact_batches"];
	echo "<tbody id='PB{$row["PB_ID"]}' style='text-align: center; border-bottom: 2px solid #333; ".(($cycle and $cycle != $row["cycle"]) ? " border-top: 10px solid #333;" : "")."'>";
	$cycle = $row["cycle"];

	$query = "
		SELECT LB.LB_ID
			,OP.name
			,DATE_FORMAT(LB.batch_date, '%d.%m') batch_date_format
			,DATE_FORMAT(LB.batch_time, '%H:%i') batch_time_format
			,LB.io_density
			,LB.sn_density
			,LB.mix_density
			,LB.temp
			,IF(ABS(19 - LB.temp) <= 4, NULL, IF(LB.temp - 19 > 4, LB.temp - 23, LB.temp - 15)) temp_diff
			,LB.iron_oxide
			,LB.sand
			,LB.crushed_stone
			,LB.cement
			,LB.plasticizer
			,LB.water
			,SUM(LF.underfilling) underfilling
			,LB.test
			,mix_letter(LB.LB_ID) letter
			,mix_id(LB.LB_ID) MF_ID
			,mix_diff({$row["CW_ID"]}, LB.mix_density) mix_diff
			,mix_io_diff(mix_id(LB.LB_ID), LB.iron_oxide) io_diff
			,mix_sn_diff(mix_id(LB.LB_ID), LB.sand) sn_diff
			,mix_cs_diff(mix_id(LB.LB_ID), LB.crushed_stone) cs_diff
			,mix_cm_diff(mix_id(LB.LB_ID), LB.cement) cm_diff
			,mix_pl_diff(mix_id(LB.LB_ID), LB.plasticizer) pl_diff
			,mix_wt_diff(mix_id(LB.LB_ID), LB.water) wt_diff
		FROM list__Batch LB
		JOIN list__Filling LF ON LF.LB_ID = LB.LB_ID
		JOIN Operator OP ON OP.OP_ID = LB.OP_ID
		WHERE LB.PB_ID = {$row["PB_ID"]}
		GROUP BY LB.LB_ID
		ORDER BY LB.batch_date, LB.batch_time
	";
	$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $subrow = mysqli_fetch_array($subres) ) {
		// Получаем список кассет
		$query = "
			SELECT LF.cassette
				,YEARWEEK(LO.opening_time, 1) o_week
				,LO.LO_ID
				,SUM(1) dbl
			FROM list__Filling LF
			LEFT JOIN list__Filling SLF ON SLF.cassette = LF.cassette AND SLF.lf_date = LF.lf_date
			LEFT JOIN list__Opening LO ON LO.LF_ID = LF.LF_ID
			WHERE LF.LB_ID = {$subrow["LB_ID"]}
			GROUP BY LF.LF_ID
			ORDER BY LF.LF_ID
		";
		$subsubres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		$cassette = "";
		while( $subsubrow = mysqli_fetch_array($subsubres) ) {
			if( $subsubrow["LO_ID"] ) {
				$cassette .= "<a href='opening.php?week={$subsubrow["o_week"]}#{$subsubrow["LO_ID"]}' title='Расформовка' target='_blank'><b class='cassette' style='".($subsubrow["dbl"] > 1 ? "color: red;" : "")."'>{$subsubrow["cassette"]}</b></a>";
			}
			else {
				$cassette .= "<b class='cassette' style='".($subsubrow["dbl"] > 1 ? "color: red;" : "")."'>{$subsubrow["cassette"]}</b>";
			}
		}

		echo "<tr id='{$subrow["LB_ID"]}'>";

		// Выводим общую ячейку с датой кодом
		if( $cnt ) {
			echo "
				<td rowspan='{$cnt}' class='bg-gray'>
					{$row["year"]}
					<h1>{$row["cycle"]}</h1>
					<b>{$row["item"]}</b><br>Замесов: <b>{$cnt}</b><br>
					<i class='fas fa-cube'></i>24: <b>{$test24}</b><br>
					<i class='fas fa-cube'></i>72: <b>{$test72}</b><br>
				</td>
			";
		}
		?>
		<td><?=$subrow["batch_date_format"]?> <?=$subrow["batch_time_format"]?></td>
		<td><span class="nowrap"><?=$subrow["MF_ID"] ? "<a href='mix_formula.php#{$subrow["MF_ID"]}' target='_blank'><b>{$subrow["letter"]}</b></a> " : "<i class='fas fa-exclamation-triangle' style='color: red;' title='Подходящий рецепт не обнаружен'></i> "?><?=($subrow["io_density"] ? "<i title='Плотность окалины' style='text-decoration: underline; background: #a52a2a80;'>".($subrow["io_density"]/1000)."</i> " : "")?><?=($subrow["sn_density"] ? "<i title='Плотность КМП' style='text-decoration: underline; background: #f4a46082;'>".($subrow["sn_density"]/1000)."</i> " : "")?></span></td>
				<td><?=$subrow["mix_density"]/1000?> <?=$subrow["test"] ? "&nbsp;<i class='fas fa-cube'></i>" : ""?><?=($subrow["mix_diff"] ? "<font style='font-size: .8em; display: block; line-height: .4em;' color='red'>".($subrow["mix_diff"] > 0 ? " +" : " ").($subrow["mix_diff"]/1000)."</font>" : "")?></td>
				<td><?=$subrow["temp"]?><?=($subrow["temp_diff"] ? "<font style='font-size: .8em; display: block; line-height: .4em;' color='red'>".($subrow["temp_diff"] > 0 ? " +" : " ").($subrow["temp_diff"])."</font>" : "")?></td>
				<td style="background: #a52a2a80; <?=($subrow["MF_ID"] ? "" : "color: red;")?>"><?=$subrow["iron_oxide"]?><?=($subrow["io_diff"] ? "<font style='font-size: .8em; display: block; line-height: .4em;' color='red'>".($subrow["io_diff"] > 0 ? " +" : " ").($subrow["io_diff"])."</font>" : "")?></td>
				<td style="background: #f4a46082; <?=($subrow["MF_ID"] ? "" : "color: red;")?>"><?=$subrow["sand"]?><?=($subrow["sn_diff"] ? "<font style='font-size: .8em; display: block; line-height: .4em;' color='red'>".($subrow["sn_diff"] > 0 ? " +" : " ").($subrow["sn_diff"])."</font>" : "")?></td>
				<td style="background: #8b45137a; <?=($subrow["MF_ID"] ? "" : "color: red;")?>"><?=$subrow["crushed_stone"]?><?=($subrow["cs_diff"] ? "<font style='font-size: .8em; display: block; line-height: .4em;' color='red'>".($subrow["cs_diff"] > 0 ? " +" : " ").($subrow["cs_diff"])."</font>" : "")?></td>
				<td style="background: #7080906b; <?=($subrow["MF_ID"] ? "" : "color: red;")?>"><?=$subrow["cement"]?><?=($subrow["cm_diff"] ? "<font style='font-size: .8em; display: block; line-height: .4em;' color='red'>".($subrow["cm_diff"] > 0 ? " +" : " ").($subrow["cm_diff"])."</font>" : "")?></td>
				<td style="background: #80800080; <?=($subrow["MF_ID"] ? "" : "color: red;")?>"><?=$subrow["plasticizer"]?><?=($subrow["pl_diff"] ? "<font style='font-size: .8em; display: block; line-height: .4em;' color='red'>".($subrow["pl_diff"] > 0 ? " +" : " ").($subrow["pl_diff"])."</font>" : "")?></td>
				<td style="background: #1e90ff85; <?=($subrow["MF_ID"] ? "" : "color: red;")?>"><?=$subrow["water"]?><?=($subrow["wt_diff"] ? "<font style='font-size: .8em; display: block; line-height: .4em;' color='red'>".($subrow["wt_diff"])."</font>" : "")?></td>
				<td colspan="2" class="nowrap"><?=$cassette?></td>
				<td><?=$subrow["underfilling"]?></td>
				<td><?=$subrow["name"]?></td>
				<?
				// Выводим общую ячейку с кнопкой редактирования
				if( $cnt ) {
					echo "<td rowspan='{$cnt}'><a href='#' class='add_filling' PB_ID='{$row["PB_ID"]}' title='Изменить чеклист оператора'><i class='fa fa-pencil-alt fa-lg'></i></a></td>";
					$cnt = 0;
				}
				?>
			</tr>
		<?
	}
	echo "</tbody>";
}
?>
</table>

<?
include "footer.php";
?>
