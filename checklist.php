<?
include "config.php";
$title = 'Замес/Заливка';
include "header.php";
include "./forms/checklist_form.php";

// Если в фильтре не установлен период, показываем последние 7 дней
if( !$_GET["date_from"] ) {
	$date = new DateTime('-7 days');
	$_GET["date_from"] = date_format($date, 'Y-m-d');
}
if( !$_GET["date_to"] ) {
	$date = new DateTime('-1 days');
	$_GET["date_to"] = date_format($date, 'Y-m-d');
}
?>

<!--Фильтр-->
<div id="filter">
	<h3>Фильтр</h3>
	<form method="get" style="position: relative;">
		<a href="/checklist.php" style="position: absolute; top: 10px; right: 10px;" class="button">Сброс</a>

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
			<th rowspan="2">Дата<br>Противовес</th>
			<th rowspan="2">Время</th>
			<th rowspan="2">Оператор</th>
			<th colspan="2">Масса кубика, кг</th>
			<th rowspan="2">Окалина, кг</th>
			<th rowspan="2">КМП, кг</th>
			<th rowspan="2">Отсев, кг</th>
			<th rowspan="2">Цемент, кг</th>
			<th rowspan="2">Вода, л</th>
			<th rowspan="2">№ кассеты</th>
			<th rowspan="2">Недолив</th>
			<th rowspan="2"></th>
		</tr>
		<tr>
			<th>Контрольный компонент</th>
			<th>Раствор ±0.05</th>
		</tr>
	</thead>
	<tbody style="text-align: center;">

<?
// Получаем список дат и противовесов и кол-во замесов на эти даты
$query = "
	SELECT
		LB.batch_date,
		LB.CW_ID,
		SUM(1) cnt
	FROM list__Batch LB
	WHERE 1
		".($_GET["date_from"] ? "AND LB.batch_date >= '{$_GET["date_from"]}'" : "")."
		".($_GET["date_to"] ? "AND LB.batch_date <= '{$_GET["date_to"]}'" : "")."
		".($_GET["CW_ID"] ? "AND LB.CW_ID={$_GET["CW_ID"]}" : "")."
		".($_GET["CB_ID"] ? "AND LB.CW_ID IN (SELECT CW_ID FROM CounterWeight WHERE CB_ID = {$_GET["CB_ID"]})" : "")."
	GROUP BY LB.batch_date, LB.CW_ID
	ORDER BY LB.batch_date DESC, LB.CW_ID
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	$cnt = $row["cnt"];

	$query = "
		SELECT LB.LB_ID
			,CW.item
			,OP.name
			,DATE_FORMAT(LB.batch_date, '%d.%m.%y') batch_date
			,DATE_FORMAT(LB.batch_time, '%H:%i') batch_time
			,LB.comp_density
			,LB.mix_density
			,LB.iron_oxide
			,LB.sand
			,LB.crushed_stone
			,LB.cement
			,LB.water
			,GROUP_CONCAT(LF.cassette ORDER BY LF.LF_ID SEPARATOR '/') cassette
			,LB.underfilling
			,LB.mix_diff
			,LB.mix_error
		FROM list__Batch LB
		JOIN CounterWeight CW ON CW.CW_ID = LB.CW_ID
		JOIN Operator OP ON OP.OP_ID = LB.OP_ID
		JOIN list__Filling LF ON LF.LB_ID = LB.LB_ID
		WHERE LB.batch_date LIKE '{$row["batch_date"]}' AND LB.CW_ID = {$row["CW_ID"]}
		GROUP BY LB.LB_ID
		ORDER BY LB.batch_time ASC
	";
	$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $subrow = mysqli_fetch_array($subres) ) {

		// Выводим общую ячейку с датой кодом
		if( $cnt ) {
			echo "<tr style='border-top: 2px solid #333;' id='{$subrow["LB_ID"]}'>";
			echo "<td rowspan='{$cnt}' style='background-color: rgba(0, 0, 0, 0.2);'>{$subrow["batch_date"]}<br><b>{$subrow["item"]}</b><br>Замесов: <b>{$cnt}</b></td>";
			$cnt = 0;
		}
		else {
			echo "<tr id='{$subrow["LB_ID"]}'>";
		}
		?>
				<td><?=$subrow["batch_time"]?></td>
				<td><?=$subrow["name"]?></td>
				<td><?=$subrow["comp_density"]/1000?></td>
				<td><?=$subrow["mix_density"]/1000?><?=($subrow["mix_error"] ? "<font style='font-size: .8em;' color='red'>".($subrow["mix_diff"] > 0 ? " +" : " ").($subrow["mix_diff"]/1000)."</font>" : "")?></td>
				<td style="background-color: rgba(0, 0, 0, 0.2);"><?=$subrow["iron_oxide"]?></td>
				<td style="background-color: rgba(0, 0, 0, 0.2);"><?=$subrow["sand"]?></td>
				<td style="background-color: rgba(0, 0, 0, 0.2);"><?=$subrow["crushed_stone"]?></td>
				<td style="background-color: rgba(0, 0, 0, 0.2);"><?=$subrow["cement"]?></td>
				<td style="background-color: rgba(0, 0, 0, 0.2);"><?=$subrow["water"]?></td>
				<td class="nowrap"><?=$subrow["cassette"]?></td>
				<td><?=$subrow["underfilling"]?></td>
				<td><a href="#" class="add_checklist" LB_ID="<?=$subrow["LB_ID"]?>" title="Изменить данные замеса"><i class="fa fa-pencil-alt fa-lg"></i></a></td>
			</tr>
		<?
	}
}
?>
	</tbody>
</table>

<div id="add_btn" class="add_checklist" CW_ID="<?=$_GET["CW_ID"]?>" batch_date="<?=$_GET["batch_date"]?>" OP_ID="<?=$_GET["OP_ID"]?>" title="Внести данные замеса"></div>

<?
include "footer.php";
?>
