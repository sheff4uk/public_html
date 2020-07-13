<?
include "config.php";
$title = 'Расформовка';
include "header.php";
include "./forms/opening_form.php";
?>
<table class="main_table">
	<thead>
		<tr>
			<th rowspan="2">Дата</th>
			<th rowspan="2">Время</th>
			<th rowspan="2">№ поста</th>
			<th colspan="4">Кол-во брака, шт</th>
			<th colspan="3">Взвешивания, кг ±3%</th>
			<th rowspan="2">Заливка</th>
			<th rowspan="2"></th>
		</tr>
		<tr>
			<th>Непролив</th>
			<th>Трещина</th>
			<th>Скол</th>
			<th>Дефект форм</th>
			<th>№1</th>
			<th>№2</th>
			<th>№3</th>
		</tr>
	</thead>
	<tbody style="text-align: center;">

<?
$query = "
	SELECT LO.LO_ID
		,LO.o_post
		,DATE_FORMAT(LO.o_date, '%d.%m.%y') o_date
		,DATE_FORMAT(LO.o_time, '%H:%i') o_time
		,LO.o_not_spill
		,LO.o_crack
		,LO.o_chipped
		,LO.o_def_form
		,LO.weight1
		,LO.weight2
		,LO.weight3
		,LO.w1_diff
		,LO.w2_diff
		,LO.w3_diff
		,LO.w1_error
		,LO.w2_error
		,LO.w3_error
		,DATE_FORMAT(LB.batch_date, '%d.%m.%y') batch_date
		,LF.cassette
		,CW.item
	FROM list__Opening LO
	LEFT JOIN list__Filling LF ON LF.LF_ID = LO.LF_ID
	LEFT JOIN list__Batch LB ON LB.LB_ID = LF.LB_ID
	LEFT JOIN CounterWeight CW ON CW.CW_ID = LB.CW_ID
	ORDER BY LO.o_date DESC, LO.o_time, LO.o_post
	LIMIT 500
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	?>
	<tr id="<?=$row["LO_ID"]?>">
		<td><?=$row["o_date"]?></td>
		<td><?=$row["o_time"]?></td>
		<td><?=$row["o_post"]?></td>
		<td style="color: red;"><?=$row["o_not_spill"]?></td>
		<td style="color: red;"><?=$row["o_crack"]?></td>
		<td style="color: red;"><?=$row["o_chipped"]?></td>
		<td style="color: red;"><?=$row["o_def_form"]?></td>
		<td><?=$row["weight1"]/1000?><?=($row["w1_error"] ? "<font style='font-size: .8em;' color='red'>".($row["w1_diff"] > 0 ? " +" : " ").($row["w1_diff"]/10)."%</font>" : "")?></td>
		<td><?=$row["weight2"]/1000?><?=($row["w2_error"] ? "<font style='font-size: .8em;' color='red'>".($row["w2_diff"] > 0 ? " +" : " ").($row["w2_diff"]/10)."%</font>" : "")?></td>
		<td><?=$row["weight3"]/1000?><?=($row["w3_error"] ? "<font style='font-size: .8em;' color='red'>".($row["w3_diff"] > 0 ? " +" : " ").($row["w3_diff"]/10)."%</font>" : "")?></td>
		<td title="Кассета[<?=$row["cassette"]?>] <?=$row["item"]?>" style="background-color: rgba(0, 0, 0, 0.2);"><?=$row["batch_date"]?> <i class="fas fa-question-circle"></i></td>
		<td><a href="#" class="add_opening" LO_ID="<?=$row["LO_ID"]?>" title="Изменить данные расформовки"><i class="fa fa-pencil-alt fa-lg"></i></a></td>
	</tr>
	<?
}
?>

	</tbody>
</table>

<div id="add_btn" class="add_opening" o_date="<?=$_GET["o_date"]?>" o_post="<?=$_GET["o_post"]?>" title="Внести данные расформовки"></div>

<?
include "footer.php";
?>
