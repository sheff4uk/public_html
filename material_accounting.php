<?
include "config.php";
$title = 'Приемка сырья';
include "header.php";

// Проверка прав на доступ к экрану
if( !in_array('material_accounting', $Rights) ) {
	header($_SERVER['SERVER_PROTOCOL'].' 403 Forbidden');
	die('Недостаточно прав для совершения операции');
}

$location = $_SERVER['REQUEST_URI'];
include "./forms/material_accounting_form.php";

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
		<a href="/material_accounting.php" style="position: absolute; top: 10px; right: 10px;" class="button">Сброс</a>

		<div class="nowrap" style="display: inline-block; margin-bottom: 10px; margin-right: 30px;">
			<span style="display: inline-block; width: 200px;">Дата приемки между:</span>
			<input name="date_from" type="date" value="<?=$_GET["date_from"]?>" class="<?=$_GET["date_from"] ? "filtered" : ""?>">
			<input name="date_to" type="date" value="<?=$_GET["date_to"]?>" class="<?=$_GET["date_to"] ? "filtered" : ""?>">
			<i class="fas fa-question-circle" title="По умолчанию устанавливаются последние 7 дней."></i>
		</div>

		<div class="nowrap" style="display: inline-block; margin-bottom: 10px; margin-right: 30px;">
			<span>Участок:</span>
			<select name="F_ID" class="<?=$_GET["F_ID"] ? "filtered" : ""?>">
				<option value=""></option>
				<?
				$query = "
					SELECT F_ID
						,f_name
					FROM factory
					ORDER BY F_ID
				";
				$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
				while( $row = mysqli_fetch_array($res) ) {
					$selected = ($row["F_ID"] == $_GET["F_ID"]) ? "selected" : "";
					echo "<option value='{$row["F_ID"]}' {$selected}>{$row["f_name"]}</option>";
				}
				?>
			</select>
		</div>

		<div class="nowrap" style="display: inline-block; margin-bottom: 10px; margin-right: 30px;">
			<span><a href="#" id="add_material_list">Наименование продукции:</a></span>
			<select name="MN" class="<?=$_GET["MN"] ? "filtered" : ""?>">
				<option value=""></option>
				<?
				$query = "
					SELECT MN.MN_ID, MN.material_name
					FROM material__Name MN
					ORDER BY MN.MN_ID
				";
				$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
				while( $row = mysqli_fetch_array($res) ) {
					$selected = ($row["MN_ID"] == $_GET["MN"]) ? "selected" : "";
					echo "<option value='{$row["MN_ID"]}' {$selected}>{$row["material_name"]}</option>";
				}
				?>
			</select>
		</div>

		<div class="nowrap" style="display: inline-block; margin-bottom: 10px; margin-right: 30px;">
			<span><a href="#" id="add_supplier_list">Поставщик:</a></span>
			<select name="MS" class="<?=$_GET["MS"] ? "filtered" : ""?>">
				<option value=""></option>
				<?
				$query = "
					SELECT MS.MS_ID, MS.supplier
					FROM material__Supplier MS
					ORDER BY MS.MS_ID
				";
				$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
				while( $row = mysqli_fetch_array($res) ) {
					$selected = ($row["MS_ID"] == $_GET["MS"]) ? "selected" : "";
					echo "<option value='{$row["MS_ID"]}' {$selected}>{$row["supplier"]}</option>";
				}
				?>
			</select>
		</div>

		<div class="nowrap" style="display: inline-block; margin-bottom: 10px; margin-right: 30px;">
			<span><a href="#" id="add_carrier_list">Перевозчик:</a></span>
			<select name="MC" class="<?=$_GET["MC"] ? "filtered" : ""?>">
				<option value=""></option>
				<?
				$query = "
					SELECT MC.MC_ID, MC.carrier
					FROM material__Carrier MC
					ORDER BY MC.MC_ID
				";
				$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
				while( $row = mysqli_fetch_array($res) ) {
					$selected = ($row["MC_ID"] == $_GET["MC"]) ? "selected" : "";
					echo "<option value='{$row["MC_ID"]}' {$selected}>{$row["carrier"]}</option>";
				}
				?>
			</select>
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
			<th>Участок</th>
			<th>Наименование продукции</th>
			<th>Поставщик</th>
			<th>Перевозчик</th>
			<th>№ттн, №тн</th>
			<th>№ автомобиля</th>
			<th>№ партии</th>
			<th>№ сертификата качества</th>
			<th>Кол-во</th>
			<th>Стоимость</th>
			<th></th>
		</tr>
	</thead>
	<tbody style="text-align: center;">
		<?
		$query = "
			SELECT MA.MA_ID
				,DATE_FORMAT(MA.ma_date,'%d.%m.%Y') ma_date_format
				,F.f_name
				,MN.material_name
				,MS.supplier
				,MC.carrier
				,MA.invoice_number
				,MA.car_number
				,MA.batch_number
				,MA.certificate_number
				,MA.ma_cnt
				,MA.ma_cost
				,IF(MA.ma_date < CURDATE() - INTERVAL 1 MONTH, 0, 1) editable
			FROM material__Arrival MA
			JOIN factory F ON F.F_ID = MA.F_ID
			JOIN material__Name MN ON MN.MN_ID = MA.MN_ID
			JOIN material__Supplier MS ON MS.MS_ID = MA.MS_ID
			LEFT JOIN material__Carrier MC ON MC.MC_ID = MA.MC_ID
			WHERE 1
				".($_GET["F_ID"] ? "AND MA.F_ID = '{$_GET["F_ID"]}'" : "")."
				".($_GET["date_from"] ? "AND MA.ma_date >= '{$_GET["date_from"]}'" : "")."
				".($_GET["date_to"] ? "AND MA.ma_date <= '{$_GET["date_to"]}'" : "")."
				".($_GET["MN"] ? "AND MA.MN_ID = {$_GET["MN"]}" : "")."
				".($_GET["MS"] ? "AND MA.MS_ID = {$_GET["MS"]}" : "")."
				".($_GET["MC"] ? "AND MA.MC_ID = {$_GET["MC"]}" : "")."
				".($_GET["IN"] ? "AND MA.invoice_number LIKE '%{$_GET["IN"]}%'" : "")."
				".($_GET["CN"] ? "AND MA.car_number LIKE '%{$_GET["CN"]}%'" : "")."
				".($_GET["BN"] ? "AND MA.batch_number LIKE '%{$_GET["BN"]}%'" : "")."
				".($_GET["CEN"] ? "AND MA.certificate_number LIKE '%{$_GET["CEN"]}%'" : "")."
			ORDER BY MA.ma_date, MA.MA_ID
		";
		$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		while( $row = mysqli_fetch_array($res) ) {
			$ma_cnt += $row["ma_cnt"];
			$ma_cost += $row["ma_cost"];
			?>
			<tr id="<?=$row["MA_ID"]?>">
				<td><?=$row["ma_date_format"]?></td>
				<td><?=$row["f_name"]?></td>
				<td><span class="nowrap"><?=$row["material_name"]?></span></td>
				<td><span class="nowrap"><?=$row["supplier"]?></span></td>
				<td><span class="nowrap"><?=$row["carrier"]?></span></td>
				<td><?=$row["invoice_number"]?></td>
				<td><?=$row["car_number"]?></td>
				<td><?=$row["batch_number"]?></td>
				<td><?=$row["certificate_number"]?></td>
				<td><?=$row["ma_cnt"]?></td>
				<td><?=$row["ma_cost"]?></td>
				<td>
				<?
					if( $row["editable"] ) {
						echo "<a href='#' class='add_material_arrival' MA_ID='{$row["MA_ID"]}' title='Редактировать'><i class='fa fa-pencil-alt fa-lg'></i></a>\n";
					}
				?>
				</td>
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
			<td></td>
			<td></td>
			<td>Итог:</td>
			<td><?=$ma_cnt?></td>
			<td><?=$ma_cost?></td>
			<td></td>
		</tr>
	</tbody>
</table>

<div id="add_btn" class="add_material_arrival" title="Внести данные приемки сырья"></div>

<?
include "footer.php";
?>
