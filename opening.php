<?
include "config.php";
$title = 'Расформовка';
include "header.php";
include "./forms/opening_form.php";

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

<!--Фильтр-->
<div id="filter">
	<h3>Фильтр</h3>
	<form method="get" style="position: relative;">
		<a href="/opening.php" style="position: absolute; top: 10px; right: 10px;" class="button">Сброс</a>

		<div class="nowrap" style="margin-bottom: 10px;">
			<span style="display: inline-block; width: 200px;">Дата расформовки между:</span>
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

//		$('#filter input[name="date_from"]').change(function() {
//			var val = $(this).val();
//			$('#filter input[name="date_to"]').val(val);
//		});
	});
</script>

<table class="main_table">
	<thead>
		<tr>
			<th rowspan="2">Дата</th>
			<th rowspan="2">Время</th>
			<th rowspan="2">№ поста</th>
			<th colspan="4">Кол-во брака, шт</th>
			<th colspan="3">Взвешивания, кг ±3%</th>
			<th rowspan="2">Заливка</th>
			<th rowspan="2"></th>
		</tr>
		<tr>
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
		,LO.o_not_spill
		,LO.o_crack
		,LO.o_chipped
		,LO.o_def_form
		,LO.weight1
		,LO.weight2
		,LO.weight3
		,LO.w1_diff
		,LO.w2_diff
		,LO.w3_diff
		,LO.w1_error
		,LO.w2_error
		,LO.w3_error
		,DATE_FORMAT(LB.batch_date, '%d.%m.%y') batch_date
		,LF.cassette
		,CW.item
	FROM list__Opening LO
	LEFT JOIN list__Filling LF ON LF.LF_ID = LO.LF_ID
	LEFT JOIN list__Batch LB ON LB.LB_ID = LF.LB_ID
	LEFT JOIN CounterWeight CW ON CW.CW_ID = LB.CW_ID
	WHERE 1
		".($_GET["date_from"] ? "AND LO.o_date >= '{$_GET["date_from"]}'" : "")."
		".($_GET["date_to"] ? "AND LO.o_date <= '{$_GET["date_to"]}'" : "")."
		".($_GET["CW_ID"] ? "AND LB.CW_ID={$_GET["CW_ID"]}" : "")."
		".($_GET["CB_ID"] ? "AND LB.CW_ID IN (SELECT CW_ID FROM CounterWeight WHERE CB_ID = {$_GET["CB_ID"]})" : "")."
	ORDER BY LO.o_date DESC, LO.o_time, LO.o_post
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	?>
	<tr id="<?=$row["LO_ID"]?>">
		<td><?=$row["o_date"]?></td>
		<td><?=$row["o_time"]?></td>
		<td><?=$row["o_post"]?></td>
		<td style="color: red;"><?=$row["o_not_spill"]?></td>
		<td style="color: red;"><?=$row["o_crack"]?></td>
		<td style="color: red;"><?=$row["o_chipped"]?></td>
		<td style="color: red;"><?=$row["o_def_form"]?></td>
		<td><?=$row["weight1"]/1000?><?=($row["w1_error"] ? "<font style='font-size: .8em;' color='red'>".($row["w1_diff"] > 0 ? " +" : " ").($row["w1_diff"]/10)."%</font>" : "")?></td>
		<td><?=$row["weight2"]/1000?><?=($row["w2_error"] ? "<font style='font-size: .8em;' color='red'>".($row["w2_diff"] > 0 ? " +" : " ").($row["w2_diff"]/10)."%</font>" : "")?></td>
		<td><?=$row["weight3"]/1000?><?=($row["w3_error"] ? "<font style='font-size: .8em;' color='red'>".($row["w3_diff"] > 0 ? " +" : " ").($row["w3_diff"]/10)."%</font>" : "")?></td>
		<td title="Кассета[<?=$row["cassette"]?>] <?=$row["item"]?>" style="background-color: rgba(0, 0, 0, 0.2);"><?=$row["batch_date"]?> <i class="fas fa-question-circle"></i></td>
		<td><a href="#" class="add_opening" LO_ID="<?=$row["LO_ID"]?>" title="Изменить данные расформовки"><i class="fa fa-pencil-alt fa-lg"></i></a></td>
	</tr>
	<?
}
?>

	</tbody>
</table>

<div id="add_btn" class="add_opening" o_date="<?=$_GET["o_date"]?>" o_post="<?=$_GET["o_post"]?>" title="Внести данные расформовки"></div>

<?
include "footer.php";
?>
