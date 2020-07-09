<?
include "config.php";
$title = 'Упаковка';
include "header.php";
include "./forms/packing_form.php";
?>
<table class="main_table">
	<thead>
		<tr>
			<th rowspan="2">Дата</th>
			<th rowspan="2">Время</th>
			<th rowspan="2">№ поста</th>
			<th colspan="4">Кол-во брака, шт</th>
			<th rowspan="2">Заливка</th>
			<th rowspan="2"></th>
		</tr>
		<tr>
			<th>Непролив</th>
			<th>Трещина</th>
			<th>Скол</th>
			<th>Дефект форм</th>
		</tr>
	</thead>
	<tbody style="text-align: center;">

<?
$query = "
	SELECT LP.LP_ID
		,LP.p_post
		,DATE_FORMAT(LP.p_date, '%d.%m.%y') p_date
		,DATE_FORMAT(LP.p_time, '%H:%i') p_time
		,LP.p_not_spill
		,LP.p_crack
		,LP.p_chipped
		,LP.p_def_form
		,DATE_FORMAT(LB.batch_date, '%d.%m.%y') batch_date
		,LF.cassette
		,CW.item
	FROM list__Packing LP
	LEFT JOIN list__Filling LF ON LF.LF_ID = LP.LF_ID
	LEFT JOIN list__Batch LB ON LB.LB_ID = LF.LB_ID
	LEFT JOIN CounterWeight CW ON CW.CW_ID = LB.CW_ID
	ORDER BY LP.p_date DESC, LP.p_time, LP.p_post
	LIMIT 500
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	?>
	<tr id="<?=$row["LP_ID"]?>">
		<td><?=$row["p_date"]?></td>
		<td><?=$row["p_time"]?></td>
		<td><?=$row["p_post"]?></td>
		<td><?=$row["p_not_spill"]?></td>
		<td><?=$row["p_crack"]?></td>
		<td><?=$row["p_chipped"]?></td>
		<td><?=$row["p_def_form"]?></td>
		<td title="Кассета[<?=$row["cassette"]?>] <?=$row["item"]?>" style="background-color: rgba(0, 0, 0, 0.2);"><?=$row["batch_date"]?> <i class="fas fa-question-circle"></i></td>
		<td><a href="#" class="add_packing" LP_ID="<?=$row["LP_ID"]?>" title="Изменить данные упаковки"><i class="fa fa-pencil-alt fa-lg"></i></a></td>
	</tr>
	<?
}
?>

	</tbody>
</table>

<div id="add_btn" class="add_packing" p_date="<?=$_GET["p_date"]?>" p_post="<?=$_GET["p_post"]?>" title="Внести данные упаковки "></div>

<?
include "footer.php";
?>
