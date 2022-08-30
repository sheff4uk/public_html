<?
include "../config.php";
?>

<!DOCTYPE html>
<html lang="ru">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">

<?
//// Проверка прав на доступ к экрану
//if( !in_array('timesheet', $Rights) ) {
//	header($_SERVER['SERVER_PROTOCOL'].' 403 Forbidden');
//	die('Недостаточно прав для совершения операции');
//}

$year = substr($_GET["month"], 0, 4);
$month = substr($_GET["month"], 4, 2);
$F_ID = $_GET["F_ID"];
$outsrc = $_GET["outsrc"];

// Узнаем кол-во дней в выбранном месяце
$strdate = '01.'.$month.'.'.$year;
$timestamp = strtotime($strdate);
$days = date('t', $timestamp);

// Сегодняшний день
$date = date_create();
$date_format = date_format($date, 'd.m.Y H:i');

// Название участка
$query = "SELECT f_name FROM factory WHERE F_ID = {$F_ID}";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
$row = mysqli_fetch_array($res);
$f_name = $row["f_name"];

echo "<title>Табель версия для печати</title>";
?>

<style type="text/css" media="print">
	@page { size: landscape; }
</style>
</head>

<style>
	body, td {
		font-family: Trebuchet MS, Tahoma, Verdana, Arial, sans-serif;
		font-size: 8pt;
	}
	table {
/*		table-layout: fixed;*/
		width: 100%;
		border-collapse: collapse;
		border-spacing: 0px;
	}
	.thead {
		text-align: center;
		font-weight: bold;
	}
	td, th {
/*		padding: 1px;*/
		border: 1px solid black;
		line-height: 1em;
		text-align: center;
	}
	.nowrap {
		white-space: nowrap;
	}
	.total {
		font-weight: bold;
	}
	.label {
		font-weight: bold;
	}
</style>

<body>
<table>
	<thead>
		<tr>
			<th rowspan="1"><img src="/img/logo.png" alt="KONSTANTA" style="width: 200px; margin: 5px;"></th>
			<th rowspan="1" style="font-size: 2em;">Табель</th>
			<th style="position: relative; padding-top: 10px;">
				<span style="position: absolute; top: 0px; left: 5px;" class="nowrap">участок</span>
				<n style="font-size: 2em;"><?=$f_name?></n></th>
			<th style="position: relative; padding-top: 10px;">
				<span style="position: absolute; top: 0px; left: 5px;" class="nowrap">период</span>
				<n style="font-size: 2em;"><?=$month?> - <?=$year?></n>
			</th>
			<th style="position: relative; padding-top: 10px;">
				<span style="position: absolute; top: 0px; left: 5px;" class="nowrap">дата документа</span>
				<n style="font-size: 2em;"><?=$date_format?></n>
			</th>
		</tr>
	</thead>
</table>

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

					echo "<th>".$i++."<br>".$weekdays[$dow]."</th>";
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
				,USR.photo
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
				<?=$row["tariff"]?>
				<br>
				<?=($row["type"] == 1 ? "Смена" : ($row["type"] == 2 ? "Час" : ($row["type"] == 3 ? "Тракторист" : "")))?>
			</td>
			<?

			// Получаем список начислений по работнику за месяц
			$query = "
				SELECT TS.TS_ID
					,DAY(TS.ts_date) Day
					,TS.duration
					,TS.pay
					,TS.rate
					,IF(TM.type = 1, 'Смена', IF(TM.type = 2, 'Час', IF(TM.type = 3, 'Час (тракторист)', ''))) type
					,TM.tariff
					,CONCAT(TS.duration DIV 60, ':', LPAD(TS.duration % 60, 2, '0')) duration_hm
					,TS.status
					,(SELECT USR_ID FROM Timesheet WHERE TS_ID = TS.sub_TS_ID) substitute
					,(SELECT SUM(1) FROM Timesheet WHERE sub_TS_ID = TS.TS_ID) sub_is
				FROM Timesheet TS
				JOIN TariffMonth TM ON TM.TM_ID = TS.TM_ID
				WHERE YEAR(TS.ts_date) = {$year}
					AND MONTH(TS.ts_date) = {$month}
					AND TS.USR_ID = {$row["USR_ID"]}
					AND TS.F_ID = {$F_ID}
				ORDER BY Day
			";
			$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

			$sigmapay1 = 0; // Сумма заработанных денег по работнику 1-15
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

					$pay = ($subrow["pay"] != null) ? round($subrow["pay"] * $subrow["rate"]) : null;

					echo "
						<td id='{$subrow["TS_ID"]}' style='font-size: .9em; overflow: visible;' class='tscell nowrap' ts_id='{$subrow["TS_ID"]}' date_format='{$d}.{$month}.{$year}' usr_name='{$row["Name"]}' photo='{$row["photo"]}' tariff='{$subrow["tariff"]}/{$subrow["type"]}' duration='{$subrow["duration_hm"]}' pay='{$subrow["pay"]}' rate='{$subrow["rate"]}' status='{$subrow["status"]}' substitute='{$subrow["substitute"]}' sub_is='{$subrow["sub_is"]}'>
							{$pay}
							".($subrow["status"] == '0' ? "<div class='label'>&mdash;</div>" : ($subrow["status"] == '1' ? "<div class='label'>ОТП</div>" : ($subrow["status"] == '2' ? "<div class='label'>УВ</div>" : ($subrow["status"] == '3' ? "<div class='label'>Б</div>" : ($subrow["status"] == '4' ? "<div class='label'>В</div>" : ($subrow["status"] == '5' ? "<div class='label'>ПР</div>" : ""))))))."
						</td>
					";

					if( $i < 16 ) {
						$sigmapay1 += $pay;
					}
					else {
						$sigmapay2 += $pay;
					}
					$dayduration[$i] += $subrow["duration"];
					$daypay[$i] += $pay;
					$daycnt[$i] += ($pay ? 1 : 0);

					if( $subrow = mysqli_fetch_array($subres) ) {
						$day = $subrow["Day"];
					}
				}
				else {
					echo "<td style='' class='tscell' ts_date='{$year}-{$month}-{$d}' usr_id='{$row["USR_ID"]}' date_format='{$d}.{$month}.{$year}' usr_name='{$row["Name"]}'></td>";
				}
				$i++;
			}

			echo "
				<td style='overflow: visible; font-weight: bold;' class='txtright' colspan='2'>
					<n>".(number_format($sigmapay1, 0, '', ' '))."</n>
				</td>
				<td style='overflow: visible; font-weight: bold;' class='txtright' colspan='2'>
					<n>".(number_format($sigmapay2, 0, '', ' '))."</n>
				</td>
				<td style='overflow: visible; font-weight: bold;' class='txtright' colspan='2'>
					<n>".(number_format(($sigmapay1 + $sigmapay2), 0, '', ' '))."</n>
				</td>
			";
		}

		// Итог снизу
		echo "<tr><td colspan='2' style='font-size: 1.5em;'><b>Σ</b></td>";
		echo "<td colspan='2' style='text-align: center;'>";
		echo "</td>";

		$i = 1;
		$sigmapay1 = 0;
		$sigmapay2 = 0;
		while ($i <= $days) {
			echo "<td style='text-align: center;'>";
			if( $daypay[$i] > 0 ) {
				echo "<b>".(number_format($daypay[$i], 0, '', ' '))."</b>";
				echo "<br><n>x{$daycnt[$i]}</i>";

				if( $i < 16 ) {
					$sigmapay1 += $daypay[$i];
				}
				else {
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
			<td style='font-weight: bold;' class='txtright' colspan='2'>
				<n>".(number_format($sigmapay1, 0, '', ' '))."</n>
			</td>
			<td style='font-weight: bold;' class='txtright' colspan='2'>
				<n>".(number_format($sigmapay2, 0, '', ' '))."</n>
			</td>
			<td style='font-weight: bold;' class='txtright' colspan='2'>
				<n>".(number_format(($sigmapay1 + $sigmapay2), 0, '', ' '))."</n>
			</td>
		";
		echo "</tr>";

	?>
	</tbody>
</table>

</body>
</html>
