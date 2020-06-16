<?
include "config.php";
$title = 'Чек-лист оператора';
include "header.php";
include "./forms/operator_checklist_form.php";

// Вывод чеклистов замеса
?>
<table style="width: 100%;">
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
			<th>Оператор</th>
			<th></th>
		</tr>
	</thead>
	<tbody style="text-align: center;">

<?
$query = "
	SELECT OC.OC_ID
		,DATE_FORMAT(OC.batch_date, '%d.%m.%y') batch_date
		,CW.item
		,OC.batch_num
		,OC.iron_oxide_weight
		,OC.iron_oxide
		,OC.sand
		,OC.cement
		,OC.water
		,OC.mix_weight
		,OP.name OPname
		,sOP.name sOPname
	FROM OperatorChecklist OC
	JOIN CounterWeight CW ON CW.CW_ID = OC.CW_ID
	JOIN Operator OP ON OP.OP_ID = OC.OP_ID
	LEFT JOIN Operator sOP ON sOP.OP_ID = OC.sOP_ID
	ORDER BY OC.batch_date DESC, OC.CW_ID, OC.batch_num
	LIMIT 500
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	?>
		<tr id="<?=$row["OC_ID"]?>">
			<td><?=$row["batch_date"]?></td>
			<td><b><?=$row["item"]?></b></td>
			<td><?=$row["batch_num"]?></td>
			<td><?=$row["iron_oxide_weight"]?></td>
			<td style="background: sandybrown;"><?=$row["iron_oxide"]?></td>
			<td style="background: palegoldenrod;"><?=$row["sand"]?></td>
			<td style="background: darkgrey;"><?=$row["cement"]?></td>
			<td style="background: lightskyblue;"><?=$row["water"]?></td>
			<td><?=$row["mix_weight"]?></td>
			<td><?=$row["OPname"]?><br><span style="font-size: .9em;"><?=$row["sOPname"]?></span></td>
			<td><a href="#" class="add_operator_checklist" OC_ID="<?=$row["OC_ID"]?>" title="Изменить данные замеса"><i class="fa fa-pencil-alt fa-lg"></i></a></td>
		</tr>
	<?
}
?>
	</tbody>
</table>

<div id="add_btn" class="add_operator_checklist" title="Внести данные замеса"></div>

<?
include "footer.php";
?>
