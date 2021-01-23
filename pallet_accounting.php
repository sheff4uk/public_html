<?
include "config.php";
$title = 'Учет поддонов';
include "header.php";
include "./forms/pallet_accounting_form.php";

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
	#pallet_arrival_btn {
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
	#pallet_return_btn {
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
	#pallet_arrival_btn:hover, #pallet_return_btn:hover {
		opacity: 1;
	}
</style>

<!--Фильтр-->
<div id="filter">
	<h3>Фильтр</h3>
	<form method="get" style="position: relative;">
		<a href="/pallet_accounting.php" style="position: absolute; top: 10px; right: 10px;" class="button">Сброс</a>

		<div class="nowrap" style="margin-bottom: 10px;">
			<span style="display: inline-block; width: 200px;">Дата между:</span>
			<input name="date_from" type="date" value="<?=$_GET["date_from"]?>" class="<?=$_GET["date_from"] ? "filtered" : ""?>">
			<input name="date_to" type="date" value="<?=$_GET["date_to"]?>" class="<?=$_GET["date_to"] ? "filtered" : ""?>">
			<i class="fas fa-question-circle" title="По умолчанию устанавливаются последние 7 дней."></i>
		</div>

		<div class="nowrap" style="display: inline-block; margin-bottom: 10px; margin-right: 30px;">
			<span>Клиент:</span>
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
			<span>Поставщик поддонов:</span>
			<select name="PS_ID" class="<?=$_GET["PS_ID"] ? "filtered" : ""?>" style="width: 100px;">
				<option value=""></option>
				<?
				$query = "
					SELECT PS.PS_ID, PS.pallet_supplier
					FROM pallet__Supplier PS
					ORDER BY PS.PS_ID
				";
				$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
				while( $row = mysqli_fetch_array($res) ) {
					$selected = ($row["PS_ID"] == $_GET["PS_ID"]) ? "selected" : "";
					echo "<option value='{$row["PS_ID"]}' {$selected}>{$row["pallet_supplier"]}</option>";
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
			<th>Поставщик поддонов / Клиент</th>
			<th>Отгружено поддонов</th>
			<th>Приобретено / возвращено поддонов</th>
			<th>Из них бракованных</th>
			<th>Из них другого формата</th>
			<th>Кол-во годных поддонов</th>
			<th>Стоимость поддона, руб</th>
			<th>Сумма, руб</th>
			<th></th>
		</tr>
	</thead>
	<tbody style="text-align: center;">

<?
$query = "
	SELECT 'R' type
		,PR.PR_ID ID
		,DATE_FORMAT(PR.pr_date, '%d.%m.%Y') date_format
		,CB.brand
		,NULL pallets_shipment
		,PR.pr_cnt
		,PR.pr_reject
		,PR.pr_wrong_format
		,PR.pr_cnt - PR.pr_reject - PR.pr_wrong_format pr_good
		,NULL pallet_cost
		,NULL sum_cost
		,PR.pr_date date
		,PR.CB_ID
		,NULL week
		,NULL LS_ID
	FROM pallet__Return PR
	JOIN ClientBrand CB ON CB.CB_ID = PR.CB_ID
	WHERE 1
		".($_GET["date_from"] ? "AND PR.pr_date >= '{$_GET["date_from"]}'" : "")."
		".($_GET["date_to"] ? "AND PR.pr_date <= '{$_GET["date_to"]}'" : "")."
		".($_GET["CB_ID"] ? "AND PR.CB_ID = {$_GET["CB_ID"]}" : "")."
		".($_GET["PS_ID"] ? "AND 0" : "")."

	UNION

	SELECT 'A'
		,PA.PA_ID
		,DATE_FORMAT(PA.pa_date, '%d.%m.%Y')
		,PS.pallet_supplier
		,NULL pallets_shipment
		,PA.pa_cnt
		,PA.pa_reject
		,NULL
		,PA.pa_cnt - PA.pa_reject
		,PA.pallet_cost
		,PA.pallet_cost * PA.pa_cnt
		,PA.pa_date
		,NULL
		,NULL
		,NULL
	FROM pallet__Arrival PA
	JOIN pallet__Supplier PS ON PS.PS_ID = PA.PS_ID
	WHERE 1
		".($_GET["date_from"] ? "AND PA.pa_date >= '{$_GET["date_from"]}'" : "")."
		".($_GET["date_to"] ? "AND PA.pa_date <= '{$_GET["date_to"]}'" : "")."
		".($_GET["CB_ID"] ? "AND 0" : "")."
		".($_GET["PS_ID"] ? "AND PS.PS_ID = {$_GET["PS_ID"]}" : "")."

	UNION

	SELECT NULL
		,NULL
		,DATE_FORMAT(LS.ls_date, '%d.%m.%Y')
		,CB.brand
		,SUM(pallets)
		,NULL
		,NULL
		,NULL
		,NULL
		,NULL
		,NULL
		,LS.ls_date
		,CW.CB_ID
		,YEARWEEK(LS.ls_date, 1)
		,LS.LS_ID
	FROM list__Shipment LS
	JOIN CounterWeight CW ON CW.CW_ID = LS.CW_ID
	JOIN ClientBrand CB ON CB.CB_ID = CW.CB_ID
	WHERE 1
		".($_GET["date_from"] ? "AND LS.ls_date >= '{$_GET["date_from"]}'" : "")."
		".($_GET["date_to"] ? "AND LS.ls_date <= '{$_GET["date_to"]}'" : "")."
		".($_GET["CB_ID"] ? "AND CW.CB_ID = {$_GET["CB_ID"]}" : "")."
		".($_GET["PS_ID"] ? "AND 0" : "")."
	GROUP BY CW.CB_ID, LS.ls_date

	ORDER BY date, CB_ID
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	$pallets_shipment += $row["pallets_shipment"];
	$pr_cnt += $row["pr_cnt"];
	$pr_reject += $row["pr_reject"];
	$pr_wrong_format += $row["pr_wrong_format"];
	$pr_good += $row["pr_good"];
	$pa_cnt += $row["pa_cnt"];
	$pa_reject += $row["pa_reject"];
	$sum_cost += $row["sum_cost"];
	?>
	<tr id="<?=$row["type"]?><?=$row["ID"]?>">
		<td><?=$row["date_format"]?></td>
		<td><span class="nowrap"><?=$row["brand"]?></span></td>
		<td><b><a href="shipment.php?week=<?=$row["week"]?>&CB_ID=<?=$row["CB_ID"]?>#<?=$row["LS_ID"]?>" target="_blank"><?=$row["pallets_shipment"]?></a></b></td>
		<td><b><?=$row["pr_cnt"]?></b></td>
		<td><b style="color: red;"><?=$row["pr_reject"]?></b></td>
		<td><b style="color: red;"><?=$row["pr_wrong_format"]?></b></td>
		<td><b style="color: green;"><?=$row["pr_good"]?></b></td>
		<td><?=(isset($row["pallet_cost"]) ? number_format($row["pallet_cost"], 0, '', ' ') : "")?></td>
		<td><?=(isset($row["sum_cost"]) ? number_format($row["sum_cost"], 0, '', ' ') : "")?></td>
		<td><?=($row["type"] ? "<a href='#' ".($row["type"] == "A" ? "class='add_arrival' PA_ID='{$row["ID"]}'" : "class='add_return' PR_ID='{$row["ID"]}'")." title='Редактировать'><i class='fa fa-pencil-alt fa-lg'></i></a>" : "")?></td>
	</tr>
	<?
}
?>
		<tr class="total">
			<td></td>
			<td>Итог:</td>
			<td><b><?=$pallets_shipment?></b></td>
			<td><b><?=$pr_cnt?></b></td>
			<td><b><?=$pr_reject?></b></td>
			<td><b><?=$pr_wrong_format?></b></td>
			<td><b><?=$pr_good?></b></td>
			<td></td>
			<td><?=(isset($sum_cost) ? number_format($sum_cost, 0, '', ' ') : "")?></td>
			<td></td>
		</tr>
	</tbody>
</table>

<div>
	<table style="font-size: 1.5em;">
		<thead>
			<tr>
				<th>Кол-во поддонов на производстве</th>
				<th>Долг клиентов</th>
			</tr>
		</thead>
		<tbody>
			<?
			// Узнаем актуальную стоимость поддона
			$query = "
				SELECT PA.pallet_cost
				FROM pallet__Arrival PA
				WHERE PA.pallet_cost > 0
				ORDER BY PA.pa_date DESC, PA.PA_ID DESC
				LIMIT 1
			";
			$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			$row = mysqli_fetch_array($res);
			$actual_pallet_cost = $row["pallet_cost"];

			// Узнаем баланс поддонов
			$query = "
				SELECT PN.pn_balance
				FROM pallet__Name PN
				WHERE PN.PN_ID = 1
			";
			$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			while( $row = mysqli_fetch_array($res) ) {
				?>
					<tr>
						<td style="text-align: center;"><b><?=$row["pn_balance"]?></b></td>
						<td style="text-align: center;">
							<?
							$query = "
								SELECT CB.brand
									,CB.pallet_balance
								FROM ClientBrand CB
							";
							$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
							while( $subrow = mysqli_fetch_array($subres) ) {
								echo "<p><span style='text-decoration: underline;'>{$subrow["brand"]}</span> <b>{$subrow["pallet_balance"]}</b> шт, на сумму <b>".number_format(( $subrow["pallet_balance"] * $actual_pallet_cost ), 0, '', ' ')."</b> руб.</p>";
							}
							?>
						</td>
					</tr>
				<?
			}
			?>
		</tbody>
	</table>
</div>

<div id="pallet_arrival_btn" class="add_arrival" title="Приобретение поддонов"><i class="fas fa-2x fa-plus"></i></div>
<div id="pallet_return_btn" class="add_return" title="Возврат поддонов"><i class="fas fa-2x fa-undo-alt"></i></div>

<?
include "footer.php";
?>
