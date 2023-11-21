<?
include "config.php";
$title = 'График заливки';
include "header.php";

// Проверка прав на доступ к экрану
if( !in_array('plan_batch', $Rights) ) {
	header($_SERVER['SERVER_PROTOCOL'].' 403 Forbidden');
	die('Недостаточно прав для совершения операции');
}

$page = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);

//Запись подтверждения печати чек-листа
if( isset($_GET["print_confirm"]) ) {
	session_start();
	$PB_ID = $_GET["print_confirm"];
	$year = $_GET["year"];

	$query = "
		UPDATE plan__Batch
		SET print_time = NOW()
			,print_author = {$_SESSION['id']}
		WHERE PB_ID = {$PB_ID}
	";
	mysqli_query( $mysqli, $query );

	// Узнаем участок и противовес
	$query = "
		SELECT F_ID, CW_ID
		FROM plan__Batch
		WHERE PB_ID = {$PB_ID}
	";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$row = mysqli_fetch_array($res);
	$F_ID = $row["F_ID"];
	$CW_ID = $row["CW_ID"];

	// Очищаем список связанных кассет
	$query = "
		DELETE FROM plan__BatchCassette
		WHERE PB_ID = {$PB_ID}
	";
	echo $query;
	mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

	// Сохраняем список связанных кассет
	$query = "
		INSERT INTO plan__BatchCassette(PB_ID, cassette)
		SELECT {$PB_ID}, cassette
		FROM Cassettes
		WHERE F_ID = {$F_ID}
			AND CW_ID = {$CW_ID}
	";
	mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

	// Перенаправление в план
	exit ('<meta http-equiv="refresh" content="0; url='.$page.'?F_ID='.$F_ID.'&year='.$year.'#'.$PB_ID.'">');
}

// Если в фильтре не установлен год, показываем текущий год
if( !$_GET["year"] ) {
	$query = "SELECT YEAR(CURDATE()) year";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$row = mysqli_fetch_array($res);
	$_GET["year"] = $row["year"];
}

// Если не выбран участок, берем из сессии
if( !$_GET["F_ID"] ) {
	$_GET["F_ID"] = $_SESSION['F_ID'];
}

include "./forms/plan_batch_form.php";

// Находим очередной цикл в этом году, чтобы создать новый план
$query = "
	SELECT IFNULL(MAX(cycle), 0) + 1 next_cycle
	FROM plan__Batch
	WHERE year = {$_GET["year"]}
		AND F_ID = {$_GET["F_ID"]}
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
$row = mysqli_fetch_array($res);
$next_cycle = $row["next_cycle"];
?>

<!--Фильтр-->
<div id="filter">
	<h3>Фильтр</h3>
	<form method="get" style="position: relative;">
		<a href="/plan_batch.php" style="position: absolute; top: 10px; right: 10px;" class="button">Сброс</a>

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

		<div class="nowrap" style="display: inline-block; margin-bottom: 10px; margin-right: 30px;">
			<span>Год:</span>
			<select name="year" class="<?=$_GET["year"] ? "filtered" : ""?>" onchange="this.form.submit()">
				<?
				$query = "
					SELECT YEAR(CURDATE()) year
					UNION
					SELECT year
					FROM plan__Batch
					GROUP BY year
					ORDER BY year DESC
				";
				$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
				while( $row = mysqli_fetch_array($res) ) {
					$selected = ($row["year"] == $_GET["year"]) ? "selected" : "";
					echo "<option value='{$row["year"]}' {$selected}>{$row["year"]}</option>";
				}
				?>
			</select>
			<i class="fas fa-question-circle" title="По умолчанию устанавливается текущий год."></i>
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
			<th rowspan="2">Цикл</th>
			<th rowspan="2">Противовес</th>
			<th rowspan="2" colspan="2">Замесов</th>
			<th rowspan="2">Кассет</th>
			<th colspan="2">Деталей</th>
			<th rowspan="2">Недоливы</th>
<!--			<th rowspan="2">Расчетное время, ч</th>-->
			<th rowspan="2">Бланк чек-листа</th>
			<th rowspan="2">Распечатан</th>
			<th rowspan="2"></th>
		</tr>
		<tr>
			<th>План</th>
			<th>Факт</th>
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
		,PB.cycle
		,SUM(PB.batches) batches
		,SUM(ROUND(PB.batches * IFNULL(PB.fillings, MF.fillings) / IFNULL(PB.per_batch, MF.per_batch))) fillings
		,SUM(ROUND(PB.batches * IFNULL(PB.fillings, MF.fillings) / IFNULL(PB.per_batch, MF.per_batch)) * IFNULL(PB.in_cassette, MF.in_cassette)) plan
		,SUM(ROUND(PB.fact_batches * PB.fillings / PB.per_batch) * PB.in_cassette) details
		,IF(SUM(PB.batches) = SUM(PB.fact_batches), (SELECT TIMESTAMPDIFF(MINUTE, MIN(TIMESTAMP(batch_date, batch_time)), MAX(TIMESTAMP(batch_date, batch_time))) FROM list__Batch WHERE PB_ID IN (SELECT PB_ID FROM plan__Batch WHERE F_ID = PB.F_ID AND year = PB.year AND cycle = PB.cycle)), NULL) duration

	FROM plan__Batch PB
	JOIN CounterWeight CW ON CW.CW_ID = PB.CW_ID
	JOIN MixFormula MF ON MF.CW_ID = CW.CW_ID AND MF.F_ID = PB.F_ID
	WHERE PB.year = {$_GET["year"]} AND PB.F_ID = {$_GET["F_ID"]}
	GROUP BY PB.cycle
	ORDER BY PB.cycle DESC
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	echo "<tbody id='C{$_GET["year"]}{$row["cycle"]}' style='text-align: center; border-bottom: 2px solid #333;'>";
	$cnt = $row["cnt"];
	$d_batches = 0;
	$d_underfilling = 0;

	$query = "
		SELECT PB.PB_ID
			,CW.item
			,PB.CW_ID
			,PB.batches
			,USR_Icon(PB.author) icon_author
			,Friendly_date(PB.change_time) friendly_date
			,DATE_FORMAT(PB.change_time, '%H:%i') friendly_time
			,ROUND(PB.batches * IFNULL(PB.fillings, MF.fillings) / IFNULL(PB.per_batch, MF.per_batch)) fillings
			,ROUND(PB.batches * IFNULL(PB.fillings, MF.fillings) / IFNULL(PB.per_batch, MF.per_batch)) * IFNULL(PB.in_cassette, MF.in_cassette) plan
			,ROUND(PB.fact_batches * PB.fillings / PB.per_batch) * PB.in_cassette details
			,SUM(LF.underfilling) underfilling
			,IF(PB.batches > 0 AND PB.fact_batches = 0, 1, 0) printable
			,(SELECT PB_ID FROM plan__Batch WHERE F_ID = {$_GET["F_ID"]} AND CW_ID = PB.CW_ID AND fact_batches = 0 AND batches > 0 ORDER BY year, cycle LIMIT 1) current_PB_ID
			,Friendly_date(PB.print_time) friendly_print_date
			,DATE_FORMAT(PB.print_time, '%H:%i') friendly_print_time
			,USR_Icon(PB.print_author) icon_print_author
		FROM plan__Batch PB
		JOIN CounterWeight CW ON CW.CW_ID = PB.CW_ID
		JOIN MixFormula MF ON MF.CW_ID = CW.CW_ID AND MF.F_ID = PB.F_ID
		LEFT JOIN list__Batch LB ON LB.PB_ID = PB.PB_ID
		LEFT JOIN list__Filling LF ON LF.LB_ID = LB.LB_ID
		WHERE PB.year = {$_GET["year"]}
			AND PB.cycle = '{$row["cycle"]}'
			AND PB.F_ID = {$_GET["F_ID"]}
		GROUP BY PB.PB_ID
		#ORDER BY PB.CW_ID
		ORDER BY IFNULL(PB.print_time, NOW()) DESC
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
			$duration = ($row["duration"] < 15 ? 15 : $row["duration"]);
			$duration_div = intdiv(round($duration / 15), 4);
			$duration_mod = round($duration / 15) % 4;

			echo "<tr id='{$subrow["PB_ID"]}' item='{$subrow["item"]}' style='border-top: 2px solid #333;'>";
			echo "<td rowspan='{$cnt}' style='background-color: rgba(0, 0, 0, 0.2);'><h2 class='nowrap'>{$_GET["year"]}-{$row["cycle"]}</h2><span class='nowrap'>{$row["week_range"]}</span><br>".($row["duration"] ? "Длительность: <b>".($duration_div > 0 ? $duration_div : "").($duration_mod == 1 ? "&frac14;" : ($duration_mod == 2 ? "&frac12;" : ($duration_mod == 3 ? "&frac34;" : "")))."</b>ч" : "")."</td>";
		}
		else {
			echo "<tr id='{$subrow["PB_ID"]}' item='{$subrow["item"]}'>";
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
			<td colspan="2"><div style="transform: scale(.8);">
				<?
				// Журнал изменений кол-ва замесов
				$query = "
					SELECT batches
						,USR_Icon(author) icon_author
						,Friendly_date(date_time) friendly_date
						,DATE_FORMAT(date_time, '%H:%i') friendly_time
					FROM plan__BatchLog
					WHERE PB_ID = {$subrow["PB_ID"]}
					ORDER BY date_time
				";
				$subsubres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
				while( $subsubrow = mysqli_fetch_array($subsubres) ) {
					echo "<n style='text-decoration: line-through;' class='nowrap'>{$subsubrow["batches"]} {$subsubrow["icon_author"]} {$subsubrow["friendly_date"]} {$subsubrow["friendly_time"]}</n><br>";
				}
				echo "</div><n class='nowrap'><b>{$subrow["batches"]}</b> {$subrow["icon_author"]} {$subrow["friendly_date"]} {$subrow["friendly_time"]}</n>";
				?>
			</td>
			<td><?=$subrow["fillings"]?></td>
			<td><?=$subrow["plan"]?></td>
			<td class='bg-gray'><?=($subrow["details"] - $subrow["underfilling"])?></td>
			<td class='bg-gray'><?=$subrow["underfilling"]?></td>
<!--			<td class='bg-gray'><?=($intdiv > 0 ? $intdiv : "")?><?=($mod == 1 ? "&frac14;" : ($mod == 2 ? "&frac12;" : ($mod == 3 ? "&frac34;" : "")))?></td>-->
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
			<td class="nowrap" style="transform: scale(.8); overflow: unset;"><?=($subrow["friendly_print_date"] ? $subrow["icon_print_author"]." ".$subrow["friendly_print_date"]." ".$subrow["friendly_print_time"] : "")?></td>
			<?
			// Выводим общую ячейку с кнопками действий
			if( $last_cycle != $row["cycle"] ) {
				echo "<td rowspan='{$cnt}'><a href='#' class='add_pb' cycle='{$row["cycle"]}' year='{$_GET["year"]}' f_id='{$_GET["F_ID"]}' title='Изменить данные плана заливки'><i class='fa fa-pencil-alt fa-lg'></i></a></td>";
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
			<td colspan="2"><?=$row["batches"]?></td>
			<td><?=$row["fillings"]?></td>
			<td><?=$row["plan"]?></td>
			<td><?=($row["details"] - $d_underfilling)?></td>
			<td><?=$d_underfilling?></td>
<!--			<td><?=($intdiv > 0 ? $intdiv : "")?><?=($mod == 1 ? "&frac14;" : ($mod == 2 ? "&frac12;" : ($mod == 3 ? "&frac34;" : "")))?></td>-->
			<td></td>
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
			<td colspan="2"><?=$batches?></td>
			<td><?=$fillings?></td>
			<td><?=$plan?></td>
			<td><?=($details - $underfilling)?></td>
			<td><?=$underfilling?></td>
<!--			<td><?=($intdiv > 0 ? $intdiv : "")?><?=($mod == 1 ? "&frac14;" : ($mod == 2 ? "&frac12;" : ($mod == 3 ? "&frac34;" : "")))?></td>-->
			<td></td>
			<td></td>
			<td></td>
		</tr>
	</tbody>
</table>

<div id="add_btn" class="add_pb" cycle="<?=$next_cycle?>" year="<?=$_GET["year"]?>" f_id="<?=$_GET["F_ID"]?>" title="Внести данные очередного плана заливки"></div>

<script>
	$(function() {
		$(".print")
			.click(function(){
				var id = $(this).parents("tr").attr("id"),
					item = $(this).parents("tr").attr("item");
				confirm(
					"<h1>Бланк чек-листа оператора был распечатан?</h1><span style='font-size: 1.2em;'>Этим Вы подтверждаете, что заливка кода <b>" + item + "</b> начнется в ближайшее время.</span>",
					"<?=$page?>?year=<?=$_GET["year"]?>&print_confirm=" + id
				);
			})
			.printPage();
	});
</script>

<?
include "footer.php";
?>
