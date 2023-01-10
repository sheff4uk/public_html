<?
include "config.php";

// Запись принадлежности кассеты аяксом
if( isset($_GET["cassette"]) ) {
	$F_ID = $_GET["F_ID"];
	$CW_ID = ($_GET["CW_ID"] > 0) ? $_GET["CW_ID"] : "NULL";
	$cassette = $_GET["cassette"];
	$query = "
		UPDATE Cassettes
		SET CW_ID = {$CW_ID}
			,F_ID = {$F_ID}
		WHERE cassette = {$cassette}
	";
	mysqli_query( $mysqli, $query );
	die();
}

$title = 'КИС Константа главная';
include "header.php";

// Проверка прав на доступ к экрану
if( !in_array('cassettes', $Rights) ) {
	header($_SERVER['SERVER_PROTOCOL'].' 403 Forbidden');
	die('Недостаточно прав для совершения операции');
}

////////////////////////
?>
<style>
	.connectedSortable {
		border: 1px solid #555;
		width: 186px;
		min-height: 20px;
		list-style-type: none;
		margin: 0;
		padding: 5px 0 0 0;
		float: left;
		margin-right: 10px;
		margin-bottom: 10px;
		background: #fff;
	}
	.connectedSortable li {
		margin: 3px;
		padding: 1px;
		float: left;
		width: 40px;
		height: 22px;
	}
	.legend {
		font-size: 1.2em;
		font-weight: bold;
	}
	legend {
		font-size: 2em;
		font-weight: bold;
	}
	fieldset {
		background: #fdce46;
	}
</style>
<script>
	$( function() {
		$( ".connectedSortable" ).sortable({
			items: "li",
			placeholder: "ui-state-highlight",
			connectWith: ".connectedSortable",
			cursor: "move",
//			revert: true,
			stop: function( event, ui ) {
				var cassette = ui.item.html(),
					CW_ID = ui.item.parent('ul').attr('cw_id'),
					F_ID = ui.item.parents('fieldset').attr('f_id');
				$.ajax({ url: "cassettes.php?F_ID="+F_ID+"&CW_ID="+CW_ID+"&cassette="+cassette, dataType: "text", async: false });
			}
		}).disableSelection();
	});
</script>
<h3>Чтобы изменить принадлежность кассеты, перетащите блок с номером кассеты в соответствующий контейнер.</h3>

<?
$query = "
	SELECT F_ID
		,f_name
	FROM factory
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	echo "<fieldset f_id='{$row["F_ID"]}' style='display: flex; justify-content: normal; flex-wrap: wrap; margin-top: 30px; text-align: center;'>";
	echo "<legend>{$row["f_name"]}</legend>";
?>

	<ul cw_id="0" class="connectedSortable" style="width: 100%;">
		<div class="legend">Резерв</div>
<?
		$query = "
			SELECT cassette
			FROM Cassettes
			WHERE F_ID = {$row["F_ID"]}
				AND CW_ID IS NULL
			ORDER BY cassette
		";
		$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		while( $subrow = mysqli_fetch_array($subres) ) {
			echo "<li class='cassette'>{$subrow["cassette"]}</li>";
		}
?>
	</ul>

<?
	$query = "
		SELECT MF.CW_ID
			,CW.item
			,MF.fillings
			,MF.per_batch
			,MF.in_cassette
		FROM MixFormula MF
		JOIN CounterWeight CW ON CW.CW_ID = MF.CW_ID
		WHERE MF.F_ID = {$row["F_ID"]}
	";
	$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $subrow = mysqli_fetch_array($subres) ) {
		echo "
			<ul cw_id='{$subrow["CW_ID"]}' class='connectedSortable' style='width: ".(2 + $subrow["fillings"] * 46)."px;'>\n
				<div class='legend' title='Заливок с одного замеса: ".($subrow["fillings"]/$subrow["per_batch"])."'>{$subrow["item"]} <i class='fa-solid fa-circle-info'></i></div>
		";

		$query = "
			SELECT cassette
			FROM Cassettes
			WHERE F_ID = {$row["F_ID"]}
				AND CW_ID = {$subrow["CW_ID"]}
			ORDER BY cassette
		";
		$subsubres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		while( $subsubrow = mysqli_fetch_array($subsubres) ) {
			echo "<li class='cassette'>{$subsubrow["cassette"]}</li>";
		}

		echo "</ul>";
	}
	echo "</fieldset>";
}
////////////////////////
include "footer.php";
?>
