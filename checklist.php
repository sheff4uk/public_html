<?
include "config.php";
$title = 'Заливка';
include "header.php";
include "./forms/checklist_form.php";
//die("<h1>Ведутся работы</h1>");
// Если в фильтре не установлен период, показываем последние 7 дней
if( !$_GET["date_from"] ) {
	$date = new DateTime('-6 days');
	$_GET["date_from"] = date_format($date, 'Y-m-d');
}
if( !$_GET["date_to"] ) {
	$date = new DateTime('-0 days');
	$_GET["date_to"] = date_format($date, 'Y-m-d');
}
?>

<style>
	#batch_plan {
		position: relative;
	}
	#batch_plan:hover > div {
		height: 300px;
		opacity: 1;
	}
	#batch_plan > div {
		background: #fff;
		height: 0px;
		border: 1px solid #bbb;
		padding: 10px;
		border-radius: 5px;
		margin-top: 10px;
		z-index: 2;
		position: absolute;
		top: -10px;
		left: 0px;
		width: 100%;
		overflow: auto;
		opacity: 0;
		transition: .3s;
		-webkit-transition: .3s;
		box-shadow: 5px 5px 8px #666;
	}
	#batch_plan > div table {
		width: 100%;
		table-layout: fixed;
	}
</style>

<div id='batch_plan'>
	<a href="#" class="button" style="width: 100%; z-index: -1;">Планируемые заливки</a>
	<div>
		<table>
			<thead>
				<tr>
					<th>Дата</th>
					<th>Противовес</th>
					<th>Замесов</th>
					<th>Заливок</th>
					<th>План</th>
					<th></th>
				</tr>
			</thead>
			<tbody style="text-align: center;">
		<?
		$query = "
			SELECT PB.PB_ID
				,DATE_FORMAT(PB.pb_date, '%d.%m.%y') pb_date_format
				,CW.item
				,PB.batches
				,PB.batches * CW.fillings fillings
				,PB.batches * CW.fillings * CW.in_cassette plan
			FROM plan__Batch PB
			JOIN CounterWeight CW ON CW.CW_ID = PB.CW_ID
			WHERE PB.fakt = 0
				AND PB.pb_date <= NOW()
			ORDER BY PB.pb_date, PB.CW_ID
		";
		$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		while( $row = mysqli_fetch_array($res) ) {
			?>
			<tr>
				<td><?=$row["pb_date_format"]?></td>
				<td><?=$row["item"]?></td>
				<td><?=$row["batches"]?></td>
				<td><?=$row["fillings"]?></td>
				<td><?=$row["plan"]?></td>
				<td><a href="#" class="add_checklist" PB_ID="<?=$row["PB_ID"]?>" title="Внести данные чеклиста оператора"><i class="fa fa-plus-square fa-lg"></i></a></td>
			</tr>
			<?

		}
		?>
			</tbody>
		</table>
	</div>
</div>

<!--Фильтр-->
<div id="filter">
	<h3>Фильтр</h3>
	<form method="get" style="position: relative;">
		<a href="/checklist.php" style="position: absolute; top: 10px; right: 10px;" class="button">Сброс</a>

		<div class="nowrap" style="margin-bottom: 10px;">
			<span style="display: inline-block; width: 200px;">Дата заливки между:</span>
			<input name="date_from" type="date" value="<?=$_GET["date_from"]?>" class="<?=$_GET["date_from"] ? "filtered" : ""?>">
			<input name="date_to" type="date" value="<?=$_GET["date_to"]?>" class="<?=$_GET["date_to"] ? "filtered" : ""?>">
			<i class="fas fa-question-circle" title="По умолчанию устанавливаются последние 7 дней."></i>
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

//		$('#filter input[name="date_from"]').change(function() {
//			var val = $(this).val();
//			$('#filter input[name="date_to"]').val(val);
//		});
	});
</script>

<table class="main_table">
	<thead>
		<tr>
			<th>Дата<br>Противовес</th>
			<th>Время</th>
			<th>Рецепт</th>
			<th>Куб раствора, кг</th>
			<th>Окалина,<br>кг ±5</th>
			<th>КМП,<br>кг ±5</th>
			<th>Отсев,<br>кг ±5</th>
			<th>Цемент,<br>кг ±2</th>
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
		,DATE_FORMAT(PB.pb_date, '%d.%m.%y') pb_date_format
		,CW.item
		,PB.CW_ID
		,PB.batches
	FROM plan__Batch PB
	JOIN CounterWeight CW ON CW.CW_ID = PB.CW_ID
	WHERE 1
		".($_GET["date_from"] ? "AND PB.pb_date >= '{$_GET["date_from"]}'" : "")."
		".($_GET["date_to"] ? "AND PB.pb_date <= '{$_GET["date_to"]}'" : "")."
		".($_GET["CW_ID"] ? "AND PB.CW_ID={$_GET["CW_ID"]}" : "")."
		".($_GET["CB_ID"] ? "AND PB.CW_ID IN (SELECT CW_ID FROM CounterWeight WHERE CB_ID = {$_GET["CB_ID"]})" : "")."
	ORDER BY PB.pb_date DESC, PB.CW_ID
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	$cnt = $row["batches"];
	echo "<tbody id='PB{$row["PB_ID"]}' style='text-align: center; border-bottom: 2px solid #333;'>";

	$query = "
		SELECT LB.LB_ID
			,OP.name
			,DATE_FORMAT(LB.batch_time, '%H:%i') batch_time_format
			,LB.io_density
			,LB.sn_density
			,LB.cs_density
			,LB.mix_density
			,LB.iron_oxide
			,LB.sand
			,LB.crushed_stone
			,LB.cement
			,LB.water
			,LB.underfilling
			,LB.test
			,mix_letter(LB.LB_ID) letter
			,mix_id(LB.LB_ID) MF_ID
			,mix_diff({$row["CW_ID"]}, LB.mix_density) mix_diff
			,mix_io_diff(mix_id(LB.LB_ID), LB.iron_oxide) io_diff
			,mix_sn_diff(mix_id(LB.LB_ID), LB.sand) sn_diff
			,mix_cs_diff(mix_id(LB.LB_ID), LB.crushed_stone) cs_diff
			,mix_cm_diff(mix_id(LB.LB_ID), LB.cement) cm_diff
			,mix_wt_diff(mix_id(LB.LB_ID), LB.water) wt_diff
		FROM list__Batch LB
		JOIN Operator OP ON OP.OP_ID = LB.OP_ID
		WHERE LB.PB_ID = {$row["PB_ID"]}
		ORDER BY LB.batch_time ASC
	";
	$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $subrow = mysqli_fetch_array($subres) ) {
		// Получаем список кассет
		$query = "
			SELECT LF.cassette
				,LO.o_date
				,LO.LO_ID
			FROM list__Filling LF
			LEFT JOIN list__Opening LO ON LO.LF_ID = LF.LF_ID
			WHERE LF.LB_ID = {$subrow["LB_ID"]}
			ORDER BY LF.LF_ID
		";
		$subsubres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		$cassette = "";
		while( $subsubrow = mysqli_fetch_array($subsubres) ) {
			if( $subsubrow["LO_ID"] ) {
				$cassette .= "<a href='opening.php?o_date_from={$subsubrow["o_date"]}&o_date_to={$subsubrow["o_date"]}#{$subsubrow["LO_ID"]}' title='Расформовка' target='_blank'><b class='cassette'>{$subsubrow["cassette"]}</b></a>";
			}
			else {
				$cassette .= "<b class='cassette'>{$subsubrow["cassette"]}</b>";
			}
		}

		echo "<tr id='{$subrow["LB_ID"]}'>";

		// Выводим общую ячейку с датой кодом
		if( $cnt ) {
			echo "<td id='PB{$row["PB_ID"]}' rowspan='{$cnt}' class='bg-gray'>{$row["pb_date_format"]}<br><b>{$row["item"]}</b><br>Замесов: <b>{$cnt}</b></td>";
		}
		?>
				<td><?=$subrow["batch_time_format"]?><?=$subrow["test"] ? "&nbsp;<i class='fas fa-cube'></i>" : ""?></td>
		<td><span class="nowrap"><?=$subrow["letter"] ? "<a href='mix_formula.php#{$subrow["MF_ID"]}' target='_blank'><b>{$subrow["letter"]}</b></a> " : "<i class='fas fa-exclamation-triangle' style='color: red;' title='Подходящий рецепт не обнаружен'></i> "?><?=($subrow["io_density"] ? "<i title='Плотность окалины' style='text-decoration: underline;'>".($subrow["io_density"]/1000)."</i> " : "")?><?=($subrow["sn_density"] ? "<i title='Плотность КМП' style='text-decoration: underline;'>".($subrow["sn_density"]/1000)."</i> " : "")?><?=($subrow["cs_density"] ? "<i title='Плотность отсева' style='text-decoration: underline;'>".($subrow["cs_density"]/1000)."</i>" : "")?></span></td>
				<td><?=$subrow["mix_density"]/1000?><?=($subrow["mix_diff"] ? "<font style='font-size: .8em;' color='red'>".($subrow["mix_diff"] > 0 ? " +" : " ").($subrow["mix_diff"]/1000)."</font>" : "")?></td>
				<td class="bg-gray" <?=($subrow["letter"] ? "" : "style='color: red;'")?>><?=$subrow["iron_oxide"]?><?=($subrow["io_diff"] ? "<font style='font-size: .8em;' color='red'>".($subrow["io_diff"] > 0 ? " +" : " ").($subrow["io_diff"])."</font>" : "")?></td>
				<td class="bg-gray" <?=($subrow["letter"] ? "" : "style='color: red;'")?>><?=$subrow["sand"]?><?=($subrow["sn_diff"] ? "<font style='font-size: .8em;' color='red'>".($subrow["sn_diff"] > 0 ? " +" : " ").($subrow["sn_diff"])."</font>" : "")?></td>
				<td class="bg-gray" <?=($subrow["letter"] ? "" : "style='color: red;'")?>><?=$subrow["crushed_stone"]?><?=($subrow["cs_diff"] ? "<font style='font-size: .8em;' color='red'>".($subrow["cs_diff"] > 0 ? " +" : " ").($subrow["cs_diff"])."</font>" : "")?></td>
				<td class="bg-gray" <?=($subrow["letter"] ? "" : "style='color: red;'")?>><?=$subrow["cement"]?><?=($subrow["cm_diff"] ? "<font style='font-size: .8em;' color='red'>".($subrow["cm_diff"] > 0 ? " +" : " ").($subrow["cm_diff"])."</font>" : "")?></td>
				<td class="bg-gray" <?=($subrow["letter"] ? "" : "style='color: red;'")?>><?=$subrow["water"]?><?=($subrow["wt_diff"] ? "<font style='font-size: .8em;' color='red'> ".($subrow["wt_diff"])."</font>" : "")?></td>
				<td colspan="2" class="nowrap"><?=$cassette?></td>
				<td><?=$subrow["underfilling"]?></td>
				<td><?=$subrow["name"]?></td>
				<?
				// Выводим общую ячейку с кнопкой редактирования
				if( $cnt ) {
					echo "<td rowspan='{$cnt}'><a href='#' class='add_checklist' PB_ID='{$row["PB_ID"]}' title='Изменить чеклист оператора'><i class='fa fa-pencil-alt fa-lg'></i></a></td>";
					$cnt = 0;
				}
				?>
			</tr>
		<?
	}
	echo "</tbody>";
}
?>
</table>

<?
include "footer.php";
?>
