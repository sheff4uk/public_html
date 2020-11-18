<?
include "config.php";
$title = 'Рецепты';
include "header.php";
include "./forms/mix_formula_form.php";
?>

<table class="main_table">
	<thead>
		<tr>
			<th rowspan="2">Противовес</th>
			<th rowspan="2">Литера</th>
			<th colspan="2">Условия применения</th>
			<th colspan="5">Рецепт</th>
			<th rowspan="2"></th>
		</tr>
		<tr>
			<th>Окалина между</th>
			<th>КМП между</th>
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
	SELECT CW.CW_ID
		,CW.item
		,MF.MF_ID
		,SUB.cnt
		,MF.letter
		,MF.io_min
		,MF.io_max
		,MF.sn_min
		,MF.sn_max
		,MF.iron_oxide
		,MF.sand
		,MF.crushed_stone
		,MF.cement
		,MF.water
	FROM MixFormula MF
	JOIN CounterWeight CW ON CW.CW_ID = MF.CW_ID
	JOIN (
		SELECT CW_ID, SUM(1) cnt
		FROM MixFormula
		GROUP BY CW_ID
	) SUB ON SUB.CW_ID = MF.CW_ID
	ORDER BY MF.CW_ID, MF.letter
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	// Выводим общую ячейку с кодом противовеса
	if( $CW_ID != $row["CW_ID"] ) {
		echo "<tr id='{$row["MF_ID"]}' style='border-top: 2px solid #333;'>";
		echo "<td rowspan='{$row["cnt"]}'><b>{$row["item"]}</b></td>";
		$cnt = 0;
	}
	else {
		echo "<tr id='{$row["MF_ID"]}'>";
	}
	$CW_ID = $row["CW_ID"]
	?>
		<td><b><?=$row["letter"]?></b></td>
		<td class="bg-gray"><?=(($row["io_min"] and $row["io_max"]) ? ($row["io_min"]/1000)." - ".($row["io_max"]/1000) : "")?></td>
		<td class="bg-gray"><?=(($row["sn_min"] and $row["sn_max"]) ? ($row["sn_min"]/1000)." - ".($row["sn_max"]/1000) : "")?></td>
		<td style="background: #a52a2a80;"><?=$row["iron_oxide"]?></td>
		<td style="background: #f4a46082;"><?=$row["sand"]?></td>
		<td style="background: #8b45137a;"><?=$row["crushed_stone"]?></td>
		<td style="background: #7080906b;"><?=$row["cement"]?></td>
		<td style="background: #1e90ff85;"><?=$row["water"]?></td>
		<td><a href="#" class="add_formula" MF_ID="<?=$row["MF_ID"]?>" title="Изменить рецепт"><i class="fa fa-pencil-alt fa-lg"></i></a></td>
	</tr>
	<?
}
?>

	</tbody>
</table>

<div id="add_btn" class="add_formula" title="Добавить новый рецепт"></div>

<?
include "footer.php";
?>
