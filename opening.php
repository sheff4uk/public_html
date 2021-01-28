<?
include "config.php";
$title = 'Расформовка';
include "header.php";
include "./forms/opening_form.php";

// Если в фильтре не установлена неделя, показываем текущую
if( !$_GET["week"] ) {
	$query = "SELECT YEARWEEK(CURDATE(), 1) week";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$row = mysqli_fetch_array($res);
	$_GET["week"] = $row["week"];
}

$CAS = isset($_GET["CAS"]) ? $_GET["CAS"] : array();
$CASs = implode(",", $CAS);

// Начинаем собирать ошибки
$query = "
	SELECT LB.LB_ID
		,LF.cassette
		,DATE_FORMAT(LF.lf_date, '%d.%m.%y') lf_date_format
		,DATE_FORMAT(LF.lf_time, '%H:%i') lf_time_format
		,YEARWEEk(LF.lf_date, 1) week
	FROM list__Filling LF
	JOIN list__Batch LB ON LB.LB_ID = LF.LB_ID
	LEFT JOIN list__Opening LO ON LO.LF_ID = LF.LF_ID
	WHERE 1
		AND LO.LO_ID IS NULL
		AND YEARWEEK(LF.lf_date + INTERVAL 1 DAY, 1) LIKE '{$_GET["week"]}'
		AND(SELECT LF_ID FROM list__Filling WHERE cassette = LF.cassette AND lf_date > LF.lf_date LIMIT 1) IS NOT NULL
	ORDER BY LF.lf_date, LF.lf_time
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	$errors .= "<p>Кассета <b class='cassette'>{$row["cassette"]}</b> залита <a href='filling.php?week={$row["week"]}#{$row["LB_ID"]}' target='_blank'>{$row["lf_date_format"]} {$row["lf_time_format"]}</a>. Нет данных по расформовке.</p>";
}
?>

<fieldset id="errors" style="color: red; border-color: red; border-radius: 20px; display: none;">
	<legend><h3>Ошибки:</h3></legend>
	<div></div>
</fieldset>


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
			<th colspan="2">Расформовка</th>
			<th rowspan="2">№ кассеты</th>
			<th rowspan="2"><i class="far fa-lg fa-hourglass" title="Интервал в часах с моента заливки."></i></th>
			<th rowspan="2">№ поста</th>
			<th colspan="4">Кол-во брака, шт</th>
			<th colspan="3">Взвешивания, кг</th>
			<th rowspan="2">Куб раствора, кг</th>
			<th rowspan="2">Противовес</th>
			<th rowspan="2">Дата заливки</th>
			<th rowspan="2"></th>
		</tr>
		<tr>
			<th>Дата</th>
			<th>Время</th>
			<th>Непролив</th>
			<th>Трещина</th>
			<th>Скол</th>
			<th>Дефект форм</th>
			<th>№1</th>
			<th>№2</th>
			<th>№3</th>
		</tr>
	</thead>
	<tbody style="text-align: center;">

<?
$query = "
	SELECT LO.LO_ID
		,LO.o_post
		,DATE_FORMAT(LO.o_date, '%d.%m.%y') o_date
		,DATE_FORMAT(LO.o_time, '%H:%i') o_time
		,o_interval(LO.LO_ID) o_interval
		,LO.o_not_spill
		,LO.o_crack
		,LO.o_chipped
		,LO.o_def_form
		,LO.weight1
		,LO.weight2
		,LO.weight3
		,IF(LO.weight1 BETWEEN CW.min_weight AND CW.max_weight, 0, IF(LO.weight1 > CW.max_weight, LO.weight1 - CW.max_weight, LO.weight1 - CW.min_weight)) w1_diff
		,IF(LO.weight2 BETWEEN CW.min_weight AND CW.max_weight, 0, IF(LO.weight2 > CW.max_weight, LO.weight2 - CW.max_weight, LO.weight2 - CW.min_weight)) w2_diff
		,IF(LO.weight3 BETWEEN CW.min_weight AND CW.max_weight, 0, IF(LO.weight3 > CW.max_weight, LO.weight3 - CW.max_weight, LO.weight3 - CW.min_weight)) w3_diff
		,DATE_FORMAT(LB.batch_date, '%d.%m.%y') batch_date_format
		,LO.cassette
		,CW.item
		,YEARWEEK(PB.pb_date, 1) pb_week
		,PB.CW_ID
		,LB.LB_ID
		,LP.LP_ID
		,LP.p_date
		,LB.mix_density
		,mix_diff(PB.CW_ID, LB.mix_density) mix_diff
		,SUM(1) dbl
	FROM list__Opening LO
	JOIN list__Opening SLO ON SLO.cassette = LO.cassette AND SLO.LF_ID = LO.LF_ID
	LEFT JOIN list__Filling LF ON LF.LF_ID = LO.LF_ID
	LEFT JOIN list__Batch LB ON LB.LB_ID = LF.LB_ID
	LEFT JOIN plan__Batch PB ON PB.PB_ID = LB.PB_ID
	LEFT JOIN CounterWeight CW ON CW.CW_ID = PB.CW_ID
	LEFT JOIN list__Packing LP ON LP.LF_ID = LF.LF_ID
	WHERE 1
		".($_GET["week"] ? "AND YEARWEEK(LO.o_date, 1) LIKE '{$_GET["week"]}'" : "")."
		".($_GET["CW_ID"] ? "AND PB.CW_ID={$_GET["CW_ID"]}" : "")."
		".($_GET["CB_ID"] ? "AND PB.CW_ID IN (SELECT CW_ID FROM CounterWeight WHERE CB_ID = {$_GET["CB_ID"]})" : "")."
		".($_GET["int24"] ? "AND o_interval(LO.LO_ID) < 24" : "")."
		".($_GET["not_spec"] ? "AND (NOT WeightSpec(PB.CW_ID, LO.weight1) OR NOT WeightSpec(PB.CW_ID, LO.weight2) OR NOT WeightSpec(PB.CW_ID, LO.weight3))" : "")."
		".($_GET["not_spill"] ? "AND LO.o_not_spill" : "")."
		".($_GET["crack"] ? "AND LO.o_crack" : "")."
		".($_GET["chipped"] ? "AND LO.o_chipped" : "")."
		".($_GET["def_form"] ? "AND LO.o_def_form" : "")."
		".($CASs ? "AND LO.cassette IN({$CASs})" : "")."
	GROUP BY LO.LO_ID
	ORDER BY LO.o_date, LO.o_time, LO.o_post
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	// Собираем ошибки номеров кассет
	if($row["dbl"] > 1) {
		$errors .= "<p>Кассета <a href='#{$row["LO_ID"]}'><b class='cassette'>{$row["cassette"]}</b></a> расформована повторно.</p>";
	}

	if( $row["LP_ID"] ) {
		$cassette = "<a href='packing.php?p_date_from={$row["p_date"]}&p_date_to={$row["p_date"]}#{$row["LP_ID"]}' title='Упаковка' target='_blank'><b class='cassette' style='".($row["dbl"] > 1 ? "color: red;" : "")."'>{$row["cassette"]}</b></a>";
	}
	else {
		$cassette = "<b class='cassette' style='".($row["dbl"] > 1 ? "color: red;" : "")."'>{$row["cassette"]}</b>";
	}
	?>
	<tr id="<?=$row["LO_ID"]?>" style="<?=(($o_date and $o_date != $row["o_date"]) ? "border-top: 10px solid #333;" : "")?>">
		<td><?=$row["o_date"]?></td>
		<td><?=$row["o_time"]?></td>
		<td><?=$cassette?></td>
		<td style="background: rgb(255,0,0,<?=((24 - $row["o_interval"]) / 10)?>);"><?=$row["o_interval"]?></td>
		<td><?=$row["o_post"]?></td>
		<td style="color: red;"><?=$row["o_not_spill"]?></td>
		<td style="color: red;"><?=$row["o_crack"]?></td>
		<td style="color: red;"><?=$row["o_chipped"]?></td>
		<td style="color: red;"><?=$row["o_def_form"]?></td>
		<td><?=$row["weight1"]/1000?><?=($row["w1_diff"] ? "<font style='font-size: .8em; display: block; line-height: .4em;' color='red'>".($row["w1_diff"] > 0 ? " +" : " ").($row["w1_diff"]/1000)."</font>" : "")?></td>
		<td><?=$row["weight2"]/1000?><?=($row["w2_diff"] ? "<font style='font-size: .8em; display: block; line-height: .4em;' color='red'>".($row["w2_diff"] > 0 ? " +" : " ").($row["w2_diff"]/1000)."</font>" : "")?></td>
		<td><?=$row["weight3"]/1000?><?=($row["w3_diff"] ? "<font style='font-size: .8em; display: block; line-height: .4em;' color='red'>".($row["w3_diff"] > 0 ? " +" : " ").($row["w3_diff"]/1000)."</font>" : "")?></td>
		<td class="bg-gray"><?=$row["mix_density"]/1000?><?=($row["mix_diff"] ? "<font style='font-size: .8em; display: block; line-height: .4em;' color='red'>".($row["mix_diff"] > 0 ? " +" : " ").($row["mix_diff"]/1000)."</font>" : "")?></td>
		<td class="bg-gray"><?=$row["item"]?></td>
		<td class="bg-gray"><a href="filling.php?week=<?=$row["pb_week"]?>#<?=$row["LB_ID"]?>" title="Заливка" target="_blank"><?=$row["batch_date_format"]?></a></td>
		<td><a href="#" class="add_opening" LO_ID="<?=$row["LO_ID"]?>" title="Изменить данные расформовки"><i class="fa fa-pencil-alt fa-lg"></i></a></td>
	</tr>
	<?
	$o_date = $row["o_date"];
}

// Выводим собранные ошибки вверку экрана
if( $errors ) {
	$_SESSION["error"][] = "Обнаружены ошибки в данных. Пожалуйста исправьте.";
?>
<script>
	$(function() {
		$('#errors div').html("<?=$errors?>");
		$('#errors').show('fast');
	});
</script>
<?
}
?>

	</tbody>
</table>

<div id="add_btn" class="add_opening" o_date="<?=$_GET["o_date"]?>" o_post="<?=$_GET["o_post"]?>" title="Внести данные расформовки"></div>

<?
include "footer.php";
?>
