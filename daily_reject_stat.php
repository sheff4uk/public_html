<?
include "config.php";
$title = 'Статистика брака';
include "header.php";

// Проверка прав на доступ к экрану
if( !in_array('reject', $Rights) ) {
	header($_SERVER['SERVER_PROTOCOL'].' 403 Forbidden');
	die('Недостаточно прав для совершения операции');
}

// Если в фильтре не установлен период, показываем последние 7 дней
if( !$_GET["date_from"] ) {
	$date = date_create('-6 days');
	$_GET["date_from"] = date_format($date, 'Y-m-d');
}
if( !$_GET["date_to"] ) {
	$date = date_create('-0 days');
	$_GET["date_to"] = date_format($date, 'Y-m-d');
}
if( !$_GET["detailing"] ) {
	$_GET["detailing"] = "day";
}
?>

<!--Фильтр-->
<div id="filter">
	<h3>Фильтр</h3>
	<form method="get" style="position: relative;">
		<a href="/daily_reject_stat.php" style="position: absolute; top: 10px; right: 10px;" class="button">Сброс</a>

		<div class="nowrap" style="margin-bottom: 10px;">
			<span>Дата между:</span>
			<input name="date_from" type="date" value="<?=$_GET["date_from"]?>" class="<?=$_GET["date_from"] ? "filtered" : ""?>">
			<input name="date_to" type="date" value="<?=$_GET["date_to"]?>" class="<?=$_GET["date_to"] ? "filtered" : ""?>">
			<i class="fas fa-question-circle" title="По умолчанию устанавливаются последние 7 дней." style="margin-right: 30px;"></i>

			<span>Детализация:</span>
			<div class='btnset' id='detailing' style="display: inline-block;">
				<input type='radio' id='day' name='detailing' value='day' <?= ($_GET["detailing"] == "day" ? "checked" : "") ?>>
					<label for='day'>по дням</label>
				<input type='radio' id='week' name='detailing' value='week' <?= ($_GET["detailing"] == "week" ? "checked" : "") ?>>
					<label for='week'>по неделям</label>
				<input type='radio' id='month' name='detailing' value='month' <?= ($_GET["detailing"] == "month" ? "checked" : "") ?>>
					<label for='month'>по месяцам</label>
			</div>
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

<table class="main_table">
	<thead>
		<tr>
			<th>Период</th>
			<th>Противовес</th>
			<th>Кол-во выявленного брака, шт</th>
			<th>% брака</th>
			<th>Стоимость брака</th>
		</tr>
	</thead>
	<tbody style="text-align: center;">

<?
$query = "
	SELECT
		".($_GET["detailing"] == "day" ? "DATE_FORMAT(LO.opening_time, '%d.%m.%Y') reject_date_format" : "")."
		".($_GET["detailing"] == "week" ? "DATE_FORMAT(LO.opening_time, '%x w%v') reject_date_format" : "")."
		".($_GET["detailing"] == "month" ? "DATE_FORMAT(LO.opening_time, '%Y %b') reject_date_format" : "")."
		".($_GET["detailing"] == "day" ? ",LO.opening_time reject_date_sort" : "")."
		".($_GET["detailing"] == "week" ? ",DATE_FORMAT(LO.opening_time, '%x%v') reject_date_sort" : "")."
		".($_GET["detailing"] == "month" ? ",DATE_FORMAT(LO.opening_time, '%Y%m') reject_date_sort" : "")."
		,CW.item
		,SUM(IFNULL(LOD.not_spill, 0) + IFNULL(LOD.crack, 0) + IFNULL(LOD.crack_drying, 0) + IFNULL(LOD.chipped, 0) + IFNULL(LOD.def_form, 0) + IFNULL(LOD.def_assembly, 0) + IFNULL(LOD.reject, 0)) `o_reject`
		,SUM(PB.in_cassette - LF.underfilling) `o_details`
		,CW.CBD
	FROM list__Opening LO
	JOIN list__Filling LF ON LF.LF_ID = LO.LF_ID
	JOIN list__Batch LB ON LB.LB_ID = LF.LB_ID
	JOIN plan__Batch PB ON PB.PB_ID = LB.PB_ID
	JOIN CounterWeight CW ON CW.CW_ID = PB.CW_ID
	LEFT JOIN list__Opening_def LOD ON LOD.LO_ID = LO.LO_ID
	WHERE 1
		".($_GET["date_from"] ? "AND DATE(LO.opening_time) >= '{$_GET["date_from"]}'" : "")."
		".($_GET["date_to"] ? "AND DATE(LO.opening_time) <= '{$_GET["date_to"]}'" : "")."
		".($_GET["CW_ID"] ? "AND CW.CW_ID={$_GET["CW_ID"]}" : "")."
		".($_GET["CB_ID"] ? "AND CW.CW_ID IN (SELECT CW_ID FROM CounterWeight WHERE CB_ID = {$_GET["CB_ID"]})" : "")."
	GROUP BY reject_date_format, PB.CW_ID
	ORDER BY reject_date_sort, PB.CW_ID
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	$o_reject += $row["o_reject"];
	$o_details += $row["o_details"];
	$CBD += $row["o_reject"] * $row["CBD"];
	?>
	<tr>
		<td><?=$row["reject_date_format"]?></td>
		<td><?=$row["item"]?></td>
		<td><?=($row["o_details"] > 0 ? $row["o_reject"] : "")?></td>
		<td><?=($row["o_details"] > 0 ? round($row["o_reject"] / $row["o_details"] * 100, 2) : "")?></td>
		<td><?=($row["o_reject"] * $row["CBD"])?></td>
	</tr>
	<?
}
?>
		<tr class="total">
			<td></td>
			<td>Итог:</td>
			<td><?=($o_details > 0 ? $o_reject : "")?></td>
			<td><?=($o_details > 0 ? round($o_reject / $o_details * 100, 2) : "")?></td>
			<td><?=$CBD?></td>
		</tr>
	</tbody>
</table>

<?
include "footer.php";
?>
