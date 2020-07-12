<?
include "config.php";
$title = 'Маршрутный лист';
include "header.php";
include "./forms/route_sheet_form.php";
$limit = 500;
?>

<h3>На экране отображается не более <?=$limit?> последних записей. Данные отсортированы по дате и времени заливки.</h3>

<!--Фильтр-->
<div id="filter">
	<h3>Фильтр</h3>
	<form method="get" style="position: relative;">
		<a href="/route_sheet.php" style="position: absolute; top: 10px; right: 10px;" class="button">Сброс</a>

		<div class="nowrap" style="margin-bottom: 10px;">
			<span style="display: inline-block; width: 200px;">Дата заливки между:</span>
			<input name="filling_date_from" type="date" value="<?=$_GET["filling_date_from"]?>" class="<?=$_GET["filling_date_from"] ? "filtered" : ""?>">
			<input name="filling_date_to" type="date" value="<?=$_GET["filling_date_to"]?>" class="<?=$_GET["filling_date_to"] ? "filtered" : ""?>">
			<span>№ смены:</span>
			<select name="filling_shift" class="<?=$_GET["filling_shift"] ? "filtered" : ""?>">
				<option value=""></option>
				<option value="1" <?=($_GET["filling_shift"] == 1? "selected" : "")?>>1 (00:00-06:59)</option>
				<option value="2" <?=($_GET["filling_shift"] == 2 ?"selected" : "")?>>2 (07:00-15:29)</option>
				<option value="3" <?=($_GET["filling_shift"] == 3? "selected" : "")?>>3 (15:30-23:59)</option>
			</select>
		</div>

		<div class="nowrap" style="margin-bottom: 10px;">
			<span style="display: inline-block; width: 200px;">Дата расформовки между:</span>
			<input name="opening_date_from" type="date" value="<?=$_GET["opening_date_from"]?>" class="<?=$_GET["opening_date_from"] ? "filtered" : ""?>">
			<input name="opening_date_to" type="date" value="<?=$_GET["opening_date_to"]?>" class="<?=$_GET["opening_date_to"] ? "filtered" : ""?>">
			<span>№ смены:</span>
			<select name="opening_shift" class="<?=$_GET["opening_shift"] ? "filtered" : ""?>">
				<option value=""></option>
				<option value="1" <?=($_GET["opening_shift"] == 1? "selected" : "")?>>1 (00:00-06:59)</option>
				<option value="2" <?=($_GET["opening_shift"] == 2 ?"selected" : "")?>>2 (07:00-15:29)</option>
				<option value="3" <?=($_GET["opening_shift"] == 3? "selected" : "")?>>3 (15:30-23:59)</option>
			</select>
		</div>

		<div class="nowrap" style="margin-bottom: 10px;">
			<span style="display: inline-block; width: 200px;">Дата упаковки между:</span>
			<input name="boxing_date_from" type="date" value="<?=$_GET["boxing_date_from"]?>" class="<?=$_GET["boxing_date_from"] ? "filtered" : ""?>">
			<input name="boxing_date_to" type="date" value="<?=$_GET["boxing_date_to"]?>" class="<?=$_GET["boxing_date_to"] ? "filtered" : ""?>">
			<span>№ смены:</span>
			<select name="boxing_shift" class="<?=$_GET["boxing_shift"] ? "filtered" : ""?>">
				<option value=""></option>
				<option value="1" <?=($_GET["boxing_shift"] == 1? "selected" : "")?>>1 (00:00-06:59)</option>
				<option value="2" <?=($_GET["boxing_shift"] == 2 ?"selected" : "")?>>2 (07:00-15:29)</option>
				<option value="3" <?=($_GET["boxing_shift"] == 3? "selected" : "")?>>3 (15:30-23:59)</option>
			</select>
		</div>

		<div class="nowrap" style="display: inline-block; margin-bottom: 10px; margin-right: 30px;">
			<span>ID:</span>
			<input name="RS_ID" type="number" min="1" value="<?=$_GET["RS_ID"]?>" class="<?=$_GET["RS_ID"] ? "filtered" : ""?>" style="width: 100px;">
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
			<span>№ замеса:</span>
			<input name="batch" type="number" min="1" max="30" value="<?=$_GET["batch"]?>" style="width: 70px;" class="<?=$_GET["batch"] ? "filtered" : ""?>">
		</div>

		<div class="nowrap" style="display: inline-block; margin-bottom: 10px; margin-right: 30px;">
			<span>№ кассеты:</span>
			<input name="cassette" type="number" min="1" max="206" value="<?=$_GET["cassette"]?>" style="width: 70px;" class="<?=$_GET["cassette"] ? "filtered" : ""?>">
		</div>

		<div class="nowrap" style="margin-bottom: 10px;">
			<span>Оператор или помошник:</span>
			<select name="OP_ID" style="width: 80px;" class="<?=$_GET["OP_ID"] ? "filtered" : ""?>">
				<option value=""></option>
				<?
				$query = "
					SELECT OP.OP_ID, OP.name
					FROM Operator OP
				";
				$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
				while( $row = mysqli_fetch_array($res) ) {
					$selected = ($row["OP_ID"] == $_GET["OP_ID"]) ? "selected" : "";
					echo "<option value='{$row["OP_ID"]}' {$selected}>{$row["name"]}</option>";
				}
				?>
			</select>
		</div>

		<div style="margin-bottom: 10px;">
			<fieldset>
				<legend>Нарушения тех. процесса:</legend>

				<div class="nowrap" style="display: inline-block; margin-bottom: 10px; margin-right: 30px;">
					<label style="text-decoration: underline;" class="<?=$_GET["int24"] ? "filtered" : ""?>">
						До расформовки менее 24 часов:
						<input type="checkbox" name="int24" value="1" <?=$_GET["int24"] ? "checked" : ""?>>
					</label>
				</div>

				<div class="nowrap" style="display: inline-block; margin-bottom: 10px; margin-right: 30px;">
					<label style="text-decoration: underline;" class="<?=$_GET["int120"] ? "filtered" : ""?>">
						До упаковки менее 120 часов:
						<input type="checkbox" name="int120" value="1" <?=$_GET["int120"] ? "checked" : ""?>>
					</label>
				</div>

				<div class="nowrap" style="display: inline-block; margin-bottom: 10px; margin-right: 30px;">
					<label style="text-decoration: underline;" class="<?=$_GET["weight"] ? "filtered" : ""?>">
						Несоответствие по весу:
						<input type="checkbox" name="weight" value="1" <?=$_GET["weight"] ? "checked" : ""?>>
					</label>
				</div>
			</fieldset>
		</div>

		<div style="margin-bottom: 10px;">
			<fieldset>
				<legend>Брак:</legend>

				<div class="nowrap" style="display: inline-block; margin-bottom: 10px; margin-right: 30px;">
					<label style="text-decoration: underline;" class="<?=$_GET["not_spill"] ? "filtered" : ""?>">
						Непролив:
						<input type="checkbox" name="not_spill" value="1" <?=$_GET["not_spill"] ? "checked" : ""?>>
					</label>
				</div>

				<div class="nowrap" style="display: inline-block; margin-bottom: 10px; margin-right: 30px;">
					<label style="text-decoration: underline;" class="<?=$_GET["crack"] ? "filtered" : ""?>">
						Трещина:
						<input type="checkbox" name="crack" value="1" <?=$_GET["crack"] ? "checked" : ""?>>
					</label>
				</div>

				<div class="nowrap" style="display: inline-block; margin-bottom: 10px; margin-right: 30px;">
					<label style="text-decoration: underline;" class="<?=$_GET["chipped"] ? "filtered" : ""?>">
						Скол:
						<input type="checkbox" name="chipped" value="1" <?=$_GET["chipped"] ? "checked" : ""?>>
					</label>
				</div>

				<div class="nowrap" style="display: inline-block; margin-bottom: 10px; margin-right: 30px;">
					<label style="text-decoration: underline;" class="<?=$_GET["def_form"] ? "filtered" : ""?>">
						Дефект форм:
						<input type="checkbox" name="def_form" value="1" <?=$_GET["def_form"] ? "checked" : ""?>>
					</label>
				</div>

			</fieldset>
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
	});
</script>

<!--Вывод маршрутных листов-->
<table class="main_table">
	<thead>
		<tr>
			<th>Противовес</th>
			<th>Операция</th>
			<th>Дата</th>
			<th>Время (смена)</th>
			<th><i class="far fa-lg fa-hourglass" title="Интервал в часах с моента заливки."></i></th>
			<th>№ замеса</th>
			<th>№ кассеты</th>
			<th>Кол-во годных деталей</th>
			<th>Непролив</th>
			<th>Трещина</th>
			<th>Скол</th>
			<th>Дефект форм</th>
			<th>Оператор/<br>пост</th>
			<th></th>
		</tr>
	</thead>
	<tbody style="text-align: center;">

<?
$count = 0; //Счетчик записей

$query = "
	SELECT RS.RS_ID
		,CW.item
		,CW.in_cassette
		,CW.min_weight
		,CW.max_weight

		,DATE_FORMAT(RS.filling_date, '%d.%m.%y') filling_date
		,DATE_FORMAT(RS.filling_time, '%H:%i') filling_time
		,RS.filling_shift
		,RS.batch
		,RS.cassette
		,RS.amount
		,OP.name OPname
		,sOP.name sOPname

		,DATE_FORMAT(RS.opening_date, '%d.%m.%y') opening_date
		,DATE_FORMAT(RS.opening_time, '%H:%i') opening_time
		,RS.opening_shift
		,RS.o_amount
		,RS.o_not_spill
		,RS.o_crack
		,RS.o_chipped
		,RS.o_def_form
		,RS.o_post

		,DATE_FORMAT(RS.boxing_date, '%d.%m.%y') boxing_date
		,DATE_FORMAT(RS.boxing_time, '%H:%i') boxing_time
		,RS.boxing_shift
		,RS.weight1
		,RS.weight2
		,RS.weight3
		,WeightSpec(RS.CW_ID, RS.weight1) spec1
		,WeightSpec(RS.CW_ID, RS.weight2) spec2
		,WeightSpec(RS.CW_ID, RS.weight3) spec3
		,RS.b_amount
		,RS.b_not_spill
		,RS.b_crack
		,RS.b_chipped
		,RS.b_def_form
		,RS.b_post

		,RS.interval1
		,RS.interval2
	FROM RouteSheet RS
	JOIN CounterWeight CW ON CW.CW_ID = RS.CW_ID
	LEFT JOIN OperatorChecklist OC ON OC.CW_ID = RS.CW_ID AND OC.batch_date = RS.filling_date AND OC.batch_num = RS.batch
	LEFT JOIN Operator OP ON OP.OP_ID = OC.OP_ID
	LEFT JOIN Operator sOP ON sOP.OP_ID = OC.sOP_ID
	WHERE 1
		".($_GET["filling_date_from"] ? "AND RS.filling_date >= '{$_GET["filling_date_from"]}'" : "")."
		".($_GET["filling_date_to"] ? "AND RS.filling_date <= '{$_GET["filling_date_to"]}'" : "")."
		".($_GET["filling_shift"] ? "AND RS.filling_shift = {$_GET["filling_shift"]}" : "")."

		".($_GET["opening_date_from"] ? "AND RS.opening_date >= '{$_GET["opening_date_from"]}'" : "")."
		".($_GET["opening_date_to"] ? "AND RS.opening_date <= '{$_GET["opening_date_to"]}'" : "")."
		".($_GET["opening_shift"] ? "AND RS.opening_shift = {$_GET["opening_shift"]}" : "")."

		".($_GET["boxing_date_from"] ? "AND RS.boxing_date >= '{$_GET["boxing_date_from"]}'" : "")."
		".($_GET["boxing_date_to"] ? "AND RS.boxing_date <= '{$_GET["boxing_date_to"]}'" : "")."
		".($_GET["boxing_shift"] ? "AND RS.boxing_shift = {$_GET["boxing_shift"]}" : "")."

		".($_GET["RS_ID"] ? "AND RS.RS_ID={$_GET["RS_ID"]}" : "")."
		".($_GET["CW_ID"] ? "AND RS.CW_ID={$_GET["CW_ID"]}" : "")."
		".($_GET["batch"] ? "AND RS.batch = {$_GET["batch"]}" : "")."
		".($_GET["cassette"] ? "AND RS.cassette = {$_GET["cassette"]}" : "")."
		".($_GET["OP_ID"] ? "AND (OC.OP_ID = {$_GET["OP_ID"]} OR OC.sOP_ID = {$_GET["OP_ID"]})" : "")."

		".($_GET["int24"] ? "AND RS.interval1 < 24" : "")."
		".($_GET["int120"] ? "AND RS.interval2 < 120" : "")."
		".($_GET["weight"] ? "AND NOT (WeightSpec(RS.CW_ID, RS.weight1) AND WeightSpec(RS.CW_ID, RS.weight2) AND WeightSpec(RS.CW_ID, RS.weight3))" : "")."

		".($_GET["not_spill"] ? "AND (RS.o_not_spill OR RS.b_not_spill)" : "")."
		".($_GET["crack"] ? "AND (RS.o_crack OR RS.b_crack)" : "")."
		".($_GET["chipped"] ? "AND (RS.o_chipped OR RS.b_chipped)" : "")."
		".($_GET["def_form"] ? "AND (RS.o_def_form OR RS.b_def_form)" : "")."
	ORDER BY RS.filling_date DESC, RS.filling_time DESC
	LIMIT {$limit}
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	?>
		<tr style="border-top: 2px solid #333;">
			<td rowspan="3"><b><?=$row["item"]?></b><p class="nowrap" id="<?=$row["RS_ID"]?>">id: <b><?=$row["RS_ID"]?></b></p></td>
			<td><i class="fas fa-lg fa-fill-drip"></i></td>
			<td><?=$row["filling_date"]?></td>
			<td><?=$row["filling_time"]?> (<?=$row["filling_shift"]?>)</td>
			<td></td>
			<td><span class="<?=($row["OPname"] ? "" : "bg-red")?>"><?=$row["batch"]?></span></td>
			<td><?=$row["cassette"]?></td>
			<td style="position: relative;"><b><?=$row["amount"]?></b><div style="background-color: chartreuse; left: 0; bottom: 0; width: <?=(100*$row["amount"]/$row["in_cassette"])?>%; position: absolute; height: 100%; opacity: .3;"></div></td>
			<td colspan="4" style="background-color: #333;"></td>
			<td><?=$row["OPname"]?><br><font style="font-size: .8em;"><?=$row["sOPname"]?></font></td>
			<td rowspan="3"><a href="#" class="add_route_sheet" RS_ID="<?=$row["RS_ID"]?>" title="Изменить маршрутный лист"><i class="fa fa-pencil-alt fa-lg"></i></a></td>
		</tr>
		<tr>
			<td><i class="fas fa-lg fa-box-open"></i></td>
			<td><?=$row["opening_date"]?></td>
			<td><?=$row["opening_time"]?> (<?=$row["opening_shift"]?>)</td>
			<td <?=($row["interval1"] < 24 ? "class='error'" : "")?>><?=$row["interval1"]?></td>
			<td colspan="2" id="weight" style="border-top: 2px solid #333; border-left: 2px solid #333; border-right: 2px solid #333;">Вес <?=$row["min_weight"]?> - <?=$row["max_weight"]?> г</td>
			<td style="position: relative;" <?=($row["o_amount"] < 0 ? "class='error'" : "")?>><b><?=$row["o_amount"]?></b><div style="background-color: chartreuse; left: 0; bottom: 0; width: <?=(100*$row["o_amount"]/$row["in_cassette"])?>%; position: absolute; height: 100%; opacity: .3;"></div></td>
			<td style="color: red;"><?=$row["o_not_spill"]?></td>
			<td style="color: red;"><?=$row["o_crack"]?></td>
			<td style="color: red;"><?=$row["o_chipped"]?></td>
			<td style="color: red;"><?=$row["o_def_form"]?></td>
			<td><?=$row["o_post"]?></td>
		</tr>
		<tr>
			<td><i class="fas fa-lg fa-pallet"></i></td>
			<td><?=$row["boxing_date"]?></td>
			<td><?=$row["boxing_time"]?> (<?=$row["boxing_shift"]?>)</td>
			<td <?=($row["interval2"] < 120 ? "class='error'" : "")?>><?=$row["interval2"]?></td>
			<td colspan="2" class="nowrap" style="border-left: 2px solid #333; border-right: 2px solid #333;">
				<span class="<?=(($row["spec1"]) ? "" : "bg-red")?>"><?=$row["weight1"]?></span>&nbsp;
				<span class="<?=(($row["spec2"]) ? "" : "bg-red")?>"><?=$row["weight2"]?></span>&nbsp;
				<span class="<?=(($row["spec3"]) ? "" : "bg-red")?>"><?=$row["weight3"]?></span>
			</td>
			<td  style="position: relative;" <?=($row["b_amount"] < 0 ? "class='error'" : "")?>><b><?=$row["b_amount"]?></b><div style="background-color: chartreuse; left: 0; bottom: 0; width: <?=(100*$row["b_amount"]/$row["in_cassette"])?>%; position: absolute; height: 100%; opacity: .3;"></div></td>
			<td style="color: red;"><?=$row["b_not_spill"]?></td>
			<td style="color: red;"><?=$row["b_crack"]?></td>
			<td style="color: red;"><?=$row["b_chipped"]?></td>
			<td style="color: red;"><?=$row["b_def_form"]?></td>
			<td><?=$row["b_post"]?></td>
		</tr>
	<?
	$count++;
}
?>
	</tbody>
</table>

<h3>Маршрутных листов на экране: <?=$count?></h3>

<div id="add_btn" class="add_route_sheet" title="Внести маршрутный лист"></div>

<?
include "footer.php";
?>
