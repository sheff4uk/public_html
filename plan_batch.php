<?
include "config.php";
$title = 'План заливки';
include "header.php";
include "./forms/plan_batch_form.php";

// Если в фильтре не установлена неделя, показываем текущую
if( !$_GET["week"] ) {
	$query = "SELECT YEARWEEK(NOW(), 1) week";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$row = mysqli_fetch_array($res);
	$_GET["week"] = $row["week"];
}
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
					SELECT YEAR(NOW()) year
					UNION
					SELECT YEAR(pb_date) year
					FROM plan__Batch
					ORDER BY year DESC
				";
				$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
				while( $row = mysqli_fetch_array($res) ) {
					echo "<optgroup label='{$row["year"]}'>";
					$query = "
						SELECT YEARWEEK(NOW(), 1) week
							,WEEK(NOW(), 1) week_format
							,DATE_FORMAT(adddate(CURDATE(), INTERVAL 0-WEEKDAY(CURDATE()) DAY), '%e %b') WeekStart
							,DATE_FORMAT(adddate(CURDATE(), INTERVAL 6-WEEKDAY(CURDATE()) DAY), '%e %b') WeekEnd
						UNION
						SELECT YEARWEEK(pb_date, 1) week
							,WEEK(pb_date, 1) week_format
							,DATE_FORMAT(adddate(pb_date, INTERVAL 0-WEEKDAY(pb_date) DAY), '%e %b') WeekStart
							,DATE_FORMAT(adddate(pb_date, INTERVAL 6-WEEKDAY(pb_date) DAY), '%e %b') WeekEnd
						FROM plan__Batch
						WHERE YEAR(pb_date) = {$row["year"]}
						GROUP BY week
						ORDER BY week DESC
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
			<i class="fas fa-question-circle" title="По умолчанию устанавливаются текущая неделя."></i>
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

<table class="main_table">
	<thead>
		<tr>
			<th>Цикл/неделя</th>
			<th>Противовес</th>
			<th>Замесов</th>
			<th>Заливок</th>
			<th>План</th>
			<th>Факт</th>
			<th>Недоливы</th>
			<th>Расчетное время, ч</th>
			<th></th>
		</tr>
	</thead>
	<tbody style="text-align: center;">

<?
$query = "
	SELECT SUM(1) cnt
		,DATE_FORMAT(PB.pb_date, '%d.%m.%y') pb_date_format
		,WEEKDAY(PB.pb_date) + 1 pb_date_weekday
		,WEEK(PB.pb_date, 1) week
		,CONCAT('[', DATE_FORMAT(adddate(PB.pb_date, INTERVAL 0-WEEKDAY(PB.pb_date) DAY), '%e %b'), ' - ', DATE_FORMAT(adddate(PB.pb_date, INTERVAL 6-WEEKDAY(PB.pb_date) DAY), '%e %b'), '] ', YEAR(PB.pb_date), ' г') week_range
		,PB.pb_date
		,SUM(PB.batches) batches
		,SUM(PB.batches * CW.fillings) fillings
		,SUM(PB.batches * CW.fillings * CW.in_cassette) plan
		,SUM(PB.fakt * CW.fillings * CW.in_cassette) fakt
		,IF(SUM(PB.batches) = SUM(PB.fakt), (SELECT TIMESTAMPDIFF(MINUTE, MAX(CAST(CONCAT(batch_date, ' ', batch_time) AS DATETIME)), CAST(CONCAT(PB.pb_date, ' 23:59:59') AS DATETIME)) FROM list__Batch WHERE PB_ID IN (SELECT PB_ID FROM plan__Batch WHERE pb_date = PB.pb_date)), NULL) diff
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
	$cnt = $row["cnt"];
	$d_batches = 0;
	$d_underfilling = 0;

	$query = "
		SELECT PB.PB_ID
			,PB.pb_date
			,CW.item
			,PB.CW_ID
			,PB.batches
			,PB.batches * CW.fillings fillings
			,PB.batches * CW.fillings * CW.in_cassette plan
			,PB.fakt * CW.fillings * CW.in_cassette fakt
			,IFNULL(SUM(LB.underfilling), 0) underfilling
			,IF(PB.fakt = 0, 1, 0) editable
			,IF(PB.fakt = 0 AND PB.pb_date <= (CURRENT_DATE() + INTERVAL 1 DAY), 1, 0) printable
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
		$fakt += $subrow["fakt"];
		$underfilling += $subrow["underfilling"];
		$d_underfilling += $subrow["underfilling"];

		// Выводим общую ячейку с датой заливки
		if( $cnt ) {
			$cnt++;
			echo "<tr id='{$subrow["PB_ID"]}' style='border-top: 2px solid #333;'>";
			echo "<td rowspan='{$cnt}' style='background-color: rgba(0, 0, 0, 0.2);'><h1>{$row["pb_date_weekday"]}</h1>Неделя: <b>{$row["week"]}</b><br><span class='nowrap'>{$row["week_range"]}</span><br>".($row["diff"] < 0 ? "Отставание: <b style='color: red;'>{$row["diff"]}</b> мин" : ($row["diff"] > 0 ? "Опережение: <b style='color: green;'>{$row["diff"]}</b> мин" : ""))."</td>";
			$cnt = 0;
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
			<td class='bg-gray'><?=($subrow["fakt"] - $subrow["underfilling"])?></td>
			<td class='bg-gray'><?=$subrow["underfilling"]?></td>
			<td class='bg-gray'><?=($intdiv > 0 ? $intdiv : "")?><?=($mod == 1 ? "&frac14;" : ($mod == 2 ? "&frac12;" : ($mod == 3 ? "&frac34;" : "")))?></td>
			<td>
				<a href='#' class='add_pb clone' pb_date="<?=$_GET["pb_date"]?>" PB_ID='<?=$subrow["PB_ID"]?>' title='Клонировать план заливки'><i class='fa fa-clone fa-lg'></i></a>
				<?=($subrow["editable"] ? "<a href='#' class='add_pb' PB_ID='{$subrow["PB_ID"]}' title='Изменить данные плана заливки'><i class='fa fa-pencil-alt fa-lg'></i></a>" : "")?>
				<?=($subrow["printable"] ? "<a href='printforms/filling_blank.php?PB_ID={$subrow["PB_ID"]}' class='print' title='Бланк чеклиста оператора'><i class='fas fa-print fa-lg'></i></a>" : "")?>
			</td>
		</tr>
		<?

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
			<td><?=($row["fakt"] - $d_underfilling)?></td>
			<td><?=$d_underfilling?></td>
			<td><?=($intdiv > 0 ? $intdiv : "")?><?=($mod == 1 ? "&frac14;" : ($mod == 2 ? "&frac12;" : ($mod == 3 ? "&frac34;" : "")))?></td>
			<td></td>
		</tr>
<?
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
		<tr class="total">
			<td></td>
			<td>Итог:</td>
			<td><?=$batches?></td>
			<td><?=$fillings?></td>
			<td><?=$plan?></td>
			<td><?=($fakt - $underfilling)?></td>
			<td><?=$underfilling?></td>
			<td><?=($intdiv > 0 ? $intdiv : "")?><?=($mod == 1 ? "&frac14;" : ($mod == 2 ? "&frac12;" : ($mod == 3 ? "&frac34;" : "")))?></td>
			<td></td>
		</tr>
	</tbody>
</table>

<table>
	<thead>
		<tr>
			<th>Дата</th>
			<th>Противовес</th>
			<th>Заливок</th>
			<th>Деталей</th>
			<th>Недоливы</th>
		</tr>
	</thead>
	<tbody style="text-align: center;">

<?
$query = "
	SELECT COUNT(distinct(PB.CW_ID)) cnt
		,DATE_FORMAT(LB.batch_date, '%d.%m.%y') batch_date_format
		,LB.batch_date
		,COUNT(LF.LF_ID) fillings
		,SUM(CW.in_cassette) fakt
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
		SELECT LB.batch_date
			,CW.item
			,COUNT(LF.LF_ID) fillings
			,SUM(CW.in_cassette) fakt
			,ROUND(IFNULL(SUM(LB.underfilling / CW.fillings), 0)) underfilling
		FROM list__Batch LB
		JOIN list__Filling LF ON LF.LB_ID = LB.LB_ID
		LEFT JOIN plan__Batch PB ON PB.PB_ID = LB.PB_ID
		LEFT JOIN CounterWeight CW ON CW.CW_ID = PB.CW_ID
		WHERE 1
			AND LB.batch_date = '{$row["batch_date"]}'
			".($_GET["CW_ID"] ? "AND PB.CW_ID={$_GET["CW_ID"]}" : "")."
			".($_GET["CB_ID"] ? "AND PB.CW_ID IN (SELECT CW_ID FROM CounterWeight WHERE CB_ID = {$_GET["CB_ID"]})" : "")."
		GROUP BY PB.CW_ID
		ORDER BY PB.CW_ID
	";
	$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $subrow = mysqli_fetch_array($subres) ) {
		$fillings += $subrow["fillings"];
		$fakt += $subrow["fakt"];
		$underfilling += $subrow["underfilling"];
		$d_underfilling += $subrow["underfilling"];

		// Выводим общую ячейку с датой заливки
		if( $cnt ) {
			$cnt++;
			echo "<tr style='border-top: 2px solid #333;'>";
			echo "<td rowspan='{$cnt}' style='background-color: rgba(0, 0, 0, 0.2);'>{$row["batch_date_format"]}</td>";
			$cnt = 0;
		}
		else {
			echo "<tr id='{$subrow["PB_ID"]}'>";
		}
		?>
			<td><?=$subrow["item"]?></td>
			<td><?=$subrow["fillings"]?></td>
			<td><?=($subrow["fakt"] - $subrow["underfilling"])?></td>
			<td><?=$subrow["underfilling"]?></td>
		</tr>
		<?

	}
?>
		<tr class="summary">
			<td>Итог:</td>
			<td><?=$row["fillings"]?></td>
			<td><?=($row["fakt"] - $d_underfilling)?></td>
			<td><?=$d_underfilling?></td>
		</tr>
<?
}
?>
<!--
		<tr class="total">
			<td></td>
			<td>Итог:</td>
			<td><?=$fillings?></td>
			<td><?=($fakt - $underfilling)?></td>
			<td><?=$underfilling?></td>
		</tr>
-->
	</tbody>
</table>

<div>
	<h3>Журнал изменений</h3>
	<table>
		<thead>
			<tr>
				<th colspan="2">Время изменения</th>
				<th>День</th>
				<th>Противовес</th>
				<th>Замесов</th>
				<th>Автор</th>
				<th>Ссылка</th>
			</tr>
		</thead>
		<tbody>
			<?
			$query = "
				SELECT PBL.PB_ID
					,Friendly_date(PBL.date_time) friendly_date
					,DATE_FORMAT(PBL.date_time, '%H:%i') time
					,DATE_FORMAT(PB.pb_date, '%d.%m.%y') pb_date_format
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
					<td class="bg-gray"><?=$row["pb_date_format"]?></td>
					<td class="bg-gray"><?=$row["item"]?></td>
					<td class="bg-gray" style="text-align: center;"><?=$row["prev_batches"]?><?=$row["batches"]?></td>
					<td style="text-align: center;"><?=$row["usr_icon"]?></td>
					<td style="text-align: center;"><a href="#<?=$row["PB_ID"]?>"><i class="fas fa-link fa-lg"></i></a></td>
				</tr>
			<?
			}
			?>
		</tbody>
	</table>
</div>

<div id="add_btn" class="add_pb" pb_date="<?=$_GET["pb_date"]?>" title="Внести данные плана заливки"></div>

<script>
	$(function() {
		$(".print").printPage();
	});
</script>

<?
include "footer.php";
?>
