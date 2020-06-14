<?
include "config.php";
include "header.php";
include "./forms/route_sheet_form.php";
?>

<!--Фильтр-->
<div id="filter">
	<h3>Фильтр</h3>
	<p>В разработке.</p>
</div>
<script>
	$(document).ready(function() {
		$( "#filter" ).accordion({
			active: false,
			collapsible: true,
			heightStyle: "content"
		});
	});
</script>

<!--Вывод маршрутных листов-->
<table style="width: 100%;">
	<thead>
		<tr>
			<th>Противовес</th>
			<th>Операция</th>
			<th>Дата</th>
			<th>Время (смена)</th>
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
		,RS.filling_shift
		,RS.batch
		,RS.cassette
		,RS.amount
		,OP.name OPname
		,sOP.name sOPname

		,DATE_FORMAT(RS.opening_date, '%d.%m.%y') opening_date
		,DATE_FORMAT(RS.opening_date, '%H:%i') opening_time
		,RS.opening_shift
		,RS.o_amount
		,RS.o_not_spill
		,RS.o_crack
		,RS.o_chipped
		,RS.o_def_form
		,RS.o_post

		,DATE_FORMAT(RS.boxing_date, '%d.%m.%y') boxing_date
		,DATE_FORMAT(RS.boxing_date, '%H:%i') boxing_time
		,RS.boxing_shift
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
	#ORDER BY RS.RS_ID DESC
	ORDER BY RS.filling_date DESC
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	?>
		<tr style="border-top: 2px solid #333;">
			<td rowspan="3"><span style="font-size: 1.5em; font-weight: bold;"><?=$row["item"]?></span><p class="nowrap" id="<?=$row["RS_ID"]?>">id: <b><?=$row["RS_ID"]?></b></p></td>
			<td>Заливка</td>
			<td><?=$row["filling_date"]?></td>
			<td><?=$row["filling_time"]?> (<?=$row["filling_shift"]?>)</td>
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
			<td><?=$row["opening_date"]?></td>
			<td><?=$row["opening_time"]?> (<?=$row["opening_shift"]?>)</td>
			<td <?=($row["interval1"] < 24 ? "class='error'" : "")?>><?=$row["interval1"]?></td>
			<td colspan="2" id="weight" style="border-top: 2px solid #333; border-left: 2px solid #333; border-right: 2px solid #333;">Вес <span class="nowrap"><?=$row["min_weight"]?> - <?=$row["max_weight"]?></span> г</td>
			<td style="position: relative;" <?=($row["o_amount"] < 0 ? "class='error'" : "")?>><b><?=$row["o_amount"]?></b><div style="background-color: chartreuse; left: 0; bottom: 0; width: <?=(100*$row["o_amount"]/$row["in_cassette"])?>%; position: absolute; height: 100%; opacity: .3;"></div></td>
			<td><?=$row["o_not_spill"]?></td>
			<td><?=$row["o_crack"]?></td>
			<td><?=$row["o_chipped"]?></td>
			<td><?=$row["o_def_form"]?></td>
			<td><?=$row["o_post"]?></td>
		</tr>
		<tr>
			<td>Упаковка</td>
			<td><?=$row["boxing_date"]?></td>
			<td><?=$row["boxing_time"]?> (<?=$row["boxing_shift"]?>)</td>
			<td <?=($row["interval2"] < 120 ? "class='error'" : "")?>><?=$row["interval2"]?></td>
			<td colspan="2" class="nowrap" style="border-left: 2px solid #333; border-right: 2px solid #333;">
				<span class="<?=(($row["weight1"] < $row["min_weight"] or $row["weight1"] > $row["max_weight"]) ? "bg-red" : "")?>"><?=$row["weight1"]?></span>&nbsp;&nbsp;
				<span class="<?=(($row["weight2"] < $row["min_weight"] or $row["weight2"] > $row["max_weight"]) ? "bg-red" : "")?>"><?=$row["weight2"]?></span>&nbsp;&nbsp;
				<span class="<?=(($row["weight3"] < $row["min_weight"] or $row["weight3"] > $row["max_weight"]) ? "bg-red" : "")?>"><?=$row["weight3"]?></span>
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
