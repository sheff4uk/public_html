<?
include "config.php";
$title = 'Склад противовесов';
include "header.php";

$page = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);

//Удаление регистрации поддона
if( isset($_GET["remove"]) ) {
	session_start();
	$LPP_ID = $_GET["remove"];

	$query = "
		UPDATE list__PackingPallet
		SET removal_time = NOW()
		WHERE LPP_ID = {$LPP_ID}
			AND scan_time IS NULL
			AND shipment_time IS NULL
	";
	mysqli_query( $mysqli, $query );

	// Перенаправление в план
	exit ('<meta http-equiv="refresh" content="0; url='.$page.'#'.$LPP_ID.'">');
}

//Восстановление регистрации поддона
if( isset($_GET["undo"]) ) {
	session_start();
	$LPP_ID = $_GET["undo"];

	$query = "
		UPDATE list__PackingPallet
		SET removal_time = NULL
		WHERE LPP_ID = {$LPP_ID}
	";
	mysqli_query( $mysqli, $query );

	// Перенаправление в план
	exit ('<meta http-equiv="refresh" content="0; url='.$page.'#'.$LPP_ID.'">');
}

//// Отсчитываем 30 рабочих дней назад
//$query = "
//	SELECT MIN(SUB.packed_date) date_from
//	FROM (
//		SELECT DATE(packed_time) packed_date
//		FROM list__PackingPallet
//		GROUP BY packed_date
//		ORDER BY packed_date DESC
//		LIMIT 30
//	) SUB
//";
//$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
//$row = mysqli_fetch_array($res);
//$date_from = $row["date_from"];

// Узнаем время упаковки самого старого поддона на складе
$query = "
	SELECT MIN(packed_time) date_from
	FROM list__PackingPallet
	WHERE shipment_time IS NULL AND removal_time IS NULL
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
$row = mysqli_fetch_array($res);
$date_from = $row["date_from"];

// Получаем список отгрузок и сохраняем в массив
$query = "
	SELECT shipment_time
	FROM list__PackingPallet
	WHERE DATE(packed_time) >= '{$date_from}' AND shipment_time IS NOT NULL
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
				SELECT CW.item
					,SUM(IF(LPP.packed_time BETWEEN NOW() - INTERVAL 18 HOUR AND NOW() - INTERVAL 0 HOUR, 1, 0)) day4
					,SUM(IF(LPP.packed_time BETWEEN NOW() - INTERVAL 42 HOUR AND NOW() - INTERVAL 18 HOUR, 1, 0)) day3
					,SUM(IF(LPP.packed_time BETWEEN NOW() - INTERVAL 66 HOUR AND NOW() - INTERVAL 42 HOUR, 1, 0)) day2
					,SUM(IF(LPP.packed_time BETWEEN NOW() - INTERVAL 90 HOUR AND NOW() - INTERVAL 66 HOUR, 1, 0)) day1
					,SUM(IF(NOW() - INTERVAL 90 HOUR < LPP.packed_time, 0, 1)) ready
					,SUM(1) total
				FROM list__PackingPallet LPP
				JOIN CounterWeight CW ON CW.CW_ID = LPP.CW_ID AND CW.CB_ID = 2
				WHERE DATE(LPP.packed_time) >= '{$date_from}'
					AND LPP.shipment_time IS NULL
					AND LPP.removal_time IS NULL
				GROUP BY LPP.CW_ID
				ORDER BY LPP.CW_ID ASC
			";
			$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			while( $row = mysqli_fetch_array($res) ) {
				echo "<tr>";
				echo "<td><b style='font-size: 1.5em; width: 60px; display: inline-block;'>{$row["item"]}</b></td>";
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
				$diff = strtotime($last_shipment_time) - strtotime($row["shipment_time"]);
				echo "<tr>";
				echo "<td class='shipment' ".($diff > 36000 ? "style='border-top: 2px solid #000;'" : "").">{$row["shipment_time_format"]}</td>";
				echo "<tr>";
				$last_shipment_time = $row["shipment_time"];
			}
		?>
		</tbody>
	</table>
</div>

<script>
	$(function() {
		// Подтверждение удаления регистрации поддона
		$(".fa-times")
			.click(function(){
				var id = $(this).parents("tr").attr("id"),
					nextID = $(this).parents("td").attr("nextID");
					item = $(this).parents("td").attr("item");
				confirm(
					"<span style='font-size: 1.2em;'>Подтвердите <font color='red'>удаление</font> регистрации с номером <b>" + nextID + "</b> (протевовес <b>" + item + "</b>).</span>",
					"<?=$page?>?remove=" + id
				);
			});

		// Подтверждение восстановления регистрации поддона
		$(".fa-undo")
			.click(function(){
				var id = $(this).parents("tr").attr("id"),
					nextID = $(this).parents("td").attr("nextID");
					item = $(this).parents("td").attr("item");
				confirm(
					"<span style='font-size: 1.2em;'>Подтвердите <font color='green'>восстановление</font> регистрации с номером <b>" + nextID + "</b> (протевовес <b>" + item + "</b>).</span>",
					"<?=$page?>?undo=" + id
				);
			});

		// При наведении строка раскрывается
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
					,item
				FROM CounterWeight
				WHERE CB_ID = 2
				ORDER BY CW_ID
			";
			$cw_arr = array();
			$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			while( $row = mysqli_fetch_array($res) ) {
				echo "<th>{$row["item"]}</th>";
				$cw_arr[] = $row["CW_ID"];
			}
			?>
		</tr>
	</thead>
	<tbody style="text-align: center; font-size: 1px;">

<?
$query = "
	SELECT LPP.LPP_ID
		,DATE_FORMAT(LPP.packed_time, '%d.%m.%Y %H:%i') packed_time_format
		,DATE_FORMAT(LPP.shipment_time, '%d.%m.%Y %H:%i') shipment_time_format
		,DATE_FORMAT(LPP.removal_time, '%d.%m.%Y %H:%i') removal_time_format
		,LPP.nextID
		,LPP.CW_ID
		,CW.item
		,LPP.shipment_time
		,IF(IFNULL(LPP.shipment_time, NOW()) - INTERVAL 90 HOUR < LPP.packed_time, 0, 1) ready
	FROM list__PackingPallet LPP
	JOIN CounterWeight CW ON CW.CW_ID = LPP.CW_ID AND CW.CB_ID = 2
	WHERE DATE(LPP.packed_time) >= '{$date_from}'
	ORDER BY LPP.packed_time DESC
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	echo "<tr id='{$row["LPP_ID"]}' class='pallet_row' ".($row["removal_time_format"] ? "style='text-decoration: line-through;'" : "")." shipment_time='{$row["shipment_time_format"]}'>";
	echo "<td>{$row["packed_time_format"]}</td>";
	foreach ( $cw_arr as $value ) {
		if( $value == $row["CW_ID"] ) {
			echo "<td nextID='{$row["nextID"]}' item='{$row["item"]}' style='background-color: ".($row["shipment_time"] ? "rgb(0,128,0,.{$shipment_arr[$row["shipment_time"]]});" : ($row["removal_time_format"] ? "gray;" : "orange;")).($row["ready"] || $row["removal_time_format"] ? "" : "border-left: 6px solid red;")."'><b>{$row["nextID"]}</b>&nbsp;".($row["shipment_time_format"] ? "" : ($row["removal_time_format"] ? "<font color='red'><i class='fa fa-undo'></i></font>" : "<font color='red'><i class='fa fa-times'></i></font>"))."<br>{$row["shipment_time_format"]}</td>";
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
