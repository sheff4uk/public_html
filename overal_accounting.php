<?
include "config.php";
$title = 'Учет СИЗ';
include "header.php";

// Проверка прав на доступ к экрану
if( !in_array('overal_accounting', $Rights) ) {
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

// Если не выбран участок, берем из сессии
if( !$_GET["F_ID"] ) {
	$_GET["F_ID"] = $_SESSION['F_ID'];
}
$F_ID = $_GET["F_ID"];

// Получаем название участка
$query = "
	SELECT f_name
	FROM factory
	WHERE F_ID = {$F_ID}
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
$row = mysqli_fetch_array($res);
$f_name = $row["f_name"];

include "./forms/overal_accounting_form.php";
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
	#outcoming_btn {
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
	#incoming_btn:hover, #outcoming_btn:hover {
		opacity: 1;
	}
</style>

<!--Фильтр-->
<div id="filter">
	<h3>Фильтр</h3>
	<form method="get" style="position: relative;">
		<a href="/overal_accounting.php" style="position: absolute; top: 10px; right: 10px;" class="button">Сброс</a>

		<div class="nowrap" style="margin-bottom: 10px;">
			<span>Участок:</span>
			<select name="F_ID" class="<?=$_GET["F_ID"] ? "filtered" : ""?>" onchange="this.form.submit()">
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
		
		<div class="nowrap" style="margin-bottom: 10px;">
			<span style="display: inline-block; width: 120px;">Дата между:</span>
			<input name="date_from" type="date" value="<?=$_GET["date_from"]?>" class="<?=$_GET["date_from"] ? "filtered" : ""?>">
			<input name="date_to" type="date" value="<?=$_GET["date_to"]?>" class="<?=$_GET["date_to"] ? "filtered" : ""?>">
			<i class="fas fa-question-circle" title="По умолчанию устанавливаются последние 7 дней."></i>
		</div>

		<div class="nowrap" style="display: inline-block; margin-bottom: 10px; margin-right: 30px;">
			<span>Наименование СИЗ:</span>
			<select name="OI_ID" class="<?=$_GET["OI_ID"] ? "filtered" : ""?>">
				<option value=""></option>
				<?
				$query = "
					SELECT OI.OI_ID, OI.overal
					FROM overal__Item OI
					ORDER BY OI.overal
				";
				$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
				while( $row = mysqli_fetch_array($res) ) {
					$selected = ($row["OI_ID"] == $_GET["OI_ID"]) ? "selected" : "";
					echo "<option value='{$row["OI_ID"]}' {$selected}>{$row["overal"]}</option>";
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
			<th>Наименование СИЗ</th>
			<th>Поступившее кол-во</th>
			<th>Ваданное кол-во</th>
			<th></th>
		</tr>
	</thead>
	<tbody style="text-align: center;">

<?
$query = "
	SELECT OA.OA_ID
		,DATE_FORMAT(OA.oa_date, '%d.%m.%Y') date_format
		,OI.overal
		,IF(OA.oa_cnt > 0, IF(OA.correction, CONCAT(OA.oa_cnt, ' (корректировка)'), OA.oa_cnt), NULL) incoming
		,IF(OA.oa_cnt < 0, IF(OA.correction, CONCAT(ABS(OA.oa_cnt), ' (корректировка)'), ABS(OA.oa_cnt)), NULL) outcoming
	FROM overal__Accounting OA
	JOIN overal__Item OI ON OI.OI_ID = OA.OI_ID
	WHERE OA.oa_cnt <> 0
		AND OA.F_ID = {$F_ID}
		".($_GET["date_from"] ? "AND OA.oa_date >= '{$_GET["date_from"]}'" : "")."
		".($_GET["date_to"] ? "AND OA.oa_date <= '{$_GET["date_to"]}'" : "")."
		".($_GET["OI_ID"] ? "AND OA.OI_ID = {$_GET["OI_ID"]}" : "")."
	ORDER BY OA.oa_date, OI.overal
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	$incoming += $row["incoming"];
	$outcoming += $row["outcoming"];
	?>
	<tr id="<?=$row["OA_ID"]?>">
		<td><?=$row["date_format"]?></td>
		<td><?=$row["overal"]?></td>
		<td><b style="color: green;"><?=$row["incoming"]?></b></td>
		<td><b><?=$row["outcoming"]?></b></td>
		<td><a href='#' <?=($row["incoming"] ? "class='add_incoming'" : "class='add_outcoming'")?> OA_ID='<?=$row["OA_ID"]?>' title='Редактировать'><i class='fa fa-pencil-alt fa-lg'></i></a></td>
	</tr>
	<?
}
?>
		<tr class="total">
			<td></td>
			<td>Итог:</td>
			<td><b><?=$incoming?></b></td>
			<td><b><?=$outcoming?></b></td>
			<td></td>
		</tr>
	</tbody>
</table>

<div>
	<h2><?=$f_name?> баланс СИЗ:</h2>
	<table style="font-size: 1.5em;">
		<thead>
			<tr>
				<th>Наименование СИЗ</th>
				<th>Наличие</th>
			</tr>
		</thead>
		<tbody>
			<?
			$query = "
				SELECT OI.overal
					,OB.oi_balance
				FROM overal__Balance OB
					JOIN overal__Item OI ON OI.OI_ID = OB.OI_ID
				WHERE OB.F_ID = {$F_ID}
					".($_GET["OI_ID"] ? "AND OI.OI_ID = {$_GET["OI_ID"]}" : "")."
				ORDER BY OI.overal
			";
			$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			while( $row = mysqli_fetch_array($res) ) {
				?>
					<tr>
						<td><?=$row["overal"]?></td>
						<td style="text-align: center;"><?=$row["oi_balance"]?></td>
					</tr>
				<?
			}
			?>
		</tbody>
	</table>
</div>

<div id="incoming_btn" class="add_incoming" title="Приход СИЗ"><i class="fas fa-2x fa-plus"></i></div>
<div id="outcoming_btn" class="add_outcoming" title="Выдача СИЗ"><i class="fas fa-2x fa-minus"></i></div>

<?
include "footer.php";
?>
