<?
include "config.php";
include "checkrights.php";

// Сохранение/редактирование
if( isset($_POST["tariff"]) ) {
	session_start();
	$F_ID = $_POST["F_ID"];
	$month = $_POST["month"];
	$user_type = $_POST["user_type"];
	$USR_ID = $_POST["USR_ID"];
	$TST_ID = $_POST["TST_ID"];
	$valid_from = $_POST["valid_from"];
	$tariff = $_POST["tariff"];
	$type = $_POST["type"];

	// Обновляем тариф
	if( $TST_ID ) {
		$query = "
			UPDATE TimesheetTariff
			SET valid_from = '{$valid_from}'
				,tariff = {$tariff}
				,type = {$type}
				,author = {$_SESSION['id']}
			WHERE TST_ID = {$TST_ID}
		";
		mysqli_query( $mysqli, $query );
	}
	else {
		$query = "
			INSERT INTO TimesheetTariff
			SET USR_ID = {$USR_ID}
				,F_ID = {$F_ID}
				,valid_from = '{$valid_from}'
				,tariff = {$tariff}
				,type = {$type}
				,author = {$_SESSION['id']}
		";
		mysqli_query( $mysqli, $query );
		$TST_ID = mysqli_insert_id( $mysqli );
	}

//	// Если изменение тарифа происходит в текущий день
//	if( $valid_from == date('Y-m-d') ) {
//
//		// Узнаем TS_ID
//		$query = "
//			SELECT TS_ID
//			FROM Timesheet
//			WHERE ts_date = CURDATE()
//				AND USR_ID = {$USR_ID}
//				AND F_ID = {$F_ID}
//		";
//		$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
//		$row = mysqli_fetch_array($res);
//		$TS_ID = $row["TS_ID"];
//
//		// Если ячейка табеля в этот день задействована
//		if( $TS_ID ) {
//			$query = "
//				SELECT COUNT(*) shift_cnt
//					,GROUP_CONCAT(TSS_ID) TSS_IDs
//				FROM TimesheetShift
//				WHERE TS_ID = {$TS_ID}
//			";
//			$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
//			$row = mysqli_fetch_array($res);
//			$shift_cnt = $row["shift_cnt"];
//			$TSS_IDs = $row["TSS_IDs"];
//
//			// Если в этот день есть смена
//			if( $shift_cnt ) {
//
//				// Записываем TST_ID в Timesheet
//				$query = "
//					UPDATE Timesheet
//					SET TST_ID = {$TST_ID}
//					WHERE TS_ID = {$TS_ID}
//				";
//				mysqli_query( $mysqli, $query );
//
//				//Обновляем регистрации чтобы пересчиталось начесление
//				$query = "
//					UPDATE TimeReg
//					SET tr_minute = tr_minute
//					WHERE TSS_ID IN ({$TSS_IDs})
//						AND del_time IS NULL
//				";
//				mysqli_query( $mysqli, $query );
//			}
//		}
//	}

	// Перенаправление в табель
	exit ('<meta http-equiv="refresh" content="0; url=/timesheet.php?F_ID='.$F_ID.'&month='.$month.'&user_type='.$user_type.'">');
}

///////////////////////
// Сохранение файлов //
///////////////////////
if( $_FILES['uploadfile']['name'] ) {
	session_start();
	$F_ID = $_POST["F_ID"];
	$month = $_POST["month"];
	$user_type = $_POST["user_type"];

	$filename = date('U').'_'.$_FILES['uploadfile']['name'];
	$uploaddir = './uploads/';
	$uploadfile = $uploaddir.basename($filename);
	$comment = convert_str($_POST["comment"]);
	$comment = mysqli_real_escape_string($mysqli, $comment);
	// Копируем файл из каталога для временного хранения файлов:
	if (copy($_FILES['uploadfile']['tmp_name'], $uploadfile))
	{
		// Записываем в БД информацию о файле
		$query = "
			INSERT INTO UserAttachments
			SET USR_ID = {$_POST["USR_ID"]}
				,filename = '{$filename}'
				,comment = '{$comment}'
		";
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

		$_SESSION["success"][] = "Файл ".$_FILES['uploadfile']['name']." успешно загружен на сервер.";
	}
	else {
		$_SESSION["alert"][] = "Ошибка! Не удалось загрузить файл на сервер!";
	}
	// Перенаправление в табель
	exit ('<meta http-equiv="refresh" content="0; url=/timesheet.php?F_ID='.$F_ID.'&month='.$month.'&user_type='.$user_type.'">');
}
//////////////////////////////

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

$user_type = $_GET["user_type"];
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
			<span>Тип:</span>
			<select name="user_type" class="<?=($_GET["user_type"] != '') ? "filtered" : ""?>" onchange="this.form.submit()">
				<option value=""></option>
			<?
				$query = "
					SELECT USR.user_type
					FROM Users USR
					WHERE USR.user_type IS NOT NULL
					GROUP BY USR.user_type
				";
				$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
				while( $row = mysqli_fetch_array($res) ) {
					$selected = ($_GET["user_type"] == $row["user_type"]) ? "selected" : "";
					echo "<option {$selected} value='{$row["user_type"]}'>{$row["user_type"]}</option>\n";
				}
			?>
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
<style>
	.label {
		background: #99F;
		border-radius: 3px;
		color: #fff;
		font-weight: bold;
		line-height: 1em;
		padding: 0.2em;
		width: fit-content;
		margin: auto;
	}
	#timesheet_report_btn {
		text-align: center;
		line-height: 68px;
		color: #fff;
		bottom: 100px;
		cursor: pointer;
		width: 56px;
		height: 56px;
		opacity: .4;
		position: fixed;
		right: 20px;
		z-index: 9;
		border-radius: 50%;
		background-color: #db4437;
		box-shadow: 0 0 4px rgb(0 0 0 / 14%), 0 4px 8px rgb(0 0 0 / 28%);
	}
	#timesheet_report_btn:hover {
		opacity: 1;
	}
</style>
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
			<th colspan="2">Работник</th>
			<th colspan="2">Тариф</th>
			<?
				// Получаем из базы список выходных дней
				$holidays = array();
				$query = "
					SELECT DATE_FORMAT(holiday, '%e') holiday
					FROM holidays
					WHERE YEAR(holiday) = {$year}
						AND MONTH(holiday) = {$month}
				";
				$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
				while( $row = mysqli_fetch_array($res) ) {
					$holidays[$row["holiday"]] = 1;
				}

				$i = 1;
				$weekdays = ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'];
				while ($i <= $days) {
					$date = $year.'-'.$month.'-'.$i;
					$day_of_week = date('N', strtotime($date));	// День недели 1..7
					$dow = date('w', strtotime($date));			// День недели 0..6
					$day = date('d', strtotime($date));			// День месяца

					if ( isset($holidays[$i]) ) { // Выделяем цветом выходные дни
						echo "<th style='background: chocolate;'>".$i."<br>".$weekdays[$dow]."</th>";
					}
					else {
						echo "<th>".$i."<br>".$weekdays[$dow]."</th>";
					}
					$i++;
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
		// Массив рассчета смен
		$TimesheetShift = array();
		// Массив файлов
		$UserAttachments = array();

		// Получаем список работников
		$query = "
			SELECT USR.USR_ID
				,USR.F_ID
				,USR_Name(USR.USR_ID) Name
				,USR_Icon(USR.USR_ID) Icon
				,USR.act
				,USR.photo
				,TST.TST_ID
				,TST.valid_from
				,TST.tariff
				,TST.type
			FROM Users USR
			JOIN TariffMonth TM ON TM.year = {$year}
				AND TM.month = {$month}
				AND TM.USR_ID = USR.USR_ID
				AND TM.F_ID = {$F_ID}
			LEFT JOIN TimesheetTariff TST ON valid_from > CURDATE()
				AND TST.USR_ID = USR.USR_ID
				AND TST.F_ID = {$F_ID}
			WHERE 1
				".(($user_type != '') ? "AND USR.user_type LIKE '{$user_type}'" : "")."
			ORDER BY Name
		";
		$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		while( $row = mysqli_fetch_array($res) ) {

			// Находим все актуальные тарифа для этого месяца
			$query = "
				(
					SELECT tariff
						,type
						,DATE_FORMAT(valid_from, '%d.%m.%Y') valid_from_format
						,CONCAT(' (Автор: ', USR_Name(author), ')') author
					FROM TimesheetTariff
					WHERE valid_from <= DATE('{$year}-{$month}-01')
						AND USR_ID = {$row["USR_ID"]}
						AND F_ID = {$F_ID}
					ORDER BY valid_from DESC
					LIMIT 1
				)
				UNION
				(
					SELECT tariff
						,type
						,DATE_FORMAT(valid_from, '%d.%m.%Y') valid_from_format
						,CONCAT(' (Автор: ', USR_Name(author), ')') author
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
				$type = ($subrow["type"] == 1 ? "с" : ($subrow["type"] == 2 ? "ч" : ($subrow["type"] == 3 ? "ч+" : ($subrow["type"] == 4 ? "м" : ""))));
				$list_tariff .= "<span title='от {$subrow["valid_from_format"]}{$subrow["author"]}' class='nowrap'>{$subrow["tariff"]}/{$type}</span><br>";
			}

			// Заполняем массив файлов
			$query = "
				SELECT UA.UA_ID
					,UA.filename
					,UA.comment
				FROM UserAttachments UA
				WHERE UA.USR_ID = {$row["USR_ID"]}
			";
			$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			while( $subrow = mysqli_fetch_array($subres) ) {
				$UserAttachments[$row["USR_ID"]][] = array("UA_ID" => $subrow["UA_ID"], "filename" => "{$subrow["filename"]}", "comment" => "{$subrow["comment"]}");
			}

			echo "<tr><td colspan='2' style='text-align: center;'><a href='#' class='usercell' USR_ID='{$row["USR_ID"]}' name='{$row["Name"]}'>{$row["Name"]}</a></td>";
			?>
			<td colspan="2" class='tariff_edit' tst_id='<?=$row["TST_ID"]?>' valid_from='<?=$row["valid_from"]?>' tariff='<?=$row["tariff"]?>' type='<?=$row["type"]?>' USR_ID='<?=$row["USR_ID"]?>' name='<?=$row["Name"]?>' style="overflow: hidden; cursor: pointer;<?=($list_tariff == '' ? " background: #f006;" : "")?>">
				<a href='#'><?=$list_tariff?></a>
			</td>
			<?

			// Получаем список начислений по работнику за месяц
			$query = "
				SELECT TS.TS_ID
					,TS.ts_date
					,TS.ts_date + INTERVAL 1 DAY tomorrow
					,IF(TIMESTAMPDIFF(DAY, TS.ts_date, CURDATE()) <= 40 AND TIMESTAMPDIFF(HOUR, TS.ts_date, NOW()) >= 32, 1, 0) editable
					,DAY(TS.ts_date) Day
					,TS.fine
					,TS.status
					,TS.payout
					,TS.comment
				FROM Timesheet TS
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

				if( $i == $day ) {
					$man_reg = 0;
					$duration_hm_title = "";
					$pay_format = "";
					$pay = null;

					// Заполняем массив расчета смен
					$query = "
						SELECT TSS.TSS_ID
							,TSS.shift_num
							,TSS.tss_tariff
							,TSS.tss_type
							,CONCAT( TSS.duration DIV 60, ':', LPAD(TSS.duration % 60, 2, '0')) duration_hm
							,TSS.pay
							,USR_Icon(TSS.tss_author) tss_author
							,DATE_FORMAT(TSS.last_edit, '%d.%m.%Y в %H:%i:%s') last_edit
							,CONCAT('&#1012', (TSS.shift_num+1), ';', TSS.duration DIV 60, ':', LPAD(TSS.duration % 60, 2, '0')) duration_hm_title
						FROM TimesheetShift TSS
						WHERE TSS.TS_ID = {$subrow["TS_ID"]}
					";
					$subsubres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
					while( $subsubrow = mysqli_fetch_array($subsubres) ) {
						$TimesheetShift[$subrow["TS_ID"]][] = array("TSS_ID" => $subsubrow["TSS_ID"], "shift_num" => $subsubrow["shift_num"], "tss_tariff" => $subsubrow["tss_tariff"], "tss_type" => "{$subsubrow["tss_type"]}", "duration_hm" => "{$subsubrow["duration_hm"]}", "pay" => "{$subsubrow["pay"]}", "tss_author" => "{$subsubrow["tss_author"]}", "last_edit" => "{$subsubrow["last_edit"]}");
						if( $subsubrow["last_edit"] != null ) {
							$man_reg = 1;
						}

						$duration_hm_title .= "{$subsubrow["duration_hm_title"]}&emsp;";
						$pay_format .= "{$subsubrow["pay"]}<br>";
						$pay += $subsubrow["pay"];
					}

					// Заполняем массив регистраций
					$query = "
						SELECT TR.TR_ID
							,TSS.shift_num
							,IF(TR.prefix, '▶', '◼') prefix
							,CONCAT(LPAD((TR.tr_minute DIV 60) % 24, 2, '0'), ':', LPAD(TR.tr_minute % 60, 2, '0')) tr_time
							,TR.tr_photo
							,CONCAT(Friendly_date(TR.add_time), '<br>', DATE_FORMAT(TR.add_time, '%H:%i:%s')) add_time
							,IF(TR.add_author IS NOT NULL, USR_Icon(TR.add_author), '') add_author
							,CONCAT(Friendly_date(TR.del_time), '<br>', DATE_FORMAT(TR.del_time, '%H:%i:%s')) del_time
							,IF(TR.del_author IS NOT NULL, USR_Icon(TR.del_author), '') del_author
						FROM TimeReg TR
						JOIN TimesheetShift TSS ON TSS.TSS_ID = TR.TSS_ID
						WHERE TSS.TS_ID = {$subrow["TS_ID"]}
						ORDER BY TSS.shift_num, TR.tr_minute, TR.TR_ID
					";
					$subsubres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
					while( $subsubrow = mysqli_fetch_array($subsubres) ) {
						$TimeReg[$subrow["TS_ID"]][] = array("TR_ID" => $subsubrow["TR_ID"], "shift_num" => $subsubrow["shift_num"], "prefix" => $subsubrow["prefix"], "tr_time" => "{$subsubrow["tr_time"]}", "tr_photo" => "{$subsubrow["tr_photo"]}", "add_time" => "{$subsubrow["add_time"]}", "add_author" => "{$subsubrow["add_author"]}", "del_time" => "{$subsubrow["del_time"]}", "del_author" => "{$subsubrow["del_author"]}");
						if( $subsubrow["add_time"] != null ) {
							$man_reg = 1;
						}
					}

					echo "
						<td title='{$duration_hm_title}' id='{$subrow["TS_ID"]}' style='font-size: .9em; overflow: visible; padding: 0px; text-align: center;".(isset($holidays[$i]) ? " background: #09f3;" : "").($pay == '0' ? " background: #f006;" : "")."' class='tscell nowrap' ts_id='{$subrow["TS_ID"]}' date_format='{$d}.{$month}.{$year}' date='{$subrow["ts_date"]}' tomorrow='{$subrow["tomorrow"]}' editable='{$subrow["editable"]}' usr_name='{$row["Name"]}' photo='{$row["photo"]}' status='{$subrow["status"]}' payout='{$subrow["payout"]}' comment='{$subrow["comment"]}'>
							".($man_reg ? "<div style='position: absolute; top: 0px; left: 0px; width: 5px; height: 5px; border-radius: 0 0 5px 0; background: red; box-shadow: 0 0 1px 1px red;'></div>" : "")."
							<div>{$pay_format}</div>
							".($subrow["payout"] ? "<div style='color: red;' title='{$subrow["comment"]}'>-{$subrow["payout"]}</div>" : "")."
							".($subrow["fine"] ? "<div style='color: red;'>-{$subrow["fine"]}</div>" : "")."
							".($subrow["status"] == '0' ? "<div class='label'>&mdash;</div>" : ($subrow["status"] == '1' ? "<div class='label'>ОТП</div>" : ($subrow["status"] == '2' ? "<div class='label'>УВ</div>" : ($subrow["status"] == '3' ? "<div class='label'>Б</div>" : ($subrow["status"] == '4' ? "<div class='label'>В</div>" : ($subrow["status"] == '5' ? "<div class='label'>ПР</div>" : ($subrow["status"] == '6' ? "<div class='label'>КОМ</div>" : "")))))))."
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
					// Узнаем дату следующего дня
					$query = "
						SELECT
							'{$year}-{$month}-{$d}' + INTERVAL 1 DAY tomorrow
							,IF(TIMESTAMPDIFF(DAY, '{$year}-{$month}-{$d}', CURDATE()) <= 40 AND TIMESTAMPDIFF(HOUR, '{$year}-{$month}-{$d}', NOW()) >= 32, 1, 0) editable
					";
					$subsubsubres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
					$subsubsubrow = mysqli_fetch_array($subsubsubres);

					echo "<td style='".(isset($holidays[$i]) ? " background: #09f3;" : "")."' class='tscell' date='{$year}-{$month}-{$d}' tomorrow='{$subsubsubrow["tomorrow"]}' editable='{$subsubsubrow["editable"]}' ts_date='{$year}-{$month}-{$d}' usr_id='{$row["USR_ID"]}' date_format='{$d}.{$month}.{$year}' usr_name='{$row["Name"]}'></td>";
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
		echo "<td colspan='2' class='nowrap' style='text-align: center; background: #3333;'><span><b>с</b> смена<br><b>ч</b> час-смена(от начала смены до регистрации выхода)<br><b>ч+</b> час-вход(от регистрации входа до регистрации выхода)<br><b>м</b> месяц(начисление происходит только в рабочие дни)</span></td>";

		$i = 1;
		$sigmapay1 = 0;
		$sigmapay2 = 0;
		while ($i <= $days) {
			echo "<td style='padding: 0px; text-align: center; background: #3333;'>";
			if( $daypay[$i] != 0 ) {
				echo "<b>".(number_format($daypay[$i], 0, '', ' '))."</b>";
				echo "<br><n>x{$daycnt[$i]}</n>";

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

<div id="timesheet_report_btn" title="Распечатать табель"><a href="/printforms/timesheet_report.php?F_ID=<?=$_GET["F_ID"]?>&month=<?=$_GET["month"]?>&user_type=<?=$user_type?>" class="print" style="color: white;"><i class="fas fa-2x fa-print"></i></a></div>

<div id='tariff_form' class="addproduct" style='display:none;'>
	<form method='post' onsubmit="JavaScript:this.subbut.disabled=true;
this.subbut.value='Подождите, пожалуйста!';">
		<fieldset>
			<input type="hidden" name="F_ID" value="<?=$F_ID?>">
			<input type="hidden" name="month" value="<?=$_GET["month"]?>">
			<input type="hidden" name="user_type" value="<?=$user_type?>">
			<input type="hidden" name="USR_ID">
			<input type="hidden" name="TST_ID">

			<h3>Запланировать изменение тарифа</h3>
			<table>
				<thead>
					<tr>
						<th>Начало действия</th>
						<th>Тариф</th>
						<th>Тип</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td><input type="date" name="valid_from" min="<?=date('Y-m-d', strtotime("+1 day"))?>" required></td>
						<td><input type="number" name="tariff" min="0" style="width: 100px;" required></td>
						<td>
							<select name="type" required>
								<option value=""></option>
								<option value="1">Смена</option>
								<option value="2">Час-смена (от начала смены до регистрации выхода)</option>
								<option value="3">Час-вход (от регистрации входа до регистрации выхода)</option>
								<option value="4">Месяц (начисление происходит только в рабочие дни)</option>
							</select>
						</td>
					</tr>
				</tbody>
			</table>
		</fieldset>
		<div>
			<hr>
			<input type='submit' name="subbut" value='Записать' style='float: right;'>
		</div>
	</form>
</div>

<div id='uploads_form' class="addproduct" style='display:none;'>
	<form enctype='multipart/form-data' method='post' onsubmit="JavaScript:this.subbut.disabled=true;
this.subbut.value='Подождите, пожалуйста!';">
		<fieldset>
			<input type="hidden" name="F_ID" value="<?=$F_ID?>">
			<input type="hidden" name="month" value="<?=$_GET["month"]?>">
			<input type="hidden" name="user_type" value="<?=$user_type?>">
			<input type="hidden" name="USR_ID">

			<a id="user_edit" href="" target="_blank" class="button">Редактировать данные работника</a>

			<table style="width: 100%;">
				<thead>
					<tr>
					<th width="">Файл</th>
					<th width="">Комментарий</th>
					<th width=""></th>
					</tr>
				</thead>
				<tbody id="attachments"></tbody>
			</table>

			<input type="file" name="uploadfile">
			<input type="text" name="comment" placeholder="Комментарий" autocomplete="off">
		</fieldset>
		<div>
			<hr>
			<input type='submit' name="subbut" value='Загрузить' style='float: right;'>
		</div>
	</form>
</div>

<script>
	$(function(){
		// Подсвечивание столбцов таблицы
		$('#timesheet').columnHover({eachCell:true, hoverClass:'hover', ignoreCols: [1]});

		// Массив регистраций в JSON
		TimeReg = <?= json_encode($TimeReg); ?>;

		// Массив расчета смен в JSON
		TimesheetShift = <?= json_encode($TimesheetShift); ?>;

		// Массив файлов в JSON
		UserAttachments = <?= json_encode($UserAttachments); ?>;

		$(".print").printPage();

		// Редактирование тарифа
		$('.tariff_edit').click( function() {
			// Проверяем сессию
			$.ajax({ url: "check_session.php?script=1", dataType: "script", async: false });

			var tariff = $(this).attr('tariff'),
				type = $(this).attr('type'),
				tst_id = $(this).attr('tst_id'),
				valid_from = $(this).attr('valid_from'),
				USR_ID = $(this).attr('USR_ID'),
				name = $(this).attr('name');

			$('#tariff_form input[name="tariff"]').val(tariff);
			$('#tariff_form select[name="type"]').val(type);
			$('#tariff_form input[name="TST_ID"]').val(tst_id);
			$('#tariff_form input[name="valid_from"]').val(valid_from);
			$('#tariff_form input[name="USR_ID"]').val(USR_ID);

//			if( valid_from == "<?=date('Y-m-d')?>" ) {
//				$('#tariff_form input[name="valid_from"]').attr("max", "<?=date('Y-m-d')?>");
//			}
//			else {
//				$('#tariff_form input[name="valid_from"]').attr("max", "");
//			}

			$('#tariff_form').dialog({
				title: name,
				resizable: false,
				width: 800,
				modal: true,
				closeText: 'Закрыть'
			});

			return false;
		});

		$('.usercell').on('click', function(){
			// Проверяем сессию
			$.ajax({ url: "check_session.php?script=1", dataType: "script", async: false });

			var USR_ID = $(this).attr('USR_ID'),
				name = $(this).attr('name');
			var arr_attachments = UserAttachments[USR_ID];
			var html = '';

			$('#uploads_form input[name=USR_ID]').val(USR_ID);
			$('#uploads_form #user_edit').attr('href', 'users.php?USR_ID='+USR_ID);

			if( arr_attachments ) {
				$.each(arr_attachments, function(key, val){
					html = html + "<tr>";
					html = html + "<td><a href='/uploads/"+val["filename"]+"' target='_blank'>"+val["filename"]+"</td>";
					html = html + "<td>"+val["comment"]+"</td>";
					html = html + "</tr>";
				});
			}

			$('#uploads_form #attachments').html(html);

			$('#uploads_form').dialog({
				title: 'Файлы | '+name,
				resizable: false,
				width: 650,
				modal: true,
				closeText: 'Закрыть'
			});

			return false;
		});
	});
</script>

<?
	include "footer.php";
?>
