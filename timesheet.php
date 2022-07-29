<?
include "config.php";
$title = 'Табель';
include "header.php";

// Если в фильтре не установлена неделя, показываем текущую
if( !$_GET["month"] ) {
	$query = "SELECT DATE_FORMAT(CURDATE(), '%Y%m') month";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$row = mysqli_fetch_array($res);
	$_GET["month"] = $row["month"];

	$year = substr($_GET["month"], 0, 4);
	$month = substr($_GET["month"], 4, 2);
}

// Если не выбран участок, берем из сессии
if( !$_GET["F_ID"] ) {
	$_GET["F_ID"] = $_SESSION['F_ID'];
}
$F_ID = $_GET["F_ID"];

include "./forms/timesheet_form.php";

// Узнаем кол-во дней в выбранном месяце
$strdate = '01.'.$month.'.'.$year;
$timestamp = strtotime($strdate);
$days = date('t', $timestamp);
?>

<!--Фильтр-->
<div id="filter">
	<h3>Фильтр</h3>
	<form method="get" style="position: relative;">
		<a href="/timesheet.php" style="position: absolute; top: 10px; right: 10px;" class="button">Сброс</a>

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
			<span>Месяц:</span>
			<select name="week" class="<?=$_GET["month"] ? "filtered" : ""?>" onchange="this.form.submit()">
				<?
				$query = "
					SELECT YEAR(CURDATE()) year
					UNION
					SELECT YEAR(ts_date) year
					FROM Timesheet
					ORDER BY year DESC
				";
				$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
				while( $row = mysqli_fetch_array($res) ) {
					echo "<optgroup label='{$row["year"]}'>";
					$query = "
						SELECT SUB.month
							,SUB.month_format
						FROM (
							SELECT YEAR(CURDATE()) year
								,DATE_FORMAT(CURDATE(), '%Y%m') month
								,DATE_FORMAT(CURDATE(), '%b') month_format
							UNION
							SELECT YEAR(ts_date) year
								,DATE_FORMAT(ts_date, '%Y%m') month
								,DATE_FORMAT(ts_date, '%b') month_format
							FROM Timesheet
							WHERE YEAR(ts_date) = {$row["year"]}
							GROUP BY month
						) SUB
						WHERE SUB.year = {$row["year"]}
						ORDER BY SUB.month DESC
					";
					$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
					while( $subrow = mysqli_fetch_array($subres) ) {
						$selected = ($subrow["month"] == $_GET["month"]) ? "selected" : "";
						echo "<option value='{$subrow["month"]}' {$selected}>{$subrow["month_format"]}</option>";
					}
					echo "</optgroup>";
				}
				?>
			</select>
			<i class="fas fa-question-circle" title="По умолчанию устанавливается текущий месяц."></i>
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

<table id="timesheet" class="main_table">
	<thead>
		<tr class="nowrap">
			<th></th>
			<?
				// Получаем производственный календарь на выбранный год
				$xml = simplexml_load_file("http://xmlcalendar.ru/data/ru/".$year."/calendar.xml");
				$json = json_encode($xml);
				$data = json_decode($json,TRUE);

				$i = 1;
				$workdays = 0;
				while ($i <= $days) {
					$date = $year.'-'.$month.'-'.$i;
					$day_of_week = date('N', strtotime($date));	// День недели
					$day = date('d', strtotime($date));			// День месяца

					// Перебираем массив и если находим дату то проверяем ее тип (тип дня: 1 - выходной день, 2 - рабочий и сокращенный (может быть использован для любого дня недели), 3 - рабочий день (суббота/воскресенье))
					$t = 0;
					foreach( $data["days"]["day"] as $key=>$value ) {
						if( $value["@attributes"]["d"] == $month.".".$day) {
							$t = $value["@attributes"]["t"];
						}
					}

					if ( (($day_of_week >= 6 and $t != "3" and $t != "2") or ($t == "1")) ) { // Выделяем цветом выходные дни
						echo "<th style='background: chocolate;'>".$i++."</th>";
					}
					else {
						echo "<th>".$i++."</th>";
						$workdays++;
					}
				}
			?>

			<th colspan="2">[1...15]</th>
			<th colspan="2">[16...<?=$days?>]</th>
			<th colspan="2" style="font-size: 1.5em;">Σ</th>
		</tr>
	</thead>
	<tbody>
	<?
		// Суммарные результаты за день
		$dayduration = array();
		$daypay = array();
		// Массив регистраций
		$TimeReg = array();

		// Получаем список работников
		$query = "
			SELECT USR.USR_ID
				,USR.F_ID
				,USR_Name(USR.USR_ID) Name
				,USR_Icon(USR.USR_ID) Icon
				,USR.act
				,IFNULL(SUM(TS.duration), 0) duration
			FROM Users USR
			# У работника должен быть активный тариф в выбранном месяце
			JOIN (
				SELECT USR_ID
				FROM Tariff
				WHERE from_date <= '{$year}-{$month}-{$days}'
				GROUP BY USR_ID
			) T ON T.USR_ID = USR.USR_ID
			LEFT JOIN Timesheet TS ON TS.USR_ID = USR.USR_ID
				AND YEAR(TS.ts_date) = {$year}
				AND MONTH(TS.ts_date) = {$month}
				AND TS.F_ID = {$F_ID}
			GROUP BY USR.USR_ID
			HAVING (act = 1 AND F_ID = {$F_ID}) OR duration > 0
			ORDER BY Name
		";
		$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		while( $row = mysqli_fetch_array($res) ) {
			echo "<tr><td style='text-align: center;'>{$row["Icon"]}</td>";

			// Получаем список часов по работнику за месяц
			$query = "
				SELECT TS_ID
					,DAY(ts_date) Day
					,duration
					,pay
				FROM Timesheet
				WHERE YEAR(ts_date) = {$year}
					AND MONTH(ts_date) = {$month}
					AND USR_ID = {$row["USR_ID"]}
					AND F_ID = {$F_ID}
			";
			$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

			$sigmaduration1 = 0; // Сумма отработанных часов по работнику 1-15
			$sigmapay1 = 0; // Сумма заработанных денег по работнику 1-15
			$sigmaduration2 = 0; // Сумма отработанных часов по работнику 16-30
			$sigmapay2 = 0; // Сумма заработанных денег по работнику 16-30
			$day = 0;
			if( $subrow = mysqli_fetch_array($subres) ) {
				$day = $subrow["Day"];
			}

			// Цикл по количеству дней в месяце
			$i = 1;
			while ($i <= $days) {
				$d = str_pad($i, 2, "0", STR_PAD_LEFT);
				if( $i == $day ) {
					// Заполняем массив регистраций
					$query = "
						SELECT TR.TR_ID
							,TR.tr_time
							,TR.tr_photo
							,CONCAT(Friendly_date(TR.add_time), '<br>', DATE_FORMAT(TR.add_time, '%H:%i:%s')) add_time
							,IF(TR.add_author IS NOT NULL, USR_Icon(TR.add_author), '') add_author
							,CONCAT(Friendly_date(TR.del_time), '<br>', DATE_FORMAT(TR.del_time, '%H:%i:%s')) del_time
							,IF(TR.del_author IS NOT NULL, USR_Icon(TR.del_author), '') del_author
						FROM TimeReg TR
						WHERE TR.TS_ID = {$subrow["TS_ID"]}
						ORDER BY TR.tr_time
					";
					$subsubres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
					while( $subsubrow = mysqli_fetch_array($subsubres) ) {
						$TimeReg[$subrow["TS_ID"]][] = array("TR_ID" => $subsubrow["TR_ID"], "tr_time" => "{$subsubrow["tr_time"]}", "tr_photo" => "{$subsubrow["tr_photo"]}", "add_time" => "{$subsubrow["add_time"]}", "add_author" => "{$subsubrow["add_author"]}", "del_time" => "{$subsubrow["del_time"]}", "del_author" => "{$subsubrow["del_author"]}");
					}

					echo "
						<td id='{$subrow["TS_ID"]}' style='overflow: visible; padding: 0px; text-align: center;' class='tscell nowrap' ts_id='{$subrow["TS_ID"]}' date_format='{$d}.{$month}.{$year}' usr_name='{$row["Name"]}'>
							<n style='".($subrow["duration"] == 0 ? " color: red; font-weight: bold;" : "")."'>".(round($subrow["duration"] / 60, 1))."ч</n>
							<br>
							<n style='color: #050;'>".(number_format($subrow["pay"], 0, '', ' '))."</n>
						</td>
					";

					if( $i < 16 ) {
						$sigmaduration1 += $subrow["duration"];
						$sigmapay1 += $subrow["pay"];
					}
					else {
						$sigmaduration2 += $subrow["duration"];
						$sigmapay2 += $subrow["pay"];
					}
					$dayduration[$i] += $subrow["duration"];
					$daypay[$i] += $subrow["pay"];

					if( $subrow = mysqli_fetch_array($subres) ) {
						$day = $subrow["Day"];
					}
				}
				else {
					echo "<td class='tscell' ts_date='{$year}-{$month}-{$d}' usr_id='{$row["USR_ID"]}' date_format='{$d}.{$month}.{$year}' usr_name='{$row["Name"]}'></td>";
				}
				$i++;
			}

			echo "
				<td style='overflow: visible; font-weight: bold; background: #3333;' class='txtright' colspan='2'>
					".(round($sigmaduration1 / 60, 1))."ч
					<br>
					<n style='color: #050;'>".(number_format($sigmapay1, 0, '', ' '))."</n>
				</td>
				<td style='overflow: visible; font-weight: bold; background: #3333;' class='txtright' colspan='2'>
					".(round($sigmaduration2 / 60, 1))."ч
					<br>
					<n style='color: #050;'>".(number_format($sigmapay2, 0, '', ' '))."</n>
				</td>
				<td style='overflow: visible; font-weight: bold; background: #3333;' class='txtright' colspan='2'>
					".(round(($sigmaduration1 + $sigmaduration2) / 60, 1))."ч
					<br>
					<n style='color: #050;'>".(number_format(($sigmapay1 + $sigmapay2), 0, '', ' '))."</n>
				</td>
			";
		}

		// Итог снизу
		echo "<tr><td style='text-align: center; font-size: 1.5em; background: #3333;'><b>Σ</b></td>";
		$i = 1;
		$sigmaduration1 = 0;
		$sigmapay1 = 0;
		$sigmaduration2 = 0;
		$sigmapay2 = 0;
		while ($i <= $days) {
			echo "<td style='padding: 0px; text-align: center; background: #3333; font-weight: bold; writing-mode: vertical-rl;' class='nowrap'>";
			if( $daypay[$i] > 0 ) {
				echo "
					".(round($dayduration[$i] / 60, 1))."ч
					<br>
					<n style='color: #050;'>".(number_format($daypay[$i], 0, '', ' '))."</n>
				";

				if( $i < 16 ) {
					$sigmaduration1 += $dayduration[$i];
					$sigmapay1 += $daypay[$i];
				}
				else {
					$sigmaduration2 += $dayduration[$i];
					$sigmapay2 += $daypay[$i];
				}

				if( $subrow = mysqli_fetch_array($subres) ) {
					$day = $subrow["Day"];
				}
			}
			echo "</td>";
			$i++;
		}
		echo "
			<td style='font-weight: bold; background: #3333;' class='txtright' colspan='2'>
				".(round($sigmaduration1 / 60, 1))."ч
				<br>
				<n style='color: #050;'>".(number_format($sigmapay1, 0, '', ' '))."</n>
			</td>
			<td style='font-weight: bold; background: #3333;' class='txtright' colspan='2'>
				".(round($sigmaduration2 / 60, 1))."ч
				<br>
				<n style='color: #050;'>".(number_format($sigmapay2, 0, '', ' '))."</n>
			</td>
			<td style='font-weight: bold; background: #3333;' class='txtright' colspan='2'>
				".(round(($sigmaduration1 + $sigmaduration2) / 60, 1))."ч
				<br>
				<n style='color: #050;'>".(number_format(($sigmapay1 + $sigmapay2), 0, '', ' '))."</n>
			</td>
		";
		echo "</tr>";

	?>
	</tbody>
</table>

<script>
	$(function(){
		// Подсвечивание столбцов таблицы
		$('#timesheet').columnHover({eachCell:true, hoverClass:'hover', ignoreCols: [1]});

		// Массив регистраций в JSON
		TimeReg = <?= json_encode($TimeReg); ?>;
	});
</script>

<?
	include "footer.php";
?>
