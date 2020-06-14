<?
include "config.php";
include "header.php";
include "./forms/batch_checklist_form.php";

// Вывод чеклистов замеса
?>
<table>
	<thead>
		<tr>
			<th>Дата</th>
			<th>Противовес</th>
			<th>№ замеса</th>
			<th>Куб окалины</th>
			<th>Окалина</th>
			<th>Отсев</th>
			<th>Цемент</th>
			<th>Вода</th>
			<th>Куб смеси</th>
			<th></th>
		</tr>
	</thead>
	<tbody style="text-align: center;">

<?
$query = "
	SELECT BC.BC_ID
		,DATE_FORMAT(BC.batch_date, '%d.%m.%y') batch_date
		,CW.item
		,BC.batch_num
		,BC.iron_oxide_weight
		,BC.iron_oxide
		,BC.sand
		,BC.cement
		,BC.water
		,BC.mix_weight
	FROM BatchChecklist BC
	JOIN CounterWeight CW ON CW.CW_ID = BC.CW_ID
	ORDER BY BC.batch_date DESC, BC.CW_ID, BC.batch_num
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	?>
		<tr id="<?=$row["BC_ID"]?>">
			<td><?=$row["batch_date"]?></td>
			<td><b><?=$row["item"]?></b></td>
			<td><?=$row["batch_num"]?></td>
			<td><?=$row["iron_oxide_weight"]?></td>
			<td><?=$row["iron_oxide"]?></td>
			<td><?=$row["sand"]?></td>
			<td><?=$row["cement"]?></td>
			<td><?=$row["water"]?></td>
			<td><?=$row["mix_weight"]?></td>
			<td><a href="#" class="add_batch_checklist" BC_ID="<?=$row["BC_ID"]?>" title="Изменить данные замеса"><i class="fa fa-pencil-alt fa-lg"></i></a></td>
		</tr>
	<?
}
?>
	</tbody>
</table>

<div id="add_btn" class="add_batch_checklist" title="Внести данные замеса"></div>

<?
include "footer.php";
?>
