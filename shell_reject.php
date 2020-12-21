<?
include "config.php";
$title = 'Списание форм';
include "header.php";
include "./forms/shell_reject_form.php";

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
	#shell_arrival_btn {
		text-align: center;
		line-height: 68px;
		color: #fff;
		bottom: 175px;
		cursor: pointer;
		width: 56px;
		height: 56px;
		opacity: .4;
		position: fixed;
		right: 20px;
		z-index: 9;
		border-radius: 50%;
		background-color: #16A085;
		box-shadow: 0 0 4px rgba(0,0,0,.14), 0 4px 8px rgba(0,0,0,.28);
	}
	#shell_reject_btn {
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
		box-shadow: 0 0 4px rgba(0,0,0,.14), 0 4px 8px rgba(0,0,0,.28);
	}
	#shell_arrival_btn:hover, #shell_reject_btn:hover {
		opacity: 1;
	}
</style>

<!--Фильтр-->
<div id="filter">
	<h3>Фильтр</h3>
	<form method="get" style="position: relative;">
		<a href="/shell_reject.php" style="position: absolute; top: 10px; right: 10px;" class="button">Сброс</a>

		<div class="nowrap" style="margin-bottom: 10px;">
			<span style="display: inline-block; width: 200px;">Дата списания между:</span>
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
			<th>Дата</th>
			<th>Противовес</th>
			<th>Пришедших форм</th>
			<th>Списанных форм</th>
			<th>Отслоения</th>
			<th>Трещины</th>
			<th>Сколы</th>
			<th></th>
		</tr>
	</thead>
	<tbody style="text-align: center;">

<?
$query = "
	SELECT 'A' type
		,SA.SA_ID ID
		,DATE_FORMAT(SA.sa_date, '%d.%m.%Y') date_format
		,CW.item
		,SA.sa_cnt
		,NULL sr_cnt
		,NULL exfolation
		,NULL crack
		,NULL chipped
		,SA.sa_date date
		,SA.CW_ID
	FROM ShellArrival SA
	JOIN CounterWeight CW ON CW.CW_ID = SA.CW_ID
	WHERE 1
		".($_GET["date_from"] ? "AND SA.sa_date >= '{$_GET["date_from"]}'" : "")."
		".($_GET["date_to"] ? "AND SA.sa_date <= '{$_GET["date_to"]}'" : "")."
		".($_GET["CW_ID"] ? "AND SA.CW_ID={$_GET["CW_ID"]}" : "")."
		".($_GET["CB_ID"] ? "AND SA.CW_ID IN (SELECT CW_ID FROM CounterWeight WHERE CB_ID = {$_GET["CB_ID"]})" : "")."

	UNION

	SELECT 'R' type
		,SR.SR_ID ID
		,DATE_FORMAT(SR.sr_date, '%d.%m.%Y') date_format
		,CW.item
		,NULL sa_cnt
		,SR.sr_cnt
		,SR.exfolation
		,SR.crack
		,SR.chipped
		,SR.sr_date date
		,SR.CW_ID
	FROM ShellReject SR
	JOIN CounterWeight CW ON CW.CW_ID = SR.CW_ID
	WHERE 1
		".($_GET["date_from"] ? "AND SR.sr_date >= '{$_GET["date_from"]}'" : "")."
		".($_GET["date_to"] ? "AND SR.sr_date <= '{$_GET["date_to"]}'" : "")."
		".($_GET["CW_ID"] ? "AND SR.CW_ID={$_GET["CW_ID"]}" : "")."
		".($_GET["CB_ID"] ? "AND SR.CW_ID IN (SELECT CW_ID FROM CounterWeight WHERE CB_ID = {$_GET["CB_ID"]})" : "")."

	ORDER BY date, CW_ID
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	$sa_cnt += $row["sa_cnt"];
	$sr_cnt += $row["sr_cnt"];
	$exfolation += $row["exfolation"];
	$crack += $row["crack"];
	$chipped += $row["chipped"];
	?>
	<tr id="<?=$row["type"]?><?=$row["ID"]?>">
		<td><?=$row["date_format"]?></td>
		<td><?=$row["item"]?></td>
		<td><b style="color: green;"><?=$row["sa_cnt"]?></b></td>
		<td><b style="color: red;"><?=$row["sr_cnt"]?></b></td>
		<td><?=$row["exfolation"]?></td>
		<td><?=$row["crack"]?></td>
		<td><?=$row["chipped"]?></td>
		<td><a href="#" <?=($row["type"] == "A" ? "class='add_arrival' SA_ID='{$row["ID"]}'" : "class='add_reject' SR_ID='{$row["ID"]}'")?> title="Редактировать"><i class="fa fa-pencil-alt fa-lg"></i></a></td>
	</tr>
	<?
}
?>
		<tr class="total">
			<td></td>
			<td>Итог:</td>
			<td><b><?=$sa_cnt?></b></td>
			<td><b><?=$sr_cnt?></b></td>
			<td><?=$exfolation?></td>
			<td><?=$crack?></td>
			<td><?=$chipped?></td>
			<td></td>
		</tr>
	</tbody>
</table>

<div>
	<h3>Баланс форм</h3>
	<table>
		<thead>
			<tr>
				<th>Противовес</th>
				<th>Кол-во годных форм</th>
				<th>Среднесуточная динамика списания форм</th>
				<th>ПИКОВОЕ число эксплуатируемых форм<br>(за последние 3 месяца)</th>
				<th>Через сколько дней возникнет дефицит форм для ПИКОВОЙ эксплуатации с учетом текущей динамики списания</th>
				<th>ОБЫЧНОЕ число эксплуатируемых форм<br>(за последние 3 месяца)</th>
				<th>Через сколько дней возникнет дефицит форм для ОБЫЧНОЙ эксплуатации с учетом текущей динамики списания</th>
			</tr>
		</thead>
		<tbody>
			<?
			$query = "
				SELECT CW.item
					,CW.shell_balance
					,ROUND(SR.sr_cnt, 1) sr_cnt_avg
					,MAX(PB.fakt) * CW.fillings * CW.in_cassette `max`
					,ROUND((CW.shell_balance - MAX(PB.fakt) * CW.fillings * CW.in_cassette) / SR.sr_cnt) `days_max`
					,DATE_FORMAT(CURDATE() + INTERVAL ROUND((CW.shell_balance - MAX(PB.fakt) * CW.fillings * CW.in_cassette) / SR.sr_cnt) DAY, '%d.%m.%Y') `date_max`
					,ROUND(AVG(IF(PB.fakt = 0 OR WEEKDAY(PB.pb_date) IN (5,6), NULL, PB.fakt))) * CW.fillings * CW.in_cassette `often`
					,ROUND((CW.shell_balance - ROUND(AVG(IF(PB.fakt = 0 OR WEEKDAY(PB.pb_date) IN (5,6), NULL, PB.fakt))) * CW.fillings * CW.in_cassette) / SR.sr_cnt) `days_often`
					,DATE_FORMAT(CURDATE() + INTERVAL ROUND((CW.shell_balance - ROUND(AVG(IF(PB.fakt = 0 OR WEEKDAY(PB.pb_date) IN (5,6), NULL, PB.fakt))) * CW.fillings * CW.in_cassette) / SR.sr_cnt) DAY, '%d.%m.%Y') `date_often`
				FROM CounterWeight CW
				LEFT JOIN plan__Batch PB ON PB.CW_ID = CW.CW_ID AND PB.pb_date BETWEEN CURDATE() - INTERVAL 3 MONTH AND CURDATE()
				LEFT JOIN (
					SELECT CW_ID
						,SUM(sr_cnt) / DATEDIFF(CURDATE(), IF(CURDATE() - INTERVAL 3 MONTH < '2020-12-04', '2020-12-04', CURDATE() - INTERVAL 3 MONTH)) sr_cnt
					FROM ShellReject
					WHERE sr_date BETWEEN CURDATE() - INTERVAL 3 MONTH AND CURDATE()
					GROUP BY CW_ID
				) SR ON SR.CW_ID = PB.CW_ID
				WHERE 1
					".($_GET["CW_ID"] ? "AND PB.CW_ID={$_GET["CW_ID"]}" : "")."
					".($_GET["CB_ID"] ? "AND PB.CW_ID IN (SELECT CW_ID FROM CounterWeight WHERE CB_ID = {$_GET["CB_ID"]})" : "")."
				GROUP BY CW.CW_ID
				ORDER BY CW.CW_ID
			";
			$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			while( $row = mysqli_fetch_array($res) ) {
			?>
				<tr>
					<td style="text-align: center;"><?=$row["item"]?></td>
					<td style="text-align: center;"><?=$row["shell_balance"]?></td>
					<td style="text-align: center;"><?=$row["sr_cnt_avg"]?></td>
					<td style="text-align: center; <?=($row["days_max"] < 0 ? "color: red;" : "")?>"><?=$row["max"]?></td>
					<td style="text-align: center;"><?=($row["days_max"] < 0 ? "<i class='fas fa-exclamation-triangle' style='color: red;'></i>" : "{$row["days_max"]}<font style='font-size: .8em; display: block; line-height: .4em;'>{$row["date_max"]}</font>")?></td>
					<td style="text-align: center; <?=($row["days_often"] < 0 ? "color: red;" : "")?>"><?=$row["often"]?></td>
					<td style="text-align: center;"><?=($row["days_often"] < 0 ? "<i class='fas fa-exclamation-triangle' style='color: red;'></i>" : "{$row["days_often"]}<font style='font-size: .8em; display: block; line-height: .4em;'>{$row["date_often"]}</font>")?></td>
				</tr>
			<?
			}
			?>
		</tbody>
	</table>
</div>

<!--<div id="shell_report_btn" title="Распечатать отчет"><a href="/printforms/shell_reject_report.php?sr_date=<?=$_GET["date"]?>&CB_ID=<?=$_GET["CB_ID"]?>" class="print" style="color: white;"><i class="fas fa-2x fa-print"></i></a></div>-->

<div id="shell_arrival_btn" class="add_arrival" sa_date="<?=$_GET["sa_date"]?>" title="Приход форм"><i class="fas fa-2x fa-plus"></i></div>
<div id="shell_reject_btn" class="add_reject" sr_date="<?=$_GET["sr_date"]?>" title="Списание форм"><i class="fas fa-2x fa-minus"></i></div>

<script>
	$(function() {
		$(".print").printPage();
	});
</script>

<?
include "footer.php";
?>
