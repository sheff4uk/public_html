<?
include "config.php";
$title = 'План заливки';
include "header.php";
include "./forms/plan_batch_form.php";

// Если в фильтре не установлена неделя, показываем текущую
if( !$_GET["week"] ) {
	$query = "SELECT YEARWEEK(CURDATE(), 1) week";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$row = mysqli_fetch_array($res);
	$_GET["week"] = $row["week"];
}

// Находим очередной цикл на этой неделе, чтобы создать новый план
$query = "
	SELECT SUB.next
		,CONCAT(RIGHT(YEARWEEK(SUB.next, 1), 2), '/', (WEEKDAY(SUB.next) + 1)) `week_cycle`
	FROM (
		SELECT IF(WEEKDAY(MAX(pb_date)) = 6, NULL, IFNULL(ADDDATE(MAX(pb_date), 1), STR_TO_DATE(CONCAT({$_GET["week"]}, '_1'), '%x%v_%w'))) `next`
		FROM plan__Batch
		WHERE YEARWEEK(pb_date, 1) = {$_GET["week"]}
	) SUB
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
$row = mysqli_fetch_array($res);
$next = $row["next"];
$week_cycle = $row["week_cycle"];
?>

<!--Фильтр-->
<div id="filter">
	<h3>Фильтр</h3>
	<form method="get" style="position: relative;">
		<a href="/plan_batch.php" style="position: absolute; top: 10px; right: 10px;" class="button">Сброс</a>

		<div class="nowrap" style="margin-bottom: 10px;">
			<span>Неделя:</span>
			<select name="week" class="<?=$_GET["week"] ? "filtered" : ""?>" onchange="this.form.submit()">
				<?
				$query = "
					SELECT LEFT(YEARWEEK(ADDDATE(CURDATE(), 7), 1), 4) year
					UNION
					SELECT LEFT(YEARWEEK(CURDATE(), 1), 4) year
					UNION
					SELECT LEFT(YEARWEEK(pb_date, 1), 4) year
					FROM plan__Batch
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
							SELECT LEFT(YEARWEEK(ADDDATE(CURDATE(), 7), 1), 4) year
								,YEARWEEK(ADDDATE(CURDATE(), 7), 1) week
								,RIGHT(YEARWEEK(ADDDATE(CURDATE(), 7), 1), 2) week_format
								,DATE_FORMAT(ADDDATE(CURDATE(), 7-WEEKDAY(CURDATE())), '%e %b') WeekStart
								,DATE_FORMAT(ADDDATE(CURDATE(), 13-WEEKDAY(CURDATE())), '%e %b') WeekEnd
							UNION
							SELECT LEFT(YEARWEEK(CURDATE(), 1), 4) year
								,YEARWEEK(CURDATE(), 1) week
								,RIGHT(YEARWEEK(CURDATE(), 1), 2) week_format
								,DATE_FORMAT(ADDDATE(CURDATE(), 0-WEEKDAY(CURDATE())), '%e %b') WeekStart
								,DATE_FORMAT(ADDDATE(CURDATE(), 6-WEEKDAY(CURDATE())), '%e %b') WeekEnd
							UNION
							SELECT LEFT(YEARWEEK(pb_date, 1), 4) year
								,YEARWEEK(pb_date, 1) week
								,RIGHT(YEARWEEK(pb_date, 1), 2) week_format
								,DATE_FORMAT(ADDDATE(pb_date, 0-WEEKDAY(pb_date)), '%e %b') WeekStart
								,DATE_FORMAT(ADDDATE(pb_date, 6-WEEKDAY(pb_date)), '%e %b') WeekEnd
							FROM plan__Batch
							WHERE LEFT(YEARWEEK(pb_date, 1), 4) = {$row["year"]}
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

		<div class="nowrap" style="display: inline-block; margin-bottom: 10px; margin-right: 30px;">
			<span>Код противовеса:</span>
			<select name="CW_ID" class="<?=$_GET["CW_ID"] ? "filtered" : ""?>" style="width: 100px;">
				<option value=""></option>
				<?
				$query = "
					SELECT CW.CW_ID, CW.item
					FROM CounterWeight CW
					ORDER BY CW.CW_ID
				";
				$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
				while( $row = mysqli_fetch_array($res) ) {
					$selected = ($row["CW_ID"] == $_GET["CW_ID"]) ? "selected" : "";
					echo "<option value='{$row["CW_ID"]}' {$selected}>{$row["item"]}</option>";
				}
				?>
			</select>
		</div>

		<div class="nowrap" style="display: inline-block; margin-bottom: 10px; margin-right: 30px;">
			<span>Бренд:</span>
			<select name="CB_ID" class="<?=$_GET["CB_ID"] ? "filtered" : ""?>" style="width: 100px;">
				<option value=""></option>
				<?
				$query = "
					SELECT CB.CB_ID, CB.brand
					FROM ClientBrand CB
					ORDER BY CB.CB_ID
				";
				$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
				while( $row = mysqli_fetch_array($res) ) {
					$selected = ($row["CB_ID"] == $_GET["CB_ID"]) ? "selected" : "";
					echo "<option value='{$row["CB_ID"]}' {$selected}>{$row["brand"]}</option>";
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

<!--Посуточная аналитика-->
<table class="main_table">
	<thead>
		<tr>
			<th>День</th>
			<th>Время первого замеса</th>
			<th>Противовес</th>
			<th>Замесов</th>
			<th>Заливок</th>
			<th>Деталей</th>
			<th>Недоливы</th>
		</tr>
	</thead>
	<tbody style="text-align: center;">

<?
$batches = 0;
$fillings = 0;
$details = 0;
$underfilling = 0;

$query = "
	SELECT COUNT(distinct(PB.CW_ID)) cnt
		,DATE_FORMAT(LB.batch_date, '%d.%m.%y') batch_date_format
		,DATE_FORMAT(LB.batch_date, '%W') weekday_format
		,LB.batch_date
		,COUNT(DISTINCT LB.LB_ID) batches
		,COUNT(LF.LF_ID) fillings
		,SUM(CW.in_cassette) details
	FROM list__Batch LB
	JOIN list__Filling LF ON LF.LB_ID = LB.LB_ID
	LEFT JOIN plan__Batch PB ON PB.PB_ID = LB.PB_ID
	LEFT JOIN CounterWeight CW ON CW.CW_ID = PB.CW_ID
	WHERE 1
		".($_GET["week"] ? "AND YEARWEEK(PB.pb_date, 1) LIKE '{$_GET["week"]}'" : "")."
		".($_GET["CW_ID"] ? "AND PB.CW_ID={$_GET["CW_ID"]}" : "")."
		".($_GET["CB_ID"] ? "AND PB.CW_ID IN (SELECT CW_ID FROM CounterWeight WHERE CB_ID = {$_GET["CB_ID"]})" : "")."
	GROUP BY LB.batch_date
	ORDER BY LB.batch_date
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	$cnt = $row["cnt"];
	$d_batches = 0;
	$d_underfilling = 0;

	$query = "
		SELECT DATE_FORMAT(MIN(LB.batch_time), '%H:%i') first_batch
			,CW.item
			,COUNT(DISTINCT LB.LB_ID) batches
			,COUNT(LF.LF_ID) fillings
			,SUM(CW.in_cassette) details
			,ROUND(IFNULL(SUM(LB.underfilling / PB.fillings_per_batch), 0)) underfilling
		FROM list__Batch LB
		JOIN list__Filling LF ON LF.LB_ID = LB.LB_ID
		LEFT JOIN plan__Batch PB ON PB.PB_ID = LB.PB_ID
		LEFT JOIN CounterWeight CW ON CW.CW_ID = PB.CW_ID
		WHERE 1
			AND LB.batch_date = '{$row["batch_date"]}'
			".($_GET["CW_ID"] ? "AND PB.CW_ID={$_GET["CW_ID"]}" : "")."
			".($_GET["CB_ID"] ? "AND PB.CW_ID IN (SELECT CW_ID FROM CounterWeight WHERE CB_ID = {$_GET["CB_ID"]})" : "")."
		GROUP BY PB.CW_ID
		ORDER BY MIN(LB.batch_time)
	";
	$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $subrow = mysqli_fetch_array($subres) ) {
		$batches += $subrow["batches"];
		$fillings += $subrow["fillings"];
		$details += $subrow["details"];
		$underfilling += $subrow["underfilling"];
		$d_underfilling += $subrow["underfilling"];

		// Выводим общую ячейку с датой заливки
		if( $cnt ) {
			$cnt++;
			echo "<tr style='border-top: 2px solid #333;'>";
			echo "<td rowspan='{$cnt}' style='background-color: rgba(0, 0, 0, 0.2);'><h2>{$row["weekday_format"]}</h2>{$row["batch_date_format"]}</td>";
			$cnt = 0;
		}
		else {
			echo "<tr id='{$subrow["PB_ID"]}'>";
		}
		?>
			<td><?=$subrow["first_batch"]?></td>
			<td><?=$subrow["item"]?></td>
			<td><?=$subrow["batches"]?></td>
			<td><?=$subrow["fillings"]?></td>
			<td><?=($subrow["details"] - $subrow["underfilling"])?></td>
			<td><?=$subrow["underfilling"]?></td>
		</tr>
		<?

	}
?>
		<tr class="summary">
			<td></td>
			<td>Итог:</td>
			<td><?=$row["batches"]?></td>
			<td><?=$row["fillings"]?></td>
			<td><?=($row["details"] - $d_underfilling)?></td>
			<td><?=$d_underfilling?></td>
		</tr>
<?
}
?>
		<tr class="total">
			<td></td>
			<td></td>
			<td>Итог:</td>
			<td><?=$batches?></td>
			<td><?=$fillings?></td>
			<td><?=($details - $underfilling)?></td>
			<td><?=$underfilling?></td>
		</tr>
	</tbody>
</table>

<!--Таблица с планом-->
<h2>План</h2>
<table>
	<thead>
		<tr>
			<th>Неделя/Цикл</th>
			<th>Противовес</th>
			<th>Замесов</th>
			<th>Заливок</th>
			<th>План</th>
			<th>Факт</th>
			<th>Недоливы</th>
			<th>Расчетное время, ч</th>
			<th></th>
			<th></th>
		</tr>
	</thead>

<?
$batches = 0;
$fillings = 0;
$plan = 0;
$details = 0;
$underfilling = 0;

$query = "
	SELECT SUM(1) + 1 cnt
		,DATE_FORMAT(PB.pb_date, '%d.%m.%y') pb_date_format
		,WEEKDAY(PB.pb_date) + 1 cycle
		,RIGHT(YEARWEEK(PB.pb_date, 1), 2) week
		,CONCAT('[', DATE_FORMAT(ADDDATE(PB.pb_date, 0-WEEKDAY(PB.pb_date)), '%e %b'), ' - ', DATE_FORMAT(ADDDATE(PB.pb_date, 6-WEEKDAY(PB.pb_date)), '%e %b'), '] ', LEFT(YEARWEEK(PB.pb_date, 1), 4), ' г') week_range
		,PB.pb_date
		,SUM(PB.batches) batches
		,SUM(PB.batches * IFNULL(PB.fillings_per_batch, CW.fillings)) fillings
		,SUM(PB.batches * IFNULL(PB.fillings_per_batch, CW.fillings) * CW.in_cassette) plan
		,SUM(PB.fact_batches * PB.fillings_per_batch * CW.in_cassette) details
		,IF(SUM(PB.batches) = SUM(PB.fact_batches), (SELECT TIMESTAMPDIFF(MINUTE, MAX(CAST(CONCAT(batch_date, ' ', batch_time) AS DATETIME)), CAST(CONCAT(PB.pb_date, ' 23:59:59') AS DATETIME)) FROM list__Batch WHERE PB_ID IN (SELECT PB_ID FROM plan__Batch WHERE pb_date = PB.pb_date)), NULL) diff
	FROM plan__Batch PB
	JOIN CounterWeight CW ON CW.CW_ID = PB.CW_ID
	WHERE 1
		".($_GET["week"] ? "AND YEARWEEK(PB.pb_date, 1) LIKE '{$_GET["week"]}'" : "")."
		".($_GET["CW_ID"] ? "AND PB.CW_ID={$_GET["CW_ID"]}" : "")."
		".($_GET["CB_ID"] ? "AND PB.CW_ID IN (SELECT CW_ID FROM CounterWeight WHERE CB_ID = {$_GET["CB_ID"]})" : "")."
	GROUP BY PB.pb_date
	ORDER BY PB.pb_date
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	echo "<tbody id='C{$_GET["week"]}{$row["cycle"]}' style='text-align: center; border-bottom: 2px solid #333;'>";
	$cnt = $row["cnt"];
	$d_batches = 0;
	$d_underfilling = 0;

	$query = "
		SELECT PB.PB_ID
			,PB.pb_date
			,CW.item
			,PB.CW_ID
			,PB.batches
			,PB.batches * IFNULL(PB.fillings_per_batch, CW.fillings) fillings
			,PB.batches * IFNULL(PB.fillings_per_batch, CW.fillings) * CW.in_cassette plan
			,PB.fact_batches * PB.fillings_per_batch * CW.in_cassette details
			,IFNULL(SUM(LB.underfilling), 0) underfilling
			,IF(PB.batches > 0 AND PB.fact_batches = 0 AND PB.pb_date <= (CURRENT_DATE() + INTERVAL 1 DAY), 1, 0) printable
			,(SELECT PB_ID FROM plan__Batch WHERE CW_ID = PB.CW_ID AND fact_batches = 0 AND batches > 0 ORDER BY pb_date LIMIT 1) current_PB_ID
		FROM plan__Batch PB
		JOIN CounterWeight CW ON CW.CW_ID = PB.CW_ID
		LEFT JOIN list__Batch LB ON LB.PB_ID = PB.PB_ID
		WHERE 1
			AND PB.pb_date = '{$row["pb_date"]}'
			".($_GET["CW_ID"] ? "AND PB.CW_ID={$_GET["CW_ID"]}" : "")."
			".($_GET["CB_ID"] ? "AND PB.CW_ID IN (SELECT CW_ID FROM CounterWeight WHERE CB_ID = {$_GET["CB_ID"]})" : "")."
		GROUP BY PB.PB_ID
		ORDER BY PB.pb_date DESC, PB.CW_ID
	";
	$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $subrow = mysqli_fetch_array($subres) ) {
		$batches += $subrow["batches"];
		$fillings += $subrow["fillings"];
		$plan += $subrow["plan"];
		$details += $subrow["details"];
		$underfilling += $subrow["underfilling"];
		$d_underfilling += $subrow["underfilling"];

		// Выводим общую ячейку с датой заливки
		if( $last_cycle != $row["cycle"] ) {
			echo "<tr id='{$subrow["PB_ID"]}' style='border-top: 2px solid #333;'>";
			echo "<td rowspan='{$cnt}' style='background-color: rgba(0, 0, 0, 0.2);'><h3>{$row["week"]}/<span style='font-size: 1.5em;'>{$row["cycle"]}</span></h3><span class='nowrap'>{$row["week_range"]}</span><br>".($row["diff"] < 0 ? "Отставание: <b style='color: red;'>{$row["diff"]}</b> мин" : ($row["diff"] > 0 ? "Опережение: <b style='color: green;'>{$row["diff"]}</b> мин" : ""))."</td>";
		}
		else {
			echo "<tr id='{$subrow["PB_ID"]}'>";
		}
		if($subrow["batches"] > 0) {
			$intdiv = intdiv($subrow["batches"] + 1, 4);
			$mod = ($subrow["batches"] + 1) % 4;
			$d_batches += $subrow["batches"] + 1;
			$w_batches += $subrow["batches"] + 1;
		}
		else {
			$intdiv = 0;
			$mod = 0;
		}
		?>
			<td><?=$subrow["item"]?></td>
			<td><?=$subrow["batches"]?></td>
			<td><?=$subrow["fillings"]?></td>
			<td><?=$subrow["plan"]?></td>
			<td class='bg-gray'><?=($subrow["details"] - $subrow["underfilling"])?></td>
			<td class='bg-gray'><?=$subrow["underfilling"]?></td>
			<td class='bg-gray'><?=($intdiv > 0 ? $intdiv : "")?><?=($mod == 1 ? "&frac14;" : ($mod == 2 ? "&frac12;" : ($mod == 3 ? "&frac34;" : "")))?></td>
			<td>
				<?
				if($subrow["printable"]) {
					if( $subrow["PB_ID"] == $subrow["current_PB_ID"] ) {
						echo "<a href='printforms/filling_blank.php?PB_ID={$subrow["PB_ID"]}' class='print' title='Бланк чеклиста оператора'><i class='fas fa-print fa-lg'></i></a>";
					}
					else {
						echo "<i class='fas fa-print fa-lg' style='color: red;' title='Чтобы распечатать очередной чек лист, должны быть внесены данные предыдущего.'></i>";
					}
				}
				?>
			</td>
			<?
			// Выводим общую ячейку с кнопками действий
			if( $last_cycle != $row["cycle"] ) {
				echo "<td rowspan='{$cnt}'><a href='#' class='add_pb' pb_date='{$row["pb_date"]}' cycle='{$row["week"]}/{$row["cycle"]}' title='Изменить данные плана заливки'><i class='fa fa-pencil-alt fa-lg'></i></a></td>";
			}
			?>
		</tr>
		<?
		$last_cycle = $row["cycle"];

	}
	if($d_batches > 0) {
		$intdiv = intdiv($d_batches, 4);
		$mod = $d_batches % 4;
	}
	else {
		$intdiv = 0;
		$mod = 0;
	}
?>
		<tr class="summary">
			<td>Итог:</td>
			<td><?=$row["batches"]?></td>
			<td><?=$row["fillings"]?></td>
			<td><?=$row["plan"]?></td>
			<td><?=($row["details"] - $d_underfilling)?></td>
			<td><?=$d_underfilling?></td>
			<td><?=($intdiv > 0 ? $intdiv : "")?><?=($mod == 1 ? "&frac14;" : ($mod == 2 ? "&frac12;" : ($mod == 3 ? "&frac34;" : "")))?></td>
			<td></td>
		</tr>
<?
	echo "</tbody>";
}
if($w_batches > 0) {
	$intdiv = intdiv($w_batches, 4);
	$mod = $w_batches % 4;
}
else {
	$intdiv = 0;
	$mod = 0;
}
?>
	<tbody style="text-align: center;">
		<tr class="total">
			<td></td>
			<td>Итог:</td>
			<td><?=$batches?></td>
			<td><?=$fillings?></td>
			<td><?=$plan?></td>
			<td><?=($details - $underfilling)?></td>
			<td><?=$underfilling?></td>
			<td><?=($intdiv > 0 ? $intdiv : "")?><?=($mod == 1 ? "&frac14;" : ($mod == 2 ? "&frac12;" : ($mod == 3 ? "&frac34;" : "")))?></td>
			<td></td>
			<td></td>
		</tr>
	</tbody>
</table>

<div>
	<h3>Журнал изменений</h3>
	<table>
		<thead>
			<tr>
				<th colspan="2">Время изменения</th>
				<th>Цикл</th>
				<th>Противовес</th>
				<th>Замесов</th>
				<th>Автор</th>
				<th>Ссылка</th>
			</tr>
		</thead>
		<tbody style="text-align: center;">
			<?
			$query = "
				SELECT PBL.PB_ID
					,Friendly_date(PBL.date_time) friendly_date
					,DATE_FORMAT(PBL.date_time, '%H:%i') time
					,DATE_FORMAT(PB.pb_date, '%d.%m.%y') pb_date_format
					,WEEKDAY(PB.pb_date) + 1 cycle
					,RIGHT(YEARWEEK(PB.pb_date, 1), 2) week
					,CW.item
					,CONCAT('<n style=\"text-decoration: line-through;\">', SPBL.batches, '</n>&nbsp;<i class=\"fas fa-arrow-right\"></i>&nbsp;') prev_batches
					,PBL.batches
					,USR_Icon(PBL.author) usr_icon
				FROM plan__BatchLog PBL
				LEFT JOIN plan__BatchLog SPBL ON SPBL.PBL_ID = PBL.prev_ID
				JOIN plan__Batch PB ON PB.PB_ID = PBL.PB_ID
					".($_GET["week"] ? "AND YEARWEEK(PB.pb_date, 1) LIKE '{$_GET["week"]}'" : "")."
					".($_GET["CW_ID"] ? "AND PB.CW_ID={$_GET["CW_ID"]}" : "")."
					".($_GET["CB_ID"] ? "AND PB.CW_ID IN (SELECT CW_ID FROM CounterWeight WHERE CB_ID = {$_GET["CB_ID"]})" : "")."
				JOIN CounterWeight CW ON CW.CW_ID = PB.CW_ID
				WHERE PBL.date_time IS NOT NULL
				ORDER BY PBL.date_time DESC
			";
			$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			while( $row = mysqli_fetch_array($res) ) {
			?>
				<tr>
					<td><?=$row["friendly_date"]?></td>
					<td><?=$row["time"]?></td>
					<td class="bg-gray"><?=$row["cycle"]?></td>
					<td class="bg-gray"><?=$row["item"]?></td>
					<td class="bg-gray"><?=$row["prev_batches"]?><?=$row["batches"]?></td>
					<td><?=$row["usr_icon"]?></td>
					<td><a href="#<?=$row["PB_ID"]?>"><i class="fas fa-link fa-lg"></i></a></td>
				</tr>
			<?
			}
			?>
		</tbody>
	</table>
</div>

<div id="add_btn" class="add_pb" pb_date="<?=$next?>" cycle="<?=$week_cycle?>" title="Внести данные очередного плана заливки"></div>

<script>
	$(function() {
		$(".print").printPage();
	});
</script>

<?
include "footer.php";
?>
