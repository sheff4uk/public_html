<?
include "config.php";
include "header.php";
include "./forms/route_sheet_form.php";

// Вывод маршрутных листов
?>
<table>
	<thead>
		<tr>
			<th>Противовес</th>
			<th>Операция</th>
			<th>Дата</th>
			<th>Время</th>
			<th><i class="far fa-lg fa-hourglass" title="Интервал в часах с моента заливки."></i></th>
			<th>№ замеса</th>
			<th>№ кассеты</th>
			<th>Кол-во годных деталей</th>
			<th>Непролив</th>
			<th>Трещина</th>
			<th>Скол</th>
			<th>Дефект форм</th>
			<th>Оператор/<br>пост</th>
			<th></th>
		</tr>
	</thead>
	<tbody style="text-align: center;">

<?
$query = "
	SELECT RS.RS_ID
		,CW.item
		,CW.in_cassette
		,CW.min_weight
		,CW.max_weight

		,DATE_FORMAT(RS.filling_date, '%d.%m.%y') filling_date
		,DATE_FORMAT(RS.filling_date, '%H:%i') filling_time
		,RS.batch
		,RS.cassette
		,RS.amount
		,OP.name OPname
		,sOP.name sOPname

		,DATE_FORMAT(RS.decoupling_date, '%d.%m.%y') decoupling_date
		,DATE_FORMAT(RS.decoupling_date, '%H:%i') decoupling_time
		,RS.d_amount
		,RS.d_not_spill
		,RS.d_crack
		,RS.d_chipped
		,RS.d_def_form
		,RS.d_post

		,DATE_FORMAT(RS.boxing_date, '%d.%m.%y') boxing_date
		,DATE_FORMAT(RS.boxing_date, '%H:%i') boxing_time
		,RS.weight1
		,RS.weight2
		,RS.weight3
		,RS.b_amount
		,RS.b_not_spill
		,RS.b_crack
		,RS.b_chipped
		,RS.b_def_form
		,RS.b_post

		,RS.interval1
		,RS.interval2
	FROM RouteSheet RS
	JOIN CounterWeight CW ON CW.CW_ID = RS.CW_ID
	JOIN Operator OP ON OP.OP_ID = RS.OP_ID
	LEFT JOIN Operator sOP ON sOP.OP_ID = RS.sOP_ID
	ORDER BY RS.RS_ID DESC
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	?>
		<tr style="border-top: 2px solid #333;">
			<td rowspan="3"><span style="font-size: 1.5em; font-weight: bold;"><?=$row["item"]?></span><p class="nowrap" id="<?=$row["RS_ID"]?>">id: <b><?=$row["RS_ID"]?></b></p></td>
			<td>Заливка</td>
			<td><?=$row["filling_date"]?></td>
			<td><?=$row["filling_time"]?></td>
			<td></td>
			<td><?=$row["batch"]?></td>
			<td><?=$row["cassette"]?></td>
			<td style="position: relative;"><b><?=$row["amount"]?></b><div style="background-color: chartreuse; left: 0; bottom: 0; width: <?=(100*$row["amount"]/$row["in_cassette"])?>%; position: absolute; height: 100%; opacity: .3;"></div></td>
			<td colspan="4" style="background-color: #333;"></td>
			<td><?=$row["OPname"]?><br><span style="font-size: .9em;"><?=$row["sOPname"]?></span></td>
			<td rowspan="3"><a href="#" class="add_route_sheet" RS_ID="<?=$row["RS_ID"]?>" title="Изменить маршрутный лист"><i class="fa fa-pencil-alt fa-lg"></i></a></td>
		</tr>
		<tr>
			<td>Расформовка</td>
			<td><?=$row["decoupling_date"]?></td>
			<td><?=$row["decoupling_time"]?></td>
			<td <?=($row["interval1"] < 24 ? "style='color: red;'" : "")?>><?=$row["interval1"]?></td>
			<td colspan="2" id="weight" style="border-top: 2px solid #333; border-left: 2px solid #333; border-right: 2px solid #333;">Вес <span class="nowrap"><?=$row["min_weight"]?> - <?=$row["max_weight"]?></span> г</td>
			<td style="position: relative;" <?=($row["d_amount"] < 0 ? "class='error'" : "")?>><b><?=$row["d_amount"]?></b><div style="background-color: chartreuse; left: 0; bottom: 0; width: <?=(100*$row["d_amount"]/$row["in_cassette"])?>%; position: absolute; height: 100%; opacity: .3;"></div></td>
			<td><?=$row["d_not_spill"]?></td>
			<td><?=$row["d_crack"]?></td>
			<td><?=$row["d_chipped"]?></td>
			<td><?=$row["d_def_form"]?></td>
			<td><?=$row["d_post"]?></td>
		</tr>
		<tr>
			<td>Упаковка</td>
			<td><?=$row["boxing_date"]?></td>
			<td><?=$row["boxing_time"]?></td>
			<td <?=($row["interval2"] < 120 ? "style='color: red;'" : "")?>><?=$row["interval2"]?></td>
			<td colspan="2" class="nowrap" style="border-left: 2px solid #333; border-right: 2px solid #333;">
				<span style="<?=(($row["weight1"] < $row["min_weight"] or $row["weight1"] > $row["max_weight"]) ? "color: red;" : "")?>"><?=$row["weight1"]?></span>&nbsp;&nbsp;
				<span style="<?=(($row["weight2"] < $row["min_weight"] or $row["weight2"] > $row["max_weight"]) ? "color: red;" : "")?>"><?=$row["weight2"]?></span>&nbsp;&nbsp;
				<span style="<?=(($row["weight3"] < $row["min_weight"] or $row["weight3"] > $row["max_weight"]) ? "color: red;" : "")?>"><?=$row["weight3"]?></span>
			</td>
			<td  style="position: relative;" <?=($row["b_amount"] < 0 ? "class='error'" : "")?>><b><?=$row["b_amount"]?></b><div style="background-color: chartreuse; left: 0; bottom: 0; width: <?=(100*$row["b_amount"]/$row["in_cassette"])?>%; position: absolute; height: 100%; opacity: .3;"></div></td>
			<td><?=$row["b_not_spill"]?></td>
			<td><?=$row["b_crack"]?></td>
			<td><?=$row["b_chipped"]?></td>
			<td><?=$row["b_def_form"]?></td>
			<td><?=$row["b_post"]?></td>
		</tr>
	<?
}
?>
	</tbody>
</table>

<div id="add_btn" class="add_route_sheet" title="Внести маршрутный лист"></div>

<?
include "footer.php";
?>
