<?
include "config.php";
$title = 'Статистика по браку';
include "header.php";
?>

<style>
	.summary td {
		background-color: rgba(0, 0, 0, 0.2);
	}
</style>

<table class="main_table">
	<thead>
		<tr>
			<th>Дата заливки</th>
			<th>Код противовеса</th>
			<th>Кол-во заливок</th>
			<th>Непролив</th>
			<th>Трещина</th>
			<th>Скол</th>
			<th>Дефект форм</th>
			<th>Всего брака</th>
		</tr>
	</thead>
	<tbody style="text-align: center;">

<?
// Получаем список дат и список залитых деталей на эти даты
$query = "
	SELECT
		RS.filling_date,
		COUNT(distinct(RS.CW_ID)) item_cnt,
		SUM(IFNULL(o_not_spill,0)) + SUM(IFNULL(b_not_spill,0)) not_spill,
		SUM(IFNULL(o_crack,0)) + SUM(IFNULL(b_crack,0)) crack,
		SUM(IFNULL(o_chipped,0)) + SUM(IFNULL(b_chipped,0)) chipped,
		SUM(IFNULL(o_def_form,0)) + SUM(IFNULL(b_def_form,0)) def_form,
		SUM(1) cnt
	FROM RouteSheet RS
	GROUP BY filling_date
	ORDER BY RS.filling_date DESC
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	$item_cnt = $row["item_cnt"];

	$query = "
		SELECT
			DATE_FORMAT(RS.filling_date, '%d.%m.%y') date,
			CW.item,
			RS.CW_ID,
			SUM(RS.o_not_spill) o_not_spill,
			SUM(RS.o_crack) o_crack,
			SUM(RS.o_chipped) o_chipped,
			SUM(RS.o_def_form) o_def_form,
			SUM(RS.b_not_spill) b_not_spill,
			SUM(RS.b_crack) b_crack,
			SUM(RS.b_chipped) b_chipped,
			SUM(RS.b_def_form) b_def_form,
			SUM(1) cnt
		FROM RouteSheet RS
		JOIN CounterWeight CW ON CW.CW_ID = RS.CW_ID
		WHERE filling_date LIKE '{$row["filling_date"]}'
		GROUP BY RS.filling_date, RS.CW_ID
		ORDER BY RS.filling_date DESC
	";
	$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $subrow = mysqli_fetch_array($subres) ) {
		// Выводим общую ячейку с датой заливки
		if( $item_cnt ) {
			$item_cnt++;
			echo "<tr style='border-top: 2px solid #333;'>";
			echo "<td rowspan='{$item_cnt}'>{$subrow["date"]}</td>";
			$item_cnt = 0;
		}
		else {
			echo "<tr>";
		}

		echo "<td>{$subrow["item"]}</td>";
		echo "<td>{$subrow["cnt"]}</td>";
		echo "<td><a href='route_sheet.php?filling_date_from={$row["filling_date"]}&amp;filling_date_to={$row["filling_date"]}&amp;CW_ID={$subrow["CW_ID"]}&amp;not_spill=1' target='_blank'>{$subrow["o_not_spill"]} / {$subrow["b_not_spill"]}</a></td>";
		echo "<td><a href='route_sheet.php?filling_date_from={$row["filling_date"]}&amp;filling_date_to={$row["filling_date"]}&amp;CW_ID={$subrow["CW_ID"]}&amp;crack=1' target='_blank'>{$subrow["o_crack"]} / {$subrow["b_crack"]}</a></td>";
		echo "<td><a href='route_sheet.php?filling_date_from={$row["filling_date"]}&amp;filling_date_to={$row["filling_date"]}&amp;CW_ID={$subrow["CW_ID"]}&amp;chipped=1' target='_blank'>{$subrow["o_chipped"]} / {$subrow["b_chipped"]}</a></td>";
		echo "<td><a href='route_sheet.php?filling_date_from={$row["filling_date"]}&amp;filling_date_to={$row["filling_date"]}&amp;CW_ID={$subrow["CW_ID"]}&amp;def_form=1' target='_blank'>{$subrow["o_def_form"]} / {$subrow["b_def_form"]}</a></td>";
		$total = $subrow["o_not_spill"] + $subrow["b_not_spill"] + $subrow["o_crack"] + $subrow["b_crack"] + $subrow["o_chipped"] + $subrow["b_chipped"] + $subrow["o_def_form"] + $subrow["b_def_form"];
		echo "<td>{$total}</td>";
		echo "</tr>";
	}
	echo "<tr class='summary'>";
	echo "<td>Итог:</td>";
	echo "<td>{$row["cnt"]}</td>";
	echo "<td>{$row["not_spill"]}</td>";
	echo "<td>{$row["crack"]}</td>";
	echo "<td>{$row["chipped"]}</td>";
	echo "<td>{$row["def_form"]}</td>";
	$total = $row["not_spill"] + $row["crack"] + $row["chipped"] + $row["def_form"];
	echo "<td>{$total}</td>";
	echo "</tr>";
}
?>
	</tbody>
</table>

<?
include "footer.php";
?>
