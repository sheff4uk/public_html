<?
include "config.php";
$title = 'Суточный брак';
include "header.php";

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
		<a href="/daily_reject.php" style="position: absolute; top: 10px; right: 10px;" class="button">Сброс</a>

		<div class="nowrap" style="margin-bottom: 10px;">
			<span style="display: inline-block; width: 200px;">Дата между:</span>
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
	});
</script>

<table class="main_table">
	<thead>
		<tr>
			<th>Дата</th>
			<th>Противовес</th>
			<th>Кол-во брака при расформовке</th>
			<th>% брака при расформовке</th>
			<th>Кол-во брака при упаковке</th>
			<th>% брака при упаковке</th>
			<th>Всего брака</th>
		</tr>
	</thead>
	<tbody style="text-align: center;">

<?
$query = "
	SELECT DATE_FORMAT(OPR.reject_date, '%d.%m.%y') reject_date_format
		,OPR.item
		,IFNULL(SUM(o_reject), 0) `o_reject`
		,IFNULL(SUM(o_details), 0) `o_details`
		,IFNULL(SUM(p_reject), 0) `p_reject`
		,IFNULL(SUM(p_details), 0) `p_details`
	FROM (
		SELECT LO.o_date `reject_date`
			,CW.item
			,CW.CW_ID
			,SUM(IFNULL(o_not_spill, 0) + IFNULL(o_crack, 0) + IFNULL(o_chipped, 0) + IFNULL(o_def_form, 0)) `o_reject`
			,SUM(CW.in_cassette) - ROUND(SUM(LB.underfilling) / CW.fillings) `o_details`
			,NULL `p_reject`
			,NULL `p_details`
		FROM list__Opening LO
		JOIN list__Filling LF ON LF.LF_ID = LO.LF_ID
		JOIN list__Batch LB ON LB.LB_ID = LF.LB_ID
		JOIN plan__Batch PB ON PB.PB_ID = LB.PB_ID
		JOIN CounterWeight CW ON CW.CW_ID = PB.CW_ID
		WHERE 1
			".($_GET["date_from"] ? "AND LO.o_date >= '{$_GET["date_from"]}'" : "")."
			".($_GET["date_to"] ? "AND LO.o_date <= '{$_GET["date_to"]}'" : "")."
			".($_GET["CW_ID"] ? "AND CW.CW_ID={$_GET["CW_ID"]}" : "")."
			".($_GET["CB_ID"] ? "AND CW.CW_ID IN (SELECT CW_ID FROM CounterWeight WHERE CB_ID = {$_GET["CB_ID"]})" : "")."
		GROUP BY PB.CW_ID, LO.o_date

		UNION

		SELECT LP.p_date
			,CW.item
			,CW.CW_ID
			,NULL
			,NULL
			,SUM(IFNULL(p_not_spill, 0) + IFNULL(p_crack, 0) + IFNULL(p_chipped, 0) + IFNULL(p_def_form, 0)) `p_reject`
			,SUM(CW.in_cassette) - ROUND(SUM(LB.underfilling) / CW.fillings) - SUM(IFNULL(o_not_spill, 0) + IFNULL(o_crack, 0) + IFNULL(o_chipped, 0) + IFNULL(o_def_form, 0)) `p_details`
		FROM list__Packing LP
		JOIN list__Filling LF ON LF.LF_ID = LP.LF_ID
		JOIN list__Batch LB ON LB.LB_ID = LF.LB_ID
		JOIN plan__Batch PB ON PB.PB_ID = LB.PB_ID
		JOIN CounterWeight CW ON CW.CW_ID = PB.CW_ID
		LEFT JOIN list__Opening LO ON LO.LF_ID = LF.LF_ID
		WHERE 1
			".($_GET["date_from"] ? "AND LP.p_date >= '{$_GET["date_from"]}'" : "")."
			".($_GET["date_to"] ? "AND LP.p_date <= '{$_GET["date_to"]}'" : "")."
			".($_GET["CW_ID"] ? "AND CW.CW_ID={$_GET["CW_ID"]}" : "")."
			".($_GET["CB_ID"] ? "AND CW.CW_ID IN (SELECT CW_ID FROM CounterWeight WHERE CB_ID = {$_GET["CB_ID"]})" : "")."
		GROUP BY PB.CW_ID, LP.p_date
	) OPR
	GROUP BY OPR.reject_date, OPR.CW_ID
	ORDER BY OPR.reject_date, OPR.CW_ID
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	$o_reject += $row["o_reject"];
	$p_reject += $row["p_reject"];
	$o_details += $row["o_details"];
	$p_details += $row["p_details"];
	?>
	<tr>
		<td><?=$row["reject_date_format"]?></td>
		<td><?=$row["item"]?></td>
		<td><?=($row["o_details"] > 0 ? $row["o_reject"] : "")?></td>
		<td><?=($row["o_details"] > 0 ? round($row["o_reject"] / $row["o_details"] * 100, 1) : "")?></td>
		<td><?=($row["p_details"] > 0 ? $row["p_reject"] : "")?></td>
		<td><?=($row["p_details"] > 0 ? round($row["p_reject"] / $row["p_details"] * 100, 1) : "")?></td>
		<td><?=($row["o_reject"] + $row["p_reject"])?></td>
	</tr>
	<?
}
?>
		<tr class="total">
			<td></td>
			<td>Итог:</td>
			<td><?=($p_details > 0 ? $p_reject : "")?></td>
			<td><?=($o_details > 0 ? round($o_reject / $o_details * 100, 1) : "")?></td>
			<td><?=($p_details > 0 ? $p_reject : "")?></td>
			<td><?=($p_details > 0 ? round($p_reject / $p_details * 100, 1) : "")?></td>
			<td><?=($o_reject + $p_reject)?></td>
		</tr>
	</tbody>
</table>

<?
include "footer.php";
?>
