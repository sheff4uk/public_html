<?
include_once "../config.php";
$ip = $_SERVER['REMOTE_ADDR'];
if( $ip != $from_ip ) die("Access denied");

//ID поддона введен вручную
if( isset($_POST["barcode"]) ) {
	$WT_ID = substr($_POST["barcode"], 0, 8);
	$nextID = substr($_POST["barcode"], -8, 8);
	exit ('<meta http-equiv="refresh" content="0; url=/dct/shipment.php?WT_ID='.$WT_ID.'&nextID='.$nextID.'">');
}

//Изменение статуса поддона из формы
if( isset($_POST["WT_ID"]) ) {
	$query = "
		UPDATE list__PackingPallet
		SET PN_ID = IF({$_POST["PN_ID"]} = 0, NULL, {$_POST["PN_ID"]})
			,shipment_time = IF({$_POST["PN_ID"]} = 0, NULL, NOW())
		WHERE WT_ID = {$_POST["WT_ID"]} AND nextID = {$_POST["nextID"]}
	";
	mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	exit ('<meta http-equiv="refresh" content="0; url=/dct/shipment.php?WT_ID='.$_POST["WT_ID"].'&nextID='.$_POST["nextID"].'">');
}

//Изменение статуса поддона сканированием
if( isset($_GET["scan"]) ) {
	$query = "
		UPDATE list__PackingPallet
		SET PN_ID = 1
			,shipment_time = NOW()
		WHERE WT_ID = {$_GET["WT_ID"]} AND nextID = {$_GET["nextID"]}
	";
	mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	exit ('<meta http-equiv="refresh" content="0; url=/dct/shipment.php?WT_ID='.$_GET["WT_ID"].'&nextID='.$_GET["nextID"].'">');
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
						$(location).attr('href','/dct/shipment.php?WT_ID='+WT_ID+'&nextID='+nextID+'&scan');
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
				$WT_ID = str_pad($_GET["WT_ID"], 8, "0", STR_PAD_LEFT);
				$nextID = str_pad($_GET["nextID"], 8, "0", STR_PAD_LEFT);
			}
			?>

			<form method="post">
				<fieldset>
					<legend><b>ID поддона:</b></legend>
					<input type="text" name="barcode" style="width: 140px;" value="<?=$WT_ID?><?=$nextID?>">
					<input type="submit" value="OK">
				</fieldset>
			</form>
			<br>

		<?
		if( isset($_GET["WT_ID"]) ) {
			// Если было сканирование
			$query = "
				SELECT CW.item
					,DATE_FORMAT(LPP.packed_time, '%d.%m.%Y %H:%i') packed_time_format
					,DATE_FORMAT(LPP.shipment_time, '%d.%m.%Y %H:%i') shipment_time_format
					,IFNULL(LPP.PN_ID, 0) PN_ID
				FROM list__PackingPallet LPP
				JOIN CounterWeight CW ON CW.CW_ID = LPP.CW_ID
				WHERE LPP.WT_ID = {$_GET["WT_ID"]} AND LPP.nextID = {$_GET["nextID"]}
			";
			$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			$row = mysqli_fetch_array($res);

			//echo "<h1 style='text-align: center;'>{$WT_ID}{$nextID}</h1>";

			if( $row["packed_time_format"] == "" ) die("<h1 style='color: red;'>Поддон с таким номером не найден!</h1>");

			//Форма изменения статуса противовеса
			?>
			<fieldset>
				<legend><b>Статус поддона:</b></legend>
				<form method="post" style="font-size: 2em;">
					<input type="hidden" name="WT_ID" value="<?=$_GET["WT_ID"]?>">
					<input type="hidden" name="nextID" value="<?=$_GET["nextID"]?>">
					<select name="PN_ID" onchange="this.form.submit()" style="font-size: 1em;">
						<option value="0">На складе</option>
						<option value="1">Отгружен</option>
						<?
//						$query = "
//							SELECT PN.PN_ID
//								,PN.pallet_name
//							FROM pallet__Name PN
//							ORDER BY PN.PN_ID
//						";
//						$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
//						while( $subrow = mysqli_fetch_array($subres) ) {
//							echo "<option value='{$subrow["PN_ID"]}'>Отгружен ({$subrow["pallet_name"]})</option>";
//						}
						?>
					</select>
				</form>
			</fieldset>
			<script>
				$(function() {
					$('select[name="PN_ID"]').val(<?=$row["PN_ID"]?>);
				});
			</script>
			<?

			echo "
				<br>
				Код: <b>{$row["item"]}</b><br>
				Контроль: <b>{$row["packed_time_format"]}</b><br>
				".($row["PN_ID"] ? "<span style='color: darkgreen;'>Отгрузка: <b>{$row["shipment_time_format"]}</b></span>" : "")."
			";
		}
		?>
	</body>
</html>
