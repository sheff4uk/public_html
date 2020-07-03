<?
include "config.php";
$title = 'Чек-лист';
include "header.php";
include "./forms/checklist_form.php";
?>

<table class="main_table">
	<thead>
		<tr>
			<th rowspan="2">Дата</th>
			<th rowspan="2">Время</th>
			<th rowspan="2">Противовес</th>
			<th rowspan="2">Оператор</th>
			<th colspan="2">Масса кубика, г</th>
			<th rowspan="2">Окалина, кг</th>
			<th rowspan="2">КМП, кг</th>
			<th rowspan="2">Отсев, кг</th>
			<th rowspan="2">Цемент, кг</th>
			<th rowspan="2">Вода, л</th>
			<th rowspan="2">№ кассеты</th>
			<th rowspan="2">Недолив</th>
			<th rowspan="2"></th>
		</tr>
		<tr>
			<th>Контрольный компонент</th>
			<th>Раствор</th>
		</tr>
	</thead>
	<tbody style="text-align: center;">

<?
$query = "
	SELECT LB.LB_ID
		,CW.item
		,OP.name
		,DATE_FORMAT(LB.batch_date, '%d.%m.%y') batch_date
		,DATE_FORMAT(LB.batch_time, '%H:%i') batch_time
		,LB.comp_density
		,LB.mix_density
		,LB.iron_oxide
		,LB.sand
		,LB.crushed_stone
		,LB.cement
		,LB.water
		,GROUP_CONCAT(LP.cassette) cassette
		,LB.underfilling
	FROM list__Batches LB
	JOIN CounterWeight CW ON CW.CW_ID = LB.CW_ID
	JOIN Operator OP ON OP.OP_ID = LB.OP_ID
	JOIN list__Pourings LP ON LP.LB_ID = LB.LB_ID
	GROUP BY LB.LB_ID
	ORDER BY LB.batch_date DESC, LB.batch_time ASC, LB.CW_ID
	LIMIT 500
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	?>
		<tr id="<?=$row["LB_ID"]?>">
			<td><?=$row["batch_date"]?></td>
			<td><?=$row["batch_time"]?></td>
			<td><b><?=$row["item"]?></b></td>
			<td><?=$row["name"]?></td>
			<td><?=$row["comp_density"]?></td>
			<td><?=$row["mix_density"]?></td>
			<td style="background-color: rgba(0, 0, 0, 0.2);"><?=$row["iron_oxide"]?></td>
			<td style="background-color: rgba(0, 0, 0, 0.2);"><?=$row["sand"]?></td>
			<td style="background-color: rgba(0, 0, 0, 0.2);"><?=$row["crushed_stone"]?></td>
			<td style="background-color: rgba(0, 0, 0, 0.2);"><?=$row["cement"]?></td>
			<td style="background-color: rgba(0, 0, 0, 0.2);"><?=$row["water"]?></td>
			<td class="nowrap"><?=$row["cassette"]?></td>
			<td><?=$row["underfilling"]?></td>
			<td><a href="#" class="add_checklist" LB_ID="<?=$row["LB_ID"]?>" title="Изменить данные замеса"><i class="fa fa-pencil-alt fa-lg"></i></a></td>
		</tr>
	<?
}
?>
	</tbody>
</table>

<div id="add_btn" class="add_checklist" CW_ID="<?=$_GET["CW_ID"]?>" batch_date="<?=$_GET["batch_date"]?>" OP_ID="<?=$_GET["OP_ID"]?>" title="Внести данные замеса"></div>

<?
include "footer.php";
?>
