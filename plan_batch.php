<?
include "config.php";
$title = 'План заливки';
include "header.php";
include "./forms/plan_batch_form.php";

// Если в фильтре не установлена неделя, показываем текущую
if( !$_GET["week"] ) {
	$query = "SELECT YEARWEEK(NOW(), 1) week";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$row = mysqli_fetch_array($res);
	$_GET["week"] = $row["week"];
}
?>

<!--Фильтр-->
<div id="filter">
	<h3>Фильтр</h3>
	<form method="get" style="position: relative;">
		<a href="/plan_batch.php" style="position: absolute; top: 10px; right: 10px;" class="button">Сброс</a>

		<div class="nowrap" style="margin-bottom: 10px;">
			<span>Неделя:</span>
			<select name="week" class="<?=$_GET["week"] ? "filtered" : ""?>">
				<?
				$query = "
					SELECT YEARWEEK(NOW(), 1) week, INSERT(YEARWEEK(NOW(), 1), 5, 0, '-w') week_format
					UNION
					SELECT YEARWEEK(pb_date, 1) week, INSERT(YEARWEEK(pb_date, 1), 5, 0, '-w') week_format
					FROM plan__Batch
					ORDER BY week DESC
				";
				$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
				while( $row = mysqli_fetch_array($res) ) {
					$selected = ($row["week"] == $_GET["week"]) ? "selected" : "";
					echo "<option value='{$row["week"]}' {$selected}>{$row["week_format"]}</option>";
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
			<th>Дата</th>
			<th>Противовес</th>
			<th>Замесов</th>
			<th>Заливок</th>
			<th>План</th>
			<th></th>
		</tr>
	</thead>
	<tbody style="text-align: center;">

<?
$query = "
	SELECT SUM(1) cnt
		,DATE_FORMAT(PB.pb_date, '%d.%m.%y') pb_date_format
		,DATE_FORMAT(PB.pb_date, '%W') pb_date_weekday
		,PB.pb_date
		,SUM(PB.batches) batches
		,SUM(PB.batches * CW.fillings) fillings
		,SUM(PB.batches * CW.fillings * CW.in_cassette) plan
	FROM plan__Batch PB
	JOIN CounterWeight CW ON CW.CW_ID = PB.CW_ID
	WHERE 1
		".($_GET["week"] ? "AND YEARWEEK(PB.pb_date, 1) LIKE '{$_GET["week"]}'" : "")."
		".($_GET["CW_ID"] ? "AND PB.CW_ID={$_GET["CW_ID"]}" : "")."
		".($_GET["CB_ID"] ? "AND PB.CW_ID IN (SELECT CW_ID FROM CounterWeight WHERE CB_ID = {$_GET["CB_ID"]})" : "")."
	GROUP BY PB.pb_date
	ORDER BY PB.pb_date, PB.CW_ID
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	$cnt = $row["cnt"];

	$query = "
		SELECT PB.PB_ID
			,DATE_FORMAT(PB.pb_date, '%d.%m.%y') pb_date_format
			,PB.pb_date
			,CW.item
			,PB.CW_ID
			,PB.batches
			,PB.batches * CW.fillings fillings
			,PB.batches * CW.fillings * CW.in_cassette plan
			,PB.fakt
		FROM plan__Batch PB
		JOIN CounterWeight CW ON CW.CW_ID = PB.CW_ID
		WHERE 1
			AND PB.pb_date = '{$row["pb_date"]}'
			".($_GET["CW_ID"] ? "AND PB.CW_ID={$_GET["CW_ID"]}" : "")."
			".($_GET["CB_ID"] ? "AND PB.CW_ID IN (SELECT CW_ID FROM CounterWeight WHERE CB_ID = {$_GET["CB_ID"]})" : "")."
		ORDER BY PB.pb_date DESC, PB.CW_ID
	";
	$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $subrow = mysqli_fetch_array($subres) ) {
		$batches += $subrow["batches"];
		$fillings += $subrow["fillings"];
		$plan += $subrow["plan"];

		// Выводим общую ячейку с датой заливки
		if( $cnt ) {
			$cnt++;
			echo "<tr id='{$subrow["PB_ID"]}' style='border-top: 2px solid #333;'>";
			echo "<td rowspan='{$cnt}' style='background-color: rgba(0, 0, 0, 0.2);'>{$row["pb_date_weekday"]}<br>{$row["pb_date_format"]}</td>";
			$cnt = 0;
		}
		else {
			echo "<tr id='{$subrow["PB_ID"]}'>";
		}

		?>
			<td><?=$subrow["item"]?></td>
			<td><?=$subrow["batches"]?></td>
			<td><?=$subrow["fillings"]?></td>
			<td><?=$subrow["plan"]?></td>
			<td>
				<a href='#' class='add_pb clone' pb_date="<?=$_GET["pb_date"]?>" PB_ID='<?=$subrow["PB_ID"]?>' title='Клонировать план заливки'><i class='fa fa-clone fa-lg'></i></a>
				<?=(!$subrow["fakt"] ? "<a href='#' class='add_pb' PB_ID='{$subrow["PB_ID"]}' title='Изменить данные плана заливки'><i class='fa fa-pencil-alt fa-lg'></i></a><a href='printforms/checklist_blank.php?PB_ID={$subrow["PB_ID"]}' class='print' title='Бланк чеклиста оператора'><i class='fas fa-print fa-lg'></i></a>" : "")?>
			</td>
		</tr>
		<?

	}
?>
		<tr class="summary">
			<td>Итог:</td>
			<td><?=$row["batches"]?></td>
			<td><?=$row["fillings"]?></td>
			<td><?=$row["plan"]?></td>
			<td></td>
		</tr>
<?
}
?>
		<tr class="total">
			<td></td>
			<td>Итог:</td>
			<td><?=$batches?></td>
			<td><?=$fillings?></td>
			<td><?=$plan?></td>
			<td></td>
		</tr>
	</tbody>
</table>

<div id="add_btn" class="add_pb" pb_date="<?=$_GET["pb_date"]?>" title="Внести данные плана заливки"></div>

<script>
	$(function() {
		$(".print").printPage();
	});
</script>

<?
include "footer.php";
?>