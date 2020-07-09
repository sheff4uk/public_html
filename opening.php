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
			<th colspan="3">Взвешивания, кг</th>
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
	SELECT
		LO.LO_ID,
		LO.o_post,
		DATE_FORMAT(LO.o_date, '%d.%m.%y') o_date,
		DATE_FORMAT(LO.o_time, '%H:%i') o_time,
		LO.o_not_spill,
		LO.o_crack,
		LO.o_chipped,
		LO.o_def_form,
		LO.weight1/1000 weight1,
		LO.weight2/1000 weight2,
		LO.weight3/1000 weight3
	FROM list__Opening LO
	ORDER BY LO.o_date DESC, LO.o_time, LO.o_post
	LIMIT 500
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	$weight1 = (float)$row["weight1"];
	$weight2 = (float)$row["weight2"];
	$weight3 = (float)$row["weight3"];
	?>
	<tr id="<?=$row["LO_ID"]?>">
		<td><?=$row["o_date"]?></td>
		<td><?=$row["o_time"]?></td>
		<td><?=$row["o_post"]?></td>
		<td><?=$row["o_not_spill"]?></td>
		<td><?=$row["o_crack"]?></td>
		<td><?=$row["o_chipped"]?></td>
		<td><?=$row["o_def_form"]?></td>
		<td><?=$weight1?></td>
		<td><?=$weight2?></td>
		<td><?=$weight3?></td>
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
