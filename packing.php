<?
include "config.php";
$title = 'Упаковка';
include "header.php";
include "./forms/packing_form.php";

// Если в фильтре не установлен период, показываем последние 7 дней
if( !$_GET["batch_date_from"] and !$_GET["batch_date_to"] ) {
	if( !$_GET["p_date_from"] ) {
		$date = new DateTime('-6 days');
		$_GET["p_date_from"] = date_format($date, 'Y-m-d');
	}
	if( !$_GET["p_date_to"] ) {
		$date = new DateTime('-0 days');
		$_GET["p_date_to"] = date_format($date, 'Y-m-d');
	}
}
?>

<!--Фильтр-->
<div id="filter">
	<h3>Фильтр</h3>
	<form method="get" style="position: relative;">
		<a href="/opening.php" style="position: absolute; top: 10px; right: 10px;" class="button">Сброс</a>

		<div class="nowrap" style="margin-bottom: 10px;">
			<span style="display: inline-block; width: 200px;">Дата упаковки между:</span>
			<input name="p_date_from" type="date" value="<?=$_GET["p_date_from"]?>" class="<?=$_GET["p_date_from"] ? "filtered" : ""?>">
			<input name="p_date_to" type="date" value="<?=$_GET["p_date_to"]?>" class="<?=$_GET["p_date_to"] ? "filtered" : ""?>">
			<i class="fas fa-question-circle" title="По умолчанию устанавливаются последние 7 дней."></i>
		</div>

		<div class="nowrap" style="margin-bottom: 10px;">
			<span style="display: inline-block; width: 200px;">Дата заливки между:</span>
			<input name="batch_date_from" type="date" value="<?=$_GET["batch_date_from"]?>" class="<?=$_GET["batch_date_from"] ? "filtered" : ""?>">
			<input name="batch_date_to" type="date" value="<?=$_GET["batch_date_to"]?>" class="<?=$_GET["batch_date_to"] ? "filtered" : ""?>">
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
					<label style="text-decoration: underline;" class="<?=$_GET["int120"] ? "filtered" : ""?>">
						Менее 120 часов с момента заливки:
						<input type="checkbox" name="int120" value="1" <?=$_GET["int120"] ? "checked" : ""?>>
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
			<th colspan="2">Упаковка</th>
			<th rowspan="2"><i class="far fa-lg fa-hourglass" title="Интервал в часах с моента заливки."></i></th>
			<th rowspan="2">№ поста</th>
			<th colspan="4">Кол-во брака, шт</th>
			<th rowspan="2">Противовес</th>
			<th rowspan="2">Дата замеса</th>
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
		</tr>
	</thead>
	<tbody style="text-align: center;">

<?
$query = "
	SELECT LP.LP_ID
		,LP.p_post
		,DATE_FORMAT(LP.p_date, '%d.%m.%y') p_date
		,DATE_FORMAT(LP.p_time, '%H:%i') p_time
		,p_interval(LP.LP_ID) p_interval
		,LP.p_not_spill
		,LP.p_crack
		,LP.p_chipped
		,LP.p_def_form
		,DATE_FORMAT(LB.batch_date, '%d.%m.%y') batch_date_format
		,LF.cassette
		,CW.item
		,LB.batch_date
		,LB.CW_ID
		,LB.LB_ID
		,LO.LO_ID
		,LO.o_date
	FROM list__Packing LP
	LEFT JOIN list__Filling LF ON LF.LF_ID = LP.LF_ID
	LEFT JOIN list__Batch LB ON LB.LB_ID = LF.LB_ID
	LEFT JOIN CounterWeight CW ON CW.CW_ID = LB.CW_ID
	LEFT JOIN list__Opening LO ON LO.LF_ID = LF.LF_ID
	WHERE 1
		".($_GET["p_date_from"] ? "AND LP.p_date >= '{$_GET["p_date_from"]}'" : "")."
		".($_GET["p_date_to"] ? "AND LP.p_date <= '{$_GET["p_date_to"]}'" : "")."
		".($_GET["batch_date_from"] ? "AND LB.batch_date >= '{$_GET["batch_date_from"]}'" : "")."
		".($_GET["batch_date_to"] ? "AND LB.batch_date <= '{$_GET["batch_date_to"]}'" : "")."
		".($_GET["CW_ID"] ? "AND LB.CW_ID={$_GET["CW_ID"]}" : "")."
		".($_GET["CB_ID"] ? "AND LB.CW_ID IN (SELECT CW_ID FROM CounterWeight WHERE CB_ID = {$_GET["CB_ID"]})" : "")."
		".($_GET["int120"] ? "AND p_interval(LP.LP_ID) < 120" : "")."
		".($_GET["not_spill"] ? "AND LP.p_not_spill" : "")."
		".($_GET["crack"] ? "AND LP.p_crack" : "")."
		".($_GET["chipped"] ? "AND LP.p_chipped" : "")."
		".($_GET["def_form"] ? "AND LP.p_def_form" : "")."
	ORDER BY LP.p_date DESC, LP.p_time, LP.p_post
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	if( $row["LO_ID"] ) {
		$cassette = "<a href='opening.php?o_date_from={$row["o_date"]}&o_date_to={$row["o_date"]}#{$row["LO_ID"]}' title='Расформовка' target='_blank'><b class='cassette'>{$row["cassette"]}</b></a>";
	}
	else {
		$cassette = "<b class='cassette'>{$row["cassette"]}</b>";
	}
	?>
	<tr id="<?=$row["LP_ID"]?>">
		<td><?=$row["p_date"]?></td>
		<td><?=$row["p_time"]?></td>
		<td <?=($row["p_interval"] < 120 ? "class='error'" : "")?>><?=$row["p_interval"]?></td>
		<td><?=$row["p_post"]?></td>
		<td style="color: red;"><?=$row["p_not_spill"]?></td>
		<td style="color: red;"><?=$row["p_crack"]?></td>
		<td style="color: red;"><?=$row["p_chipped"]?></td>
		<td style="color: red;"><?=$row["p_def_form"]?></td>
		<td class="bg-gray"><?=$row["item"]?></td>
		<td class="bg-gray"><a href="checklist.php?date_from=<?=$row["batch_date"]?>&date_to=<?=$row["batch_date"]?>&CW_ID=<?=$row["CW_ID"]?>#<?=$row["LB_ID"]?>" title="Замес" target="_blank"><?=$row["batch_date_format"]?></a></td>
		<td class="bg-gray"><?=$cassette?></td>
		<td><a href="#" class="add_packing" LP_ID="<?=$row["LP_ID"]?>" title="Изменить данные упаковки"><i class="fa fa-pencil-alt fa-lg"></i></a></td>
	</tr>
	<?
}
?>

	</tbody>
</table>

<div id="add_btn" class="add_packing" p_date="<?=$_GET["p_date"]?>" p_post="<?=$_GET["p_post"]?>" title="Внести данные упаковки "></div>

<script>
	$(function() {
		// При выборе даты заливки сбрасываются даты упаковки
		$('input[name="batch_date_from"]').change(function() {
			$('input[name="p_date_from"]').val('');
			$('input[name="p_date_to"]').val('');
		});
		$('input[name="batch_date_to"]').change(function() {
			$('input[name="p_date_from"]').val('');
			$('input[name="p_date_to"]').val('');
		});
		// При выборе даты упаковки сбрасываются даты заливки
		$('input[name="p_date_from"]').change(function() {
			$('input[name="batch_date_from"]').val('');
			$('input[name="batch_date_to"]').val('');
		});
		$('input[name="p_date_to"]').change(function() {
			$('input[name="batch_date_from"]').val('');
			$('input[name="batch_date_to"]').val('');
		});
	});
</script>

<?
include "footer.php";
?>
