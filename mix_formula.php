<?
include "config.php";
$title = 'Рецепты';
include "header.php";
include "./forms/mix_formula_form.php";

// Если не выбран участок, берем из сессии
if( !$_GET["F_ID"] ) {
	$_GET["F_ID"] = $_SESSION['F_ID'];
}
?>

<!--Фильтр-->
<div id="filter">
	<h3>Фильтр</h3>
	<form method="get" style="position: relative;">
		<a href="/mix_formula.php" style="position: absolute; top: 10px; right: 10px;" class="button">Сброс</a>

		<div class="nowrap" style="margin-bottom: 10px;">
			<span>Участок:</span>
			<select name="F_ID" class="<?=$_GET["F_ID"] ? "filtered" : ""?>" onchange="this.form.submit()">
				<?
				$query = "
					SELECT F_ID
						,f_name
					FROM factory
					ORDER BY F_ID
				";
				$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
				while( $row = mysqli_fetch_array($res) ) {
					$selected = ($row["F_ID"] == $_GET["F_ID"]) ? "selected" : "";
					echo "<option value='{$row["F_ID"]}' {$selected}>{$row["f_name"]}</option>";
				}
				?>
			</select>
		</div>

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
			<th rowspan="2">Противовес</th>
			<th colspan="8">Ингредиенты</th>
			<th rowspan="2"></th>
		</tr>
		<tr>
			<th>Мелкая дробь, кг</th>
			<th>Крупная дробь, кг</th>
			<th>Окалина, кг</th>
			<th>КМП, кг</th>
			<th>Отсев, кг</th>
			<th>Цемент, кг</th>
			<th>Пластификатор, кг</th>
			<th>Вода, кг</th>
		</tr>
	</thead>
	<tbody style="text-align: center;">

<?
$query = "
	SELECT CW.CW_ID
		,CW.item
		,MF.MF_ID
		,MF.s_fraction
		,MF.l_fraction
		,MF.iron_oxide
		,MF.sand
		,MF.crushed_stone
		,MF.cement
		,MF.plasticizer
		,MF.water
	FROM CounterWeight CW
	JOIN MixFormula MF ON MF.CW_ID = CW.CW_ID AND MF.F_ID = {$_GET["F_ID"]}
	ORDER BY CW.CW_ID
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	?>
	<tr id="<?=$row["MF_ID"]?>">
		<td><b><?=$row["item"]?></b></td>
		<td style="background: #7952eb88;"><?=$row["s_fraction"]?></td>
		<td style="background: #51d5d788;"><?=$row["l_fraction"]?></td>
		<td style="background: #a52a2a80;"><?=$row["iron_oxide"]?></td>
		<td style="background: #f4a46082;"><?=$row["sand"]?></td>
		<td style="background: #8b45137a;"><?=$row["crushed_stone"]?></td>
		<td style="background: #7080906b;"><?=$row["cement"]?></td>
		<td style="background: #80800080;"><?=$row["plasticizer"]?></td>
		<td style="background: #1e90ff85;"><?=$row["water"]?></td>
		<td><a href="#" class="add_formula" MF_ID="<?=$row["MF_ID"]?>" item="<?=$row["item"]?>" F_ID="<?=$_GET["F_ID"]?>" title="Изменить рецепт"><i class="fa fa-pencil-alt fa-lg"></i></a></td>
	</tr>
	<?
}
?>

	</tbody>
</table>

<?
include "footer.php";
?>
