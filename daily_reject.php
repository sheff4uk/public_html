<?
include "config.php";
$title = 'Суточный учет брака';
include "header.php";
include "./forms/daily_reject_form.php";
?>
<table class="main_table">
	<thead>
		<tr>
			<th>Дата</th>
			<th>Противовес</th>
			<th>Расформовка</th>
			<th>Упаковка</th>
			<th></th>
		</tr>
	</thead>
	<tbody style="text-align: center;">

<?
$query = "
	SELECT LDR.LDR_ID
		,DATE_FORMAT(LDR.reject_date, '%d.%m.%y') reject_date
		,CW.item
		,LDR.o_reject_cnt
		,LDR.p_reject_cnt
	FROM list__DailyReject LDR
	JOIN CounterWeight CW ON CW.CW_ID = LDR.CW_ID
	ORDER BY LDR.reject_date DESC, LDR.CW_ID
	LIMIT 500
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	?>
	<tr id="<?=$row["LDR_ID"]?>">
		<td><?=$row["reject_date"]?></td>
		<td><?=$row["item"]?></td>
		<td><?=$row["o_reject_cnt"]?></td>
		<td><?=$row["p_reject_cnt"]?></td>
		<td><a href="#" class="add_reject" LDR_ID="<?=$row["LDR_ID"]?>" title="Изменить данные cуточного брака"><i class="fa fa-pencil-alt fa-lg"></i></a></td>
	</tr>
	<?
}
?>

	</tbody>
</table>

<div id="add_btn" class="add_reject" reject_date="<?=$_GET["reject_date"]?>" title="Внести данные суточного брака"></div>

<?
include "footer.php";
?>
