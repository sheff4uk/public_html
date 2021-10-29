<?
include "config.php";
$title = 'Склад противовесов';
include "header.php";

// Получаем список отгрузок и сохраняем в массив
$query = "
	SELECT shipment_time
	FROM list__PackingPallet
	WHERE packed_time > NOW() - INTERVAL 4 WEEK AND shipment_time IS NOT NULL
	GROUP BY shipment_time
	ORDER BY shipment_time DESC
";
$shipment_arr = array();
$i = 100;
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	$i--;
	$shipment_arr["{$row["shipment_time"]}"] = $i;
}
?>
<style>
	.main_table tbody tr:hover {
		font-size: 14px;
	}
	#wr_stock {
		position: fixed;
		background-color: white;
		left: -280px;
		border: 1px solid #bbb;
		padding: 10px;
		border-radius: 10px;
		width: 300px;
		opacity: .8;
		transition: .3s;
		z-index: 10;
	}
	#wr_stock:hover {
		left: 0px;
	}
	#wr_shipment {
		position: fixed;
		background-color: white;
		right: -120px;
		border: 1px solid #bbb;
		padding: 10px;
		border-radius: 10px;
		width: 140px;
		opacity: .8;
		transition: .3s;
		z-index: 10;
	}
	#wr_shipment:hover {
		right: 0px;
	}
</style>

<div id="wr_stock">
	<h2>Складской запас</h2>
	<table style="text-align: center; font-weight: bold;">
		<thead>
			<tr>
				<th rowspan="2">Код</th>
				<th colspan="6">Кол-во поддонов</th>
			</tr>
			<tr>
				<th>4д</th>
				<th>3д</th>
				<th>2д</th>
				<th>1д</th>
				<th>Готовые</th>
				<th>Всего</th>
			</tr>
		</thead>
		<tbody>
		<?
			$query = "
				SELECT substr(CW.item, -3, 3) short_item
					,SUM(IF(LPP.packed_time BETWEEN NOW() - INTERVAL 18 HOUR AND NOW() - INTERVAL 0 HOUR, 1, 0)) day4
					,SUM(IF(LPP.packed_time BETWEEN NOW() - INTERVAL 42 HOUR AND NOW() - INTERVAL 18 HOUR, 1, 0)) day3
					,SUM(IF(LPP.packed_time BETWEEN NOW() - INTERVAL 66 HOUR AND NOW() - INTERVAL 42 HOUR, 1, 0)) day2
					,SUM(IF(LPP.packed_time BETWEEN NOW() - INTERVAL 90 HOUR AND NOW() - INTERVAL 66 HOUR, 1, 0)) day1
					,SUM(IF(NOW() - INTERVAL 90 HOUR < LPP.packed_time, 0, 1)) ready
					,SUM(1) total
				FROM list__PackingPallet LPP
				JOIN CounterWeight CW ON CW.CW_ID = LPP.CW_ID
				WHERE LPP.packed_time > NOW() - INTERVAL 4 WEEK AND LPP.shipment_time IS NULL
				GROUP BY LPP.CW_ID
				ORDER BY LPP.CW_ID ASC
			";
			$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			while( $row = mysqli_fetch_array($res) ) {
				echo "<tr>";
				echo "<td><b style='font-size: 1.5em; width: 60px; display: inline-block;'>{$row["short_item"]}</b></td>";
				echo "<td style='color: rgb(100,0,0);'>{$row["day4"]}</td>";
				echo "<td style='color: rgb(150,0,0);'>{$row["day3"]}</td>";
				echo "<td style='color: rgb(200,0,0);'>{$row["day2"]}</td>";
				echo "<td style='color: rgb(250,0,0);'>{$row["day1"]}</td>";
				echo "<td style='color: orange;'>{$row["ready"]}</td>";
				echo "<td>{$row["total"]}</td>";
				echo "<tr>";
			}
		?>
		</tbody>
	</table>
</div>

<div id="wr_shipment">
	<h2>Отгрузки</h2>
	<table style="text-align: center; font-weight: bold;">
		<thead>
			<tr>
				<th>Время отгрузки</th>
			</tr>
		</thead>
		<tbody>
		<?
			$query = "
				SELECT DATE_FORMAT(shipment_time, '%d.%m.%Y %H:%i') shipment_time_format
					,shipment_time
				FROM list__PackingPallet
				GROUP BY shipment_time
				ORDER BY shipment_time DESC
				LIMIT 20
			";
			$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			while( $row = mysqli_fetch_array($res) ) {
				echo "<tr>";
				echo "<td class='shipment'>{$row["shipment_time_format"]}</td>";
				echo "<tr>";
			}
		?>
		</tbody>
	</table>
</div>

<script>
	$(function() {
		$('.shipment').hover(
			function() {
				var shipment_time = $(this).html();
				$('.pallet_row[shipment_time="'+shipment_time+'"]').css('font-size', '14px');
			},
			function() {
				var shipment_time = $(this).html();
				$('.pallet_row[shipment_time="'+shipment_time+'"]').css('font-size', '');
			}
		);
	});
</script>

<table class="main_table">
	<thead>
		<tr>
			<th>Время упаковки</th>
			<?
			$query = "
				SELECT CW_ID
					,substr(item,-3, 3) short_item
				FROM CounterWeight
				WHERE CB_ID = 2
				ORDER BY CW_ID
			";
			$cw_arr = array();
			$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			while( $row = mysqli_fetch_array($res) ) {
				echo "<th>{$row["short_item"]}</th>";
				$cw_arr[] = $row["CW_ID"];
			}
			?>
		</tr>
	</thead>
	<tbody style="text-align: center; font-size: 1px;">

<?
$query = "
	SELECT DATE_FORMAT(packed_time, '%d.%m.%Y %H:%i') packed_time_format
		,DATE_FORMAT(shipment_time, '%d.%m.%Y %H:%i') shipment_time_format
		,nextID
		,CW_ID
		,shipment_time
		,IF(IFNULL(shipment_time, NOW()) - INTERVAL 90 HOUR < packed_time, 0, 1) ready
	FROM list__PackingPallet
	WHERE packed_time > NOW() - INTERVAL 4 WEEK
	ORDER BY packed_time DESC
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	echo "<tr class='pallet_row' shipment_time='{$row["shipment_time_format"]}'>";
	echo "<td>{$row["packed_time_format"]}</td>";
	foreach ( $cw_arr as $value ) {
		if( $value == $row["CW_ID"] ) {
			echo "<td style='background-color: ".($row["shipment_time"] ? "rgb(0,128,0,.{$shipment_arr[$row["shipment_time"]]});" : "orange;").($row["ready"] ? "" : "border-left: 6px solid red;")."'><b>{$row["nextID"]}</b><br>{$row["shipment_time_format"]}</td>";
		}
		else {
			echo "<td></td>";
		}
	}
	echo "</tr>";
}
?>
	</tbody>
</table>

<?
include "footer.php";
?>
