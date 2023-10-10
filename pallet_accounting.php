<?
include "config.php";
$title = 'Учет поддонов';
include "header.php";

// Проверка прав на доступ к экрану
if( !in_array('pallet_accounting', $Rights) ) {
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

include "./forms/pallet_accounting_form.php";
?>

<style>
	#incoming_btn {
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
	#disposal_btn {
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
	#incoming_btn:hover, #disposal_btn:hover {
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
			<span>Поддон:</span>
			<select name="PN_ID" class="<?=$_GET["PN_ID"] ? "filtered" : ""?>" style="width: 100px;">
				<option value=""></option>
				<?
				$query = "
					SELECT PN.PN_ID
						,PN.pallet_name
					FROM pallet__Name PN
					ORDER BY PN.PN_ID
				";
				$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
				while( $row = mysqli_fetch_array($res) ) {
					$selected = ($row["PN_ID"] == $_GET["PN_ID"]) ? "selected" : "";
					echo "<option value='{$row["PN_ID"]}' {$selected}>{$row["pallet_name"]}</option>";
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
			<th>Субъект</th>
			<th>Поддон</th>
			<th>Отгружено поддонов</th>
			<th>Списано поддонов</th>
			<th>Поступило поддонов</th>
			<th>Из них бракованных</th>
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
		,PN.pallet_name
		,NULL pallets_shipment
		,NULL pd_cnt
		,PR.pr_cnt
		,PR.pr_reject
		,NULL pallet_cost
		,NULL sum_cost
		,PR.pr_date date
		,PR.CB_ID
		,NULL week
		,NULL LS_ID
	FROM pallet__Return PR
	JOIN ClientBrand CB ON CB.CB_ID = PR.CB_ID
	JOIN pallet__Name PN ON PN.PN_ID = PR.PN_ID
	WHERE 1
		".($_GET["date_from"] ? "AND PR.pr_date >= '{$_GET["date_from"]}'" : "")."
		".($_GET["date_to"] ? "AND PR.pr_date <= '{$_GET["date_to"]}'" : "")."
		".($_GET["CB_ID"] ? "AND PR.CB_ID = {$_GET["CB_ID"]}" : "")."
		".($_GET["PN_ID"] ? "AND PR.PN_ID = {$_GET["PN_ID"]}" : "")."
		".($_GET["PS_ID"] ? "AND 0" : "")."

	UNION

	SELECT 'A'
		,PA.PA_ID
		,DATE_FORMAT(PA.pa_date, '%d.%m.%Y')
		,PS.pallet_supplier
		,PN.pallet_name
		,NULL pallets_shipment
		,NULL
		,PA.pa_cnt
		,PA.pa_reject
		,PA.pallet_cost
		,PA.pallet_cost * PA.pa_cnt
		,PA.pa_date
		,NULL
		,NULL
		,NULL
	FROM pallet__Arrival PA
	JOIN pallet__Supplier PS ON PS.PS_ID = PA.PS_ID
	JOIN pallet__Name PN ON PN.PN_ID = PS.PN_ID
	WHERE 1
		".($_GET["date_from"] ? "AND PA.pa_date >= '{$_GET["date_from"]}'" : "")."
		".($_GET["date_to"] ? "AND PA.pa_date <= '{$_GET["date_to"]}'" : "")."
		".($_GET["CB_ID"] ? "AND 0" : "")."
		".($_GET["PN_ID"] ? "AND PS.PN_ID = {$_GET["PN_ID"]}" : "")."
		".($_GET["PS_ID"] ? "AND PS.PS_ID = {$_GET["PS_ID"]}" : "")."


	UNION

	SELECT 'D'
		,PD.PD_ID
		,DATE_FORMAT(PD.pd_date, '%d.%m.%Y')
		,CB.brand
		,PN.pallet_name
		,NULL pallets_shipment
		,PD.pd_cnt
		,NULL
		,NULL
		,NULL
		,NULL
		,PD.pd_date
		,NULL
		,NULL
		,NULL
	FROM pallet__Disposal PD
	JOIN pallet__Name PN ON PN.PN_ID = PD.PN_ID
	LEFT JOIN ClientBrand CB ON CB.CB_ID = PD.CB_ID
	WHERE PD.pd_cnt > 0
		".($_GET["date_from"] ? "AND PD.pd_date >= '{$_GET["date_from"]}'" : "")."
		".($_GET["date_to"] ? "AND PD.pd_date <= '{$_GET["date_to"]}'" : "")."
		".($_GET["CB_ID"] ? "AND CB.CB_ID = {$_GET["CB_ID"]}" : "")."
		".($_GET["PN_ID"] ? "AND PD.PN_ID = {$_GET["PN_ID"]}" : "")."
		".($_GET["PS_ID"] ? "AND 0" : "")."

	UNION

	SELECT 'F'
		,PD.PD_ID
		,DATE_FORMAT(PD.pd_date, '%d.%m.%Y')
		,'<b>Ремонт</b>'
		,PN.pallet_name
		,NULL
		,NULL
		,PD.pd_cnt * -1
		,NULL
		,NULL
		,NULL
		,PD.pd_date
		,NULL
		,NULL
		,NULL
	FROM pallet__Disposal PD
	JOIN pallet__Name PN ON PN.PN_ID = PD.PN_ID
	WHERE PD.pd_cnt < 0
		".($_GET["date_from"] ? "AND PD.pd_date >= '{$_GET["date_from"]}'" : "")."
		".($_GET["date_to"] ? "AND PD.pd_date <= '{$_GET["date_to"]}'" : "")."
		".($_GET["CB_ID"] ? "AND 0" : "")."
		".($_GET["PN_ID"] ? "AND PD.PN_ID = {$_GET["PN_ID"]}" : "")."
		".($_GET["PS_ID"] ? "AND 0" : "")."

	UNION

	SELECT NULL
		,NULL
		,DATE_FORMAT(LS.ls_date, '%d.%m.%Y')
		,CB.brand
		,PN.pallet_name
		,LS.pallets
		,NULL
		,NULL
		,NULL
		,NULL
		,NULL
		,LS.ls_date
		,CWP.CB_ID
		,YEARWEEK(LS.ls_date, 1)
		,LS.LS_ID
	FROM list__Shipment LS
	JOIN CounterWeightPallet CWP ON CWP.CWP_ID = LS.CWP_ID
	JOIN ClientBrand CB ON CB.CB_ID = CWP.CB_ID
	JOIN pallet__Name PN ON PN.PN_ID = LS.PN_ID
	WHERE 1
		".($_GET["date_from"] ? "AND LS.ls_date >= '{$_GET["date_from"]}'" : "")."
		".($_GET["date_to"] ? "AND LS.ls_date <= '{$_GET["date_to"]}'" : "")."
		".($_GET["CB_ID"] ? "AND CB.CB_ID = {$_GET["CB_ID"]}" : "")."
		".($_GET["PN_ID"] ? "AND LS.PN_ID = {$_GET["PN_ID"]}" : "")."
		".($_GET["PS_ID"] ? "AND 0" : "")."
	ORDER BY date, type, CB_ID
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	$pallets_shipment += $row["pallets_shipment"];
	$pd_cnt += $row["pd_cnt"];
	$pr_cnt += $row["pr_cnt"];
	$pr_reject += $row["pr_reject"];
	$pa_cnt += $row["pa_cnt"];
	$pa_reject += $row["pa_reject"];
	$sum_cost += $row["sum_cost"];
	?>
	<tr id="<?=$row["type"]?><?=$row["ID"]?>">
		<td><?=$row["date_format"]?></td>
		<td><span class="nowrap"><?=$row["brand"]?></span></td>
		<td><?=$row["pallet_name"]?></td>
		<td><b><a href="shipment.php?week=<?=$row["week"]?>&CB_ID=<?=$row["CB_ID"]?>#<?=$row["LS_ID"]?>" target="_blank"><?=$row["pallets_shipment"]?></a></b></td>
		<td><b><?=$row["pd_cnt"]?></b></td>
		<td><b><?=$row["pr_cnt"]?></b></td>
		<td><b style="color: red;"><?=$row["pr_reject"]?></b></td>
		<td><?=(isset($row["pallet_cost"]) ? number_format($row["pallet_cost"], 0, '', ' ') : "")?></td>
		<td><?=(isset($row["sum_cost"]) ? number_format($row["sum_cost"], 0, '', ' ') : "")?></td>
		<td><?=($row["type"] ? "<a href='#' ".($row["type"] == "D" ? "class='add_disposal' PD_ID='{$row["ID"]}'" : "class='add_incoming' incoming_ID='{$row["ID"]}' type='{$row["type"]}'")."><i class='fa fa-pencil-alt fa-lg'></i></a>" : "")?></td>
	</tr>
	<?
}
	if( isset($_GET["CB_ID"]) && isset($_GET["PN_ID"]) ) {
		if( $_GET["CB_ID"] && $_GET["PN_ID"] && $pallets_shipment ) {
			// Высчитываем процент недостачи поддонов
			$shortfall = round(($pallets_shipment - $pr_cnt + $pr_reject + $pd_cnt) / $pallets_shipment * 100);
			$shortfall = "Недостача: ".($shortfall >= 30 ? "<n style='color: red;'>{$shortfall}%</>" : "<n>{$shortfall}%</n>");
		}
	}
?>
		<tr class="total" style="font-size: 1.2em;">
			<td colspan="2"><?=$shortfall?></td>
			<td>Итог:</td>
			<td><?=$pallets_shipment?></td>
			<td><?=$pd_cnt?></td>
			<td><?=$pr_cnt?></td>
			<td><?=$pr_reject?></td>
			<td></td>
			<td><?=(isset($sum_cost) ? number_format($sum_cost, 0, '', ' ') : "")?></td>
			<td></td>
		</tr>
	</tbody>
</table>

<div>
	<h2>Поддоны на производстве:</h2>
	<table style="font-size: 1.5em;">
		<thead>
			<tr>
				<th>Наименование</th>
				<th>Кол-во</th>
			</tr>
		</thead>
		<tbody>
			<?
			$query = "
				SELECT PN.PN_ID
					,PN.pallet_name
					,PN.pn_balance
				FROM pallet__Name PN
				ORDER BY PN.PN_ID
			";
			$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			while( $row = mysqli_fetch_array($res) ) {
				?>
					<tr>
						<td style="text-align: center;"><?=$row["pallet_name"]?></td>
						<td style="text-align: center;"><?=$row["pn_balance"]?></td>
					</tr>
				<?
			}
			?>
		</tbody>
	</table>
</div>

<div>
	<h2>Поддоны у клиентов:</h2>
	<table style="font-size: 1.5em;">
		<thead>
			<tr>
				<th>Клиент</th>
				<th>Наименование</th>
				<th>Кол-во</th>
			</tr>
		</thead>
		<tbody>
			<?
			$query = "
				SELECT PCB.PN_ID
					,PN.pallet_name
					,CB.brand
					,PCB.pallet_balance
				FROM pallet__ClientBalance PCB
				JOIN pallet__Name PN ON PN.PN_ID = PCB.PN_ID
				JOIN ClientBrand CB ON CB.CB_ID = PCB.CB_ID
				ORDER BY PCB.CB_ID, PCB.PN_ID
			";
			$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			while( $row = mysqli_fetch_array($res) ) {
				// Узнаем актуальную стоимость поддона
				$query = "
					SELECT PA.pallet_cost
					FROM pallet__Arrival PA
					JOIN pallet__Supplier PS ON PS.PS_ID = PA.PS_ID AND PS.PN_ID = {$row["PN_ID"]}
					WHERE PA.pallet_cost > 0
					ORDER BY PA.pa_date DESC, PA.PA_ID DESC
					LIMIT 1
				";
				$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
				$subrow = mysqli_fetch_array($subres);
				$actual_pallet_cost = $subrow["pallet_cost"];
				?>
					<tr>
						<td style="text-align: center;"><?=$row["brand"]?></td>
						<td style="text-align: center;"><?=$row["pallet_name"]?></td>
						<td style="text-align: center;"><?=$row["pallet_balance"]." шт, на сумму <b>".number_format(( $row["pallet_balance"] * $actual_pallet_cost ), 0, '', ' ')."</b> руб."?></td>
					</tr>
				<?
			}
			?>
		</tbody>
	</table>
</div>

<div id="incoming_btn" class="add_incoming" title="Поступление поддонов"><i class="fas fa-2x fa-plus"></i></div>
<div id="disposal_btn" class="add_disposal" title="Списание поддонов"><i class="fas fa-2x fa-minus"></i></div>

<?
include "footer.php";
?>
