<?
include "config.php";
$title = 'План производства';
include "header.php";
include "./forms/plan_production_form.php";

// Если в фильтре не установлена неделя, показываем текущую
if( !$_GET["week"] ) {
	$query = "SELECT YEARWEEK(NOW(), 1) week";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$row = mysqli_fetch_array($res);
	$_GET["week"] = $row["week"];
}
?>

<!--Фильтр-->
<div id="filter">
	<h3>Фильтр</h3>
	<form method="get" style="position: relative;">
		<a href="/plan_production.php" style="position: absolute; top: 10px; right: 10px;" class="button">Сброс</a>

		<div class="nowrap" style="margin-bottom: 10px;">
			<span>Неделя:</span>
			<select name="week" class="<?=$_GET["week"] ? "filtered" : ""?>">
				<?
				$query = "
					SELECT YEARWEEK(NOW(), 1) week, INSERT(YEARWEEK(NOW(), 1), 5, 0, '-w') week_format
					UNION
					SELECT YEARWEEK(pp_date, 1) week, INSERT(YEARWEEK(pp_date, 1), 5, 0, '-w') week_format
					FROM plan__Production
					ORDER BY week DESC
				";
				$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
				while( $row = mysqli_fetch_array($res) ) {
					$selected = ($row["week"] == $_GET["week"]) ? "selected" : "";
					echo "<option value='{$row["week"]}' {$selected}>{$row["week_format"]}</option>";
				}
				?>
			</select>
			<i class="fas fa-question-circle" title="По умолчанию устанавливаются текущая неделя."></i>
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
			<th>Замесов</th>
			<th>Заливок</th>
			<th>План</th>
			<th>Факт</th>
			<th></th>
		</tr>
	</thead>
	<tbody style="text-align: center;">

<?
$query = "
	SELECT SUM(1) cnt
		,DATE_FORMAT(PP.pp_date, '%d.%m.%y') pp_date_format
		,PP.pp_date
		,SUM(PP.batches) batches
		,SUM(PP.batches * CW.fillings) fillings
		,SUM(PP.batches * CW.fillings * CW.in_cassette) plan
		,SUM(SUB.fakt) fakt
	FROM plan__Production PP
	JOIN CounterWeight CW ON CW.CW_ID = PP.CW_ID
	LEFT JOIN (
		SELECT LB.batch_date
			,LB.CW_ID
			,SUM(CW.in_cassette) - ROUND(SUM(LB.underfilling/CW.fillings)) fakt
		FROM list__Batch LB
		JOIN CounterWeight CW ON CW.CW_ID = LB.CW_ID
		JOIN list__Filling LF ON LF.LB_ID = LB.LB_ID
		WHERE 1
			".($_GET["week"] ? "AND YEARWEEK(LB.batch_date, 1) LIKE '{$_GET["week"]}'" : "")."
			".($_GET["CW_ID"] ? "AND LB.CW_ID={$_GET["CW_ID"]}" : "")."
			".($_GET["CB_ID"] ? "AND LB.CW_ID IN (SELECT CW_ID FROM CounterWeight WHERE CB_ID = {$_GET["CB_ID"]})" : "")."
		GROUP BY LB.batch_date, LB.CW_ID
	) SUB ON SUB.batch_date = PP.pp_date AND SUB.CW_ID = PP.CW_ID
	WHERE 1
		".($_GET["week"] ? "AND YEARWEEK(PP.pp_date, 1) LIKE '{$_GET["week"]}'" : "")."
		".($_GET["CW_ID"] ? "AND PP.CW_ID={$_GET["CW_ID"]}" : "")."
		".($_GET["CB_ID"] ? "AND PP.CW_ID IN (SELECT CW_ID FROM CounterWeight WHERE CB_ID = {$_GET["CB_ID"]})" : "")."
	GROUP BY PP.pp_date
	ORDER BY PP.pp_date DESC, PP.CW_ID
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	$cnt = $row["cnt"];

	$query = "
		SELECT PP.PP_ID
			,DATE_FORMAT(PP.pp_date, '%d.%m.%y') pp_date_format
			,PP.pp_date
			,CW.item
			,PP.CW_ID
			,PP.batches
			,PP.batches * CW.fillings fillings
			,PP.batches * CW.fillings * CW.in_cassette plan
			,SUB.fakt
		FROM plan__Production PP
		JOIN CounterWeight CW ON CW.CW_ID = PP.CW_ID
		LEFT JOIN (
			SELECT LB.batch_date
				,LB.CW_ID
				,SUM(CW.in_cassette) - ROUND(SUM(LB.underfilling/CW.fillings)) fakt
			FROM list__Batch LB
			JOIN CounterWeight CW ON CW.CW_ID = LB.CW_ID
			JOIN list__Filling LF ON LF.LB_ID = LB.LB_ID
			WHERE 1
				AND LB.batch_date = '{$row["pp_date"]}'
				".($_GET["CW_ID"] ? "AND LB.CW_ID={$_GET["CW_ID"]}" : "")."
				".($_GET["CB_ID"] ? "AND LB.CW_ID IN (SELECT CW_ID FROM CounterWeight WHERE CB_ID = {$_GET["CB_ID"]})" : "")."
			GROUP BY LB.batch_date, LB.CW_ID
		) SUB ON SUB.batch_date = PP.pp_date AND SUB.CW_ID = PP.CW_ID
		WHERE 1
			AND PP.pp_date = '{$row["pp_date"]}'
			".($_GET["CW_ID"] ? "AND PP.CW_ID={$_GET["CW_ID"]}" : "")."
			".($_GET["CB_ID"] ? "AND PP.CW_ID IN (SELECT CW_ID FROM CounterWeight WHERE CB_ID = {$_GET["CB_ID"]})" : "")."
		ORDER BY PP.pp_date DESC, PP.CW_ID
	";
	$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $subrow = mysqli_fetch_array($subres) ) {
		$batches += $subrow["batches"];
		$fillings += $subrow["fillings"];
		$plan += $subrow["plan"];
		$fakt += $subrow["fakt"];

		// Выводим общую ячейку с датой заливки
		if( $cnt ) {
			$cnt++;
			echo "<tr id='{$subrow["PP_ID"]}' style='border-top: 2px solid #333;'>";
			echo "<td rowspan='{$cnt}' style='background-color: rgba(0, 0, 0, 0.2);'>{$row["pp_date_format"]}</td>";
			$cnt = 0;
		}
		else {
			echo "<tr id='{$subrow["PP_ID"]}'>";
		}

		?>
			<td><?=$subrow["item"]?></td>
			<td><?=$subrow["batches"]?></td>
			<td><?=$subrow["fillings"]?></td>
			<td><?=$subrow["plan"]?></td>
			<td class="bg-gray"><?=$subrow["fakt"]?></td>
			<td>
				<?=(!$subrow["fakt"] ? "<a href='#' class='add_pp' PP_ID='{$subrow["PP_ID"]}' title='Изменить данные производственного плана'><i class='fa fa-pencil-alt fa-lg'></i></a>" : "")?>
				<a href="printforms/checklist_blank.php?PP_ID=<?=$subrow["PP_ID"]?>" class="print" title="Бланк чеклиста оператора"><i class="fas fa-print fa-lg"></i></a></td>
		</tr>
		<?

	}
?>
		<tr class="summary">
			<td>Итог:</td>
			<td><?=$row["batches"]?></td>
			<td><?=$row["fillings"]?></td>
			<td><?=$row["plan"]?></td>
			<td class="bg-gray"><?=$row["fakt"]?></td>
			<td></td>
		</tr>
<?
}
?>
		<tr class="total">
			<td></td>
			<td>Итог:</td>
			<td><?=$batches?></td>
			<td><?=$fillings?></td>
			<td><?=$plan?></td>
			<td class="bg-gray"><?=$fakt?></td>
			<td></td>
		</tr>
	</tbody>
</table>

<div id="add_btn" class="add_pp" pp_date="<?=$_GET["pp_date"]?>" title="Внести данные производственного плана"></div>

<script>
	$(function() {
		$(".print").printPage();
	});
</script>

<?
include "footer.php";
?>
