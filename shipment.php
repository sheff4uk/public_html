<?
include "config.php";
$title = 'Отгрузка';
include "header.php";
include "./forms/shipment_form.php";

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
		<a href="/shipment.php" style="position: absolute; top: 10px; right: 10px;" class="button">Сброс</a>

		<div class="nowrap" style="margin-bottom: 10px;">
			<span>Неделя:</span>
			<select name="week" class="<?=$_GET["week"] ? "filtered" : ""?>" onchange="this.form.submit()">
				<?
				$query = "
					SELECT LEFT(YEARWEEK(CURDATE(), 1), 4) year
					UNION
					SELECT LEFT(YEARWEEK(ls_date, 1), 4) year
					FROM list__Shipment
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
							SELECT LEFT(YEARWEEK(ls_date, 1), 4) year
								,YEARWEEK(ls_date, 1) week
								,RIGHT(YEARWEEK(ls_date, 1), 2) week_format
								,DATE_FORMAT(ADDDATE(ls_date, 0-WEEKDAY(ls_date)), '%e %b') WeekStart
								,DATE_FORMAT(ADDDATE(ls_date, 6-WEEKDAY(ls_date)), '%e %b') WeekEnd
							FROM list__Shipment
							WHERE LEFT(YEARWEEK(ls_date, 1), 4) = {$row["year"]}
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
			<th>Поддон</th>
			<th>Кол-во</th>
			<th>Деталей</th>
			<th></th>
		</tr>
	</thead>
	<tbody style="text-align: center;">

<?
$query = "
	SELECT SUM(1) cnt
		,DATE_FORMAT(LS.ls_date, '%d.%m.%y') ls_date_format
		,DATE_FORMAT(LS.ls_date, '%W') ls_date_weekday
		,LS.ls_date
		,SUM(LS.pallets) pallets
		,SUM(LS.pallets * LS.in_pallet) details
	FROM list__Shipment LS
	WHERE 1
		".($_GET["week"] ? "AND YEARWEEK(LS.ls_date, 1) LIKE '{$_GET["week"]}'" : "")."
		".($_GET["CW_ID"] ? "AND LS.CW_ID={$_GET["CW_ID"]}" : "")."
		".($_GET["CB_ID"] ? "AND LS.CW_ID IN (SELECT CW_ID FROM CounterWeight WHERE CB_ID = {$_GET["CB_ID"]})" : "")."
	GROUP BY LS.ls_date
	ORDER BY LS.ls_date, LS.CW_ID
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	$cnt = $row["cnt"];

	$query = "
		SELECT LS.LS_ID
			,LS.ls_date
			,CW.item
			,LS.CW_ID
			,LS.pallets
			,LS.pallets * LS.in_pallet details
			,PN.pallet_name
		FROM list__Shipment LS
		JOIN CounterWeight CW ON CW.CW_ID = LS.CW_ID
		JOIN pallet__Name PN ON PN.PN_ID = LS.PN_ID
		WHERE 1
			AND LS.ls_date = '{$row["ls_date"]}'
			".($_GET["CW_ID"] ? "AND LS.CW_ID={$_GET["CW_ID"]}" : "")."
			".($_GET["CB_ID"] ? "AND LS.CW_ID IN (SELECT CW_ID FROM CounterWeight WHERE CB_ID = {$_GET["CB_ID"]})" : "")."
		ORDER BY LS.ls_date DESC, LS.CW_ID
	";
	$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $subrow = mysqli_fetch_array($subres) ) {
		$pallets += $subrow["pallets"];
		$details += $subrow["details"];

		// Выводим общую ячейку с датой
		if( $cnt ) {
			$cnt++;
			echo "<tr id='{$subrow["LS_ID"]}' style='border-top: 2px solid #333;'>";
			echo "<td rowspan='{$cnt}' style='background-color: rgba(0, 0, 0, 0.2);'>{$row["ls_date_weekday"]}<br>{$row["ls_date_format"]}</td>";
			$cnt = 0;
		}
		else {
			echo "<tr id='{$subrow["LS_ID"]}'>";
		}

		?>
			<td><?=$subrow["item"]?></td>
			<td><?=$subrow["pallet_name"]?></td>
			<td><?=$subrow["pallets"]?></td>
			<td><?=$subrow["details"]?></td>
			<td>
				<a href='#' class='add_ps' LS_ID='<?=$subrow["LS_ID"]?>' title='Изменить данные плана отгрузки'><i class='fa fa-pencil-alt fa-lg'></i></a>
			</td>
		</tr>
		<?

	}
?>
		<tr class="summary">
			<td></td>
			<td>Итог:</td>
			<td><?=$row["pallets"]?></td>
			<td><?=$row["details"]?></td>
			<td></td>
		</tr>
<?
}
?>
		<tr class="total">
			<td></td>
			<td></td>
			<td>Итог:</td>
			<td><?=$pallets?></td>
			<td><?=$details?></td>
			<td></td>
		</tr>
	</tbody>
</table>

<div id="add_btn" class="add_ps" ls_date="<?=$_GET["ls_date"]?>" title="Внести данные плана отгрузки"></div>

<script>
	$(function() {
		$(".print").printPage();
	});
</script>

<?
include "footer.php";
?>
