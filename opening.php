<?
include "config.php";
$title = 'Расформовка';
include "header.php";
include "./forms/opening_form.php";

// Если в фильтре не установлена неделя, показываем текущую
if( !$_GET["week"] ) {
	$query = "SELECT YEARWEEK(NOW(), 1) week";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$row = mysqli_fetch_array($res);
	$_GET["week"] = $row["week"];
}

// Начинаем собирать ошибки
$query = "
	SELECT LB.LB_ID
		,LF.cassette
		,DATE_FORMAT(LF.lf_date, '%d.%m.%y') lf_date_format
		,DATE_FORMAT(LF.lf_time, '%H:%i') lf_time_format
		,YEARWEEk(LF.lf_date, 1) week
	FROM list__Filling LF
	JOIN list__Batch LB ON LB.LB_ID = LF.LB_ID
	LEFT JOIN list__Opening LO ON LO.LF_ID = LF.LF_ID
	WHERE 1
		AND LO.LO_ID IS NULL
		AND YEARWEEK(LF.lf_date + INTERVAL 1 DAY, 1) LIKE '{$_GET["week"]}'
	HAVING (SELECT LF_ID FROM list__Filling WHERE cassette = LF.cassette AND lf_date > LF.lf_date LIMIT 1) IS NOT NULL
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	$errors .= "<p>Кассета <b class='cassette'>{$row["cassette"]}</b> залита <a href='filling.php?week={$row["week"]}#{$row["LB_ID"]}'>{$row["lf_date_format"]} {$row["lf_time_format"]}</a>. Нет данных по расформовке.</p>";
}
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
			<span>Неделя:</span>
			<select name="week" class="<?=$_GET["week"] ? "filtered" : ""?>" onchange="this.form.submit()">
				<?
				$query = "
					SELECT YEAR(NOW()) year
					UNION
					SELECT YEAR(o_date) year
					FROM list__Opening
					ORDER BY year DESC
				";
				$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
				while( $row = mysqli_fetch_array($res) ) {
					echo "<optgroup label='{$row["year"]}'>";
					$query = "
						SELECT YEARWEEK(NOW(), 1) week
							,WEEK(NOW(), 1) week_format
							,DATE_FORMAT(adddate(NOW(), INTERVAL 2-DAYOFWEEK(NOW()) DAY), '%e %b') WeekStart
							,DATE_FORMAT(adddate(NOW(), INTERVAL 8-DAYOFWEEK(NOW()) DAY), '%e %b') WeekEnd
						UNION
						SELECT YEARWEEK(o_date, 1) week
							,WEEK(o_date, 1) week_format
							,DATE_FORMAT(adddate(o_date, INTERVAL 2-DAYOFWEEK(o_date) DAY), '%e %b') WeekStart
							,DATE_FORMAT(adddate(o_date, INTERVAL 8-DAYOFWEEK(o_date) DAY), '%e %b') WeekEnd
						FROM list__Opening
						WHERE YEAR(o_date) = {$row["year"]}
						GROUP BY week
						ORDER BY week DESC
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
			<i class="fas fa-question-circle" title="По умолчанию устанавливаются текущая неделя."></i>
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

		<div style="margin-bottom: 10px;">
			<fieldset>
				<legend>Нарушение тех. процесса:</legend>
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

		<div style="margin-bottom: 10px;">
			<fieldset>
				<legend>Брак:</legend>

				<div class="nowrap" style="display: inline-block; margin-bottom: 10px; margin-right: 30px;">
					<label style="text-decoration: underline;" class="<?=$_GET["not_spill"] ? "filtered" : ""?>">
						Непролив:
						<input type="checkbox" name="not_spill" value="1" <?=$_GET["not_spill"] ? "checked" : ""?>>
					</label>
				</div>

				<div class="nowrap" style="display: inline-block; margin-bottom: 10px; margin-right: 30px;">
					<label style="text-decoration: underline;" class="<?=$_GET["crack"] ? "filtered" : ""?>">
						Трещина:
						<input type="checkbox" name="crack" value="1" <?=$_GET["crack"] ? "checked" : ""?>>
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
						Дефект форм:
						<input type="checkbox" name="def_form" value="1" <?=$_GET["def_form"] ? "checked" : ""?>>
					</label>
				</div>

			</fieldset>
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
			<th colspan="2">Расформовка</th>
			<th rowspan="2"><i class="far fa-lg fa-hourglass" title="Интервал в часах с моента заливки."></i></th>
			<th rowspan="2">№ поста</th>
			<th colspan="4">Кол-во брака, шт</th>
			<th colspan="3">Взвешивания, кг</th>
			<th rowspan="2">Куб раствора, кг</th>
			<th rowspan="2">Противовес</th>
			<th rowspan="2">Дата заливки</th>
			<th rowspan="2">№ кассеты</th>
			<th rowspan="2"></th>
		</tr>
		<tr>
			<th>Дата</th>
			<th>Время</th>
			<th>Непролив</th>
			<th>Трещина</th>
			<th>Скол</th>
			<th>Дефект форм</th>
			<th>№1</th>
			<th>№2</th>
			<th>№3</th>
		</tr>
	</thead>
	<tbody style="text-align: center;">

<?
$query = "
	SELECT LO.LO_ID
		,LO.o_post
		,DATE_FORMAT(LO.o_date, '%d.%m.%y') o_date
		,DATE_FORMAT(LO.o_time, '%H:%i') o_time
		,o_interval(LO.LO_ID) o_interval
		,LO.o_not_spill
		,LO.o_crack
		,LO.o_chipped
		,LO.o_def_form
		,LO.weight1
		,LO.weight2
		,LO.weight3
		,IF(LO.weight1 BETWEEN CW.min_weight AND CW.max_weight, 0, IF(LO.weight1 > CW.max_weight, LO.weight1 - CW.max_weight, LO.weight1 - CW.min_weight)) w1_diff
		,IF(LO.weight2 BETWEEN CW.min_weight AND CW.max_weight, 0, IF(LO.weight2 > CW.max_weight, LO.weight2 - CW.max_weight, LO.weight2 - CW.min_weight)) w2_diff
		,IF(LO.weight3 BETWEEN CW.min_weight AND CW.max_weight, 0, IF(LO.weight3 > CW.max_weight, LO.weight3 - CW.max_weight, LO.weight3 - CW.min_weight)) w3_diff
		,DATE_FORMAT(PB.pb_date, '%d.%m.%y') pb_date_format
		,LO.cassette
		,CW.item
		,YEARWEEK(PB.pb_date, 1) pb_week
		,PB.CW_ID
		,LB.LB_ID
		,LP.LP_ID
		,LP.p_date
		,LB.mix_density
		,mix_diff(PB.CW_ID, LB.mix_density) mix_diff
		,SUM(1) dbl
	FROM list__Opening LO
	JOIN list__Opening SLO ON SLO.cassette = LO.cassette AND SLO.LF_ID = LO.LF_ID
	LEFT JOIN list__Filling LF ON LF.LF_ID = LO.LF_ID
	LEFT JOIN list__Batch LB ON LB.LB_ID = LF.LB_ID
	LEFT JOIN plan__Batch PB ON PB.PB_ID = LB.PB_ID
	LEFT JOIN CounterWeight CW ON CW.CW_ID = PB.CW_ID
	LEFT JOIN list__Packing LP ON LP.LF_ID = LF.LF_ID
	WHERE 1
		".($_GET["week"] ? "AND YEARWEEK(LO.o_date, 1) LIKE '{$_GET["week"]}'" : "")."
		".($_GET["CW_ID"] ? "AND PB.CW_ID={$_GET["CW_ID"]}" : "")."
		".($_GET["CB_ID"] ? "AND PB.CW_ID IN (SELECT CW_ID FROM CounterWeight WHERE CB_ID = {$_GET["CB_ID"]})" : "")."
		".($_GET["int24"] ? "AND o_interval(LO.LO_ID) < 24" : "")."
		".($_GET["not_spec"] ? "AND (NOT WeightSpec(PB.CW_ID, LO.weight1) OR NOT WeightSpec(PB.CW_ID, LO.weight2) OR NOT WeightSpec(PB.CW_ID, LO.weight3))" : "")."
		".($_GET["not_spill"] ? "AND LO.o_not_spill" : "")."
		".($_GET["crack"] ? "AND LO.o_crack" : "")."
		".($_GET["chipped"] ? "AND LO.o_chipped" : "")."
		".($_GET["def_form"] ? "AND LO.o_def_form" : "")."
	GROUP BY LO.LO_ID
	ORDER BY LO.o_date, LO.o_time, LO.o_post
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	// Собираем ошибки номеров кассет
	if($row["dbl"] > 1) {
		$errors .= "<p>Кассета <a href='#{$row["LO_ID"]}'><b class='cassette'>{$row["cassette"]}</b></a> расформована повторно.</p>";
	}

	if( $row["LP_ID"] ) {
		$cassette = "<a href='packing.php?p_date_from={$row["p_date"]}&p_date_to={$row["p_date"]}#{$row["LP_ID"]}' title='Упаковка' target='_blank'><b class='cassette' style='".($row["dbl"] > 1 ? "color: red;" : "")."'>{$row["cassette"]}</b></a>";
	}
	else {
		$cassette = "<b class='cassette' style='".($row["dbl"] > 1 ? "color: red;" : "")."'>{$row["cassette"]}</b>";
	}
	?>
	<tr id="<?=$row["LO_ID"]?>">
		<td><?=$row["o_date"]?></td>
		<td><?=$row["o_time"]?></td>
		<td <?=($row["o_interval"] < 24 ? "class='error'" : "")?>><?=$row["o_interval"]?></td>
		<td><?=$row["o_post"]?></td>
		<td style="color: red;"><?=$row["o_not_spill"]?></td>
		<td style="color: red;"><?=$row["o_crack"]?></td>
		<td style="color: red;"><?=$row["o_chipped"]?></td>
		<td style="color: red;"><?=$row["o_def_form"]?></td>
		<td><?=$row["weight1"]/1000?><?=($row["w1_diff"] ? "<font style='font-size: .8em; display: block; line-height: .4em;' color='red'>".($row["w1_diff"] > 0 ? " +" : " ").($row["w1_diff"]/1000)."</font>" : "")?></td>
		<td><?=$row["weight2"]/1000?><?=($row["w2_diff"] ? "<font style='font-size: .8em; display: block; line-height: .4em;' color='red'>".($row["w2_diff"] > 0 ? " +" : " ").($row["w2_diff"]/1000)."</font>" : "")?></td>
		<td><?=$row["weight3"]/1000?><?=($row["w3_diff"] ? "<font style='font-size: .8em; display: block; line-height: .4em;' color='red'>".($row["w3_diff"] > 0 ? " +" : " ").($row["w3_diff"]/1000)."</font>" : "")?></td>
		<td class="bg-gray"><?=$row["mix_density"]/1000?><?=($row["mix_diff"] ? "<font style='font-size: .8em; display: block; line-height: .4em;' color='red'>".($row["mix_diff"] > 0 ? " +" : " ").($row["mix_diff"]/1000)."</font>" : "")?></td>
		<td class="bg-gray"><?=$row["item"]?></td>
		<td class="bg-gray"><a href="filling.php?week=<?=$row["pb_week"]?>#<?=$row["LB_ID"]?>" title="Заливка" target="_blank"><?=$row["pb_date_format"]?></a></td>
		<td class="bg-gray"><?=$cassette?></td>
		<td><a href="#" class="add_opening" LO_ID="<?=$row["LO_ID"]?>" title="Изменить данные расформовки"><i class="fa fa-pencil-alt fa-lg"></i></a></td>
	</tr>
	<?
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
<?
}
?>

	</tbody>
</table>

<div id="add_btn" class="add_opening" o_date="<?=$_GET["o_date"]?>" o_post="<?=$_GET["o_post"]?>" title="Внести данные расформовки"></div>

<?
include "footer.php";
?>
