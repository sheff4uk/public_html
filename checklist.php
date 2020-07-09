<?
include "config.php";
$title = 'Замес/Заливка';
include "header.php";
include "./forms/checklist_form.php";
?>

<table class="main_table">
	<thead>
		<tr>
			<th rowspan="2">Дата<br>Противовес</th>
			<th rowspan="2">Время</th>
			<th rowspan="2">Оператор</th>
			<th colspan="2">Масса кубика, кг</th>
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
// Получаем список дат и противовесов и кол-во замесов на эти даты
$query = "
	SELECT
		LB.batch_date,
		LB.CW_ID,
		SUM(1) cnt
	FROM list__Batch LB
	GROUP BY LB.batch_date, LB.CW_ID
	ORDER BY LB.batch_date DESC, LB.CW_ID
	LIMIT 30
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	$cnt = $row["cnt"];

	$query = "
		SELECT LB.LB_ID
			,CW.item
			,OP.name
			,DATE_FORMAT(LB.batch_date, '%d.%m.%y') batch_date
			,DATE_FORMAT(LB.batch_time, '%H:%i') batch_time
			,LB.comp_density/1000 comp_density
			,LB.mix_density/1000 mix_density
			,LB.iron_oxide
			,LB.sand
			,LB.crushed_stone
			,LB.cement
			,LB.water
			,GROUP_CONCAT(LF.cassette ORDER BY LF.LF_ID SEPARATOR '/') cassette
			,LB.underfilling
		FROM list__Batch LB
		JOIN CounterWeight CW ON CW.CW_ID = LB.CW_ID
		JOIN Operator OP ON OP.OP_ID = LB.OP_ID
		JOIN list__Filling LF ON LF.LB_ID = LB.LB_ID
		WHERE LB.batch_date LIKE '{$row["batch_date"]}' AND LB.CW_ID = {$row["CW_ID"]}
		GROUP BY LB.LB_ID
		ORDER BY LB.batch_time ASC
	";
	$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $subrow = mysqli_fetch_array($subres) ) {
		$comp_density = (float)$subrow["comp_density"];
		$mix_density = (float)$subrow["mix_density"];

		// Выводим общую ячейку с датой кодом
		if( $cnt ) {
			echo "<tr style='border-top: 2px solid #333;' id='{$subrow["LB_ID"]}'>";
			echo "<td rowspan='{$cnt}' style='background-color: rgba(0, 0, 0, 0.2);'>{$subrow["batch_date"]}<br><b>{$subrow["item"]}</b><br>Замесов: <b>{$cnt}</b></td>";
			$cnt = 0;
		}
		else {
			echo "<tr id='{$subrow["LB_ID"]}'>";
		}
		?>
				<td><?=$subrow["batch_time"]?></td>
				<td><?=$subrow["name"]?></td>
				<td><?=$comp_density?></td>
				<td><?=$mix_density?></td>
				<td style="background-color: rgba(0, 0, 0, 0.2);"><?=$subrow["iron_oxide"]?></td>
				<td style="background-color: rgba(0, 0, 0, 0.2);"><?=$subrow["sand"]?></td>
				<td style="background-color: rgba(0, 0, 0, 0.2);"><?=$subrow["crushed_stone"]?></td>
				<td style="background-color: rgba(0, 0, 0, 0.2);"><?=$subrow["cement"]?></td>
				<td style="background-color: rgba(0, 0, 0, 0.2);"><?=$subrow["water"]?></td>
				<td class="nowrap"><?=$subrow["cassette"]?></td>
				<td><?=$subrow["underfilling"]?></td>
				<td><a href="#" class="add_checklist" LB_ID="<?=$subrow["LB_ID"]?>" title="Изменить данные замеса"><i class="fa fa-pencil-alt fa-lg"></i></a></td>
			</tr>
		<?
	}
}
?>
	</tbody>
</table>

<div id="add_btn" class="add_checklist" CW_ID="<?=$_GET["CW_ID"]?>" batch_date="<?=$_GET["batch_date"]?>" OP_ID="<?=$_GET["OP_ID"]?>" title="Внести данные замеса"></div>

<?
include "footer.php";
?>
