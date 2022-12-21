<?
include "config.php";
$title = 'Склад противовесов';
include "header.php";

// Проверка прав на доступ к экрану
if( !in_array('stock', $Rights) ) {
	header($_SERVER['SERVER_PROTOCOL'].' 403 Forbidden');
	die('Недостаточно прав для совершения операции');
}

// Если не выбран участок, берем из сессии
if( !$_GET["F_ID"] ) {
	$_GET["F_ID"] = $_SESSION['F_ID'];
}

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

	exit ('<meta http-equiv="refresh" content="0; url='.$page.'#'.$LPP_ID.'">');
}

// Узнаем время упаковки самого старого поддона на складе
$query = "
	SELECT IFNULL(DATE(MIN(LPP.packed_time)), CURDATE()) date_from
	FROM list__PackingPallet LPP
	WHERE LPP.shipment_time IS NULL
		AND LPP.removal_time IS NULL
		AND LPP.WT_ID IN (SELECT WT_ID FROM WeighingTerminal WHERE F_ID = {$_GET["F_ID"]})
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
$row = mysqli_fetch_array($res);
$date_from = $row["date_from"];

// Получаем список отгрузок и сохраняем в массив
$query = "
	SELECT LPP.shipment_time
	FROM list__PackingPallet LPP
	WHERE DATE(LPP.packed_time) >= '{$date_from}'
		AND LPP.shipment_time IS NOT NULL
		AND LPP.WT_ID IN (SELECT WT_ID FROM WeighingTerminal WHERE F_ID = {$_GET["F_ID"]})
	GROUP BY LPP.shipment_time
	ORDER BY LPP.shipment_time DESC
";
$shipment_arr = array();
$i = 100;
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	$i--;
	$i = ($i < 1) ? 1 : $i;
	$shipment_arr["{$row["shipment_time"]}"] = $i;
}
?>

<!--Фильтр-->
<div id="filter">
	<h3>Фильтр</h3>
	<form method="get" style="position: relative;">
		<a href="/stock.php" style="position: absolute; top: 10px; right: 10px;" class="button">Сброс</a>

		<div class="nowrap" style="margin-bottom: 10px;">
			<span>Участок:</span>
			<select name="F_ID" class="<?=$_GET["F_ID"] ? "filtered" : ""?>" onchange="this.form.submit()">
				<?
				$query = "
					SELECT F_ID
						,f_name
					FROM factory
					ORDER BY F_ID
				";
				$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
				while( $row = mysqli_fetch_array($res) ) {
					$selected = ($row["F_ID"] == $_GET["F_ID"]) ? "selected" : "";
					echo "<option value='{$row["F_ID"]}' {$selected}>{$row["f_name"]}</option>";
				}
				?>
			</select>
		</div>

	</form>
</div>

<?
// Узнаем есть ли фильтр
$filter = 0;
foreach ($_GET as &$value) {
	if( $value ) $filter = 1;
}
?>

<script>
	$(document).ready(function() {
		$( "#filter" ).accordion({
			active: <?=($filter ? "0" : "false")?>,
			collapsible: true,
			heightStyle: "content"
		});

		// При скроле сворачивается фильтр
		$(window).scroll(function(){
			$( "#filter" ).accordion({
				active: "false"
			});
		});
	});
</script>

<style>
	.main_table tbody tr:hover {
		font-size: 14px;
	}
	#wr_stock {
		position: fixed;
		background-color: white;
		border: 1px solid #bbb;
		padding: 10px;
		border-radius: 10px;
		opacity: .8;
		transition: .3s;
		z-index: 10;
		right: calc(100% - 20px);
	}
	#wr_stock:hover {
		left: 0px;
		right: unset;
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
				<th rowspan="2">Комплект противовесов</th>
				<th colspan="6">Кол-во паллетов</th>
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
				SELECT CONCAT(CW.item, ' (', CWP.in_pallet, 'шт)') item
					,SUM(IF(LPP.packed_time BETWEEN NOW() - INTERVAL 18 HOUR AND NOW() - INTERVAL 0 HOUR AND LPP.shipment_time IS NULL AND LPP.removal_time IS NULL, 1, 0)) day4
					,SUM(IF(LPP.packed_time BETWEEN NOW() - INTERVAL 42 HOUR AND NOW() - INTERVAL 18 HOUR AND LPP.shipment_time IS NULL AND LPP.removal_time IS NULL, 1, 0)) day3
					,SUM(IF(LPP.packed_time BETWEEN NOW() - INTERVAL 66 HOUR AND NOW() - INTERVAL 42 HOUR AND LPP.shipment_time IS NULL AND LPP.removal_time IS NULL, 1, 0)) day2
					,SUM(IF(LPP.packed_time BETWEEN NOW() - INTERVAL 90 HOUR AND NOW() - INTERVAL 66 HOUR AND LPP.shipment_time IS NULL AND LPP.removal_time IS NULL, 1, 0)) day1
					,SUM(IF(NOW() - INTERVAL 90 HOUR < LPP.packed_time AND LPP.shipment_time IS NULL AND LPP.removal_time IS NULL, 0, 1)) ready
					,SUM(IF(LPP.shipment_time IS NULL AND LPP.removal_time IS NULL, 1, 0)) total
				FROM list__PackingPallet LPP
				JOIN CounterWeightPallet CWP ON CWP.CWP_ID = LPP.CWP_ID
				JOIN CounterWeight CW ON CW.CW_ID = CWP.CW_ID
				WHERE LPP.WT_ID IN (SELECT WT_ID FROM WeighingTerminal WHERE F_ID = {$_GET["F_ID"]})
					AND DATE(LPP.packed_time) >= '{$date_from}'
					#AND LPP.shipment_time IS NULL
					#AND LPP.removal_time IS NULL
					#AND CW.CB_ID = 2
				GROUP BY LPP.CWP_ID
				ORDER BY LPP.CWP_ID ASC
			";
			$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			while( $row = mysqli_fetch_array($res) ) {
				echo "<tr>";
				echo "<td><span class='nowrap'>{$row["item"]}</span></td>";
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
				SELECT DATE_FORMAT(LPP.shipment_time, '%d.%m.%Y %H:%i') shipment_time_format
					,LPP.shipment_time
				FROM list__PackingPallet LPP
				WHERE LPP.WT_ID IN (SELECT WT_ID FROM WeighingTerminal WHERE F_ID = {$_GET["F_ID"]})
				GROUP BY LPP.shipment_time
				ORDER BY LPP.shipment_time DESC
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
					"<span style='font-size: 1.2em;'>Подтвердите <font color='red'>удаление</font> регистрации с номером <b>" + nextID + "</b> (комплект противовесов <b>" + item + "</b>).</span>",
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
					"<span style='font-size: 1.2em;'>Подтвердите <font color='green'>восстановление</font> регистрации с номером <b>" + nextID + "</b> (комплект противовесов <b>" + item + "</b>).</span>",
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
				SELECT LPP.CWP_ID
					,CONCAT(CW.item, ' (', CWP.in_pallet, 'шт)') item
				FROM list__PackingPallet LPP
				JOIN CounterWeightPallet CWP ON CWP.CWP_ID = LPP.CWP_ID
				JOIN CounterWeight CW ON CW.CW_ID = CWP.CW_ID
				WHERE LPP.WT_ID IN (SELECT WT_ID FROM WeighingTerminal WHERE F_ID = {$_GET["F_ID"]})
					AND DATE(LPP.packed_time) >= '{$date_from}'
					#AND LPP.shipment_time IS NULL
					#AND LPP.removal_time IS NULL
					#AND CW.CB_ID = 2
				GROUP BY LPP.CWP_ID
				ORDER BY LPP.CWP_ID ASC
			";
			$cw_arr = array();
			$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			while( $row = mysqli_fetch_array($res) ) {
				echo "<th>{$row["item"]}</th>";
				$cwp_arr[] = $row["CWP_ID"];
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
		,LPP.CWP_ID
		,CONCAT(CW.item, ' (', CWP.in_pallet, 'шт)') item
		,LPP.shipment_time
		,IF(IFNULL(LPP.shipment_time, NOW()) - INTERVAL 90 HOUR < LPP.packed_time, 0, 1) ready
	FROM list__PackingPallet LPP
	JOIN CounterWeightPallet CWP ON CWP.CWP_ID = LPP.CWP_ID
	JOIN CounterWeight CW ON CW.CW_ID = CWP.CW_ID
	WHERE DATE(LPP.packed_time) >= '{$date_from}'
		AND LPP.WT_ID IN (SELECT WT_ID FROM WeighingTerminal WHERE F_ID = {$_GET["F_ID"]})
		#AND CW.CB_ID = 2
	ORDER BY LPP.packed_time DESC
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	echo "<tr id='{$row["LPP_ID"]}' class='pallet_row' ".($row["removal_time_format"] ? "style='text-decoration: line-through;'" : "")." shipment_time='{$row["shipment_time_format"]}'>";
	echo "<td>{$row["packed_time_format"]}</td>";
	foreach ( $cwp_arr as $value ) {
		if( $value == $row["CWP_ID"] ) {
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
