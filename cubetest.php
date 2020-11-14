<?
include "config.php";
$title = 'Протокол испытаний куба';
include "header.php";
include "./forms/cubetest_form.php";

// Если в фильтре не установлена неделя, показываем текущую
if( !$_GET["week"] ) {
	$query = "SELECT YEARWEEK(NOW(), 1) week";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$row = mysqli_fetch_array($res);
	$_GET["week"] = $row["week"];
}
?>

<style>
	#cube_test_plan {
		position: relative;
	}
	#cube_test_plan:hover > div {
		height: 300px;
		opacity: 1;
	}
	#cube_test_plan > div {
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
	#cube_test_plan > div table {
		width: 100%;
		table-layout: fixed;
	}
</style>

<div id='cube_test_plan'>
	<a href="#" class="button" style="width: 100%; z-index: -1;">Планируемые испытания</a>
	<div>
		<table>
			<thead>
				<tr>
					<th>Противовес</th>
					<th>Дата заливки</th>
					<th>Время заливки</th>
					<th>Масса куба смеси, кг</th>
					<th>Дата испытания</th>
					<th>Время испытания</th>
					<th>Выдержка в часах</th>
					<th></th>
				</tr>
			</thead>
			<tbody style="text-align: center;">
		<?
		$query = "
			SELECT LB.LB_ID
				,PB.CW_ID
				,CW.item
				,YEARWEEK(PB.pb_date, 1) pb_week
				,DATE_FORMAT(PB.pb_date, '%d.%m.%y') pb_date_format
				,DATE_FORMAT(LB.batch_time, '%H:%i') batch_time_format
				,LB.mix_density
				,DATE_FORMAT(PB.pb_date + INTERVAL 1 DAY, '%d.%m.%y') test_date_format
				,DATE_FORMAT(LB.batch_time, '%H:%i') test_time_format
				,PB.pb_date + INTERVAL 1 DAY test_date
				,24 delay
				,CAST(CONCAT(PB.pb_date + INTERVAL 1 DAY, ' ', LB.batch_time) as datetime) test_date_time
			FROM list__Batch LB
			JOIN plan__Batch PB ON PB.PB_ID = LB.PB_ID
			JOIN CounterWeight CW ON CW.CW_ID = PB.CW_ID
			LEFT JOIN list__CubeTest LCT ON LCT.LB_ID = LB.LB_ID AND LCT.delay = 24
			WHERE LB.test = 1
				AND LCT.LCT_ID IS NULL
			UNION ALL
			SELECT LB.LB_ID
				,PB.CW_ID
				,CW.item
				,YEARWEEK(PB.pb_date, 1) pb_week
				,DATE_FORMAT(PB.pb_date, '%d.%m.%y') pb_date_format
				,DATE_FORMAT(LB.batch_time, '%H:%i') batch_time_format
				,LB.mix_density
				,DATE_FORMAT(PB.pb_date + INTERVAL 3 DAY, '%d.%m.%y') test_date_format
				,DATE_FORMAT(LB.batch_time, '%H:%i') test_time_format
				,PB.pb_date + INTERVAL 3 DAY test_date
				,72 delay
				,CAST(CONCAT(PB.pb_date + INTERVAL 3 DAY, ' ', LB.batch_time) as datetime) test_date_time
			FROM list__Batch LB
			JOIN plan__Batch PB ON PB.PB_ID = LB.PB_ID
			JOIN CounterWeight CW ON CW.CW_ID = PB.CW_ID
			LEFT JOIN list__CubeTest LCT ON LCT.LB_ID = LB.LB_ID AND LCT.delay = 72
			WHERE LB.test = 1
				AND LCT.LCT_ID IS NULL
			ORDER BY test_date_time
		";
		$now = new DateTime("now");
		$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		while( $row = mysqli_fetch_array($res) ) {
			$test_date_time = new DateTime($row["test_date_time"]);
			$error = $test_date_time < $now ? "error" : "";
			?>
			<tr>
				<td class="bg-gray"><?=$row["item"]?></td>
				<td class="bg-gray"><a href="filling.php?week=<?=$row["pb_week"]?>#<?=$row["LB_ID"]?>" title="Заливка" target="_blank"><?=$row["pb_date_format"]?></a></td>
				<td class="bg-gray"><?=$row["batch_time_format"]?></td>
				<td class="bg-gray"><?=$row["mix_density"]/1000?></td>
				<td class="<?=$error?>"><?=$row["test_date_format"]?></td>
				<td class="<?=$error?>"><?=$row["test_time_format"]?></td>
				<td><?=$row["delay"]?></td>
				<td><a href="#" class="add_cubetest" LB_ID="<?=$row["LB_ID"]?>" delay="<?=$row["delay"]?>" test_date="<?=$row["test_date"]?>" title="Внести данные испытания куба"><i class="fa fa-plus-square fa-lg"></i></a></td>
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
		<a href="/cubetest.php" style="position: absolute; top: 10px; right: 10px;" class="button">Сброс</a>

		<div class="nowrap" style="margin-bottom: 10px;">
			<span>Неделя:</span>
			<select name="week" class="<?=$_GET["week"] ? "filtered" : ""?>" onchange="this.form.submit()">
				<?
				$query = "
					SELECT YEAR(NOW()) year
					UNION
					SELECT YEAR(test_date) year
					FROM list__CubeTest
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
						SELECT YEARWEEK(test_date, 1) week
							,WEEK(test_date, 1) week_format
							,DATE_FORMAT(adddate(test_date, INTERVAL 0-WEEKDAY(test_date) DAY), '%e %b') WeekStart
							,DATE_FORMAT(adddate(test_date, INTERVAL 6-WEEKDAY(test_date) DAY), '%e %b') WeekEnd
						FROM list__CubeTest
						WHERE YEAR(test_date) = {$row["year"]}
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

		<div class="nowrap" style="display: inline-block; margin-bottom: 10px; margin-right: 30px;">
			<span>Выдержка:</span>
			<select name="delay" class="<?=$_GET["delay"] ? "filtered" : ""?>" style="width: 100px;">
				<option value=""></option>
				<option value="24" <?=($_GET["delay"]==24 ? "selected" : "")?>>24 часа</option>
				<option value="72" <?=($_GET["delay"]==72 ? "selected" : "")?>>72 часа</option>
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
			<th>Противовес</th>
			<th>Дата заливки</th>
			<th>Время заливки</th>
			<th>Масса куба смеси, кг</th>
			<th>Дата испытания</th>
			<th>Время испытания</th>
			<th>Масса испытуемого куба, кг</th>
			<th>Давление, МПа</th>
			<th>Выдержка в часах</th>
			<th></th>
		</tr>
	</thead>
	<tbody style="text-align: center;">

<?
$query = "
	SELECT LCT.LCT_ID
		,LCT.LB_ID
		,DATE_FORMAT(LCT.test_date, '%d.%m.%y') test_date
		,DATE_FORMAT(LCT.test_time, '%H:%i') test_time
		,CW.item
		,PB.CW_ID
		,YEARWEEK(PB.pb_date, 1) pb_week
		,DATE_FORMAT(PB.pb_date, '%d.%m.%y') pb_date_format
		,DATE_FORMAT(LB.batch_time, '%H:%i') batch_time_format
		,TIMESTAMPDIFF(HOUR, CAST(CONCAT(PB.pb_date, ' ', LB.batch_time) as datetime), CAST(CONCAT(LCT.test_date, ' ', LCT.test_time) as datetime)) delay_fact
		,LCT.delay
		,LB.mix_density
		,LCT.cube_weight
		,LCT.pressure
		,IF(LCT.delay = 24, IF(LCT.pressure < 20, 1, 0), IF(LCT.pressure < 30, 1, 0)) press_error
		,IF(LCT.USR_ID, USR_Icon(LCT.USR_ID), '') USR_Icon
		,DATE_FORMAT(LCT.last_edit, '%d.%m.%y в %H:%i:%s') last_edit
	FROM list__CubeTest LCT
	JOIN list__Batch LB ON LB.LB_ID = LCT.LB_ID
	JOIN plan__Batch PB ON PB.PB_ID = LB.PB_ID
	JOIN CounterWeight CW ON CW.CW_ID = PB.CW_ID
	WHERE 1
		".($_GET["week"] ? "AND YEARWEEK(LCT.test_date, 1) LIKE '{$_GET["week"]}'" : "")."
		".($_GET["CW_ID"] ? "AND PB.CW_ID={$_GET["CW_ID"]}" : "")."
		".($_GET["CB_ID"] ? "AND PB.CW_ID IN (SELECT CW_ID FROM CounterWeight WHERE CB_ID = {$_GET["CB_ID"]})" : "")."
		".($_GET["delay"] ? "AND LCT.delay={$_GET["delay"]}" : "")."
	ORDER BY LCT.test_date DESC, LCT.test_time DESC
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	?>
	<tr id="<?=$row["LCT_ID"]?>">
		<td class="bg-gray"><?=$row["item"]?></td>
		<td class="bg-gray"><a href="filling.php?week=<?=$row["pb_week"]?>#<?=$row["LB_ID"]?>" title="Заливка" target="_blank"><?=$row["pb_date_format"]?></a></td>
		<td class="bg-gray"><?=$row["batch_time_format"]?></td>
		<td class="bg-gray"><?=$row["mix_density"]/1000?></td>
		<td><?=$row["test_date"]?></td>
		<td><?=$row["test_time"]?></td>
		<td><?=$row["cube_weight"]/1000?></td>
		<td class="<?=($row["press_error"] ? "error" : "")?>"><?=$row["pressure"]?></td>
		<td class="<?=($row["delay_fact"] != $row["delay"] ? "error" : "")?>"><?=$row["delay_fact"]?></td>
		<td><a href="#" class="add_cubetest" LCT_ID="<?=$row["LCT_ID"]?>" title="Изменить данные испытания куба"><i class="fa fa-pencil-alt fa-lg"></i></a><?=$row["USR_Icon"]?><?=($row["last_edit"] ? "<i class='fas fa-clock' title='Сохранено ".$row["last_edit"]."'.></i>" : "")?></td>
	</tr>
	<?
}
?>

	</tbody>
</table>

<?
include "footer.php";
?>
