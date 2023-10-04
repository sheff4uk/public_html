<?
include "config.php";
$title = 'График отгрузки';
include "header.php";

// Проверка прав на доступ к экрану
if( !in_array('plan_shipment', $Rights) ) {
	header($_SERVER['SERVER_PROTOCOL'].' 403 Forbidden');
	die('Недостаточно прав для совершения операции');
}

// Если в фильтре не установлена неделя, показываем текущую
if( !$_GET["week"] ) {
	$query = "SELECT YEARWEEK(CURDATE(), 1) week";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$row = mysqli_fetch_array($res);
	$_GET["week"] = $row["week"];
}

// Если не выбран участок, берем из сессии
if( !$_GET["F_ID"] ) {
	$_GET["F_ID"] = $_SESSION['F_ID'];
}

$ps_data = array(); // Список паллет и их кол-во
?>

<!--Фильтр-->
<div id="filter">
	<h3>Фильтр</h3>
	<form method="get" style="position: relative;">
		<a href="/plan_shipment.php" style="position: absolute; top: 10px; right: 10px;" class="button">Сброс</a>

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

		<div class="nowrap" style="margin-bottom: 10px;">
			<span>Неделя:</span>
			<select name="week" class="<?=$_GET["week"] ? "filtered" : ""?>" onchange="this.form.submit()">
				<?
				$query = "
					SELECT LEFT(YEARWEEK(CURDATE(), 1), 4) year
					UNION
					SELECT LEFT(YEARWEEK(ps_date, 1), 4) year
					FROM plan__Shipment
					ORDER BY year DESC
				";
				$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
				while( $row = mysqli_fetch_array($res) ) {
					echo "<optgroup label='{$row["year"]}'>";
					$query = "
						SELECT SUB.week
							,SUB.week_format
							,SUB.WeekStart
							,SUB.WeekEnd
						FROM (
							SELECT LEFT(YEARWEEK(CURDATE(), 1), 4) year
								,YEARWEEK(CURDATE(), 1) week
								,RIGHT(YEARWEEK(CURDATE(), 1), 2) week_format
								,DATE_FORMAT(ADDDATE(CURDATE(), 0-WEEKDAY(CURDATE())), '%e %b') WeekStart
								,DATE_FORMAT(ADDDATE(CURDATE(), 6-WEEKDAY(CURDATE())), '%e %b') WeekEnd
							UNION
							SELECT LEFT(YEARWEEK(ps_date, 1), 4) year
								,YEARWEEK(ps_date, 1) week
								,RIGHT(YEARWEEK(ps_date, 1), 2) week_format
								,DATE_FORMAT(ADDDATE(ps_date, 0-WEEKDAY(ps_date)), '%e %b') WeekStart
								,DATE_FORMAT(ADDDATE(ps_date, 6-WEEKDAY(ps_date)), '%e %b') WeekEnd
							FROM plan__Shipment
							WHERE LEFT(YEARWEEK(ps_date, 1), 4) = {$row["year"]}
							GROUP BY week
						) SUB
						WHERE SUB.year = {$row["year"]}
						ORDER BY SUB.week DESC
					";
					$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
					while( $subrow = mysqli_fetch_array($subres) ) {
						$selected = ($subrow["week"] == $_GET["week"]) ? "selected" : "";
						echo "<option value='{$subrow["week"]}' {$selected}>{$subrow["week_format"]} [{$subrow["WeekStart"]} - {$subrow["WeekEnd"]}]</option>";
					}
					echo "</optgroup>";
				}
				?>
			</select>
			<i class="fas fa-question-circle" title="По умолчанию устанавливается текущая неделя."></i>
		</div>

		<button style="float: right;">Фильтр</button>
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

<!--Таблица с планом-->
<table class="main_table">
	<thead>
		<tr>
			<th>Планируемая дата отгрузки</th>
			<th>Очередь</th>
			<th>Противовес</th>
			<th>Количество</th>
			<th>Отгрузка состоялась</th>
			<th></th>
		</tr>
	</thead>
<?

$query = "
	SELECT SUM(1) cnt
		,SUM(PSC.quantity) pallets
		,DATE_FORMAT(PS.ps_date, '%d.%m.%Y') friendly_ps_date
		,PS.ps_date
	FROM plan__Shipment PS
	JOIN plan__ShipmentCWP PSC ON PSC.PS_ID = PS.PS_ID
	WHERE YEARWEEK(PS.ps_date, 1) LIKE '{$_GET["week"]}'
		AND PS.F_ID = {$_GET["F_ID"]}
	GROUP BY PS.ps_date
	ORDER BY PS.ps_date DESC
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	echo "<tbody style='text-align: center; border-bottom: 2px solid #333;'>";
	$cnt = $row["cnt"];

	$query = "
		SELECT PS.PS_ID
			,SUM(1) cnt
			,SUM(PSC.quantity) pallets
			,PS.priority
			,DATE_FORMAT(PS.shipment_time, '%d.%m.%Y %H:%i') friendly_shipment_time
		FROM plan__Shipment PS
		JOIN plan__ShipmentCWP PSC ON PSC.PS_ID = PS.PS_ID
		WHERE PS.ps_date = '{$row["ps_date"]}'
			AND PS.F_ID = {$_GET["F_ID"]}
		GROUP BY PS.priority
		ORDER BY PS.priority
	";
	$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $subrow = mysqli_fetch_array($subres) ) {
		$subcnt = $subrow["cnt"];
		// Выводим общую ячейку с датой
		if( $ps_date != $row["ps_date"] ) {
			echo "<tr style='border-top: 2px solid #333;'>";
			echo "<td rowspan='{$cnt}' style='background-color: rgba(0, 0, 0, 0.2);'><h2 class='nowrap'>{$row["friendly_ps_date"]}</h2><span class='nowrap'><b>{$row["pallets"]}</b> паллет</span></td>";
			$priority = 0;
		}
		$query = "
			SELECT CW.item
				,PSC.quantity
				,PSC.CWP_ID
			FROM plan__Shipment PS
			JOIN plan__ShipmentCWP PSC ON PSC.PS_ID = PS.PS_ID
			JOIN CounterWeightPallet CWP ON CWP.CWP_ID = PSC.CWP_ID
			JOIN CounterWeight CW ON CW.CW_ID = CWP.CW_ID
			WHERE PS.ps_date = '{$row["ps_date"]}'
				AND PS.F_ID = {$_GET["F_ID"]}
				AND PS.priority = {$subrow["priority"]}
			ORDER BY PSC.CWP_ID
		";
		$subsubres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		while( $subsubrow = mysqli_fetch_array($subsubres) ) {
			// Выводим общую ячейку с очередностью
			if( $priority != $subrow["priority"] ) {
				if( $ps_date == $row["ps_date"] ) {
					echo "<tr style='border-top: 2px solid #333;'>";
				}
				echo "<td id='{$subrow["PS_ID"]}' rowspan='{$subcnt}'><h1 class='nowrap'>{$subrow["priority"]}</h1><span class='nowrap'><b>{$subrow["pallets"]}</b> паллет</span></td>";
			}
			else {
				echo "<tr>";
			}

			$ps_data[ $subrow["PS_ID"] ][] = array( "CWP_ID"=>$subsubrow["CWP_ID"], "quantity"=>$subsubrow["quantity"] );

			echo "<td>{$subsubrow["item"]}</td>";
			echo "<td>{$subsubrow["quantity"]}</td>";
			// Выводим общую ячейку с отгрузкой и кнопками действий
			if( $priority != $subrow["priority"] ) {
				echo "<td rowspan='{$subcnt}'>{$subrow["friendly_shipment_time"]}</td>";
				echo "<td rowspan='{$subcnt}'>";
				if( !$subrow["friendly_shipment_time"] )
					echo "<a href='#' class='add_ps' ps_id='{$subrow["PS_ID"]}' ps_date='{$row["ps_date"]}' priority='{$subrow["priority"]}' title='Изменить запланированную отгрузку'><i class='fa fa-pencil-alt fa-lg'></i></a>";
				echo "</td>";
			}
			echo "</tr>";
	
			$priority = $subrow["priority"];
		}
		$ps_date = $row["ps_date"];
	}
	echo "</tbody>";
}
?>
</table>

<div id="add_btn" class="add_ps" title="Запланировать новую отгрузку"></div>

<?
include "./forms/plan_shipment_form.php";
include "footer.php";
?>
