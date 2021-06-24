<?
include "config.php";
$title = 'План заливки';
include "header.php";
include "./forms/plan_batch_form.php";

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

	// Перенаправление в план
	exit ('<meta http-equiv="refresh" content="0; url='.$page.'?year='.$year.'#'.$PB_ID.'">');
}

// Если в фильтре не установлен год, показываем текущий год
if( !$_GET["year"] ) {
	$query = "SELECT YEAR(CURDATE()) year";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$row = mysqli_fetch_array($res);
	$_GET["year"] = $row["year"];
}

// Находим очередной цикл в этом году, чтобы создать новый план
$query = "
	SELECT IFNULL(MAX(cycle), 0) + 1 next_cycle
	FROM plan__Batch
	WHERE year = {$_GET["year"]}
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

<!--Таблица с планом-->
<table class="main_table">
	<thead>
		<tr>
			<th>Цикл</th>
			<th>Противовес</th>
			<th>Замесов</th>
			<th>Заливок</th>
			<th>План</th>
			<th>Факт</th>
			<th>Недоливы</th>
			<th>Расчетное время, ч</th>
			<th>Бланк чек-листа</th>
			<th>Распечатан</th>
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
		,PB.cycle
		,SUM(PB.batches) batches
		,SUM(PB.batches * IFNULL(PB.fillings_per_batch, CW.fillings)) fillings
		,SUM(PB.batches * IFNULL(PB.fillings_per_batch, CW.fillings) * IFNULL(PB.in_cassette, CW.in_cassette)) plan
		,SUM(PB.fact_batches * PB.fillings_per_batch * PB.in_cassette) details
		,IF(SUM(PB.batches) = SUM(PB.fact_batches), (SELECT TIMESTAMPDIFF(MINUTE, MIN(TIMESTAMP(batch_date, batch_time)), MAX(TIMESTAMP(batch_date, batch_time))) FROM list__Batch WHERE PB_ID IN (SELECT PB_ID FROM plan__Batch WHERE year = PB.year AND cycle = PB.cycle)), NULL) duration

	FROM plan__Batch PB
	JOIN CounterWeight CW ON CW.CW_ID = PB.CW_ID
	WHERE PB.year = {$_GET["year"]}
		".($_GET["CW_ID"] ? "AND PB.CW_ID={$_GET["CW_ID"]}" : "")."
		".($_GET["CB_ID"] ? "AND PB.CW_ID IN (SELECT CW_ID FROM CounterWeight WHERE CB_ID = {$_GET["CB_ID"]})" : "")."
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
			,PB.batches * IFNULL(PB.fillings_per_batch, CW.fillings) fillings
			,PB.batches * IFNULL(PB.fillings_per_batch, CW.fillings) * IFNULL(PB.in_cassette, CW.in_cassette) plan
			,PB.fact_batches * PB.fillings_per_batch * PB.in_cassette details
			,SUM(LF.underfilling) underfilling
			,IF(PB.batches > 0 AND PB.fact_batches = 0, 1, 0) printable
			,(SELECT PB_ID FROM plan__Batch WHERE CW_ID = PB.CW_ID AND fact_batches = 0 AND batches > 0 ORDER BY year, cycle LIMIT 1) current_PB_ID
			,Friendly_date(PB.print_time) friendly_print_date
			,DATE_FORMAT(PB.print_time, '%H:%i') friendly_print_time
			,USR_Icon(PB.print_author) icon_print_author
		FROM plan__Batch PB
		JOIN CounterWeight CW ON CW.CW_ID = PB.CW_ID
		LEFT JOIN list__Batch LB ON LB.PB_ID = PB.PB_ID
		LEFT JOIN list__Filling LF ON LF.LB_ID = LB.LB_ID
		WHERE PB.year = {$_GET["year"]}
			AND PB.cycle = '{$row["cycle"]}'
			".($_GET["CW_ID"] ? "AND PB.CW_ID={$_GET["CW_ID"]}" : "")."
			".($_GET["CB_ID"] ? "AND PB.CW_ID IN (SELECT CW_ID FROM CounterWeight WHERE CB_ID = {$_GET["CB_ID"]})" : "")."
		GROUP BY PB.PB_ID
		ORDER BY PB.CW_ID
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
			echo "<td rowspan='{$cnt}' style='background-color: rgba(0, 0, 0, 0.2);'><h3>{$row["cycle"]}</h3><span class='nowrap'>{$row["week_range"]}</span><br>".($row["duration"] ? "Продолжительность: <b>{$row["duration"]}</b> мин" : "")."</td>";
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
			<td><?=($subrow["friendly_print_date"] ? $subrow["friendly_print_date"]." ".$subrow["friendly_print_time"]." ".$subrow["icon_print_author"] : "")?></td>
			<?
			// Выводим общую ячейку с кнопками действий
			if( $last_cycle != $row["cycle"] ) {
				echo "<td rowspan='{$cnt}'><a href='#' class='add_pb' cycle='{$row["cycle"]}' year='{$_GET["year"]}' title='Изменить данные плана заливки'><i class='fa fa-pencil-alt fa-lg'></i></a></td>";
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
					,PB.cycle
					,CW.item
					,CONCAT('<n style=\"text-decoration: line-through;\">', SPBL.batches, '</n>&nbsp;<i class=\"fas fa-arrow-right\"></i>&nbsp;') prev_batches
					,PBL.batches
					,USR_Icon(PBL.author) usr_icon
				FROM plan__BatchLog PBL
				LEFT JOIN plan__BatchLog SPBL ON SPBL.PBL_ID = PBL.prev_ID
				JOIN plan__Batch PB ON PB.PB_ID = PBL.PB_ID
					AND PB.year = {$_GET["year"]}
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

<div id="add_btn" class="add_pb" cycle="<?=$next_cycle?>" year="<?=$_GET["year"]?>" title="Внести данные очередного плана заливки"></div>

<script>
	$(function() {
		$(".print")
			.click(function(){
				var id = $(this).parents("tr").attr("id");
				confirm(
					"<h1>Бланк чек-листа оператора был распечатан?</h1>",
					"<?=$page?>?year=<?=$_GET["year"]?>&print_confirm=" + id
				);
			})
			.printPage();
	});
</script>

<?
include "footer.php";
?>
