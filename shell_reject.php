<?
include "config.php";
$title = 'Списание форм';
include "header.php";
include "./forms/shell_reject_form.php";

// Если в фильтре не установлена дата, показываем сегодня
if( !$_GET["date"] ) {
	$date = new DateTime();
	$_GET["date"] = date_format($date, 'Y-m-d');
}
?>

<style>
	#shell_report_btn {
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
	#shell_report_btn:hover {
		opacity: 1;
	}
</style>

<!--Фильтр-->
<div id="filter">
	<h3>Фильтр</h3>
	<form method="get" style="position: relative;">
		<a href="/shell_reject.php" style="position: absolute; top: 10px; right: 10px;" class="button">Сброс</a>

		<div class="nowrap" style="margin-bottom: 10px;">
			<span style="display: inline-block; width: 100px;">Дата:</span>
			<input name="date" type="date" value="<?=$_GET["date"]?>" class="<?=$_GET["date"] ? "filtered" : ""?>" onchange="this.form.submit();">
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
			<th>Кол-во бракованных форм</th>
			<th>Отслоения</th>
			<th>Трещины</th>
			<th>Сколы</th>
			<th></th>
		</tr>
	</thead>
	<tbody style="text-align: center;">

<?
$query = "
	SELECT SR.SR_ID
		,DATE_FORMAT(SR.sr_date, '%d.%m.%Y') sr_date_format
		,CW.item
		,SR.sr_cnt
		,SR.exfolation
		,SR.crack
		,SR.chipped
	FROM ShellReject SR
	JOIN CounterWeight CW ON CW.CW_ID = SR.CW_ID
	WHERE 1
		".($_GET["date"] ? "AND SR.sr_date = '{$_GET["date"]}'" : "")."
		".($_GET["CB_ID"] ? "AND SR.CW_ID IN (SELECT CW_ID FROM CounterWeight WHERE CB_ID = {$_GET["CB_ID"]})" : "")."
	ORDER BY SR.sr_date DESC, SR.CW_ID
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	$sr_cnt += $row["sr_cnt"];
	$exfolation += $row["exfolation"];
	$crack += $row["crack"];
	$chipped += $row["chipped"];
	?>
	<tr id="<?=$row["SR_ID"]?>">
		<td><?=$row["sr_date_format"]?></td>
		<td><?=$row["item"]?></td>
		<td><?=$row["sr_cnt"]?></td>
		<td><?=$row["exfolation"]?></td>
		<td><?=$row["crack"]?></td>
		<td><?=$row["chipped"]?></td>
		<td><a href="#" class="add_reject" SR_ID="<?=$row["SR_ID"]?>" title="Редактировать"><i class="fa fa-pencil-alt fa-lg"></i></a></td>
	</tr>
	<?
}
?>
		<tr class="total">
			<td></td>
			<td>Итог:</td>
			<td><?=$sr_cnt?></td>
			<td><?=$exfolation?></td>
			<td><?=$crack?></td>
			<td><?=$chipped?></td>
			<td></td>
		</tr>
	</tbody>
</table>

<div id="shell_report_btn" title="Распечатать отчет"><a href="/printforms/shell_reject_report.php?sr_date=<?=$_GET["date"]?>&CB_ID=<?=$_GET["CB_ID"]?>" class="print" style="color: white;"><i class="fas fa-2x fa-print"></i></a></div>
<div id="add_btn" class="add_reject" sr_date="<?=$_GET["sr_date"]?>" title="Внести данные"></div>

<script>
	$(function() {
		$(".print").printPage();
	});
</script>

<?
include "footer.php";
?>
