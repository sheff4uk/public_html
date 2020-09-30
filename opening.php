<?
include "config.php";
$title = 'Расформовка';
include "header.php";
include "./forms/opening_form.php";

// Если в фильтре не установлен период, показываем последние 7 дней
if( !$_GET["pb_date_from"] and !$_GET["pb_date_to"] ) {
	if( !$_GET["o_date_from"] ) {
		$date = new DateTime('-6 days');
		$_GET["o_date_from"] = date_format($date, 'Y-m-d');
	}
	if( !$_GET["o_date_to"] ) {
		$date = new DateTime('-0 days');
		$_GET["o_date_to"] = date_format($date, 'Y-m-d');
	}
}
?>

<!--Фильтр-->
<div id="filter">
	<h3>Фильтр</h3>
	<form method="get" style="position: relative;">
		<a href="/opening.php" style="position: absolute; top: 10px; right: 10px;" class="button">Сброс</a>

		<div class="nowrap" style="margin-bottom: 10px;">
			<span style="display: inline-block; width: 200px;">Дата расформовки между:</span>
			<input name="o_date_from" type="date" value="<?=$_GET["o_date_from"]?>" class="<?=$_GET["o_date_from"] ? "filtered" : ""?>">
			<input name="o_date_to" type="date" value="<?=$_GET["o_date_to"]?>" class="<?=$_GET["o_date_to"] ? "filtered" : ""?>">
			<i class="fas fa-question-circle" title="По умолчанию устанавливаются последние 7 дней."></i>
		</div>

		<div class="nowrap" style="margin-bottom: 10px;">
			<span style="display: inline-block; width: 200px;">Дата заливки между:</span>
			<input name="pb_date_from" type="date" value="<?=$_GET["pb_date_from"]?>" class="<?=$_GET["pb_date_from"] ? "filtered" : ""?>">
			<input name="pb_date_to" type="date" value="<?=$_GET["pb_date_to"]?>" class="<?=$_GET["pb_date_to"] ? "filtered" : ""?>">
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

//		$('#filter input[name="date_from"]').change(function() {
//			var val = $(this).val();
//			$('#filter input[name="date_to"]').val(val);
//		});
	});
</script>

<table class="main_table">
	<thead>
		<tr>
			<th colspan="2">Расформовка</th>
			<th rowspan="2"><i class="far fa-lg fa-hourglass" title="Интервал в часах с моента заливки."></i></th>
			<th rowspan="2">№ поста</th>
			<th colspan="4">Кол-во брака, шт</th>
			<th colspan="3">Взвешивания, кг ±3%</th>
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
		,IF(ABS(LO.w1_diff) <= 30, NULL, IF(LO.w1_diff > 30, LO.w1_diff - 30, LO.w1_diff + 30)) w1_diff
		,IF(ABS(LO.w2_diff) <= 30, NULL, IF(LO.w2_diff > 30, LO.w2_diff - 30, LO.w2_diff + 30)) w2_diff
		,IF(ABS(LO.w3_diff) <= 30, NULL, IF(LO.w3_diff > 30, LO.w3_diff - 30, LO.w3_diff + 30)) w3_diff
		,LO.w1_error
		,LO.w2_error
		,LO.w3_error
		,DATE_FORMAT(PB.pb_date, '%d.%m.%y') pb_date_format
		,LF.cassette
		,CW.item
		,PB.pb_date
		,PB.CW_ID
		,LB.LB_ID
		,LP.LP_ID
		,LP.p_date
	FROM list__Opening LO
	LEFT JOIN list__Filling LF ON LF.LF_ID = LO.LF_ID
	LEFT JOIN list__Batch LB ON LB.LB_ID = LF.LB_ID
	LEFT JOIN plan__Batch PB ON PB.PB_ID = LB.PB_ID
	LEFT JOIN CounterWeight CW ON CW.CW_ID = PB.CW_ID
	LEFT JOIN list__Packing LP ON LP.LF_ID = LF.LF_ID
	WHERE 1
		".($_GET["o_date_from"] ? "AND LO.o_date >= '{$_GET["o_date_from"]}'" : "")."
		".($_GET["o_date_to"] ? "AND LO.o_date <= '{$_GET["o_date_to"]}'" : "")."
		".($_GET["pb_date_from"] ? "AND PB.pb_date >= '{$_GET["pb_date_from"]}'" : "")."
		".($_GET["pb_date_to"] ? "AND PB.pb_date <= '{$_GET["pb_date_to"]}'" : "")."
		".($_GET["CW_ID"] ? "AND PB.CW_ID={$_GET["CW_ID"]}" : "")."
		".($_GET["CB_ID"] ? "AND PB.CW_ID IN (SELECT CW_ID FROM CounterWeight WHERE CB_ID = {$_GET["CB_ID"]})" : "")."
		".($_GET["int24"] ? "AND o_interval(LO.LO_ID) < 24" : "")."
		".($_GET["not_spec"] ? "AND (LO.w1_error OR LO.w2_error OR LO.w3_error)" : "")."
		".($_GET["not_spill"] ? "AND LO.o_not_spill" : "")."
		".($_GET["crack"] ? "AND LO.o_crack" : "")."
		".($_GET["chipped"] ? "AND LO.o_chipped" : "")."
		".($_GET["def_form"] ? "AND LO.o_def_form" : "")."
	ORDER BY LO.o_date DESC, LO.o_time, LO.o_post
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	if( $row["LP_ID"] ) {
		$cassette = "<a href='packing.php?p_date_from={$row["p_date"]}&p_date_to={$row["p_date"]}#{$row["LP_ID"]}' title='Упаковка' target='_blank'><b class='cassette'>{$row["cassette"]}</b></a>";
	}
	else {
		$cassette = "<b class='cassette'>{$row["cassette"]}</b>";
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
		<td><?=$row["weight1"]/1000?><?=($row["w1_error"] ? "<font style='font-size: .8em; display: block; line-height: .4em;' color='red'>".($row["w1_diff"] > 0 ? " +" : " ").($row["w1_diff"]/10)."%</font>" : "")?></td>
		<td><?=$row["weight2"]/1000?><?=($row["w2_error"] ? "<font style='font-size: .8em; display: block; line-height: .4em;' color='red'>".($row["w2_diff"] > 0 ? " +" : " ").($row["w2_diff"]/10)."%</font>" : "")?></td>
		<td><?=$row["weight3"]/1000?><?=($row["w3_error"] ? "<font style='font-size: .8em; display: block; line-height: .4em;' color='red'>".($row["w3_diff"] > 0 ? " +" : " ").($row["w3_diff"]/10)."%</font>" : "")?></td>
		<td class="bg-gray"><?=$row["item"]?></td>
		<td class="bg-gray"><a href="checklist.php?date_from=<?=$row["pb_date"]?>&date_to=<?=$row["pb_date"]?>&CW_ID=<?=$row["CW_ID"]?>#<?=$row["LB_ID"]?>" title="Заливка" target="_blank"><?=$row["pb_date_format"]?></a></td>
		<td class="bg-gray"><?=$cassette?></td>
		<td><a href="#" class="add_opening" LO_ID="<?=$row["LO_ID"]?>" title="Изменить данные расформовки"><i class="fa fa-pencil-alt fa-lg"></i></a></td>
	</tr>
	<?
}
?>

	</tbody>
</table>

<div id="add_btn" class="add_opening" o_date="<?=$_GET["o_date"]?>" o_post="<?=$_GET["o_post"]?>" title="Внести данные расформовки"></div>

<script>
	$(function() {
		// При выборе даты заливки сбрасываются даты расформовки
		$('input[name="pb_date_from"]').change(function() {
			$('input[name="o_date_from"]').val('');
			$('input[name="o_date_to"]').val('');
		});
		$('input[name="pb_date_to"]').change(function() {
			$('input[name="o_date_from"]').val('');
			$('input[name="o_date_to"]').val('');
		});
		// При выборе даты расформовки сбрасываются даты заливки
		$('input[name="o_date_from"]').change(function() {
			$('input[name="pb_date_from"]').val('');
			$('input[name="pb_date_to"]').val('');
		});
		$('input[name="o_date_to"]').change(function() {
			$('input[name="pb_date_from"]').val('');
			$('input[name="pb_date_to"]').val('');
		});
	});
</script>

<?
include "footer.php";
?>
