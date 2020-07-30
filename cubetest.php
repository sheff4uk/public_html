<?
include "config.php";
$title = 'Протокол испытаний куба';
include "header.php";
include "./forms/cubetest_form.php";
?>

<table class="main_table">
	<thead>
		<tr>
			<th rowspan="2">Дата испытания</th>
			<th rowspan="2">Противовес</th>
			<th colspan="4">24 часа</th>
			<th colspan="4">72 часа</th>
			<th rowspan="2"></th>
		</tr>
		<tr>
			<th>Дата заливки</th>
			<th>Время теста</th>
			<th>Масса куба, кг</th>
			<th>Давление, МПа</th>
			<th>Дата заливки</th>
			<th>Время теста</th>
			<th>Масса куба, кг</th>
			<th>Давление, МПа</th>
		</tr>
	</thead>
	<tbody style="text-align: center;">

<?
$query = "
	SELECT LCT.LCT_ID
		,DATE_FORMAT(LCT.test_date, '%d.%m.%y') test_date
		,CW.item
		,DATE_FORMAT(LCT.24_filling_date, '%d.%m.%y') 24_filling_date
		,DATE_FORMAT(LCT.24_test_time, '%H:%i') 24_test_time
		,LCT.24_cube_weight
		,LCT.24_pressure
		,DATE_FORMAT(LCT.72_filling_date, '%d.%m.%y') 72_filling_date
		,DATE_FORMAT(LCT.72_test_time, '%H:%i') 72_test_time
		,LCT.72_cube_weight
		,LCT.72_pressure
	FROM list__CubeTest LCT
	JOIN CounterWeight CW ON CW.CW_ID = LCT.CW_ID
	ORDER BY LCT.test_date DESC, LCT.CW_ID, LCT.24_test_time DESC
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	?>
	<tr id="<?=$row["LCT_ID"]?>">
		<td><?=$row["test_date"]?></td>
		<td><?=$row["item"]?></td>
		<td><?=$row["24_filling_date"]?></td>
		<td><?=$row["24_test_time"]?></td>
		<td><?=$row["24_cube_weight"]/1000?></td>
		<td><?=$row["24_pressure"]?></td>
		<td><?=$row["72_filling_date"]?></td>
		<td><?=$row["72_test_time"]?></td>
		<td><?=$row["72_cube_weight"]/1000?></td>
		<td><?=$row["72_pressure"]?></td>
		<td><a href="#" class="add_cubetest" LCT_ID="<?=$row["LCT_ID"]?>" title="Изменить данные испытания куба"><i class="fa fa-pencil-alt fa-lg"></i></a></td>
	</tr>
	<?
}
?>

	</tbody>
</table>

<div id="add_btn" class="add_cubetest" test_date="<?=$_GET["test_date"]?>" title="Внести данные испытания куба"></div>

<?
include "footer.php";
?>
