<?
include_once "../config.php";

//Изменение статуса поддона
if( isset($_POST["WT_ID"]) ) {
	$query = "
		UPDATE list__PackingPallet
		SET PN_ID = IF({$_POST["PN_ID"]} = 0, NULL, {$_POST["PN_ID"]})
			,shipment_time = IF({$_POST["PN_ID"]} = 0, NULL, NOW())
		WHERE WT_ID = {$_POST["WT_ID"]} AND nextID = {$_POST["nextID"]}
	";
	mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	exit ('<meta http-equiv="refresh" content="0; url=/dct/cwreject.php?WT_ID='.$_POST["WT_ID"].'&nextID='.$_POST["nextID"].'">');
}
?>

<!DOCTYPE html>
<html lang="ru">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<title>Отгрузка</title>
		<script src="../js/jquery-1.11.3.min.js"></script>
		<script>
			$(function() {
				// Считывание штрихкода
				var barcode="";
				$(document).keydown(function(e)
				{
					var code = (e.keyCode ? e.keyCode : e.which);
					if (code==0) barcode="";
					if( code==13 || code==9 )// Enter key hit. Tab key hit.
					{
						var WT_ID = Number(barcode.substr(0, 8)),
							nextID = Number(barcode.substr(8, 8));
						$(location).attr('href','/dct/cwreject.php?WT_ID='+WT_ID+'&nextID='+nextID);
						barcode="";
						return false;
					}
					else
					{
						if (code >= 48 && code <= 57) {
							barcode = barcode + String.fromCharCode(code);
						}
					}
				});
			});
		</script>
	</head>
	<body>
		<h3>Отсканируйте поддон</h3>
		<?
		if( isset($_GET["WT_ID"]) ) {
			$query = "
				SELECT DATE_FORMAT(LPP.packed_time, '%d.%m.%Y %h:%i') packed_time_format
					,DATE_FORMAT(LPP.shipment_time, '%d.%m.%Y %h:%i') shipment_time_format
					,IFNULL(LPP.PN_ID, 0) PN_ID
				FROM list__PackingPallet LPP
				JOIN CounterWeight CW ON CW.CW_ID = LPP.CW_ID
				WHERE LPP.WT_ID = {$_GET["WT_ID"]} AND LPP.nextID = {$_GET["nextID"]}
			";
			$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			$row = mysqli_fetch_array($res);

			$WT_ID = str_pad($_GET["WT_ID"], 8, "0", STR_PAD_LEFT);
			$nextID = str_pad($_GET["nextID"], 8, "0", STR_PAD_LEFT);
			echo "<h1 style='text-align: center;'>{$WT_ID}{$nextID}</h1>";

			//Форма изменения статуса противовеса
			?>
			<fieldset>
				<legend>Статус паллета</legend>
				<form method="post" style="font-size: 2em;">
					<input type="hidden" name="WT_ID" value="<?=$_GET["WT_ID"]?>">
					<input type="hidden" name="nextID" value="<?=$_GET["nextID"]?>">
					<input type="datetime-local">
					<select name="pallet_status">
						<option value="0">На складе</option>
						<?
						$query = "
							SELECT PN.PN_ID
								,PN.pallet_name
							FROM pallet__Name PN
						";
						$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
						while( $row = mysqli_fetch_array($res) ) {
							echo "<option value='{$row["PN_ID"]}'>Отгружен ({$row["pallet_name"]})</option>";
						}
						?>
					</select>
				</form>
			</fieldset>
			<script>
				$(function() {
					$('select[name="pallet_status"]').val(<?=$row["PN_ID"]?>);
				});
			</script>
			<?

			echo "
				Контроль: <b>{$row["packed_time_format"]}</b><br>
				".($row["PN_ID"] ? "Отгрузка: {$row["shipment_time_format"]}" : "")."
			";
		}
		?>
	</body>
</html>
