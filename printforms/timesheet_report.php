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
$user_type = $_GET["user_type"];

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
		table-layout: fixed;
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
				,USR.photo
			FROM Users USR
			JOIN TariffMonth TM ON TM.year = {$year}
				AND TM.month = {$month}
				AND TM.USR_ID = USR.USR_ID
				AND TM.F_ID = {$F_ID}
			WHERE 1
				".(($user_type != '') ? "AND USR.user_type LIKE '{$user_type}'" : "")."
			ORDER BY Name
		";
		$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		while( $row = mysqli_fetch_array($res) ) {

			// Находим все актуальные тарифа для этого месяца
			$query = "
				(
					SELECT TST_ID
						,tariff
						,type
						,DATE_FORMAT(valid_from, '%d') valid_from_format
						,DATE_FORMAT(DATE('{$year}-{$month}-01'), '%Y%m') cur_month
						,DATE_FORMAT(valid_from, '%Y%m') from_month
					FROM TimesheetTariff
					WHERE valid_from <= DATE('{$year}-{$month}-01')
						AND USR_ID = {$row["USR_ID"]}
						AND F_ID = {$F_ID}
					ORDER BY valid_from DESC
					LIMIT 1
				)
				UNION
				(
					SELECT TST_ID
						,tariff
						,type
						,DATE_FORMAT(valid_from, '%d') valid_from_format
						,DATE_FORMAT(DATE('{$year}-{$month}-01'), '%Y%m') cur_month
						,DATE_FORMAT(valid_from, '%Y%m') from_month
					FROM TimesheetTariff
					WHERE YEAR(valid_from) = {$year}
						AND MONTH(valid_from) = {$month}
						AND USR_ID = {$row["USR_ID"]}
						AND F_ID = {$F_ID}
					ORDER BY valid_from
				)
			";
			$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			$list_tariff = "";
			while( $subrow = mysqli_fetch_array($subres) ) {
				$valid_from = ($subrow["cur_month"] == $subrow["from_month"]) ? "({$subrow["valid_from_format"]})" : "";
				$type = ($subrow["type"] == 1 ? "с" : ($subrow["type"] == 2 ? "ч" : ($subrow["type"] == 3 ? "ч+" : ($subrow["type"] == 4 ? "м" : ""))));
				$list_tariff .= "{$subrow["tariff"]}/{$type}{$valid_from}<br>";
			}

			echo "<tr><td colspan='2' style='text-align: center; overflow: hidden;'>{$row["Name"]}</td>";
			?>
			<td colspan="2" style="font-size: .8em;" class="nowrap">
				<?=$list_tariff?>
			</td>
			<?

			// Получаем список начислений по работнику за месяц
			$query = "
				SELECT TS.TS_ID
					,DAY(TS.ts_date) Day
					,SUM(TSS.pay) pay
					,GROUP_CONCAT(TSS.pay SEPARATOR '<br>') pay_format
					,TS.fine
					,TS.status
					,TS.payout
					,TS.comment
				FROM Timesheet TS
				LEFT JOIN TimesheetShift TSS ON TSS.TS_ID = TS.TS_ID
				WHERE YEAR(TS.ts_date) = {$year}
					AND MONTH(TS.ts_date) = {$month}
					AND TS.USR_ID = {$row["USR_ID"]}
					AND TS.F_ID = {$F_ID}
				GROUP BY TS.TS_ID
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

					$pay = $subrow["pay"];

					echo "
						<td id='{$subrow["TS_ID"]}' style='font-size: .8em; overflow: visible;' class='tscell nowrap' >
							<div>{$subrow["pay_format"]}</div>
							".($subrow["payout"] ? "<div>-{$subrow["payout"]}</div>" : "")."
							".($subrow["fine"] ? "<div>-{$subrow["fine"]}</div>" : "")."
							".($subrow["status"] == '0' ? "<div class='label'>&mdash;</div>" : ($subrow["status"] == '1' ? "<div class='label'>ОТП</div>" : ($subrow["status"] == '2' ? "<div class='label'>УВ</div>" : ($subrow["status"] == '3' ? "<div class='label'>Б</div>" : ($subrow["status"] == '4' ? "<div class='label'>В</div>" : ($subrow["status"] == '5' ? "<div class='label'>ПР</div>" : ""))))))."
						</td>
					";

					if( $i < 16 ) {
						$sigmapay1 += $pay - $subrow["payout"] - $subrow["fine"];
					}
					else {
						$sigmapay2 += $pay - $subrow["payout"] - $subrow["fine"];
					}
					$daypay[$i] += $pay - $subrow["payout"] - $subrow["fine"];
					$daycnt[$i] += ($pay > 0 ? 1 : 0);

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
		echo "<td colspan='2' class='nowrap' style='text-align: center; font-size: .8em;'>";
		echo "<b>с</b> смена<br><b>ч</b> час-смена<br><b>ч+</b> час-вход<br><b>м</b> месяц";
		echo "</td>";

		$i = 1;
		$sigmapay1 = 0;
		$sigmapay2 = 0;
		while ($i <= $days) {
			echo "<td style='text-align: center;'>";
			if( $daypay[$i] != 0 ) {
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
