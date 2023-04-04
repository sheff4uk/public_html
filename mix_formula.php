<?
include "config.php";
$title = 'Рецепты';
include "header.php";

// Проверка прав на доступ к экрану
if( !in_array('mix_formula', $Rights) ) {
	header($_SERVER['SERVER_PROTOCOL'].' 403 Forbidden');
	die('Недостаточно прав для совершения операции');
}

include "./forms/mix_formula_form.php";

// Если не выбран участок, берем из сессии
if( !$_GET["F_ID"] ) {
	$_GET["F_ID"] = $_SESSION['F_ID'];
}
?>

<!--Фильтр-->
<div id="filter">
	<h3>Фильтр</h3>
	<form method="get" style="position: relative;">
		<a href="/mix_formula.php" style="position: absolute; top: 10px; right: 10px;" class="button">Сброс</a>

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
			<th>Противовес</th>
			<th class="s_fraction">Мелкая дробь, кг</th>
			<th class="l_fraction">Крупная дробь, кг</th>
			<th class="iron_oxide">Окалина, кг</th>
			<th class="slag10">Шлак 0-10, кг</th>
			<th class="slag20">Шлак 10-20, кг</th>
			<th class="slag020">Шлак 0-20, кг</th>
			<th class="slag30">Шлак 5-30, кг</th>
			<th class="sand">КМП, кг</th>
			<th class="crushed_stone">Отсев, кг</th>
			<th class="cement">Цемент, кг</th>
			<th class="plasticizer">Пластификатор, кг</th>
			<th>Вода, кг</th>
			<th></th>
		</tr>
	</thead>
	<tbody style="text-align: center;">

<?
$query = "
	SELECT CW.CW_ID
		,CW.item
		,MF.MF_ID
		,MF.s_fraction
		,MF.l_fraction
		,MF.iron_oxide
		,MF.slag10
		,MF.slag20
		,MF.slag020
		,MF.slag30
		,MF.sand
		,MF.crushed_stone
		,MF.cement
		,MF.plasticizer
		,MF.water
	FROM CounterWeight CW
	JOIN MixFormula MF ON MF.CW_ID = CW.CW_ID AND MF.F_ID = {$_GET["F_ID"]}
	ORDER BY CW.CW_ID
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	$s_fraction += $row["s_fraction"];
	$l_fraction += $row["l_fraction"];
	$iron_oxide += $row["iron_oxide"];
	$slag10 += $row["slag10"];
	$slag20 += $row["slag20"];
	$slag020 += $row["slag020"];
	$slag30 += $row["slag30"];
	$sand += $row["sand"];
	$crushed_stone += $row["crushed_stone"];
	$cement += $row["cement"];
	$plasticizer += $row["plasticizer"];
	?>
	<tr id="<?=$row["MF_ID"]?>">
		<td><b><?=$row["item"]?></b></td>
		<td style="background: #7952eb88;" class="s_fraction"><?=$row["s_fraction"]?></td>
		<td style="background: #51d5d788;" class="l_fraction"><?=$row["l_fraction"]?></td>
		<td style="background: #a52a2a80;" class="iron_oxide"><?=$row["iron_oxide"]?></td>
		<td style="background: #33333380;" class="slag10"><?=$row["slag10"]?></td>
		<td style="background: #33333380;" class="slag20"><?=$row["slag20"]?></td>
		<td style="background: #33333380;" class="slag020"><?=$row["slag020"]?></td>
		<td style="background: #33333380;" class="slag30"><?=$row["slag30"]?></td>
		<td style="background: #f4a46082;" class="sand"><?=$row["sand"]?></td>
		<td style="background: #8b45137a;" class="crushed_stone"><?=$row["crushed_stone"]?></td>
		<td style="background: #7080906b;" class="cement"><?=$row["cement"]?></td>
		<td style="background: #80800080;" class="plasticizer"><?=$row["plasticizer"]?></td>
		<td style="background: #1e90ff85;"><?=$row["water"]?></td>
		<td><a href="#" class="add_formula" MF_ID="<?=$row["MF_ID"]?>" item="<?=$row["item"]?>" F_ID="<?=$_GET["F_ID"]?>" title="Изменить рецепт"><i class="fa fa-pencil-alt fa-lg"></i></a></td>
	</tr>
	<?
}
?>

	</tbody>
</table>

<style>
	<?=($s_fraction ? "" : ".s_fraction{ display: none; }")?>
	<?=($l_fraction ? "" : ".l_fraction{ display: none; }")?>
	<?=($iron_oxide ? "" : ".iron_oxide{ display: none; }")?>
	<?=($slag10 ? "" : ".slag10{ display: none; }")?>
	<?=($slag20 ? "" : ".slag20{ display: none; }")?>
	<?=($slag020 ? "" : ".slag020{ display: none; }")?>
	<?=($slag30 ? "" : ".slag30{ display: none; }")?>
	<?=($sand ? "" : ".sand{ display: none; }")?>
	<?=($crushed_stone ? "" : ".crushed_stone{ display: none; }")?>
	<?=($cement ? "" : ".cement{ display: none; }")?>
	<?=($plasticizer ? "" : ".plasticizer{ display: none; }")?>
</style>

<?
include "footer.php";
?>
