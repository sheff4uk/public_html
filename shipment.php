<?
include "config.php";
$title = 'Отгрузка';
include "header.php";

// Проверка прав на доступ к экрану
if( !in_array('shipment', $Rights) ) {
	header($_SERVER['SERVER_PROTOCOL'].' 403 Forbidden');
	die('Недостаточно прав для совершения операции');
}

include "./forms/shipment_form.php";

// Если в фильтре не установлен период, показываем последние 7 дней
if( !$_GET["date_from"] ) {
	$date = date_create('-6 days');
	$_GET["date_from"] = date_format($date, 'Y-m-d');
}
if( !$_GET["date_to"] ) {
	$date = date_create('-0 days');
	$_GET["date_to"] = date_format($date, 'Y-m-d');
}
?>

<!--Фильтр-->
<div id="filter">
	<h3>Фильтр</h3>
	<form method="get" style="position: relative;">
		<a href="/shipment.php" style="position: absolute; top: 10px; right: 10px;" class="button">Сброс</a>

		<div class="nowrap" style="margin-bottom: 10px;">
			<span style="display: inline-block; width: 200px;">Дата отгрузки между:</span>
			<input name="date_from" type="date" value="<?=$_GET["date_from"]?>" class="<?=$_GET["date_from"] ? "filtered" : ""?>">
			<input name="date_to" type="date" value="<?=$_GET["date_to"]?>" class="<?=$_GET["date_to"] ? "filtered" : ""?>">
			<i class="fas fa-question-circle" title="По умолчанию устанавливаются последние 7 дней."></i>
		</div>

		<div class="nowrap" style="display: inline-block; margin-bottom: 10px; margin-right: 30px;">
			<span>Комплект противовесов:</span>
			<select name="CWP_ID" class="<?=$_GET["CWP_ID"] ? "filtered" : ""?>" style="width: 200px;">
				<option value=""></option>
				<?
				$query = "
					SELECT CWP.CWP_ID
						,IFNULL(CW.item, CWP.cwp_name) item
						,CWP.in_pallet
					FROM CounterWeightPallet CWP
					LEFT JOIN CounterWeight CW ON CW.CW_ID = CWP.CW_ID
					ORDER BY CWP.CWP_ID
				";
				$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
				while( $row = mysqli_fetch_array($res) ) {
					$selected = ($row["CWP_ID"] == $_GET["CWP_ID"]) ? "selected" : "";
					echo "<option value='{$row["CWP_ID"]}' {$selected}>{$row["item"]} ({$row["in_pallet"]}шт)</option>";
				}
				?>
			</select>
		</div>

		<div class="nowrap" style="display: inline-block; margin-bottom: 10px; margin-right: 30px;">
			<span>Продукт:</span>
			<select name="product" class="<?=$_GET["product"] ? "filtered" : ""?>" style="width: 200px;">
				<option value=""></option>
				<?
				$query = "
					SELECT CWP.product
					FROM CounterWeightPallet CWP
					GROUP BY CWP.product
					ORDER BY CWP.product
				";
				$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
				while( $row = mysqli_fetch_array($res) ) {
					$selected = ($row["product"] == $_GET["product"]) ? "selected" : "";
					echo "<option value='{$row["product"]}' {$selected}>{$row["product"]}</option>";
				}
				?>
			</select>
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
			<th>Комплект противовесов</th>
			<th>Поддон</th>
			<th>Паллетов</th>
			<th>Деталей в паллете</th>
			<th>Всего деталей</th>
			<th></th>
		</tr>
	</thead>
	<tbody style="text-align: center;">

<?
$query = "
	SELECT SUM(1) cnt
		,DATE_FORMAT(LS.ls_date, '%d.%m.%y') ls_date_format
		,DATE_FORMAT(LS.ls_date, '%W') ls_date_weekday
		,LS.ls_date
		,SUM(LS.pallets) pallets
		,SUM(LS.pallets * LS.in_pallet) details
	FROM list__Shipment LS
	JOIN CounterWeightPallet CWP ON CWP.CWP_ID = LS.CWP_ID
	WHERE 1
		".($_GET["date_from"] ? "AND LS.ls_date >= '{$_GET["date_from"]}'" : "")."
		".($_GET["date_to"] ? "AND LS.ls_date <= '{$_GET["date_to"]}'" : "")."
		".($_GET["CWP_ID"] ? "AND LS.CWP_ID={$_GET["CWP_ID"]}" : "")."
		".($_GET["CB_ID"] ? "AND CWP.CB_ID = {$_GET["CB_ID"]}" : "")."
		".($_GET["product"] ? "AND CWP.product LIKE '{$_GET["product"]}'" : "")."
	GROUP BY LS.ls_date
	ORDER BY LS.ls_date, LS.CWP_ID
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	$cnt = $row["cnt"];

	$query = "
		SELECT LS.LS_ID
			,LS.ls_date
			,CONCAT(IFNULL(CW.item, CWP.cwp_name), ' (', CWP.in_pallet, 'шт)') item
			,LS.CWP_ID
			,LS.pallets
			,LS.in_pallet
			,LS.pallets * LS.in_pallet details
			,PN.pallet_name
		FROM list__Shipment LS
		JOIN pallet__Name PN ON PN.PN_ID = LS.PN_ID
		JOIN CounterWeightPallet CWP ON CWP.CWP_ID = LS.CWP_ID
		LEFT JOIN CounterWeight CW ON CW.CW_ID = CWP.CW_ID
		WHERE 1
			AND LS.ls_date = '{$row["ls_date"]}'
			".($_GET["CWP_ID"] ? "AND LS.CWP_ID={$_GET["CWP_ID"]}" : "")."
			".($_GET["CB_ID"] ? "AND CWP.CB_ID = {$_GET["CB_ID"]}" : "")."
			".($_GET["product"] ? "AND CWP.product LIKE '{$_GET["product"]}'" : "")."
		ORDER BY LS.ls_date DESC, LS.CWP_ID
	";
	$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $subrow = mysqli_fetch_array($subres) ) {
		$pallets += $subrow["pallets"];
		$details += $subrow["details"];

		// Выводим общую ячейку с датой
		if( $cnt ) {
			$cnt++;
			echo "<tr id='{$subrow["LS_ID"]}' style='border-top: 2px solid #333;'>";
			echo "<td rowspan='{$cnt}' style='background-color: rgba(0, 0, 0, 0.2);'>{$row["ls_date_weekday"]}<br>{$row["ls_date_format"]}</td>";
			$cnt = 0;
		}
		else {
			echo "<tr id='{$subrow["LS_ID"]}'>";
		}

		?>
			<td><?=$subrow["item"]?></td>
			<td><?=$subrow["pallet_name"]?></td>
			<td><?=$subrow["pallets"]?></td>
			<td><?=$subrow["in_pallet"]?></td>
			<td><?=$subrow["details"]?></td>
			<td>
				<a href='#' class='add_ps' LS_ID='<?=$subrow["LS_ID"]?>' title='Изменить данные плана отгрузки'><i class='fa fa-pencil-alt fa-lg'></i></a>
			</td>
		</tr>
		<?

	}
?>
		<tr class="summary">
			<td></td>
			<td>Итог:</td>
			<td><?=$row["pallets"]?></td>
			<td></td>
			<td><?=$row["details"]?></td>
			<td></td>
		</tr>
<?
}
?>
		<tr class="total">
			<td></td>
			<td></td>
			<td>Итог:</td>
			<td><?=$pallets?></td>
			<td></td>
			<td><?=$details?></td>
			<td></td>
		</tr>
	</tbody>
</table>

<div id="add_btn" class="add_ps" ls_date="<?=$_GET["ls_date"]?>" title="Внести данные плана отгрузки"></div>

<script>
	$(function() {
		$(".print").printPage();
	});
</script>

<?
include "footer.php";
?>
