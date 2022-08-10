<?
include "config.php";

// Сохранение/редактирование
if( isset($_POST["F_ID"]) ) {
	session_start();
	$F_ID = $_POST["F_ID"];
	$month = $_POST["month"];
	$outsrc = $_POST["outsrc"];

	// Изменение тарифов возможно только в текущем месяце
	if( $month == date('Ym') ) {
		foreach ($_POST["tariff"] as $key => $value) {
			$USR_ID = $key;
			$new_tariff = $value;
			$new_type = $_POST["type"][$key];

			// Узнаем текущее значение
			$query = "
				SELECT TM.tariff, TM.type
				FROM TariffMonth TM
				WHERE CONCAT(TM.year, LPAD(TM.month, 2, '0')) LIKE '{$month}'
					AND TM.USR_ID = {$USR_ID}
					AND TM.F_ID = {$F_ID}
			";
			$res = mysqli_query( $mysqli, $query );
			$row = mysqli_fetch_array($res);
			$tariff = $row["tariff"];
			$type = $row["type"];

			// Если тариф изменился
			if( $new_tariff != $tariff or $new_type != $type ) {
				// Обновляем тариф
				$query = "
					UPDATE TariffMonth TM
					SET TM.tariff = {$new_tariff}, TM.type = {$new_type}
					WHERE CONCAT(TM.year, LPAD(TM.month, 2, '0')) LIKE '{$month}'
						AND TM.USR_ID = {$USR_ID}
						AND TM.F_ID = {$F_ID}
				";
				mysqli_query( $mysqli, $query );

				// Пересчитываем начисления в табеле
				$query = "
					SELECT GROUP_CONCAT(TS.TS_ID) TS_IDs
					FROM Timesheet TS
					WHERE TS.USR_ID = {$USR_ID}
						AND TS.F_ID = {$F_ID}
						AND DATE_FORMAT(TS.ts_date, '%Y%m') LIKE '{$month}'
				";
				$res = mysqli_query( $mysqli, $query );
				$row = mysqli_fetch_array($res);
				$TS_IDs = $row["TS_IDs"];

				$query = "
					UPDATE TimeReg TR
					SET TR.tr_time = TR.tr_time
					WHERE TR.TS_ID IN (0,{$TS_IDs})
				";
				mysqli_query( $mysqli, $query );
			}
		}
	}

	// Перенаправление в табель
	exit ('<meta http-equiv="refresh" content="0; url=/timesheet.php?F_ID='.$F_ID.'&month='.$month.'&outsrc='.$outsrc.'">');
}

$title = 'Табель';
include "header.php";

// Проверка прав на доступ к экрану
if( !in_array('timesheet', $Rights) ) {
	header($_SERVER['SERVER_PROTOCOL'].' 403 Forbidden');
	die('Недостаточно прав для совершения операции');
}

// Если в фильтре не установлена неделя, показываем текущую
if( !$_GET["month"] ) {
	$query = "SELECT DATE_FORMAT(CURDATE(), '%Y%m') month";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$row = mysqli_fetch_array($res);
	$_GET["month"] = $row["month"];

}
$year = substr($_GET["month"], 0, 4);
$month = substr($_GET["month"], 4, 2);

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

$outsrc = $_GET["outsrc"];
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
			<select name="month" class="<?=$_GET["month"] ? "filtered" : ""?>" onchange="this.form.submit()">
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
								#,DATE_FORMAT(CURDATE(), '%b') month_format
								,DATE_FORMAT(CURDATE(), '%c') month_format
							UNION
							SELECT YEAR(ts_date) year
								,DATE_FORMAT(ts_date, '%Y%m') month
								#,DATE_FORMAT(ts_date, '%b') month_format
								,DATE_FORMAT(ts_date, '%c') month_format
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
						echo "<option value='{$subrow["month"]}' {$selected}>{$MONTHS[$subrow["month_format"]]}</option>";
					}
					echo "</optgroup>";
				}
				?>
			</select>
			<i class="fas fa-question-circle" title="По умолчанию устанавливается текущий месяц."></i>
		</div>

		<div class="nowrap" style="margin-bottom: 10px;">
			<span>Аутсорсер:</span>
			<select name="outsrc" class="<?=($_GET["outsrc"] != '') ? "filtered" : ""?>" onchange="this.form.submit()">
				<option value=""></option>
				<option <?=(($_GET["outsrc"] == '1') ? "selected" : "")?> value="1">Да</option>
				<option <?=(($_GET["outsrc"] == '0') ? "selected" : "")?> value="0">Нет</option>
			</select>
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

<form method="post">
<input type="hidden" name="F_ID" value="<?=$_GET["F_ID"]?>">
<input type="hidden" name="month" value="<?=$_GET["month"]?>">
<input type="hidden" name="outsrc" value="<?=$_GET["outsrc"]?>">

<table id="timesheet" class="main_table">
	<thead>
		<tr class="nowrap">
			<th colspan="2">Работник</th>
			<th colspan="2">Тариф</th>
			<?
				// Получаем производственный календарь на выбранный год
				$xml = simplexml_load_file("http://xmlcalendar.ru/data/ru/".$year."/calendar.xml");
				$json = json_encode($xml);
				$data = json_decode($json,TRUE);

				$i = 1;
				$workdays = 0;
				$weekdays = ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'];
				while ($i <= $days) {
					$date = $year.'-'.$month.'-'.$i;
					$day_of_week = date('N', strtotime($date));	// День недели 1..7
					$dow = date('w', strtotime($date));			// День недели 0..6
					$day = date('d', strtotime($date));			// День месяца

					// Перебираем массив и если находим дату то проверяем ее тип (тип дня: 1 - выходной день, 2 - рабочий и сокращенный (может быть использован для любого дня недели), 3 - рабочий день (суббота/воскресенье))
					$t = 0;
					foreach( $data["days"]["day"] as $key=>$value ) {
						if( $value["@attributes"]["d"] == $month.".".$day) {
							$t = $value["@attributes"]["t"];
						}
					}

					if ( (($day_of_week >= 6 and $t != "3" and $t != "2") or ($t == "1")) ) { // Выделяем цветом выходные дни
						echo "<th style='background: chocolate;'>".$i++."<br>".$weekdays[$dow]."</th>";
					}
					else {
						echo "<th>".$i++."<br>".$weekdays[$dow]."</th>";
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
		$daycnt = array();
		// Массив регистраций
		$TimeReg = array();

		// Получаем список работников
		$query = "
			SELECT USR.USR_ID
				,USR.F_ID
				,USR_Name(USR.USR_ID) Name
				,USR_Icon(USR.USR_ID) Icon
				,USR.act
				,USR.official
				,TM.tariff
				,TM.type
			FROM Users USR
			JOIN TariffMonth TM ON TM.year = {$year}
				AND TM.month = {$month}
				AND TM.USR_ID = USR.USR_ID
				AND TM.F_ID = {$F_ID}
			WHERE 1
				".(($outsrc != '') ? "AND IFNULL(USR.outsourcer, 0) = {$outsrc}" : "")."
			ORDER BY Name
		";
		$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		while( $row = mysqli_fetch_array($res) ) {
			echo "<tr><td colspan='2' style='text-align: center; ".($row["official"] ? "background: #0F05;" : "")."'>{$row["Name"]}</td>";
			?>
			<td colspan="2">
				<input type="number" value="<?=$row["tariff"]?>" name="tariff[<?=$row["USR_ID"]?>]" min="0" style="width: 60px;" <?=$_GET["month"] == date('Ym') ? "" : "disabled"?>>
				<br>
				<div class="nowrap">
					<input type="radio" name="type[<?=$row["USR_ID"]?>]" value="1" style="margin: 0;" title="Смена" <?=$row["type"] == 1 ? "checked" : ""?> <?=$_GET["month"] == date('Ym') ? "" : "disabled"?>>
					<input type="radio" name="type[<?=$row["USR_ID"]?>]" value="2" style="margin: 0;" title="Час" <?=$row["type"] == 2 ? "checked" : ""?> <?=$_GET["month"] == date('Ym') ? "" : "disabled"?>>
					<input type="radio" name="type[<?=$row["USR_ID"]?>]" value="3" style="margin: 0;" title="Час (тракторист)"  <?=$row["type"] == 3 ? "checked" : ""?> <?=$_GET["month"] == date('Ym') ? "" : "disabled"?>>
				</div>
			</td>
			<?

			// Получаем список начислений по работнику за месяц
			$query = "
				SELECT TS.TS_ID
					,DAY(TS.ts_date) Day
					,TS.duration
					,TS.pay
					,IF(TM.type = 1, 'Смена', IF(TM.type = 2, 'Час', IF(TM.type = 3, 'Час (тракторист)', ''))) type
					,TM.tariff
					,CONCAT(TS.duration DIV 60, ':', LPAD(TS.duration % 60, 2, '0')) duration_hm
				FROM Timesheet TS
				JOIN TariffMonth TM ON TM.TM_ID = TS.TM_ID
				WHERE YEAR(TS.ts_date) = {$year}
					AND MONTH(TS.ts_date) = {$month}
					AND TS.USR_ID = {$row["USR_ID"]}
					AND TS.F_ID = {$F_ID}
				ORDER BY Day
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
				$date = $year.'-'.$month.'-'.$i;
				$day_of_week = date('N', strtotime($date));	// День недели 1..7
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
					$man_reg = 0;
					$subsubres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
					while( $subsubrow = mysqli_fetch_array($subsubres) ) {
						$TimeReg[$subrow["TS_ID"]][] = array("TR_ID" => $subsubrow["TR_ID"], "tr_time" => "{$subsubrow["tr_time"]}", "tr_photo" => "{$subsubrow["tr_photo"]}", "add_time" => "{$subsubrow["add_time"]}", "add_author" => "{$subsubrow["add_author"]}", "del_time" => "{$subsubrow["del_time"]}", "del_author" => "{$subsubrow["del_author"]}");
						if( $subsubrow["add_time"] != '' and $subsubrow["del_time"] == '' ) {
							$man_reg = 1;
						}
					}

					echo "
						<td id='{$subrow["TS_ID"]}' style='font-size: .9em; overflow: visible; padding: 0px; text-align: center;".($day_of_week >= 6 ? " background: #09f3;" : "").($subrow["pay"] == '0' ? " background: #f006;" : "")."' class='tscell nowrap' ts_id='{$subrow["TS_ID"]}' date_format='{$d}.{$month}.{$year}' usr_name='{$row["Name"]}' tariff='{$subrow["tariff"]}/{$subrow["type"]}' duration='{$subrow["duration_hm"]}' pay='{$subrow["pay"]}'>
							".($man_reg ? "<div style='position: absolute; top: 0px; left: 0px; width: 5px; height: 5px; border-radius: 0 0 5px 0; background: red; box-shadow: 0 0 1px 1px red;'></div>" : "")."
							{$subrow["pay"]}
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
					$daycnt[$i] += ($subrow["pay"] ? 1 : 0);

					if( $subrow = mysqli_fetch_array($subres) ) {
						$day = $subrow["Day"];
					}
				}
				else {
					echo "<td style='".($day_of_week >= 6 ? " background: #09f3;" : "")."' class='tscell' ts_date='{$year}-{$month}-{$d}' usr_id='{$row["USR_ID"]}' date_format='{$d}.{$month}.{$year}' usr_name='{$row["Name"]}'></td>";
				}
				$i++;
			}

			echo "
				<td style='overflow: visible; font-weight: bold; background: #3333;' class='txtright' colspan='2'>
					<n>".(number_format($sigmapay1, 0, '', ' '))."</n>
				</td>
				<td style='overflow: visible; font-weight: bold; background: #3333;' class='txtright' colspan='2'>
					<n>".(number_format($sigmapay2, 0, '', ' '))."</n>
				</td>
				<td style='overflow: visible; font-weight: bold; background: #3333;' class='txtright' colspan='2'>
					<n>".(number_format(($sigmapay1 + $sigmapay2), 0, '', ' '))."</n>
				</td>
			";
		}

		// Итог снизу
		echo "<tr><td colspan='2' style='text-align: center; font-size: 1.5em; background: #3333;'><b>Σ</b></td>";
		echo "<td colspan='2' style='text-align: center; background: #3333;'>";
		if( $_GET["month"] == date('Ym') ) {
			echo "<button title='Сохранить изменения в тарифах'><i class='fas fa-save'></i></button>";
		}
		echo "</td>";

		$i = 1;
		$sigmaduration1 = 0;
		$sigmapay1 = 0;
		$sigmaduration2 = 0;
		$sigmapay2 = 0;
		while ($i <= $days) {
			echo "<td style='padding: 0px; text-align: center; background: #3333;'>";
			if( $daypay[$i] > 0 ) {
				echo "<b>".(number_format($daypay[$i], 0, '', ' '))."</b>";
				echo "<br><n>x{$daycnt[$i]}</i>";

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
				<n>".(number_format($sigmapay1, 0, '', ' '))."</n>
			</td>
			<td style='font-weight: bold; background: #3333;' class='txtright' colspan='2'>
				<n>".(number_format($sigmapay2, 0, '', ' '))."</n>
			</td>
			<td style='font-weight: bold; background: #3333;' class='txtright' colspan='2'>
				<n>".(number_format(($sigmapay1 + $sigmapay2), 0, '', ' '))."</n>
			</td>
		";
		echo "</tr>";

	?>
	</tbody>
</table>
</form>

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
