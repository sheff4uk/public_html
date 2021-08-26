<?
include "config.php";
$title = 'Изготовление';
include "header.php";

// Если в фильтре не установлена неделя, показываем текущую
if( !$_GET["week"] ) {
	$query = "SELECT YEARWEEK(CURDATE(), 1) week";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$row = mysqli_fetch_array($res);
	$_GET["week"] = $row["week"];
}

$CAS = isset($_GET["CAS"]) ? $_GET["CAS"] : array();
$CASs = implode(",", $CAS);
?>

<!--Фильтр-->
<div id="filter">
	<h3>Фильтр</h3>
	<form method="get" style="position: relative;">
		<a href="/manufacturing.php" style="position: absolute; top: 10px; right: 10px;" class="button">Сброс</a>

		<div class="nowrap" style="margin-bottom: 10px;">
			<span>Неделя:</span>
			<select name="week" class="<?=$_GET["week"] ? "filtered" : ""?>" onchange="this.form.submit()">
				<?
				$query = "
					SELECT LEFT(YEARWEEK(CURDATE(), 1), 4) year
					UNION
					SELECT LEFT(YEARWEEK(assembling_time, 1), 4) year
					FROM list__Assembling
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
							SELECT LEFT(YEARWEEK(assembling_time, 1), 4) year
								,YEARWEEK(assembling_time, 1) week
								,RIGHT(YEARWEEK(assembling_time, 1), 2) week_format
								,DATE_FORMAT(ADDDATE(assembling_time, 0-WEEKDAY(assembling_time)), '%e %b') WeekStart
								,DATE_FORMAT(ADDDATE(assembling_time, 6-WEEKDAY(assembling_time)), '%e %b') WeekEnd
							FROM list__Assembling
							WHERE LEFT(YEARWEEK(assembling_time, 1), 4) = {$row["year"]}
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
			<span>№№ Кассет:</span>
			<select name="CAS[]" class="<?=$_GET["CAS"] ? "filtered" : ""?>" style="width: 350px;" multiple>
				<?
				for ($i = 1; $i <= $cassetts; $i++) {
					$selected = in_array($i, $_GET["CAS"]) ? "selected" : "";
					echo "<option value='{$i}' {$selected}>{$i}</option>";
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
	$(function() {
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

		$('select[name="CAS[]"]').select2({
			placeholder: "Выберите интересующие номера кассет",
			allowClear: true,
			//closeOnSelect: false,
			scrollAfterSelect: false,
			language: "ru"
		});
	});
</script>

<table class="main_table">
	<thead>
		<tr>
			<th colspan="4">Сборка</th>
			<th colspan="5">Заливка</th>
			<th colspan="6">Расформовка</th>
		</tr>
		<tr>
			<th>Время</th>
			<th>Кассета</th>
			<th>Мастер</th>
			<th>Дефекты</th>

			<th>Время</th>
			<th>Противовес</th>
			<th>Куб раствора, г</th>
			<th>Оператор</th>
			<th>Дефекты</th>

			<th>Время</th>
			<th><i class="far fa-lg fa-hourglass" title="Интервал в часах с момента заливки"></i></th>
			<th>Деталей залито/зарегистрировано</th>
			<th>Средний вес, г</th>
			<th>Мастер</th>
			<th>Дефекты</th>
		</tr>
	</thead>
	<tbody style="text-align: center;">


<?
$query = "
	SELECT LA.LA_ID
		,DATE_FORMAT(LA.assembling_time, '%d.%m.%y %H:%i') assembling_time_format
		,LA.cassette
		,USR_Icon(LA.assembling_master) assembling_master

		,DATE_FORMAT(LF.filling_time, '%d.%m.%y %H:%i') filling_time_format
		,YEARWEEK(LB.batch_date, 1) lb_week
		,LB.LB_ID
		,SUBSTRING(CW.item, -3, 3) item
		,LB.mix_density
		,mix_diff(PB.CW_ID, LB.mix_density) mix_diff
		,USR_Icon(LB.operator) operator

		,LO.LO_ID
		,DATE_FORMAT(LO.opening_time, '%d.%m.%y %H:%i') opening_time_format
		,o_interval(LO.LO_ID) o_interval
		,PB.in_cassette
		,(SELECT SUM(1) FROM list__Weight WHERE LO_ID = LO.LO_ID) cnt_weight
		,(SELECT ROUND(AVG(weight)) FROM list__Weight WHERE LO_ID = LO.LO_ID) avg_weight
		,IF((SELECT ROUND(AVG(weight)) FROM list__Weight WHERE LO_ID = LO.LO_ID) BETWEEN ROUND(CW.min_weight + (CW.min_weight/100*CW.drying_percent)) AND ROUND(CW.max_weight + (CW.max_weight/100*CW.drying_percent)), 0, IF((SELECT ROUND(AVG(weight)) FROM list__Weight WHERE LO_ID = LO.LO_ID) > ROUND(CW.max_weight + (CW.max_weight/100*CW.drying_percent)), (SELECT ROUND(AVG(weight)) FROM list__Weight WHERE LO_ID = LO.LO_ID) - ROUND(CW.max_weight + (CW.max_weight/100*CW.drying_percent)), (SELECT ROUND(AVG(weight)) FROM list__Weight WHERE LO_ID = LO.LO_ID) - ROUND(CW.min_weight + (CW.min_weight/100*CW.drying_percent)))) avg_diff
		,USR_Icon(LO.opening_master) opening_master

		,LOD.not_spill
		,LOD.crack
		,LOD.crack_drying
		,LOD.chipped
		,LOD.def_form
		,LOD.def_assembly
	FROM list__Assembling LA
	JOIN list__Filling LF ON LF.LA_ID = LA.LA_ID
	LEFT JOIN list__Batch LB ON LB.LB_ID = LF.LB_ID
	LEFT JOIN plan__Batch PB ON PB.PB_ID = LB.PB_ID
	LEFT JOIN CounterWeight CW ON CW.CW_ID = PB.CW_ID
	LEFT JOIN list__Opening LO ON LO.LF_ID = LF.LF_ID
	LEFT JOIN list__Opening_def LOD ON LOD.LO_ID = LO.LO_ID
	WHERE YEARWEEK(LA.assembling_time, 1) LIKE '{$_GET["week"]}'
	ORDER BY LA.assembling_time
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	?>
	<tr>
		<td style="background-color: rgba(255, 127, 80, 0.3);"><?=$row["assembling_time_format"]?></td>
		<td style="background-color: rgba(255, 127, 80, 0.3);"><b class="cassette"><?=$row["cassette"]?></b></td>
		<td style="background-color: rgba(255, 127, 80, 0.3);"><?=$row["assembling_master"]?></td>
		<td class="nowrap" style="background-color: rgba(255, 127, 80, 0.3); text-align: left;">
			<?=($row["def_form"] ? "<span><font color='red'>{$row["def_form"]}</font> д. формы</span><br>" : "")?>
			<?=($row["def_assembly"] ? "<span><font color='red'>{$row["def_assembly"]}</font> д. сборки</span><br>" : "")?>
		</td>

		<td style="background-color: rgba(127, 255, 212, 0.3);"><a href="filling.php?week=<?=$row["lb_week"]?>#<?=$row["LB_ID"]?>" title="Заливка" target="_blank"><?=$row["filling_time_format"]?></a></td>
		<td style="background-color: rgba(127, 255, 212, 0.3); font-size: 2em;"><?=$row["item"]?></td>
		<td style="background-color: rgba(127, 255, 212, 0.3);"><?=number_format($row["mix_density"], 0, ',', '&nbsp;')?><?=($row["mix_diff"] ? "<font style='font-size: .8em; display: block; line-height: .4em;' color='red'>".($row["mix_diff"] > 0 ? " +" : " ").number_format($row["mix_diff"], 0, ',', '&nbsp;')."</font>" : "")?></td>
		<td style="background-color: rgba(127, 255, 212, 0.3);"><?=$row["operator"]?></td>
		<td class="nowrap" style="background-color: rgba(127, 255, 212, 0.3); text-align: left;">
			<?=($row["not_spill"] ? "<span><font color='red'>{$row["not_spill"]}</font> непролив</span><br>" : "")?>
			<?=($row["crack_drying"] ? "<span><font color='red'>{$row["crack_drying"]}</font> усад. трещина</span><br>" : "")?>
		</td>

		<td><?=$row["opening_time_format"]?></td>
		<td style="background: rgb(255,0,0,<?=($row["o_interval"] ? (24 - $row["o_interval"]) / 10 : 0)?>);"><?=$row["o_interval"]?></td>
		<td <?=(abs($row["in_cassette"] - $row["cnt_weight"]) > $row["in_cassette"] / 2 ? "style='color: red;'" : "")?>><?=$row["in_cassette"]?> / <?=$row["cnt_weight"]?></td>
		<td><a href="#" class="transactions" LO_ID="<?=$row["LO_ID"]?>" cassette="<?=$row["cassette"]?>" item="<?=$row["item"]?>" title="Список регистраций"><?=($row["avg_weight"] ? number_format($row["avg_weight"], 0, ',', '&nbsp;') : "")?><?=($row["avg_diff"] ? "<font style='font-size: .8em; display: block; line-height: .4em;' color='red'>".($row["avg_diff"] > 0 ? " +" : " ").number_format($row["avg_diff"], 0, ',', '&nbsp;')."</font>" : "")?></a></td>
		<td><?=$row["opening_master"]?></td>
		<td class="nowrap" style="text-align: left;">
			<?=($row["crack"] ? "<span><font color='red'>{$row["crack"]}</font> мех. трещина</span><br>" : "")?>
			<?=($row["chipped"] ? "<span><font color='red'>{$row["chipped"]}</font> скол</span><br>" : "")?>
		</td>
	</tr>
	<?
}
?>
	</tbody>
</table>

<div id='weight_form' title='Регистрации противовесов' style='display:none;'>
	<span style="display: inline-block; width: 30%;">Кассета: <b id="cassette" style="font-size: 2em;"></b></span>
	<span style="display: inline-block; width: 30%;">Код: <b id="item" style="font-size: 2em;"></b></span>
	<fieldset>
		<!--Содержимое формы аяксом-->
	</fieldset>
</div>

<script>
	$(function() {
		// Кнопка просмотра регистраций
		$('.transactions').click( function() {
			// Проверяем сессию
			$.ajax({ url: "check_session.php?script=1", dataType: "script", async: false });

			var LO_ID = $(this).attr("LO_ID"),
				cassette = $(this).attr("cassette"),
				item = $(this).attr("item");

			$('#cassette').text(cassette);
			$('#item').text(item);

			//Рисуем форму
			$.ajax({ url: "/ajax/opening_form_ajax.php?LO_ID="+LO_ID, dataType: "script", async: false });

			$('#weight_form').dialog({
				resizable: false,
				width: 1000,
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
