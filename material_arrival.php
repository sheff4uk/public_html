<?
include "config.php";
$title = 'Приемка сырья';
include "header.php";
include "./forms/material_arrival_form.php";

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

<!--Фильтр-->
<div id="filter">
	<h3>Фильтр</h3>
	<form method="get" style="position: relative;">
		<a href="/material_arrival.php" style="position: absolute; top: 10px; right: 10px;" class="button">Сброс</a>

		<div class="nowrap" style="margin-bottom: 10px;">
			<span style="display: inline-block; width: 200px;">Дата приемки между:</span>
			<input name="date_from" type="date" value="<?=$_GET["date_from"]?>" class="<?=$_GET["date_from"] ? "filtered" : ""?>">
			<input name="date_to" type="date" value="<?=$_GET["date_to"]?>" class="<?=$_GET["date_to"] ? "filtered" : ""?>">
			<i class="fas fa-question-circle" title="По умолчанию устанавливаются последние 7 дней."></i>
		</div>

		<div class="nowrap" style="display: inline-block; margin-bottom: 10px; margin-right: 30px;">
			<span>Наименование продукции:</span>
			<input type="text" name="MN" value="<?=$_GET["MN"]?>" class="<?=$_GET["MN"] ? "filtered" : ""?>">
		</div>

		<div class="nowrap" style="display: inline-block; margin-bottom: 10px; margin-right: 30px;">
			<span>Поставщик:</span>
			<input type="text" name="S" value="<?=$_GET["S"]?>" class="<?=$_GET["S"] ? "filtered" : ""?>">
		</div>

		<div class="nowrap" style="display: inline-block; margin-bottom: 10px; margin-right: 30px;">
			<span>№ттн, №тн:</span>
			<input type="text" name="IN" value="<?=$_GET["IN"]?>" class="<?=$_GET["IN"] ? "filtered" : ""?>">
		</div>

		<div class="nowrap" style="display: inline-block; margin-bottom: 10px; margin-right: 30px;">
			<span>№ автомобиля:</span>
			<input type="text" name="CN" value="<?=$_GET["CN"]?>" class="<?=$_GET["CN"] ? "filtered" : ""?>">
		</div>

		<div class="nowrap" style="display: inline-block; margin-bottom: 10px; margin-right: 30px;">
			<span>№ партии:</span>
			<input type="text" name="BN" value="<?=$_GET["BN"]?>" class="<?=$_GET["BN"] ? "filtered" : ""?>">
		</div>

		<div class="nowrap" style="display: inline-block; margin-bottom: 10px; margin-right: 30px;">
			<span>№ сертификата качества:</span>
			<input type="text" name="CEN" value="<?=$_GET["CEN"]?>" class="<?=$_GET["CEN"] ? "filtered" : ""?>">
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
	});
</script>

<table class="main_table">
	<thead>
		<tr>
			<th>Дата приемки</th>
			<th>Наименование продукции</th>
			<th>Поставщик</th>
			<th>№ттн, №тн</th>
			<th>№ автомобиля</th>
			<th>№ партии</th>
			<th>№ сертификата качества</th>
			<th>Кол-во, т</th>
			<th></th>
		</tr>
	</thead>
	<tbody style="text-align: center;">
		<?
		$query = "
			SELECT MA.MA_ID
				,DATE_FORMAT(MA.ma_date,'%d.%m.%Y') ma_date_format
				,MA.material_name
				,MA.supplier
				,MA.invoice_number
				,MA.car_number
				,MA.batch_number
				,MA.certificate_number
				,MA.ma_cnt
			FROM material__Arrival MA
			WHERE 1
				".($_GET["date_from"] ? "AND MA.ma_date >= '{$_GET["date_from"]}'" : "")."
				".($_GET["date_to"] ? "AND MA.ma_date <= '{$_GET["date_to"]}'" : "")."
				".($_GET["MN"] ? "AND MA.material_name LIKE '%{$_GET["MN"]}%'" : "")."
				".($_GET["S"] ? "AND MA.supplier LIKE '%{$_GET["S"]}%'" : "")."
				".($_GET["IN"] ? "AND MA.invoice_number LIKE '%{$_GET["IN"]}%'" : "")."
				".($_GET["CN"] ? "AND MA.car_number LIKE '%{$_GET["CN"]}%'" : "")."
				".($_GET["BN"] ? "AND MA.batch_number LIKE '%{$_GET["BN"]}%'" : "")."
				".($_GET["CEN"] ? "AND MA.certificate_number LIKE '%{$_GET["CEN"]}%'" : "")."
			ORDER BY MA.ma_date, MA.MA_ID
		";
		$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		while( $row = mysqli_fetch_array($res) ) {
			$ma_cnt += $row["ma_cnt"];
			?>
			<tr id="<?=$row["MA_ID"]?>">
				<td><?=$row["ma_date_format"]?></td>
				<td><?=$row["material_name"]?></td>
				<td><?=$row["supplier"]?></td>
				<td><?=$row["invoice_number"]?></td>
				<td><?=$row["car_number"]?></td>
				<td><?=$row["batch_number"]?></td>
				<td><?=$row["certificate_number"]?></td>
				<td><?=$row["ma_cnt"]?></td>
				<td><a href="#" class="add_material_arrival" MA_ID="<?=$row["MA_ID"]?>" title="Редактировать"><i class="fa fa-pencil-alt fa-lg"></i></a></td>
			</tr>
			<?
		}
		?>

		<tr class="total">
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td>Итог:</td>
			<td><?=$ma_cnt?></td>
			<td></td>
		</tr>
	</tbody>
</table>

<div id="add_btn" class="add_material_arrival" title="Внести данные приемки сырья"></div>

<?
include "footer.php";
?>
