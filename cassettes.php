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

$title = 'Кассеты';
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
/*		margin-bottom: 10px;*/
		background: #fff;
		max-height: 200px;
		overflow: overlay;
		height: 100%;
	}
	.connectedSortable::-webkit-scrollbar {
		width: 5px;
	}
	.connectedSortable::-webkit-scrollbar-thumb {
		background: #333;
	}
	.connectedSortable li {
		margin: 3px;
		padding: 1px;
		float: left;
		width: 40px;
		height: 22px;
	}
	.legend {
/*		font-size: 1.2em;*/
		font-weight: bold;
		word-break: break-word;
	}
	legend {
		font-size: 2em;
		font-weight: bold;
	}
	fieldset {
		background: #fdce46;
	}
	.cassette_num {
		position: absolute;
		left: -8px;
		top: -8px;
		background: dodgerblue;
		height: 20px;
		border-radius: 10px;
		padding: 2px;
		min-width: 20px;
		color: #fff;
		line-height: 16px;
		font-size: 12px;
		font-weight: normal;
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
	$cassette_num_factory = 0;
	echo "<fieldset f_id='{$row["F_ID"]}' style='display: flex; justify-content: normal; flex-wrap: wrap; margin-top: 30px; text-align: center;'>";
?>
	<div style="position: relative; width: 100%; margin-bottom: 10px;">
		<ul cw_id="0" class="connectedSortable" style="width: 100%;">
			<div class="legend">Резерв</div>
<?
			$cassette_num = 0;

			$query = "
				SELECT cassette
					,1 - 0.05 * LEAST(10, IFNULL(last_filling_days(cassette), 99)) opacity
				FROM Cassettes
				WHERE F_ID = {$row["F_ID"]}
					AND CW_ID IS NULL
				ORDER BY cassette
			";
			$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			while( $subrow = mysqli_fetch_array($subres) ) {
				echo "<li class='cassette' style='opacity: {$subrow["opacity"]};'>{$subrow["cassette"]}</li>";
				$cassette_num++;
				$cassette_num_factory++;
			}
?>
		</ul>
		<span class="cassette_num"><?=$cassette_num?></span>
	</div>

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
		echo "<div style='position: relative; margin-bottom: 10px;'>";
		echo "
			<ul cw_id='{$subrow["CW_ID"]}' class='connectedSortable' style='width: ".(7 + $subrow["fillings"] * 46)."px;'>\n
				<div class='legend' title='Заливок с одного замеса: ".($subrow["fillings"]/$subrow["per_batch"])."'>{$subrow["item"]} <i class='fa-solid fa-circle-info'></i></div>\n
		";

		$cassette_num = 0;

		$query = "
			SELECT cassette
				,1 - 0.05 * LEAST(10, IFNULL(last_filling_days(cassette), 99)) opacity
			FROM Cassettes
			WHERE F_ID = {$row["F_ID"]}
				AND CW_ID = {$subrow["CW_ID"]}
			ORDER BY cassette
		";
		$subsubres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		while( $subsubrow = mysqli_fetch_array($subsubres) ) {
			echo "<li class='cassette' style='opacity: {$subsubrow["opacity"]};'>{$subsubrow["cassette"]}</li>";
			$cassette_num++;
			$cassette_num_factory++;
		}

		echo "</ul>";
		echo "<span class='cassette_num'>{$cassette_num}</span>";
		echo "</div>";
	}
	echo "<legend style='position: relative;'>{$row["f_name"]}<span class='cassette_num'>{$cassette_num_factory}</span></legend>";
	echo "</fieldset>";
}
////////////////////////
include "footer.php";
?>
