<?php
include "config.php";
$title = 'Расформовка';
include "header.php";

// Проверка прав на доступ к экрану
if( !in_array('filling_opening', $Rights) ) {
	header($_SERVER['SERVER_PROTOCOL'].' 403 Forbidden');
	die('Недостаточно прав для совершения операции');
}

include "./forms/opening_form.php";

// Если в фильтре не установлена неделя, показываем текущую
if( !$_GET["week"] ) {
	$query = "SELECT YEARWEEK(CURDATE(), 1) week";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$row = mysqli_fetch_array($res);
	$_GET["week"] = $row["week"];
}

$CAS = isset($_GET["CAS"]) ? $_GET["CAS"] : array();
$CASs = implode(",", $CAS);

// Если не выбран участок, берем из сессии
if( !$_GET["F_ID"] ) {
	$_GET["F_ID"] = $_SESSION['F_ID'];
}

// Начинаем собирать ошибки
//$query = "
//	SELECT LB.LB_ID
//		,LF.cassette
//		,DATE_FORMAT(LF.filling_time, '%d.%m.%y %H:%i') filling_time_format
//		,YEARWEEk(LF.filling_time, 1) week
//	FROM list__Filling LF
//	JOIN list__Batch LB ON LB.LB_ID = LF.LB_ID
//	LEFT JOIN list__Opening LO ON LO.LF_ID = LF.LF_ID
//	WHERE 1
//		AND LO.LO_ID IS NULL
//		AND YEARWEEK(LF.filling_time + INTERVAL 1 DAY, 1) LIKE '{$_GET["week"]}'
//		AND(SELECT LF_ID FROM list__Filling WHERE cassette = LF.cassette AND filling_time > LF.filling_time LIMIT 1) IS NOT NULL
//	ORDER BY LF.filling_time
//";
//$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
//while( $row = mysqli_fetch_array($res) ) {
//	$errors .= "<p>Кассета <b class='cassette'>{$row["cassette"]}</b> залита <a href='filling.php?week={$row["week"]}#{$row["LB_ID"]}' target='_blank'>{$row["filling_time_format"]}</a>. Нет данных по расформовке.</p>";
//}
?>

<fieldset id="errors" style="color: red; border-color: red; border-radius: 20px; display: none;">
	<legend><h3>Ошибки:</h3></legend>
	<div></div>
</fieldset>

<!--Фильтр-->
<div id="filter">
	<h3>Фильтр</h3>
	<form method="get" style="position: relative;">
		<a href="/opening.php" style="position: absolute; top: 10px; right: 10px;" class="button">Сброс</a>

		<div class="nowrap" style="margin-bottom: 10px;">
			<span>Участок:</span>
			<select name="F_ID" class="<?=$_GET["F_ID"] ? "filtered" : ""?>" onchange="this.form.submit()">
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

		<div class="nowrap" style="margin-bottom: 10px;">
			<span>Неделя:</span>
			<select name="week" class="<?=$_GET["week"] ? "filtered" : ""?>" onchange="this.form.submit()">
				<?php
				$query = "
					SELECT LEFT(YEARWEEK(CURDATE(), 1), 4) year
					UNION
					SELECT LEFT(YEARWEEK(opening_time, 1), 4) year
					FROM list__Opening
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
							SELECT LEFT(YEARWEEK(opening_time, 1), 4) year
								,YEARWEEK(opening_time, 1) week
								,RIGHT(YEARWEEK(opening_time, 1), 2) week_format
								,DATE_FORMAT(ADDDATE(opening_time, 0-WEEKDAY(opening_time)), '%e %b') WeekStart
								,DATE_FORMAT(ADDDATE(opening_time, 6-WEEKDAY(opening_time)), '%e %b') WeekEnd
							FROM list__Opening
							WHERE LEFT(YEARWEEK(opening_time, 1), 4) = {$row["year"]}
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
				<?php
				$query = "
					SELECT CW.CW_ID, CW.item
					FROM CounterWeight CW
					JOIN MixFormula MF ON MF.CW_ID = CW.CW_ID
						AND MF.F_ID = {$_GET["F_ID"]}
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

<!--
		<div class="nowrap" style="display: inline-block; margin-bottom: 10px; margin-right: 30px;">
			<span>Клиент:</span>
			<select name="CB_ID" class="<?=$_GET["CB_ID"] ? "filtered" : ""?>" style="width: 100px;">
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
-->

		<div class="nowrap" style="display: inline-block; margin-bottom: 10px; margin-right: 30px;">
			<span>№№ Кассет:</span>
			<select name="CAS[]" class="<?=$_GET["CAS"] ? "filtered" : ""?>" style="width: 350px;" multiple>
				<?php
				for ($i = 1; $i <= $cassetts; $i++) {
					$selected = ( isset($_GET["CAS"]) && in_array($i, $_GET["CAS"]) ) ? "selected" : "";
					echo "<option value='{$i}' {$selected}>{$i}</option>";
				}
				?>
			</select>
		</div>

<!--
		<div style="margin-bottom: 10px;">
			<fieldset>
				<legend>Нарушение тех. процесса: (условие ИЛИ)</legend>
				<div class="nowrap" style="display: inline-block; margin-bottom: 10px; margin-right: 30px;">
					<label style="text-decoration: underline;" class="<?=$_GET["int24"] ? "filtered" : ""?>">
						Менее 24 часов с момента заливки:
						<input type="checkbox" name="int24" value="1" <?=$_GET["int24"] ? "checked" : ""?>>
					</label>
				</div>

				<div class="nowrap" style="display: inline-block; margin-bottom: 10px; margin-right: 30px;">
					<label style="text-decoration: underline;" class="<?=$_GET["not_spec"] ? "filtered" : ""?>">
						Несоответствие по весу:
						<input type="checkbox" name="not_spec" value="1" <?=$_GET["not_spec"] ? "checked" : ""?>>
					</label>
				</div>
			</fieldset>
		</div>
-->

<!--
		<div style="margin-bottom: 10px;">
			<fieldset>
				<legend>Брак: (условие ИЛИ)</legend>

				<div class="nowrap" style="display: inline-block; margin-bottom: 10px; margin-right: 30px;">
					<label style="text-decoration: underline;" class="<?=$_GET["not_spill"] ? "filtered" : ""?>">
						Непролив:
						<input type="checkbox" name="not_spill" value="1" <?=$_GET["not_spill"] ? "checked" : ""?>>
					</label>
				</div>

				<div class="nowrap" style="display: inline-block; margin-bottom: 10px; margin-right: 30px;">
					<label style="text-decoration: underline;" class="<?=$_GET["crack"] ? "filtered" : ""?>">
						Мех. трещина:
						<input type="checkbox" name="crack" value="1" <?=$_GET["crack"] ? "checked" : ""?>>
					</label>
				</div>

				<div class="nowrap" style="display: inline-block; margin-bottom: 10px; margin-right: 30px;">
					<label style="text-decoration: underline;" class="<?=$_GET["crack_drying"] ? "filtered" : ""?>">
						Усад. трещина:
						<input type="checkbox" name="crack_drying" value="1" <?=$_GET["crack_drying"] ? "checked" : ""?>>
					</label>
				</div>

				<div class="nowrap" style="display: inline-block; margin-bottom: 10px; margin-right: 30px;">
					<label style="text-decoration: underline;" class="<?=$_GET["chipped"] ? "filtered" : ""?>">
						Скол:
						<input type="checkbox" name="chipped" value="1" <?=$_GET["chipped"] ? "checked" : ""?>>
					</label>
				</div>

				<div class="nowrap" style="display: inline-block; margin-bottom: 10px; margin-right: 30px;">
					<label style="text-decoration: underline;" class="<?=$_GET["def_form"] ? "filtered" : ""?>">
						Дефект формы:
						<input type="checkbox" name="def_form" value="1" <?=$_GET["def_form"] ? "checked" : ""?>>
					</label>
				</div>

				<div class="nowrap" style="display: inline-block; margin-bottom: 10px; margin-right: 30px;">
					<label style="text-decoration: underline;" class="<?=$_GET["def_assembly"] ? "filtered" : ""?>">
						Дефект сборки:
						<input type="checkbox" name="def_assembly" value="1" <?=$_GET["def_assembly"] ? "checked" : ""?>>
					</label>
				</div>

			</fieldset>
		</div>
-->

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
	$(function() {
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

		$('select[name="CAS[]"]').select2({
			placeholder: "Выберите интересующие номера кассет",
			allowClear: true,
			//closeOnSelect: false,
			scrollAfterSelect: false,
			language: "ru"
		});
	});
</script>

<style>
	.diff_alert {
		font-size: .8em;
		display: block;
		line-height: .4em;
		color: red;
		/* display: none; */
	}
</style>

<table class="main_table">
	<thead>
		<tr>
			<th colspan="2">Расформовка</th>
			<th rowspan="2">№ кассеты</th>
			<th rowspan="2"><i class="far fa-lg fa-hourglass" title="Интервал в часах с моента заливки."></i></th>
			<th colspan="2" rowspan="2">Брак</th>
			<th rowspan="2">Деталей залито/зарегистрировано</th>
			<th rowspan="2">Средний вес, г</th>
			<th rowspan="2">Куб раствора, г</th>
			<th rowspan="2">Противовес</th>
			<th rowspan="2">Дата заливки</th>
<!--			<th rowspan="2"></th>-->
		</tr>
		<tr>
			<th>Дата</th>
			<th>Время</th>
		</tr>
	</thead>
	<tbody style="text-align: center;">

<?php
$query = "
	SELECT LO.LO_ID
		,DATE_FORMAT(LO.opening_time, '%d.%m.%y') o_date
		,DATE_FORMAT(LO.opening_time, '%H:%i') o_time
		,o_interval(LO.LO_ID) o_interval
		,LOD.not_spill
		,LOD.crack
		,LOD.crack_drying
		,LOD.chipped
		,LOD.def_form
		,LOD.def_assembly
		,LOD.reject
		,(PB.in_cassette - LF.underfilling) in_cassette
		,COUNT(LW.nextID) cnt_weight
			#,MIN(LW.weight) min_weight
		,ROUND(AVG(LW.weight)) avg_weight
			#,MAX(LW.weight) max_weight
			#,IF(MIN(LW.weight) BETWEEN ROUND(CW.min_weight/100*101) AND ROUND(CW.max_weight/100*101), 0, IF(MIN(LW.weight) > ROUND(CW.max_weight/100*101), MIN(LW.weight) - ROUND(CW.max_weight/100*101), MIN(LW.weight) - ROUND(CW.min_weight/100*101))) min_diff
		,IF(ROUND(AVG(LW.weight)) BETWEEN ROUND(CW.min_weight/100*101) AND ROUND(CW.max_weight/100*101), 0, IF(ROUND(AVG(LW.weight)) > ROUND(CW.max_weight/100*101), ROUND(AVG(LW.weight)) - ROUND(CW.max_weight/100*101), ROUND(AVG(LW.weight)) - ROUND(CW.min_weight/100*101))) avg_diff
			#,IF(MAX(LW.weight) BETWEEN ROUND(CW.min_weight/100*101) AND ROUND(CW.max_weight/100*101), 0, IF(MAX(LW.weight) > ROUND(CW.max_weight/100*101), MAX(LW.weight) - ROUND(CW.max_weight/100*101), MAX(LW.weight) - ROUND(CW.min_weight/100*101))) max_diff
		,DATE_FORMAT(LB.batch_date, '%d.%m.%y') batch_date_format
		,LO.cassette
		,CW.item
		,YEARWEEK(LB.batch_date, 1) lb_week
		,PB.CW_ID
		,LB.LB_ID
		,LB.mix_density
		,mix_diff(MF.MF_ID, LB.mix_density) mix_diff
	FROM list__Opening LO
	JOIN list__Filling LF ON LF.LF_ID = LO.LF_ID
	JOIN list__Batch LB ON LB.LB_ID = LF.LB_ID
	JOIN plan__Batch PB ON PB.PB_ID = LB.PB_ID
	JOIN CounterWeight CW ON CW.CW_ID = PB.CW_ID
	JOIN MixFormula MF ON MF.CW_ID = CW.CW_ID
		AND MF.F_ID = PB.F_ID
	LEFT JOIN list__Opening_def LOD ON LOD.LO_ID = LO.LO_ID
	LEFT JOIN list__Weight LW ON LW.LO_ID = LO.LO_ID
	WHERE PB.F_ID = {$_GET["F_ID"]}
		".($_GET["week"] ? "AND YEARWEEK(LO.opening_time, 1) LIKE '{$_GET["week"]}'" : "")."
		".($_GET["CW_ID"] ? "AND PB.CW_ID={$_GET["CW_ID"]}" : "")."
		AND (
			".(($_GET["int24"] or $_GET["not_spec"]) ? "0" : "1")."
			".($_GET["int24"] ? "OR o_interval(LO.LO_ID) < 24" : "")."
			".($_GET["not_spec"] ? "OR (NOT WeightSpec(PB.CW_ID, LO.weight1) OR NOT WeightSpec(PB.CW_ID, LO.weight2) OR NOT WeightSpec(PB.CW_ID, LO.weight3))" : "")."
		)
		AND (
			".(($_GET["not_spill"] or $_GET["crack"] or $_GET["crack_drying"] or $_GET["chipped"] or $_GET["def_form"] or $_GET["def_assembly"] or $_GET["reject"]) ? "0" : "1")."
			".($_GET["not_spill"] ? "OR LO.not_spill" : "")."
			".($_GET["crack"] ? "OR LO.crack" : "")."
			".($_GET["crack_drying"] ? "OR LO.crack_drying" : "")."
			".($_GET["chipped"] ? "OR LO.chipped" : "")."
			".($_GET["def_form"] ? "OR LO.def_form" : "")."
			".($_GET["def_assembly"] ? "OR LO.def_assembly" : "")."
			".($_GET["reject"] ? "OR LO.reject" : "")."
		)
		".($CASs ? "AND LO.cassette IN({$CASs})" : "")."
	GROUP BY LO.LO_ID
	ORDER BY LO.opening_time
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	// Собираем ошибки номеров кассет
	if($row["dbl"] > 1) {
		$errors .= "<p>Кассета <a href='#{$row["LO_ID"]}'><b class='cassette'>{$row["cassette"]}</b></a> расформована повторно.</p>";
	}

	$cassette = "<b class='cassette' style='".($row["dbl"] > 1 ? "color: red;" : "")."'>{$row["cassette"]}</b>";

	// Итоговая строка в конце дня
	if( $o_date and $o_date != $row["o_date"] ) {
		echo "
			<tr style='background: #333 !important; color: #fff;'>\n
				<td colspan='6'></td>\n
				<td style='line-height: 5px;'><b>{$in_cassette} / {$cnt_weight}</b></td>\n
				<td colspan='4'></td>\n
			</tr>\n
		";
		$in_cassette = 0;
		$cnt_weight = 0;
	}
	$in_cassette += $row["in_cassette"];
	$cnt_weight += $row["cnt_weight"];
	$in_cassette_total += $row["in_cassette"];
	$cnt_weight_total += $row["cnt_weight"];
	?>
	<tr id="<?=$row["LO_ID"]?>" style="<?=(($o_date and $o_date != $row["o_date"] and 0) ? "border-top: 10px solid #333;" : "")?>">
		<td><?=$row["o_date"]?></td>
		<td><?=$row["o_time"]?></td>
		<td><?=$cassette?></td>
		<td style="background: rgb(255,0,0,<?=((24 - $row["o_interval"]) / 10)?>);"><?=$row["o_interval"]?></td>
		<!-- <td><?=$row["o_interval"]?></td> -->
		<td colspan="2" class="nowrap" style="text-align: left;">
			<?=($row["not_spill"] ? "<font color='red'>{$row["not_spill"]}</font> непролив<br>" : "")?>
			<?=($row["crack"] ? "<font color='red'>{$row["crack"]}</font> мех. трещина<br>" : "")?>
			<?=($row["crack_drying"] ? "<font color='red'>{$row["crack_drying"]}</font> усад. трещина<br>" : "")?>
			<?=($row["chipped"] ? "<font color='red'>{$row["chipped"]}</font> скол<br>" : "")?>
			<?=($row["def_form"] ? "<font color='red'>{$row["def_form"]}</font> дефект формы<br>" : "")?>
			<?=($row["def_assembly"] ? "<font color='red'>{$row["def_assembly"]}</font> дефект сборки<br>" : "")?>
			<?=($row["reject"] ? "<font color='red'>{$row["reject"]}</font> брак<br>" : "")?>
		</td>
		<td <?=(abs($row["in_cassette"] - $row["cnt_weight"]) > $row["in_cassette"] / 2 ? "style='color: red;'" : "")?>><?=$row["in_cassette"]?> / <?=$row["cnt_weight"]?></td>
<!--		<td><?=($row["min_weight"] ? $row["min_weight"]/1000 : "")?><?=($row["min_diff"] ? "<font class='diff_alert'>".($row["min_diff"] > 0 ? " +" : " ").($row["min_diff"]/1000)."</font>" : "")?></td>-->
		<td><a href="#" class="edit_transactions" LO_ID="<?=$row["LO_ID"]?>" title="Список регистраций"><?=($row["avg_weight"] ? number_format($row["avg_weight"], 0, ',', '&nbsp;') : "")?><?=($row["avg_diff"] ? "<font class='diff_alert'>".($row["avg_diff"] > 0 ? " +" : " ").number_format($row["avg_diff"], 0, ',', '&nbsp;')."</font>" : "")?></a></td>
<!--		<td><?=($row["max_weight"] ? $row["max_weight"]/1000 : "")?><?=($row["max_diff"] ? "<font class='diff_alert'>".($row["max_diff"] > 0 ? " +" : " ").($row["max_diff"]/1000)."</font>" : "")?></td>-->
		<td class="bg-gray"><?=number_format($row["mix_density"], 0, ',', '&nbsp;')?><?=($row["mix_diff"] ? "<font class='diff_alert'>".($row["mix_diff"] > 0 ? " +" : " ").number_format($row["mix_diff"], 0, ',', '&nbsp;')."</font>" : "")?></td>
		<td class="bg-gray"><?=$row["item"]?></td>
		<td class="bg-gray"><a href="filling.php?F_ID=<?=$_GET["F_ID"]?>&week=<?=$row["lb_week"]?>#<?=$row["LB_ID"]?>" title="Заливка" target="_blank"><?=$row["batch_date_format"]?></a></td>
<!--
		<td>
			<a href="#" class="add_opening" LO_ID="<?=$row["LO_ID"]?>" title="Изменить данные расформовки"><i class="fa fa-pencil-alt fa-lg"></i></a>
		</td>
-->
	</tr>
	<?php
	$o_date = $row["o_date"];
}

// Вставляем итоговую строку последнего дня
if( $in_cassette ) {
	echo "
		<tr style='background: #333 !important; color: #fff;'>\n
			<td colspan='6'></td>\n
			<td style='line-height: 5px;'><b>{$in_cassette} / {$cnt_weight}</b></td>\n
			<td colspan='4'></td>\n
		</tr>\n
	";
}

// Вставляем итоговую строку конце таблицы
if( $in_cassette_total ) {
	echo "
		<tr class='total'>\n
			<td colspan='6'></td>\n
			<td><b>{$in_cassette_total} / {$cnt_weight_total}</b></td>\n
			<td colspan='4'></td>\n
		</tr>\n
	";
}

// Выводим собранные ошибки вверку экрана
if( $errors ) {
	$_SESSION["error"][] = "Обнаружены ошибки в данных. Пожалуйста исправьте.";
?>
<script>
	$(function() {
		$('#errors div').html("<?=$errors?>");
		$('#errors').show('fast');
	});
</script>
<?php
}
?>

	</tbody>
</table>

<?php
include "footer.php";
?>
