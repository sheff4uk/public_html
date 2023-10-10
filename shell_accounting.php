<?
include "config.php";
$title = 'Списание форм';
include "header.php";

// Проверка прав на доступ к экрану
if( !in_array('shell_accounting', $Rights) ) {
	header($_SERVER['SERVER_PROTOCOL'].' 403 Forbidden');
	die('Недостаточно прав для совершения операции');
}

// Если в фильтре не установлен период, показываем последние 7 дней
if( !$_GET["date_from"] ) {
	$date = date_create('-6 days');
	$_GET["date_from"] = date_format($date, 'Y-m-d');
}
if( !$_GET["date_to"] ) {
	$date = date_create('-0 days');
	$_GET["date_to"] = date_format($date, 'Y-m-d');
}

include "./forms/shell_accounting_form.php";
?>

<style>
	#shell_report_btn {
		text-align: center;
		line-height: 68px;
		color: #fff;
		bottom: 250px;
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
	#shell_arrival_btn:hover, #shell_reject_btn:hover, #shell_report_btn:hover {
		opacity: 1;
	}
</style>

<!--Фильтр-->
<div id="filter">
	<h3>Фильтр</h3>
	<form method="get" style="position: relative;">
		<a href="/shell_accounting.php" style="position: absolute; top: 10px; right: 10px;" class="button">Сброс</a>

		<div class="nowrap" style="margin-bottom: 10px;">
			<span style="display: inline-block; width: 200px;">Дата списания между:</span>
			<input name="date_from" type="date" value="<?=$_GET["date_from"]?>" class="<?=$_GET["date_from"] ? "filtered" : ""?>">
			<input name="date_to" type="date" value="<?=$_GET["date_to"]?>" class="<?=$_GET["date_to"] ? "filtered" : ""?>">
			<i class="fas fa-question-circle" title="По умолчанию устанавливаются последние 7 дней."></i>
		</div>

		<div class="nowrap" style="display: inline-block; margin-bottom: 10px; margin-right: 30px;">
			<span>Код противовеса:</span>
			<select name="CW_ID" class="<?=$_GET["CW_ID"] ? "filtered" : ""?>">
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
			<span>Клиент:</span>
			<select name="CB_ID" class="<?=$_GET["CB_ID"] ? "filtered" : ""?>">
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
			<th>Объем (факт), л</th>
			<th>Объем (по чертежу), л</th>
			<th>Списанных форм</th>
			<th>Отслоения</th>
			<th>Трещины</th>
			<th>Сколы</th>
			<th>Партия</th>
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
		,SA.actual_volume
		,CW.drawing_volume
		,NULL sr_cnt
		,NULL exfolation
		,NULL crack
		,NULL chipped
		,SA.batch_number batch
		,SA.sa_date date
		,SA.CW_ID
		,(SELECT SUM(1) FROM shell__Item WHERE SA_ID = SA.SA_ID) barcode
		,1 edit
	FROM shell__Arrival SA
	JOIN CounterWeight CW ON CW.CW_ID = SA.CW_ID
	WHERE 1
		".($_GET["date_from"] ? "AND SA.sa_date >= '{$_GET["date_from"]}'" : "")."
		".($_GET["date_to"] ? "AND SA.sa_date <= '{$_GET["date_to"]}'" : "")."
		".($_GET["CW_ID"] ? "AND SA.CW_ID={$_GET["CW_ID"]}" : "")."
		".($_GET["CB_ID"] ? "AND CW.CB_ID = {$_GET["CB_ID"]}" : "")."

	UNION

	SELECT 'R' type
		,SR.SR_ID ID
		,DATE_FORMAT(SR.sr_date, '%d.%m.%Y') date_format
		,CW.item
		,NULL
		,NULL
		,NULL
		,SR.sr_cnt
		,SR.exfolation
		,SR.crack
		,SR.chipped
		,IFNULL(DATE_FORMAT(SA.sa_date, '%d.%m.%Y'), SR.batch_number)
		,SR.sr_date date
		,IFNULL(SR.CW_ID, SA.CW_ID)
		,NULL
		,IF(SR.SA_ID, 0, 1) edit
	FROM shell__Reject SR
	LEFT JOIN shell__Arrival SA ON SA.SA_ID = SR.SA_ID
	JOIN CounterWeight CW ON CW.CW_ID = IFNULL(SR.CW_ID, SA.CW_ID)
	WHERE 1
		".($_GET["date_from"] ? "AND SR.sr_date >= '{$_GET["date_from"]}'" : "")."
		".($_GET["date_to"] ? "AND SR.sr_date <= '{$_GET["date_to"]}'" : "")."
		".($_GET["CW_ID"] ? "AND IFNULL(SR.CW_ID, SA.CW_ID) = {$_GET["CW_ID"]}" : "")."
		".($_GET["CB_ID"] ? "AND CW.CB_ID = {$_GET["CB_ID"]}" : "")."

	ORDER BY date, type, CW_ID
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
		<td><?=($row["actual_volume"] ? $row["actual_volume"]/1000 : "")?></td>
		<td><?=($row["drawing_volume"] ? $row["drawing_volume"]/1000 : "")?></td>
		<td><b style="color: red;"><?=$row["sr_cnt"]?></b></td>
		<td><?=$row["exfolation"]?></td>
		<td><?=$row["crack"]?></td>
		<td><?=$row["chipped"]?></td>
		<td><?=$row["batch"]?></td>
		<td>
			<?
			if( $row["edit"] ) {
				echo "<a href='#' ".($row["type"] == "A" ? "class='add_arrival' SA_ID='{$row["ID"]}'" : "class='add_reject' SR_ID='{$row["ID"]}'")." title='Редактировать'><i class='fa fa-pencil-alt fa-lg'></i></a>";
			}
			if( $row["type"] == 'A' && $row["barcode"] ) {
				echo "<a href='printforms/shell_label.php?SA_ID={$row["ID"]}' class='print' title='Штрихкоды на формы'><i class='fas fa-print fa-lg'></i></a>";
			}
			?>
		</td>
	</tr>
	<?
}
?>
		<tr class="total">
			<td></td>
			<td>Итог:</td>
			<td><b><?=$sa_cnt?></b></td>
			<td></td>
			<td></td>
			<td><b><?=$sr_cnt?></b></td>
			<td><?=$exfolation?></td>
			<td><?=$crack?></td>
			<td><?=$chipped?></td>
			<td></td>
			<td></td>
		</tr>
	</tbody>
</table>

<div>
	<table>
		<thead>
			<tr>
				<th>Противовес</th>
				<th>Кол-во годных форм</th>
				<th>Средний ресурс форм до её списания в циклах заливки</th>
				<th>Среднесуточное списание форм</th>
				<th>Списаний за прошедшие сутки</th>
				<th>Текущая потребность в формах</th>
				<th>Дефицит форм в штуках</th>
				<th>Через сколько дней наступит дефицит форм</th>
			</tr>
		</thead>
		<tbody>
			<?
			$query = "
				SELECT CW.item
					,CW.shell_balance
					,ROUND((WB.fillings * PB.in_cassette) / WR.sr_cnt) `durability`
					,ROUND(WR.sr_cnt / DATEDIFF(CURDATE() - INTERVAL 1 DAY, '2020-12-04'), 1) `sr_avg`
					#,ROUND(AVG(PB.fact_batches * PB.fillings / PB.per_batch * PB.in_cassette)) `often`
					#,MAX(ROUND(PB.fact_batches * PB.fillings / PB.per_batch) * PB.in_cassette) `max`
					,(
						SELECT SUM(MixFormula.in_cassette)
						FROM Cassettes
						JOIN MixFormula ON MixFormula.F_ID = Cassettes.F_ID
							AND MixFormula.CW_ID = Cassettes.CW_ID
						WHERE MixFormula.CW_ID = CW.CW_ID
					) current_need
					#,MAX(ROUND(PB.fact_batches * PB.fillings / PB.per_batch) * PB.in_cassette) - CW.shell_balance `need`
					#,ROUND((CW.shell_balance - MAX(PB.fact_batches * PB.fillings / PB.per_batch * PB.in_cassette)) / (WR.sr_cnt / DATEDIFF(CURDATE() - INTERVAL 1 DAY, '2020-12-04'))) `days_max`
					#,DATE_FORMAT(CURDATE() + INTERVAL ROUND((CW.shell_balance - MAX(PB.fact_batches * PB.fillings / PB.per_batch * PB.in_cassette)) / (WR.sr_cnt / DATEDIFF(CURDATE() - INTERVAL 1 DAY, '2020-12-04'))) DAY, '%d/%m/%Y') `date_max`
					,SR.sr_cnt
				FROM CounterWeight CW
				LEFT JOIN (
					SELECT CW_ID
						,SUM(sr_cnt) sr_cnt
					FROM shell__Reject
					WHERE sr_date = CURDATE() - INTERVAL 1 DAY
					GROUP BY CW_ID
				) SR ON SR.CW_ID = CW.CW_ID
				LEFT JOIN plan__Batch PB ON PB.CW_ID = CW.CW_ID
				# Число заливок с 04.12.2020
				LEFT JOIN (
					SELECT PB.CW_ID
						,SUM(1) fillings
					FROM list__Filling LF
					JOIN list__Batch LB ON LB.LB_ID = LF.LB_ID
					JOIN plan__Batch PB ON PB.PB_ID = LB.PB_ID
					WHERE DATE(LF.filling_time) BETWEEN '2020-12-04' AND CURDATE() - INTERVAL 1 DAY
					GROUP BY PB.CW_ID
				) WB ON WB.CW_ID = CW.CW_ID
				# Число списаний с 04.12.2020
				LEFT JOIN (
					SELECT CW_ID
						,SUM(sr_cnt) sr_cnt
					FROM shell__Reject
					WHERE sr_date BETWEEN '2020-12-04' AND CURDATE() - INTERVAL 1 DAY
					GROUP BY CW_ID
				) WR ON WR.CW_ID = CW.CW_ID
				WHERE 1
					".($_GET["CW_ID"] ? "AND CW.CW_ID={$_GET["CW_ID"]}" : "")."
					".($_GET["CB_ID"] ? "AND CW.CB_ID = {$_GET["CB_ID"]}" : "")."
				GROUP BY CW.CW_ID
				ORDER BY CW.CW_ID
			";
			$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			while( $row = mysqli_fetch_array($res) ) {
				//$pallets += $row["pallets"];
				$need = $row["current_need"] - $row["shell_balance"];
				$days_max = ($row["sr_avg"] > 0) ? round( ($row["shell_balance"] - $row["current_need"]) / $row["sr_avg"] ) : null;
				?>
					<tr>
						<td style="text-align: center;"><?=$row["item"]?></td>
						<td style="text-align: center;"><?=$row["shell_balance"]?></td>
						<td style="text-align: center;"><?=$row["durability"]?></td>
						<td style="text-align: center;"><?=$row["sr_avg"]?></td>
						<td style="text-align: center;"><?=$row["sr_cnt"]?></td>
<!--
						<td style="text-align: center; <?=($row["often"] > $row["shell_balance"] ? "color: red;" : "")?>"><?=$row["often"]?></td>
						<td style="text-align: center; <?=($row["max"] > $row["shell_balance"] ? "color: red;" : "")?>"><?=$row["max"]?></td>
-->
						<td style="text-align: center; <?=($row["current_need"] > $row["shell_balance"] ? "color: red;" : "")?>"><?=$row["current_need"]?></td>
						<td style="text-align: center; color: red;"><?=($need > 0 ? $need : "")?></td>
						<td style="text-align: center;"><?=($days_max < 0 ? "" : $days_max)?></td>
					</tr>
				<?
			}
			?>
		</tbody>
	</table>
</div>

<!--<div id="shell_report_btn" title="Распечатать отчет"><a href="/printforms/shell_accounting_report.php?CB_ID=2" class="print" style="color: white;"><i class="fas fa-2x fa-print"></i></a></div>-->

<div id="shell_arrival_btn" class="add_arrival" title="Приход форм"><i class="fas fa-2x fa-plus"></i></div>
<div id="shell_reject_btn" class="add_reject" title="Списание форм"><i class="fas fa-2x fa-minus"></i></div>

<script>
	$(function() {
		$(".print").printPage();
	});
</script>

<?
include "footer.php";
?>
