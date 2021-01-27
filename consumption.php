<?
include "config.php";
$title = 'Расход сырья';
include "header.php";

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

<table class="main_table">
	<thead>
		<tr>
			<th>Дата заливки</th>
			<th>Окалина, кг</th>
			<th>КМП, кг</th>
			<th>Отсев, кг</th>
			<th>Цемент, кг</th>
			<th>Вода, кг</th>
		</tr>
	</thead>
	<tbody style="text-align: center;">
		<?
		$query = "
			SELECT DATE_FORMAT(PB.pb_date,'%d.%m.%Y') pb_date_format
				,SUM(LB.iron_oxide) iron_oxide
				,SUM(LB.sand) sand
				,SUM(LB.crushed_stone) crushed_stone
				,SUM(LB.cement) cement
				,SUM(LB.water) water
			FROM plan__Batch PB
			JOIN list__Batch LB ON LB.PB_ID = PB.PB_ID
			WHERE 1
				".($_GET["date_from"] ? "AND PB.pb_date >= '{$_GET["date_from"]}'" : "")."
				".($_GET["date_to"] ? "AND PB.pb_date <= '{$_GET["date_to"]}'" : "")."
				".($_GET["CW_ID"] ? "AND PB.CW_ID={$_GET["CW_ID"]}" : "")."
				".($_GET["CB_ID"] ? "AND PB.CW_ID IN (SELECT CW_ID FROM CounterWeight WHERE CB_ID = {$_GET["CB_ID"]})" : "")."
			GROUP BY PB.pb_date
			ORDER BY PB.pb_date
		";
		$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		while( $row = mysqli_fetch_array($res) ) {
			$iron_oxide += $row["iron_oxide"];
			$sand += $row["sand"];
			$crushed_stone += $row["crushed_stone"];
			$cement += $row["cement"];
			$water += $row["water"];
			?>
			<tr>
				<td><?=$row["pb_date_format"]?></td>
				<td><?=$row["iron_oxide"]?></td>
				<td><?=$row["sand"]?></td>
				<td><?=$row["crushed_stone"]?></td>
				<td><?=$row["cement"]?></td>
				<td><?=$row["water"]?></td>
			</tr>
			<?
		}
		?>

		<tr class="total">
			<td>Итог:</td>
			<td><?=$iron_oxide?></td>
			<td><?=$sand?></td>
			<td><?=$crushed_stone?></td>
			<td><?=$cement?></td>
			<td><?=$water?></td>
		</tr>
	</tbody>
</table>

<?
include "footer.php";
?>
