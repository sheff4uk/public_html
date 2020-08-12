<?
include "config.php";
$title = 'Протокол испытаний куба';
include "header.php";
include "./forms/cubetest_form.php";
?>

<h1>Планируемые испытания</h1>
<table style="table-layout: fixed; width: 100%;">
	<thead>
		<tr>
			<th>Противовес</th>
			<th>Дата замеса</th>
			<th>Время замеса</th>
			<th>Масса куба смеси, кг</th>
			<th>Дата испытания</th>
			<th>Время испытания</th>
			<th>Масса испытуемого куба, кг</th>
			<th>Давление, МПа</th>
			<th>Выдержка в часах</th>
			<th></th>
		</tr>
	</thead>
	<tbody style="text-align: center;">
<?
$query = "
	SELECT LB.LB_ID
		,CW.CW_ID
		,CW.item
		,LB.batch_date
		,DATE_FORMAT(LB.batch_date, '%d.%m.%y') batch_date_format
		,DATE_FORMAT(LB.batch_time, '%H:%i') batch_time_format
		,LB.mix_density
		,DATE_FORMAT(LB.batch_date + INTERVAL 1 DAY, '%d.%m.%y') test_date_format
		,DATE_FORMAT(LB.batch_time, '%H:%i') test_time_format
		,LB.batch_date + INTERVAL 1 DAY test_date
		,24 delay
		,1 type
		,CAST(CONCAT(LB.batch_date + INTERVAL 1 DAY, ' ', LB.batch_time) as datetime) test_date_time
	FROM list__Batch LB
	JOIN CounterWeight CW ON CW.CW_ID = LB.CW_ID
	LEFT JOIN list__CubeTest LCT ON LCT.LB_ID = LB.LB_ID AND LCT.type = 1
	WHERE LB.test = 1
		AND LCT.LCT_ID IS NULL
	UNION ALL
	SELECT LB.LB_ID
		,CW.CW_ID
		,CW.item
		,LB.batch_date
		,DATE_FORMAT(LB.batch_date, '%d.%m.%y') batch_date_format
		,DATE_FORMAT(LB.batch_time, '%H:%i') batch_time_format
		,LB.mix_density
		,DATE_FORMAT(LB.batch_date + INTERVAL 3 DAY, '%d.%m.%y') test_date_format
		,DATE_FORMAT(LB.batch_time, '%H:%i') test_time_format
		,LB.batch_date + INTERVAL 3 DAY test_date
		,72 delay
		,2 type
		,CAST(CONCAT(LB.batch_date + INTERVAL 3 DAY, ' ', LB.batch_time) as datetime) test_date_time
	FROM list__Batch LB
	JOIN CounterWeight CW ON CW.CW_ID = LB.CW_ID
	LEFT JOIN list__CubeTest LCT ON LCT.LB_ID = LB.LB_ID AND LCT.type = 2
	WHERE LB.test = 1
		AND LCT.LCT_ID IS NULL
	ORDER BY test_date_time
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	?>
	<tr>
		<td class="bg-gray"><?=$row["item"]?></td>
		<td class="bg-gray"><a href="checklist.php?date_from=<?=$row["batch_date"]?>&date_to=<?=$row["batch_date"]?>&CW_ID=<?=$row["CW_ID"]?>#<?=$row["LB_ID"]?>" title="Замес" target="_blank"><?=$row["batch_date_format"]?></a></td>
		<td class="bg-gray"><?=$row["batch_time_format"]?></td>
		<td class="bg-gray"><?=$row["mix_density"]/1000?></td>
		<td><?=$row["test_date_format"]?></td>
		<td><?=$row["test_time_format"]?></td>
		<td></td>
		<td></td>
		<td><?=$row["delay"]?></td>
		<td><a href="#" class="add_cubetest" LB_ID="<?=$row["LB_ID"]?>" type="<?=$row["type"]?>" test_date="<?=$row["test_date"]?>" title="Внести данные испытания куба"><i class="fa fa-plus-square fa-lg"></i></a></td>
	</tr>
	<?
}
?>
	</tbody>
</table>

<h1>Произведенные испытания</h1>
<table class="main_table">
	<thead>
		<tr>
			<th>Противовес</th>
			<th>Дата замеса</th>
			<th>Время замеса</th>
			<th>Масса куба смеси, кг</th>
			<th>Дата испытания</th>
			<th>Время испытания</th>
			<th>Масса испытуемого куба, кг</th>
			<th>Давление, МПа</th>
			<th>Выдержка в часах</th>
			<th></th>
		</tr>
	</thead>
	<tbody style="text-align: center;">

<?
$query = "
	SELECT LCT.LCT_ID
		,LCT.LB_ID
		,DATE_FORMAT(LCT.test_date, '%d.%m.%y') test_date
		,DATE_FORMAT(LCT.test_time, '%H:%i') test_time
		,CW.item
		,CW.CW_ID
		,LB.batch_date batch_date
		,DATE_FORMAT(LB.batch_date, '%d.%m.%y') batch_date_format
		,DATE_FORMAT(LB.batch_time, '%H:%i') batch_time_format
		,TIMESTAMPDIFF(HOUR, CAST(CONCAT(LB.batch_date, ' ', LB.batch_time) as datetime), CAST(CONCAT(LCT.test_date, ' ', LCT.test_time) as datetime)) delay
		,LB.mix_density
		,LCT.cube_weight
		,LCT.pressure
	FROM list__CubeTest LCT
	JOIN list__Batch LB ON LB.LB_ID = LCT.LB_ID
	JOIN CounterWeight CW ON CW.CW_ID = LB.CW_ID
	ORDER BY LCT.test_date DESC, LCT.test_time DESC
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	?>
	<tr id="<?=$row["LCT_ID"]?>">
		<td class="bg-gray"><?=$row["item"]?></td>
		<td class="bg-gray"><a href="checklist.php?date_from=<?=$row["batch_date"]?>&date_to=<?=$row["batch_date"]?>&CW_ID=<?=$row["CW_ID"]?>#<?=$row["LB_ID"]?>" title="Замес" target="_blank"><?=$row["batch_date_format"]?></a></td>
		<td class="bg-gray"><?=$row["batch_time_format"]?></td>
		<td class="bg-gray"><?=$row["mix_density"]/1000?></td>
		<td><?=$row["test_date"]?></td>
		<td><?=$row["test_time"]?></td>
		<td><?=$row["cube_weight"]/1000?></td>
		<td><?=$row["pressure"]?></td>
		<td><?=$row["delay"]?></td>
		<td><a href="#" class="add_cubetest" LCT_ID="<?=$row["LCT_ID"]?>" title="Изменить данные испытания куба"><i class="fa fa-pencil-alt fa-lg"></i></a></td>
	</tr>
	<?
}
?>

	</tbody>
</table>

<?
include "footer.php";
?>
