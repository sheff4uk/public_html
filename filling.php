<?
include "config.php";
$title = 'Заливка';
include "header.php";

// Проверка прав на доступ к экрану
if( !in_array('filling_opening', $Rights) ) {
	header($_SERVER['SERVER_PROTOCOL'].' 403 Forbidden');
	die('Недостаточно прав для совершения операции');
}

include "./forms/filling_form.php";
//die("<h1>Ведутся работы</h1>");

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
?>

<!--Фильтр-->
<div id="filter">
	<h3>Фильтр</h3>
	<form method="get" style="position: relative;">
		<a href="/filling.php" style="position: absolute; top: 10px; right: 10px;" class="button">Сброс</a>

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
					SELECT LEFT(YEARWEEK(batch_date, 1), 4) year
					FROM list__Batch
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
							SELECT LEFT(YEARWEEK(batch_date, 1), 4) year
								,YEARWEEK(batch_date, 1) week
								,RIGHT(YEARWEEK(batch_date, 1), 2) week_format
								,DATE_FORMAT(ADDDATE(batch_date, 0-WEEKDAY(batch_date)), '%e %b') WeekStart
								,DATE_FORMAT(ADDDATE(batch_date, 6-WEEKDAY(batch_date)), '%e %b') WeekEnd
							FROM list__Batch
							WHERE LEFT(YEARWEEK(batch_date, 1), 4) = {$row["year"]}
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

<!--
		<div class="nowrap" style="display: inline-block; margin-bottom: 10px; margin-right: 30px;">
			<span>№ кассеты:</span>
			<input type="number" min="1" max="<?=$cassettes?>" name="cassette" value="<?=$_GET["cassette"]?>" class="<?=$_GET["cassette"] ? "filtered" : ""?>" style="width: 80px;">
		</div>
-->

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

<style>
	.diff_alert {
		font-size: .8em;
		display: block;
		line-height: .4em;
		color: red;
		#display: none;
	}
</style>

<table class="main_table">
	<thead>
		<tr>
			<th>Цикл</th>
			<th>Дата время замеса</th>
			<th>Куб раствора, кг</th>
			<th>t, ℃ 22±8</th>
			<th class="s_fraction">Мелкая дробь,<br>кг</th>
			<th class="l_fraction">Крупная дробь,<br>кг</th>
			<th class="iron_oxide">Окалина,<br>кг</th>
			<th class="slag10">Шлак 0-10,<br>кг</th>
			<th class="slag20">Шлак 10-20,<br>кг</th>
			<th class="slag020">Шлак 0-20,<br>кг</th>
			<th class="slag30">Шлак 5-30,<br>кг</th>
			<th class="sand">КМП,<br>кг</th>
			<th class="crushed_stone">Отсев,<br>кг</th>
			<th class="crushed_stone515">Отсев 5-15,<br>кг</th>
			<th>Цемент,<br>кг</th>
			<th class="plasticizer">Пластификатор,<br>кг</th>
			<th>Вода, л</th>
			<th colspan="2">№ кассеты</th>
			<th>Недолив</th>
			<th>Оператор</th>
			<th></th>
		</tr>
	</thead>

<?
// Получаем список дат и противовесов и кол-во замесов на эти даты
$query = "
	SELECT PB.PB_ID
		,PB.year
		,PB.cycle
		,CW.item
		,PB.CW_ID
		,MF.MF_ID
		,PB.batches
		,PB.fact_batches
		,MIN(TIMESTAMP(LB.batch_date, LB.batch_time)) time
		,IF(COUNT(LCT24.LCT_ID)=COUNT(IF(LB.test = 1, LB.LB_ID, NULL)), CEIL(COUNT(LCT24.LCT_ID) * 2 / 3), 0) `24tests`
		,IF(COUNT(LCT72.LCT_ID)=COUNT(IF(LB.test = 1, LB.LB_ID, NULL)), CEIL(COUNT(LCT72.LCT_ID) * 2 / 3), 0) `72tests`
		,PB.sf_density
		,PB.lf_density
		,PB.io_density
		,PB.sl10_density
		,PB.sl20_density
		,PB.sl020_density
		,PB.sl30_density
		,PB.sn_density
		,PB.cs_density
		,PB.cs515_density
		,IF( IFNULL(PB.print_time, PB.change_time) < NOW() - INTERVAL 10 DAY, 0, 1 ) editable
	FROM plan__Batch PB
	JOIN CounterWeight CW ON CW.CW_ID = PB.CW_ID
	JOIN MixFormula MF ON MF.CW_ID = CW.CW_ID AND MF.F_ID = PB.F_ID
	JOIN list__Batch LB ON LB.PB_ID = PB.PB_ID
	LEFT JOIN list__CubeTest LCT24 ON LCT24.LB_ID = LB.LB_ID AND LCT24.delay = 24
	LEFT JOIN list__CubeTest LCT72 ON LCT72.LB_ID = LB.LB_ID AND LCT72.delay = 72
	WHERE PB.F_ID = {$_GET["F_ID"]}
		AND PB.PB_ID IN (SELECT PB_ID FROM list__Batch WHERE YEARWEEK(batch_date, 1) LIKE '{$_GET["week"]}' GROUP BY PB_ID)
		".($_GET["CW_ID"] ? "AND PB.CW_ID={$_GET["CW_ID"]}" : "")."
	GROUP BY PB.PB_ID
	ORDER BY PB.cycle, time
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	// Находим средний результат испытаний кубиков
	$query = "
		SELECT ROUND(AVG(SUB.pressure)) `pressure`
		FROM (
			SELECT LCT.pressure
			FROM list__CubeTest LCT
			JOIN list__Batch LB ON LB.LB_ID = LCT.LB_ID AND LB.PB_ID = {$row['PB_ID']}
			WHERE LCT.delay = 24
			ORDER BY LCT.pressure DESC
			LIMIT {$row['24tests']}
		) SUB
		UNION ALL
		SELECT ROUND(AVG(SUB.pressure)) `pressure`
		FROM (
			SELECT LCT.pressure
			FROM list__CubeTest LCT
			JOIN list__Batch LB ON LB.LB_ID = LCT.LB_ID AND LB.PB_ID = {$row['PB_ID']}
			WHERE LCT.delay = 72
			ORDER BY LCT.pressure DESC
			LIMIT {$row['72tests']}
		) SUB
	";
	$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$test24 = mysqli_result($subres,0,'pressure');
	$test72 = mysqli_result($subres,1,'pressure');

	$cnt = $row["fact_batches"];
	echo "<tbody id='PB{$row["PB_ID"]}' style='text-align: center; border-bottom: 2px solid #333; ".(($cycle and $cycle != $row["cycle"]) ? " border-top: 10px solid #333;" : "")."'>";
	$cycle = $row["cycle"];
	$sf_density = $row["sf_density"]/1000;
	$lf_density = $row["lf_density"]/1000;
	$io_density = $row["io_density"]/1000;
	$sl10_density = $row["sl10_density"]/1000;
	$sl20_density = $row["sl20_density"]/1000;
	$sl020_density = $row["sl020_density"]/1000;
	$sl30_density = $row["sl30_density"]/1000;
	$sn_density = $row["sn_density"]/1000;
	$cs_density = $row["cs_density"]/1000;
	$cs515_density = $row["cs515_density"]/1000;
	$j = 0;

	$query = "
		SELECT LB.LB_ID
			,USR_Icon(operator) name
			,DATE_FORMAT(LB.batch_date, '%d.%m') batch_date_format
			,DATE_FORMAT(LB.batch_time, '%H:%i') batch_time_format
			,LB.mix_density
			,LB.temp
			,IF(ABS(22 - LB.temp) <= 8, NULL, IF(LB.temp - 22 > 8, LB.temp - 30, LB.temp - 14)) temp_diff
			,PB.per_batch
			,LB.s_fraction
			,LB.l_fraction
			,LB.iron_oxide
			,LB.slag10
			,LB.slag20
			,LB.slag020
			,LB.slag30
			,LB.sand
			,LB.crushed_stone
			,LB.crushed_stone515
			,LB.cement
			,LB.plasticizer
			,LB.water
			,SUM(LF.underfilling) underfilling
			,LB.test
			,mix_diff({$row["MF_ID"]}, LB.mix_density) mix_diff
			,mix_sf_diff({$row["MF_ID"]}, LB.s_fraction) sf_diff
			,mix_lf_diff({$row["MF_ID"]}, LB.l_fraction) lf_diff
			,mix_io_diff({$row["MF_ID"]}, LB.iron_oxide) io_diff
			,mix_sl10_diff({$row["MF_ID"]}, LB.slag10) sl10_diff
			,mix_sl20_diff({$row["MF_ID"]}, LB.slag20) sl20_diff
			,mix_sl020_diff({$row["MF_ID"]}, LB.slag020) sl020_diff
			,mix_sl30_diff({$row["MF_ID"]}, LB.slag30) sl30_diff
			,mix_sn_diff({$row["MF_ID"]}, LB.sand) sn_diff
			,mix_cs_diff({$row["MF_ID"]}, LB.crushed_stone) cs_diff
			,mix_cs515_diff({$row["MF_ID"]}, LB.crushed_stone515) cs515_diff
			,mix_cm_diff({$row["MF_ID"]}, LB.cement) cm_diff
			,mix_pl_diff({$row["MF_ID"]}, LB.plasticizer) pl_diff
			,mix_wt_diff({$row["MF_ID"]}, LB.water) wt_diff
		FROM plan__Batch PB
		JOIN list__Batch LB ON LB.PB_ID = PB.PB_ID
		LEFT JOIN list__Filling LF ON LF.LB_ID = LB.LB_ID
		WHERE LB.PB_ID = {$row["PB_ID"]}
		GROUP BY LB.LB_ID
		ORDER BY LB.batch_date, LB.batch_time
	";
	$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $subrow = mysqli_fetch_array($subres) ) {
		$s_fraction += $subrow["s_fraction"];
		$l_fraction += $subrow["l_fraction"];
		$iron_oxide += $subrow["iron_oxide"];
		$slag10 += $subrow["slag10"];
		$slag20 += $subrow["slag20"];
		$slag020 += $subrow["slag020"];
		$slag30 += $subrow["slag30"];
		$sand += $subrow["sand"];
		$crushed_stone += $subrow["crushed_stone"];
		$crushed_stone515 += $subrow["crushed_stone515"];
		$plasticizer += $subrow["plasticizer"];

		// Получаем список кассет
		$query = "
			SELECT LF.cassette
				,YEARWEEK(LO.opening_time, 1) o_week
				,LO.LO_ID
			FROM list__Filling LF
			LEFT JOIN list__Opening LO ON LO.LF_ID = LF.LF_ID
			WHERE LF.LB_ID = {$subrow["LB_ID"]}
			ORDER BY LF.LF_ID
		";
		$subsubres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		$cassette = "<td colspan='2' rowspan='{$subrow["per_batch"]}' class='nowrap'>";
		while( $subsubrow = mysqli_fetch_array($subsubres) ) {
			if( $subsubrow["LO_ID"] ) {
				$cassette .= "<a href='opening.php?F_ID={$_GET["F_ID"]}&week={$subsubrow["o_week"]}#{$subsubrow["LO_ID"]}' title='Расформовка' target='_blank'><b class='cassette'>{$subsubrow["cassette"]}</b></a>";
			}
			else {
				$cassette .= "<b class='cassette'>{$subsubrow["cassette"]}</b>";
			}
		}
		$cassette .= "</td><td rowspan='{$subrow["per_batch"]}'>{$subrow["underfilling"]}</td>";

		echo "<tr id='{$subrow["LB_ID"]}'>";

		// Выводим общую ячейку с датой кодом
		if( $cnt ) {
			echo "
				<td rowspan='{$cnt}' class='bg-gray'>
					<h2 class='nowrap'>{$row["year"]}-{$row["cycle"]}</h2>
					<b>{$row["item"]}</b><br>Замесов: <b>{$cnt}</b><br>
					<i class='fas fa-cube'></i>24: <b>{$test24}</b>МПа<br>
					<i class='fas fa-cube'></i>72: <b>{$test72}</b>МПа<br>
					".($sf_density ? "<span class='nowrap'><b>{$sf_density}</b>кг мел. дробь</span>" : "")."
					".($lf_density ? "<span class='nowrap'><b>{$lf_density}</b>кг круп. дробь</span>" : "")."
					".($io_density ? "<span class='nowrap'><b>{$io_density}</b>кг окалина</span>" : "")."
					".($sl10_density ? "<span class='nowrap'><b>{$sl10_density}</b>кг шлак 0-10</span>" : "")."
					".($sl20_density ? "<span class='nowrap'><b>{$sl20_density}</b>кг шлак 10-20</span>" : "")."
					".($sl020_density ? "<span class='nowrap'><b>{$sl020_density}</b>кг шлак 0-20</span>" : "")."
					".($sl30_density ? "<span class='nowrap'><b>{$sl30_density}</b>кг шлак 5-30</span>" : "")."
					".($sn_density ? "<span class='nowrap'><b>{$sn_density}</b>кг КМП</span>" : "")."
					".($cs_density ? "<span class='nowrap'><b>{$cs_density}</b>кг отсев</span>" : "")."
					".($cs515_density ? "<span class='nowrap'><b>{$cs515_density}</b>кг отсев 5-15</span>" : "")."
				</td>
			";
		}
		?>
		<td><?=$subrow["batch_date_format"]?> <?=$subrow["batch_time_format"]?></td>
				<td><?=$subrow["mix_density"]/1000?> <?=$subrow["test"] ? "&nbsp;<i class='fas fa-cube'></i>" : ""?><?=($subrow["mix_diff"] ? "<font class='diff_alert'>".($subrow["mix_diff"] > 0 ? " +" : " ").($subrow["mix_diff"]/1000)."</font>" : "")?></td>
				<td><?=$subrow["temp"]?><?=($subrow["temp_diff"] ? "<font class='diff_alert'>".($subrow["temp_diff"] > 0 ? " +" : " ").($subrow["temp_diff"])."</font>" : "")?></td>
				<td class="s_fraction" style="background: #7952eb88;"><?=$subrow["s_fraction"]?><?=($subrow["sf_diff"] ? "<font class='diff_alert'>".($subrow["sf_diff"] > 0 ? " +" : " ").($subrow["sf_diff"])."</font>" : "")?></td>
				<td class="l_fraction" style="background: #51d5d788;"><?=$subrow["l_fraction"]?><?=($subrow["lf_diff"] ? "<font class='diff_alert'>".($subrow["lf_diff"] > 0 ? " +" : " ").($subrow["lf_diff"])."</font>" : "")?></td>
				<td class="iron_oxide" style="background: #a52a2a80;"><?=$subrow["iron_oxide"]?><?=($subrow["io_diff"] ? "<font class='diff_alert'>".($subrow["io_diff"] > 0 ? " +" : " ").($subrow["io_diff"])."</font>" : "")?></td>
				<td class="slag10" style="background: #33333380;"><?=$subrow["slag10"]?><?=($subrow["sl10_diff"] ? "<font class='diff_alert'>".($subrow["sl10_diff"] > 0 ? " +" : " ").($subrow["sl10_diff"])."</font>" : "")?></td>
				<td class="slag20" style="background: #33333380;"><?=$subrow["slag20"]?><?=($subrow["sl20_diff"] ? "<font class='diff_alert'>".($subrow["sl20_diff"] > 0 ? " +" : " ").($subrow["sl20_diff"])."</font>" : "")?></td>
				<td class="slag020" style="background: #33333380;"><?=$subrow["slag020"]?><?=($subrow["sl020_diff"] ? "<font class='diff_alert'>".($subrow["sl020_diff"] > 0 ? " +" : " ").($subrow["sl020_diff"])."</font>" : "")?></td>
				<td class="slag30" style="background: #33333380;"><?=$subrow["slag30"]?><?=($subrow["sl30_diff"] ? "<font class='diff_alert'>".($subrow["sl30_diff"] > 0 ? " +" : " ").($subrow["sl30_diff"])."</font>" : "")?></td>
				<td class="sand" style="background: #f4a46082;"><?=$subrow["sand"]?><?=($subrow["sn_diff"] ? "<font class='diff_alert'>".($subrow["sn_diff"] > 0 ? " +" : " ").($subrow["sn_diff"])."</font>" : "")?></td>
				<td class="crushed_stone" style="background: #8b45137a;"><?=$subrow["crushed_stone"]?><?=($subrow["cs_diff"] ? "<font class='diff_alert'>".($subrow["cs_diff"] > 0 ? " +" : " ").($subrow["cs_diff"])."</font>" : "")?></td>
				<td class="crushed_stone515" style="background: #8b45137a;"><?=$subrow["crushed_stone515"]?><?=($subrow["cs515_diff"] ? "<font class='diff_alert'>".($subrow["cs515_diff"] > 0 ? " +" : " ").($subrow["cs515_diff"])."</font>" : "")?></td>
				<td style="background: #7080906b;"><?=$subrow["cement"]?><?=($subrow["cm_diff"] ? "<font class='diff_alert'>".($subrow["cm_diff"] > 0 ? " +" : " ").($subrow["cm_diff"])."</font>" : "")?></td>
				<td class="plasticizer" style="background: #80800080;"><?=$subrow["plasticizer"]?><?=($subrow["pl_diff"] ? "<font class='diff_alert'>".($subrow["pl_diff"] > 0 ? " +" : " ").($subrow["pl_diff"])."</font>" : "")?></td>
				<td style="background: #1e90ff85;"><?=$subrow["water"]?><?=($subrow["wt_diff"] ? "<font class='diff_alert'>".($subrow["wt_diff"])."</font>" : "")?></td>
				<?=($j == 0 ? $cassette : "")?>
				<td><?=$subrow["name"]?></td>
				<?
				// Выводим общую ячейку с кнопкой редактирования
				if( $cnt ) {
					echo "<td rowspan='{$cnt}'>\n";
					if( $row["editable"] ) {
						echo "<a href='#' class='add_filling' PB_ID='{$row["PB_ID"]}' title='Изменить чеклист оператора'><i class='fa fa-pencil-alt fa-lg'></i></a>\n";
					}
					echo "</td>\n";
					$cnt = 0;
				}
				?>
			</tr>
		<?
		$j++;
		$j = ($j == $subrow["per_batch"] ? 0 : $j);
	}
	echo "</tbody>";
}
?>
</table>

<style>
	<?=($s_fraction ? "" : ".s_fraction{ display: none; }")?>
	<?=($l_fraction ? "" : ".l_fraction{ display: none; }")?>
	<?=($iron_oxide ? "" : ".iron_oxide{ display: none; }")?>
	<?=($slag10 ? "" : ".slag10{ display: none; }")?>
	<?=($slag20 ? "" : ".slag20{ display: none; }")?>
	<?=($slag020 ? "" : ".slag020{ display: none; }")?>
	<?=($slag30 ? "" : ".slag30{ display: none; }")?>
	<?=($sand ? "" : ".sand{ display: none; }")?>
	<?=($crushed_stone ? "" : ".crushed_stone{ display: none; }")?>
	<?=($crushed_stone515 ? "" : ".crushed_stone515{ display: none; }")?>
	<?=($plasticizer ? "" : ".plasticizer{ display: none; }")?>
</style>

<?
include "footer.php";
?>
