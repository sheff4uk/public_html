<?
include "config.php";
$title = 'Протокол испытаний куба';
include "header.php";
include "./forms/cubetest_form.php";

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

<h1>Планируемые испытания</h1>
<table class="main_table">
	<thead>
		<tr>
			<th>Противовес</th>
			<th>Дата замеса</th>
			<th>Время замеса</th>
			<th>Масса куба смеси, кг</th>
			<th>Дата испытания</th>
			<th>Время испытания</th>
			<th>Масса испытуемого куба, кг</th>
			<th>Давление, МПа</th>
			<th>Выдержка в часах</th>
			<th></th>
		</tr>
	</thead>
	<tbody style="text-align: center;">
<?
$query = "
	SELECT LB.LB_ID
		,CW.CW_ID
		,CW.item
		,LB.batch_date
		,DATE_FORMAT(LB.batch_date, '%d.%m.%y') batch_date_format
		,DATE_FORMAT(LB.batch_time, '%H:%i') batch_time_format
		,LB.mix_density
		,DATE_FORMAT(LB.batch_date + INTERVAL 1 DAY, '%d.%m.%y') test_date_format
		,DATE_FORMAT(LB.batch_time, '%H:%i') test_time_format
		,LB.batch_date + INTERVAL 1 DAY test_date
		,24 delay
		,CAST(CONCAT(LB.batch_date + INTERVAL 1 DAY, ' ', LB.batch_time) as datetime) test_date_time
	FROM list__Batch LB
	JOIN CounterWeight CW ON CW.CW_ID = LB.CW_ID
	LEFT JOIN list__CubeTest LCT ON LCT.LB_ID = LB.LB_ID AND LCT.delay = 24
	WHERE LB.test = 1
		AND LCT.LCT_ID IS NULL
	UNION ALL
	SELECT LB.LB_ID
		,CW.CW_ID
		,CW.item
		,LB.batch_date
		,DATE_FORMAT(LB.batch_date, '%d.%m.%y') batch_date_format
		,DATE_FORMAT(LB.batch_time, '%H:%i') batch_time_format
		,LB.mix_density
		,DATE_FORMAT(LB.batch_date + INTERVAL 3 DAY, '%d.%m.%y') test_date_format
		,DATE_FORMAT(LB.batch_time, '%H:%i') test_time_format
		,LB.batch_date + INTERVAL 3 DAY test_date
		,72 delay
		,CAST(CONCAT(LB.batch_date + INTERVAL 3 DAY, ' ', LB.batch_time) as datetime) test_date_time
	FROM list__Batch LB
	JOIN CounterWeight CW ON CW.CW_ID = LB.CW_ID
	LEFT JOIN list__CubeTest LCT ON LCT.LB_ID = LB.LB_ID AND LCT.delay = 72
	WHERE LB.test = 1
		AND LCT.LCT_ID IS NULL
	ORDER BY test_date_time
";
$now = new DateTime("now");
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	$test_date_time = new DateTime($row["test_date_time"]);
	$error = $test_date_time < $now ? "error" : "";
	?>
	<tr>
		<td class="bg-gray"><?=$row["item"]?></td>
		<td class="bg-gray"><a href="checklist.php?date_from=<?=$row["batch_date"]?>&date_to=<?=$row["batch_date"]?>&CW_ID=<?=$row["CW_ID"]?>#<?=$row["LB_ID"]?>" title="Замес" target="_blank"><?=$row["batch_date_format"]?></a></td>
		<td class="bg-gray"><?=$row["batch_time_format"]?></td>
		<td class="bg-gray"><?=$row["mix_density"]/1000?></td>
		<td class="<?=$error?>"><?=$row["test_date_format"]?></td>
		<td class="<?=$error?>"><?=$row["test_time_format"]?></td>
		<td></td>
		<td></td>
		<td><?=$row["delay"]?></td>
		<td><a href="#" class="add_cubetest" LB_ID="<?=$row["LB_ID"]?>" delay="<?=$row["delay"]?>" test_date="<?=$row["test_date"]?>" title="Внести данные испытания куба"><i class="fa fa-plus-square fa-lg"></i></a></td>
	</tr>
	<?
}
?>
	</tbody>
</table>

<h1>Произведенные испытания</h1>

<!--Фильтр-->
<div id="filter">
	<h3>Фильтр</h3>
	<form method="get" style="position: relative;">
		<a href="/cubetest.php" style="position: absolute; top: 10px; right: 10px;" class="button">Сброс</a>

		<div class="nowrap" style="margin-bottom: 10px;">
			<span style="display: inline-block; width: 200px;">Дата испытания между:</span>
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

		<div class="nowrap" style="display: inline-block; margin-bottom: 10px; margin-right: 30px;">
			<span>Выдержка:</span>
			<select name="delay" class="<?=$_GET["delay"] ? "filtered" : ""?>" style="width: 100px;">
				<option value=""></option>
				<option value="24" <?=($_GET["delay"]==24 ? "selected" : "")?>>24 часа</option>
				<option value="72" <?=($_GET["delay"]==72 ? "selected" : "")?>>72 часа</option>
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

//		$('#filter input[name="date_from"]').change(function() {
//			var val = $(this).val();
//			$('#filter input[name="date_to"]').val(val);
//		});
	});
</script>

<table class="main_table">
	<tbody style="text-align: center;">

<?
$query = "
	SELECT LCT.LCT_ID
		,LCT.LB_ID
		,DATE_FORMAT(LCT.test_date, '%d.%m.%y') test_date
		,DATE_FORMAT(LCT.test_time, '%H:%i') test_time
		,CW.item
		,LB.CW_ID
		,LB.batch_date batch_date
		,DATE_FORMAT(LB.batch_date, '%d.%m.%y') batch_date_format
		,DATE_FORMAT(LB.batch_time, '%H:%i') batch_time_format
		,TIMESTAMPDIFF(HOUR, CAST(CONCAT(LB.batch_date, ' ', LB.batch_time) as datetime), CAST(CONCAT(LCT.test_date, ' ', LCT.test_time) as datetime)) delay_fact
		,LCT.delay
		,LB.mix_density
		,LCT.cube_weight
		,LCT.pressure
	FROM list__CubeTest LCT
	JOIN list__Batch LB ON LB.LB_ID = LCT.LB_ID
	JOIN CounterWeight CW ON CW.CW_ID = LB.CW_ID
	WHERE 1
		".($_GET["date_from"] ? "AND LCT.test_date >= '{$_GET["date_from"]}'" : "")."
		".($_GET["date_to"] ? "AND LCT.test_date <= '{$_GET["date_to"]}'" : "")."
		".($_GET["CW_ID"] ? "AND LB.CW_ID={$_GET["CW_ID"]}" : "")."
		".($_GET["CB_ID"] ? "AND LB.CW_ID IN (SELECT CW_ID FROM CounterWeight WHERE CB_ID = {$_GET["CB_ID"]})" : "")."
		".($_GET["delay"] ? "AND LCT.delay={$_GET["delay"]}" : "")."
	ORDER BY LCT.test_date DESC, LCT.test_time DESC
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	?>
	<tr id="<?=$row["LCT_ID"]?>">
		<td class="bg-gray"><?=$row["item"]?></td>
		<td class="bg-gray"><a href="checklist.php?date_from=<?=$row["batch_date"]?>&date_to=<?=$row["batch_date"]?>&CW_ID=<?=$row["CW_ID"]?>#<?=$row["LB_ID"]?>" title="Замес" target="_blank"><?=$row["batch_date_format"]?></a></td>
		<td class="bg-gray"><?=$row["batch_time_format"]?></td>
		<td class="bg-gray"><?=$row["mix_density"]/1000?></td>
		<td><?=$row["test_date"]?></td>
		<td><?=$row["test_time"]?></td>
		<td><?=$row["cube_weight"]/1000?></td>
		<td><?=$row["pressure"]?></td>
		<td class="<?=($row["delay_fact"] != $row["delay"] ? "error" : "")?>"><?=$row["delay_fact"]?></td>
		<td><a href="#" class="add_cubetest" LCT_ID="<?=$row["LCT_ID"]?>" title="Изменить данные испытания куба"><i class="fa fa-pencil-alt fa-lg"></i></a></td>
	</tr>
	<?
}
?>

	</tbody>
</table>

<?
include "footer.php";
?>
