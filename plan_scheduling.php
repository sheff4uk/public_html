<?
include "config.php";
$title = 'План отгрузки';
include "header.php";
include "./forms/plan_scheduling_form.php";

// Если в фильтре не установлена неделя, показываем текущую
if( !$_GET["week"] ) {
	$query = "SELECT YEARWEEK(CURDATE(), 1) week";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$row = mysqli_fetch_array($res);
	$_GET["week"] = $row["week"];
}
?>

<!--Фильтр-->
<div id="filter">
	<h3>Фильтр</h3>
	<form method="get" style="position: relative;">
		<a href="/plan_scheduling.php" style="position: absolute; top: 10px; right: 10px;" class="button">Сброс</a>

		<div class="nowrap" style="margin-bottom: 10px;">
			<span>Неделя:</span>
			<select name="week" class="<?=$_GET["week"] ? "filtered" : ""?>" onchange="this.form.submit()">
				<?
				$query = "
					SELECT LEFT(YEARWEEK(CURDATE(), 1), 4) year
					UNION
					SELECT LEFT(YEARWEEK(ps_date, 1), 4) year
					FROM plan__Scheduling
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
							SELECT LEFT(YEARWEEK(ps_date, 1), 4) year
								,YEARWEEK(ps_date, 1) week
								,RIGHT(YEARWEEK(ps_date, 1), 2) week_format
								,DATE_FORMAT(ADDDATE(ps_date, 0-WEEKDAY(ps_date)), '%e %b') WeekStart
								,DATE_FORMAT(ADDDATE(ps_date, 6-WEEKDAY(ps_date)), '%e %b') WeekEnd
							FROM plan__Scheduling
							WHERE LEFT(YEARWEEK(ps_date, 1), 4) = {$row["year"]}
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
			<th>Паллетов</th>
			<th>План</th>
			<th>По графику</th>
			<th></th>
		</tr>
	</thead>
	<tbody style="text-align: center;">

<?
$query = "
	SELECT SUM(1) cnt
		,DATE_FORMAT(PS.ps_date, '%d.%m.%y') ps_date_format
		,DATE_FORMAT(PS.ps_date, '%W') ps_date_weekday
		,PS.ps_date
		,SUM(PS.pallets) pallets
		,SUM(PS.pallets * CW.in_pallet) plan
		,SUM(PB.batches * CW.fillings * CW.in_cassette) filling_plan
	FROM plan__Scheduling PS
	JOIN CounterWeight CW ON CW.CW_ID = PS.CW_ID
	LEFT JOIN plan__Batch PB ON PB.pb_date + INTERVAL 5 DAY = PS.ps_date AND PB.CW_ID = PS.CW_ID
	WHERE 1
		".($_GET["week"] ? "AND YEARWEEK(PS.ps_date, 1) LIKE '{$_GET["week"]}'" : "")."
		".($_GET["CW_ID"] ? "AND PS.CW_ID={$_GET["CW_ID"]}" : "")."
		".($_GET["CB_ID"] ? "AND PS.CW_ID IN (SELECT CW_ID FROM CounterWeight WHERE CB_ID = {$_GET["CB_ID"]})" : "")."
	GROUP BY PS.ps_date
	ORDER BY PS.ps_date, PS.CW_ID
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	$cnt = $row["cnt"];

	$query = "
		SELECT PS.PS_ID
			,PS.ps_date
			,CW.item
			,PS.CW_ID
			,PS.pallets
			,PS.pallets * CW.in_pallet plan
			,PB.PB_ID
			,YEARWEEK(PB.pb_date, 1) week
			,PB.batches * CW.fillings * CW.in_cassette filling_plan
			,PB.fakt
		FROM plan__Scheduling PS
		JOIN CounterWeight CW ON CW.CW_ID = PS.CW_ID
		LEFT JOIN plan__Batch PB ON PB.pb_date + INTERVAL 5 DAY = PS.ps_date AND PB.CW_ID = PS.CW_ID
		WHERE 1
			AND PS.ps_date = '{$row["ps_date"]}'
			".($_GET["CW_ID"] ? "AND PS.CW_ID={$_GET["CW_ID"]}" : "")."
			".($_GET["CB_ID"] ? "AND PS.CW_ID IN (SELECT CW_ID FROM CounterWeight WHERE CB_ID = {$_GET["CB_ID"]})" : "")."
		ORDER BY PS.ps_date DESC, PS.CW_ID
	";
	$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $subrow = mysqli_fetch_array($subres) ) {
		$pallets += $subrow["pallets"];
		$plan += $subrow["plan"];
		$filling_plan += $subrow["filling_plan"];

		// Выводим общую ячейку с датой
		if( $cnt ) {
			$cnt++;
			echo "<tr id='{$subrow["PS_ID"]}' style='border-top: 2px solid #333;'>";
			echo "<td rowspan='{$cnt}' style='background-color: rgba(0, 0, 0, 0.2);'>{$row["ps_date_weekday"]}<br>{$row["ps_date_format"]}</td>";
			$cnt = 0;
		}
		else {
			echo "<tr id='{$subrow["PS_ID"]}'>";
		}

		?>
			<td><?=$subrow["item"]?></td>
			<td><?=$subrow["pallets"]?></td>
			<td><?=$subrow["plan"]?></td>
		<td class="bg-gray"><a href="plan_batch.php?week=<?=$subrow["week"]?>#<?=$subrow["PB_ID"]?>" target="_blank" <?=($subrow["fakt"] ? "" : "style='opacity: .7;'")?>><?=$subrow["filling_plan"]?></a></td>
			<td>
				<a href='#' class='add_ps clone' ps_date="<?=$_GET["ps_date"]?>" PS_ID='<?=$subrow["PS_ID"]?>' title='Клонировать план отгрузки'><i class='fa fa-clone fa-lg'></i></a>
				<a href='#' class='add_ps' PS_ID='<?=$subrow["PS_ID"]?>' title='Изменить данные плана отгрузки'><i class='fa fa-pencil-alt fa-lg'></i></a>
			</td>
		</tr>
		<?

	}
?>
		<tr class="summary">
			<td>Итог:</td>
			<td><?=$row["pallets"]?></td>
			<td><?=$row["plan"]?></td>
			<td><?=$row["filling_plan"]?></td>
			<td></td>
		</tr>
<?
}
?>
		<tr class="total">
			<td></td>
			<td>Итог:</td>
			<td><?=$pallets?></td>
			<td><?=$plan?></td>
			<td><?=$filling_plan?></td>
			<td></td>
		</tr>
	</tbody>
</table>

<div id="add_btn" class="add_ps" ps_date="<?=$_GET["ps_date"]?>" title="Внести данные плана отгрузки"></div>

<script>
	$(function() {
		$(".print").printPage();
	});
</script>

<?
include "footer.php";
?>
