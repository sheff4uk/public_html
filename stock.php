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

	exit ('<meta http-equiv="refresh" content="0; url=/stock.php?F_ID='.$_GET["F_ID"].'#'.$LPP_ID.'">');
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

	exit ('<meta http-equiv="refresh" content="0; url=/stock.php?F_ID='.$_GET["F_ID"].'#'.$LPP_ID.'">');
}

//Принудительный сбор данных с терминала упаковки
if( isset($_GET["download"]) ) {
	$query = "
		SELECT from_ip
			,notification_group
		FROM factory
		WHERE F_ID = {$_GET["F_ID"]}
	";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$row = mysqli_fetch_array($res);
	$from_ip = $row["from_ip"];
	$notification_group = $row["notification_group"];

	////////////////////////////////////////////////////////
	// функции сбора данных с весовых терминалов
	include "functions_WT.php";
	////////////////////////////////////////////////////////

	// По этому списку терминалов собираем данные по упаковке паллетов
	$query = "
		SELECT WT.WT_ID
			,WT.port
			,WT.last_transaction
		FROM WeighingTerminal WT
		WHERE WT.F_ID = {$_GET["F_ID"]}
			AND WT.type = 3
			AND WT.act = 1
	";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $row = mysqli_fetch_array($res) ) {
		// Открываем сокет и запускаем функцию чтения и записывания в БД регистраций
		$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
		if( socket_connect($socket, $from_ip, $row["port"]) ) {
			// Чтение регистраций поддонов
			read_transaction_LPP($row["last_transaction"]+1, 1, $socket, $mysqli);

			// Запись в терминал даты заливки
			//set_terminal_text($filling_time_format, $socket, $mysqli);
		}
		else {
			message_to_telegram("<b>Нет связи с терминалом этикетирования паллетов!</b>", $notification_group);
		}
		socket_close($socket);
	}
	////////////////////////////////////////////////////////////

	exit ('<meta http-equiv="refresh" content="0; url=/stock.php?F_ID='.$_GET["F_ID"].'">');
}

// Узнаем время упаковки самого старого поддона на складе
$query = "
	SELECT IFNULL(DATE(MIN(LPP.packed_time)), CURDATE()) date_from
	FROM list__PackingPallet LPP
	WHERE LPP.shipment_time IS NULL
		AND LPP.removal_time IS NULL
		AND LPP.F_ID = {$_GET["F_ID"]}
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
		AND LPP.F_ID = {$_GET["F_ID"]}
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

		$( "#summary" ).accordion({
			active: false,
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
		top: 50px;
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

<!--<div id="wr_stock">-->
<div id="summary">
	<h3>Складской запас</h3>
	<div>
	<table style="text-align: center; font-weight: bold;">
		<thead>
			<tr>
				<th rowspan="2"><a href="stock.php?F_ID=<?=$_GET["F_ID"]?>&download" title="Собрать данные из терминала"><i class="fa-solid fa-download fa-2x"></i></a> Комплект противовесов</th>
				<th colspan="8">Кол-во паллетов</th>
			</tr>
			<tr>
				<th>5д</th>
				<th>4д</th>
				<th>3д</th>
				<th>2д</th>
				<th>1д</th>
				<th>Готовые</th>
				<th>Всего</th>
				<th>В кассетах</th>
			</tr>
		</thead>
		<tbody>
		<?
			$query = "
				SELECT CONCAT(IFNULL(CW.item, CWP.cwp_name), ' (', CWP.in_pallet, 'шт)') item
					,CWP.in_pallet
					,SUM(SUB.day5) day5
					,SUM(SUB.day4) day4
					,SUM(SUB.day3) day3
					,SUM(SUB.day2) day2
					,SUM(SUB.day1) day1
					,SUM(SUB.ready) ready
					,SUM(SUB.total) total
					,SUM(SUB.in_cass) in_cass
					,SUM(SUB.in_cassette) in_cassette
				FROM CounterWeightPallet CWP
				JOIN (
					SELECT LPP.CWP_ID
					,SUM(IF(LPP.packed_time BETWEEN NOW() - INTERVAL 18 HOUR AND NOW() - INTERVAL 0 HOUR AND LPP.shipment_time IS NULL AND LPP.removal_time IS NULL, 1, 0)) day5
					,SUM(IF(LPP.packed_time BETWEEN NOW() - INTERVAL 42 HOUR AND NOW() - INTERVAL 18 HOUR AND LPP.shipment_time IS NULL AND LPP.removal_time IS NULL, 1, 0)) day4
					,SUM(IF(LPP.packed_time BETWEEN NOW() - INTERVAL 66 HOUR AND NOW() - INTERVAL 42 HOUR AND LPP.shipment_time IS NULL AND LPP.removal_time IS NULL, 1, 0)) day3
					,SUM(IF(LPP.packed_time BETWEEN NOW() - INTERVAL 90 HOUR AND NOW() - INTERVAL 66 HOUR AND LPP.shipment_time IS NULL AND LPP.removal_time IS NULL, 1, 0)) day2
					,SUM(IF(LPP.packed_time BETWEEN NOW() - INTERVAL 114 HOUR AND NOW() - INTERVAL 90 HOUR AND LPP.shipment_time IS NULL AND LPP.removal_time IS NULL, 1, 0)) day1
					,SUM(IF(LPP.packed_time <= NOW() - INTERVAL 114 HOUR AND LPP.shipment_time IS NULL AND LPP.removal_time IS NULL, 1, 0)) ready
					,SUM(IF(LPP.shipment_time IS NULL AND LPP.removal_time IS NULL, 1, 0)) total
					#,CWP.in_pallet
					,0 in_cass
					,0 in_cassette
					FROM list__PackingPallet LPP
					WHERE LPP.F_ID = {$_GET["F_ID"]}
					AND DATE(LPP.packed_time) >= '{$date_from}'
					GROUP BY LPP.CWP_ID
					
					UNION
					
					SELECT CWP.CWP_ID
					,0
					,0
					,0
					,0
					,0
					,0
					,0
					,ROUND(SUM(IF((SELECT LF_ID FROM list__Filling WHERE cassette = LF.cassette AND filling_time > LF.filling_time LIMIT 1) IS NULL, (PB.in_cassette - LF.underfilling), 0)) / CWP.in_pallet) in_cass
					,SUM(IF((SELECT LF_ID FROM list__Filling WHERE cassette = LF.cassette AND filling_time > LF.filling_time LIMIT 1) IS NULL, (PB.in_cassette - LF.underfilling), 0)) in_cassette
					FROM CounterWeightPallet CWP
					JOIN plan__Batch PB ON PB.CW_ID = CWP.CW_ID
					JOIN list__Batch LB ON LB.PB_ID = PB.PB_ID
					JOIN list__Filling LF ON LF.LB_ID = LB.LB_ID
					AND LF.filling_time > NOW() - INTERVAL 2 WEEK
					LEFT JOIN list__Opening LO ON LO.LF_ID = LF.LF_ID
					WHERE PB.F_ID = {$_GET["F_ID"]}
					AND LO.LO_ID IS NULL
					GROUP BY CWP.CWP_ID
				) SUB ON SUB.CWP_ID = CWP.CWP_ID
				LEFT JOIN CounterWeight CW ON CW.CW_ID = CWP.CW_ID
				WHERE IFNULL(CW.CB_ID, 0) != 5
				GROUP BY SUB.CWP_ID
				ORDER BY SUB.CWP_ID ASC
			";

			$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			while( $row = mysqli_fetch_array($res) ) {
				echo "<tr>";
				echo "<td><span class='nowrap'>{$row["item"]}</span></td>";
				echo "<td style='color: rgb(50,0,0);'>{$row["day5"]}</td>";
				echo "<td style='color: rgb(100,0,0);'>{$row["day4"]}</td>";
				echo "<td style='color: rgb(150,0,0);'>{$row["day3"]}</td>";
				echo "<td style='color: rgb(200,0,0);'>{$row["day2"]}</td>";
				echo "<td style='color: rgb(250,0,0);'>{$row["day1"]}</td>";
				echo "<td style='color: orange;'>{$row["ready"]}</td>";
				echo "<td>{$row["total"]} (".($row["total"] * $row["in_pallet"]).")</td>";
				echo "<td>{$row["in_cass"]} ({$row["in_cassette"]})</td>";
				echo "<tr>";
			}
		?>
		</tbody>
	</table>
	</div>
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
				WHERE LPP.F_ID = {$_GET["F_ID"]}
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
					"stock.php?remove=" + id + "&F_ID=<?=$_GET["F_ID"]?>"
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
					"stock.php?undo=" + id + "&F_ID=<?=$_GET["F_ID"]?>"
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
					,CONCAT(IFNULL(CW.item, CWP.cwp_name), ' (', CWP.in_pallet, 'шт)') item
				FROM list__PackingPallet LPP
				JOIN CounterWeightPallet CWP ON CWP.CWP_ID = LPP.CWP_ID
				LEFT JOIN CounterWeight CW ON CW.CW_ID = CWP.CW_ID
				WHERE LPP.F_ID = {$_GET["F_ID"]}
					AND DATE(LPP.packed_time) >= '{$date_from}'
					AND IFNULL(CW.CB_ID, 0) != 5
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
		,CONCAT(IFNULL(CW.item, CWP.cwp_name), ' (', CWP.in_pallet, 'шт)') item
		,LPP.shipment_time
		#,IF(IFNULL(LPP.shipment_time, NOW()) - INTERVAL 114 HOUR < LPP.packed_time, 0, 1) ready
		,1 ready
	FROM list__PackingPallet LPP
	JOIN CounterWeightPallet CWP ON CWP.CWP_ID = LPP.CWP_ID
	LEFT JOIN CounterWeight CW ON CW.CW_ID = CWP.CW_ID
	WHERE DATE(LPP.packed_time) >= '{$date_from}'
		AND LPP.F_ID = {$_GET["F_ID"]}
		AND IFNULL(CW.CB_ID, 0) != 5
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
