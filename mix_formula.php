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
<?
	$query = "
		SELECT MN.MN_ID
			,MN.material_name
		FROM MixFormula MF
		JOIN MixFormulaMaterial MFM ON MFM.MF_ID = MF.MF_ID
		JOIN material__Name MN ON MN.MN_ID = MFM.MN_ID
		WHERE MF.F_ID = {$_GET["F_ID"]}
		GROUP BY MN.material_name
		ORDER BY MN.material_name
	";
	$MN_IDs = "0";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $row = mysqli_fetch_array($res) ) {
		echo "<th>{$row["material_name"]}, кг</th>";
		$MN_IDs .= ",".$row["MN_ID"];
	}
?>
			<th>Вода, л</th>
			<th></th>
		</tr>
	</thead>
	<tbody style="text-align: center;">

<?
$query = "
	SELECT CW.CW_ID
		,CW.item
		,MF.MF_ID
		,MF.water
	FROM MixFormula MF
	JOIN CounterWeight CW ON CW.CW_ID = MF.CW_ID
	WHERE MF.F_ID = {$_GET["F_ID"]}
	ORDER BY MF.CW_ID
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	?>
	<tr id="<?=$row["MF_ID"]?>">
		<td><b><?=$row["item"]?></b></td>
		<?
		$query = "
			SELECT MFM.quantity
				,MN.color
			FROM material__Name MN
			LEFT JOIN MixFormulaMaterial MFM ON MFM.MN_ID = MN.MN_ID
				AND MFM.MF_ID = {$row["MF_ID"]}
			WHERE MN.MN_ID IN ({$MN_IDs})
			ORDER BY MN.material_name
		";
		$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		while( $subrow = mysqli_fetch_array($subres) ) {
			echo "<td style='background: #{$subrow["color"]};'>{$subrow["quantity"]}</td>";
		}

	?>
		<td style="background: #1e90ff85;"><?=$row["water"]?></td>
		<td><a href="#" class="add_formula" MF_ID="<?=$row["MF_ID"]?>" item="<?=$row["item"]?>" F_ID="<?=$_GET["F_ID"]?>" title="Изменить рецепт"><i class="fa fa-pencil-alt fa-lg"></i></a></td>
	</tr>
	<?
}
?>

	</tbody>
</table>

<?
include "footer.php";
?>
