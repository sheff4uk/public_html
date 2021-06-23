<?
include "config.php";
$title = 'План заливки';
include "header.php";
include "./forms/plan_batch_form.php";

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

<h2 style='color: #911;'>ВНИМАНИЕ! Производственные сутки начинаются в 07:00.</h2>

<!--Фильтр-->
<div id="filter">
	<h3>Фильтр</h3>
	<form method="get" style="position: relative;">
		<a href="/consumption.php" style="position: absolute; top: 10px; right: 10px;" class="button">Сброс</a>

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
	});
</script>

<!--Посуточная аналитика-->
<table class="main_table">
	<thead>
		<tr>
			<th>Производственные сутки</th>
			<th>Время первого замеса</th>
			<th>Цикл</th>
			<th>Противовес</th>
			<th>Замесов</th>
			<th>Заливок</th>
			<th>Деталей</th>
			<th>Недоливы</th>
		</tr>
	</thead>
	<tbody style="text-align: center;">

<?
$batches = 0;
$fillings = 0;
$details = 0;
$underfilling = 0;

$query = "
	SELECT COUNT(DISTINCT PB.CW_ID, PB.cycle) cnt
		,DATE_FORMAT(LB.prod_day, '%d.%m.%y') prod_day_format
		,DATE_FORMAT(LB.prod_day, '%W') prod_weekday_format
		,LB.prod_day
		,COUNT(DISTINCT LB.LB_ID) batches
		,COUNT(LF.LF_ID) fillings
		,SUM(PB.in_cassette) details
	FROM list__Batch LB
	JOIN list__Filling LF ON LF.LB_ID = LB.LB_ID
	JOIN plan__Batch PB ON PB.PB_ID = LB.PB_ID
	JOIN CounterWeight CW ON CW.CW_ID = PB.CW_ID
	WHERE LB.prod_day BETWEEN '{$_GET["date_from"]}' AND '{$_GET["date_to"]}'
		".($_GET["CW_ID"] ? "AND PB.CW_ID={$_GET["CW_ID"]}" : "")."
		".($_GET["CB_ID"] ? "AND PB.CW_ID IN (SELECT CW_ID FROM CounterWeight WHERE CB_ID = {$_GET["CB_ID"]})" : "")."
	GROUP BY LB.prod_day
	ORDER BY LB.prod_day
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	$cnt = $row["cnt"];
	$d_batches = 0;
	$d_underfilling = 0;

	$query = "
		SELECT DATE_FORMAT(MIN(TIMESTAMP(LB.batch_date, LB.batch_time)), '%H:%i') first_batch
			,CW.item
			,COUNT(DISTINCT LB.LB_ID) batches
			,COUNT(LF.LF_ID) fillings
			,SUM(PB.in_cassette) details
			,SUM(LF.underfilling) underfilling
			,PB.cycle
		FROM list__Batch LB
		JOIN list__Filling LF ON LF.LB_ID = LB.LB_ID
		JOIN plan__Batch PB ON PB.PB_ID = LB.PB_ID
		JOIN CounterWeight CW ON CW.CW_ID = PB.CW_ID
		WHERE 1
			AND LB.prod_day = '{$row["prod_day"]}'
			".($_GET["CW_ID"] ? "AND PB.CW_ID={$_GET["CW_ID"]}" : "")."
			".($_GET["CB_ID"] ? "AND PB.CW_ID IN (SELECT CW_ID FROM CounterWeight WHERE CB_ID = {$_GET["CB_ID"]})" : "")."
		GROUP BY PB.CW_ID, PB.cycle
		ORDER BY MIN(TIMESTAMP(LB.batch_date, LB.batch_time))
	";
	$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $subrow = mysqli_fetch_array($subres) ) {
		$batches += $subrow["batches"];
		$fillings += $subrow["fillings"];
		$details += $subrow["details"];
		$underfilling += $subrow["underfilling"];
		$d_underfilling += $subrow["underfilling"];

		// Выводим общую ячейку с датой заливки
		if( $cnt ) {
			$cnt++;
			echo "<tr style='border-top: 2px solid #333;'>";
			echo "<td rowspan='{$cnt}' style='background-color: rgba(0, 0, 0, 0.2);'><h2>{$row["prod_weekday_format"]}</h2>{$row["prod_day_format"]}</td>";
			$cnt = 0;
		}
		else {
			echo "<tr id='{$subrow["PB_ID"]}'>";
		}
		?>
			<td><?=$subrow["first_batch"]?></td>
			<td><?=$subrow["cycle"]?></td>
			<td><?=$subrow["item"]?></td>
			<td><?=$subrow["batches"]?></td>
			<td><?=$subrow["fillings"]?></td>
			<td><?=($subrow["details"] - $subrow["underfilling"])?></td>
			<td><?=$subrow["underfilling"]?></td>
		</tr>
		<?

	}
?>
		<tr class="summary">
			<td></td>
			<td></td>
			<td>Итог:</td>
			<td><?=$row["batches"]?></td>
			<td><?=$row["fillings"]?></td>
			<td><?=($row["details"] - $d_underfilling)?></td>
			<td><?=$d_underfilling?></td>
		</tr>
<?
}
?>
		<tr class="total">
			<td></td>
			<td></td>
			<td></td>
			<td>Итог:</td>
			<td><?=$batches?></td>
			<td><?=$fillings?></td>
			<td><?=($details - $underfilling)?></td>
			<td><?=$underfilling?></td>
		</tr>
	</tbody>
</table>

<?
include "footer.php";
?>
