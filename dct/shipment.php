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
			,scan_time = IF({$_POST["PN_ID"]} = 0, NULL, NOW())
		WHERE WT_ID = {$_POST["WT_ID"]} AND nextID = {$_POST["nextID"]}
	";
	mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	exit ('<meta http-equiv="refresh" content="0; url=/dct/shipment.php?WT_ID='.$_POST["WT_ID"].'&nextID='.$_POST["nextID"].'">');
}

//Изменение статуса поддона сканированием
//if( isset($_GET["scan"]) ) {
//	$query = "
//		UPDATE list__PackingPallet
//		SET PN_ID = 1
//			,scan_time = NOW()
//		WHERE WT_ID = {$_GET["WT_ID"]} AND nextID = {$_GET["nextID"]}
//	";
//	mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
//	exit ('<meta http-equiv="refresh" content="0; url=/dct/shipment.php?WT_ID='.$_GET["WT_ID"].'&nextID='.$_GET["nextID"].'">');
//}

//Отгрузка машины
if( isset($_POST["lpp_id"]) ) {
	$LPP_IDs = "0";
	foreach ($_POST["lpp_id"] as $key => $value) {
		$LPP_IDs .= ",{$value}";
	}
	$query = "
		UPDATE list__PackingPallet
		SET shipment_time = NOW()
		WHERE LPP_ID IN ({$LPP_IDs})
	";
	mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

	// Сообщение в телеграм об отгрузке машины
	$message = "<b>Машина отправлена:</b>";
	$query = "
		SELECT CW.item
			,SUM(1) cnt
		FROM list__PackingPallet LPP
		JOIN CounterWeight CW ON CW.CW_ID = LPP.CW_ID
		WHERE LPP.LPP_ID IN ({$LPP_IDs})
		GROUP BY LPP.CW_ID
		ORDER BY LPP.CW_ID
	";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $row = mysqli_fetch_array($res) ) {
		$message .= "\n{$row["item"]} x {$row["cnt"]}";
	}
	message_to_telegram($message, '217756119');
	//message_to_telegram($bc, TELEGRAM_CHATID);

	exit ('<meta http-equiv="refresh" content="0; url=/dct/shipment.php">');
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
						//$(location).attr('href','/dct/shipment.php?WT_ID='+WT_ID+'&nextID='+nextID+'&scan');
						$(location).attr('href','/dct/shipment.php?WT_ID='+WT_ID+'&nextID='+nextID);
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
					<input type="text" name="barcode" style="width: 210px; font-size: 1.4em;" value="<?=$WT_ID?><?=$nextID?>">
					<input type="submit" style="font-size: 1.4em; background-color: yellow;" value="OK">
				</fieldset>
			</form>
			<br>

		<?
		if( isset($_GET["WT_ID"]) ) {
			// Если было сканирование
			$query = "
				SELECT LPP.LPP_ID
					,CW.item
					,DATE_FORMAT(LPP.packed_time, '%d.%m.%Y %H:%i:%s') packed_time_format
					,DATE_FORMAT(LPP.scan_time, '%d.%m.%Y %H:%i:%s') scan_time_format
					,DATE_FORMAT(LPP.shipment_time, '%d.%m.%Y %H:%i:%s') shipment_time_format
					,IFNULL(LPP.PN_ID, 0) PN_ID
				FROM list__PackingPallet LPP
				JOIN CounterWeight CW ON CW.CW_ID = LPP.CW_ID
				WHERE LPP.WT_ID = {$_GET["WT_ID"]} AND LPP.nextID = {$_GET["nextID"]}
			";
			$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			$row = mysqli_fetch_array($res);

			$LPP_ID = $row["LPP_ID"];

			//echo "<h1 style='text-align: center;'>{$WT_ID}{$nextID}</h1>";

			if( $row["packed_time_format"] == "" ) die("<h1 style='color: red;'>Поддон с таким номером не найден!</h1>");

			//Форма изменения статуса противовеса
			?>
			<fieldset style="display: none;">
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
				<span style='display: inline-block; width: 120px;'>Код:</span><b style='font-size: 2em;'>{$row["item"]}</b><br>
				<span style='display: inline-block; width: 120px;'>Контроль:</span><b>{$row["packed_time_format"]}</b><br>
				".($row["scan_time_format"] ? "<span style='display: inline-block; width: 120px;'>Сканирование:</span><span style='color: green;'><b>{$row["scan_time_format"]}</b></span><br>" : "")."
				".($row["shipment_time_format"] ? "<span style='display: inline-block; width: 120px;'>Отгрузка:</span><span style='color: red;'><b>{$row["shipment_time_format"]}</b></span>" : "")."
				<br>
			";

			echo "<fieldset id='do'>";
			echo "<form method='post'>";
			$status = 0; //Статус поддона
			if( $row["scan_time_format"] ) {
				if( $row["shipment_time_format"] ) {
					echo "<font color='red'>Данный поддон отгружен</font>";
				}
				else {
					?>
					<font color="red">Данный поддон в списке на отгрузку</font><br><br>
					<input type="hidden" name="WT_ID" value="<?=$_GET["WT_ID"]?>">
					<input type="hidden" name="nextID" value="<?=$_GET["nextID"]?>">
					<input type="hidden" name="PN_ID" value="0">
					<input type="submit" value="Из списка ✕" style="background-color: red; font-size: 2em; color: white;">
					<?
				}
			}
			else {
				$status = 1;
				?>
					<input type="hidden" name="WT_ID" value="<?=$_GET["WT_ID"]?>">
					<input type="hidden" name="nextID" value="<?=$_GET["nextID"]?>">
					<input type="hidden" name="PN_ID" value="1">
					<input type="submit" value="В список ⬇" style="background-color: green; font-size: 2em; color: white;">
				<?
			}
			echo "</form>";
			echo "</fieldset>";
		}

		// Список подготовленных к отгрузке поддонов
		$i = 0;
		echo "<fieldset><legend><b>Сканированные поддоны:</b></legend>";
		echo "<form method='post'>";
		echo "
			<table cellspacing='0' cellpadding='2' border='1'>
				<thead>
					<tr>
						<th>№ п/п</th>
						<th>Код</th>
						<th>Время сканирования</th>
						<th>Последние 4 цифры штрих-кода</th>
					</tr>
				</thead>
				<tbody>
		";
		$query = "
			SELECT LPP.LPP_ID
				,substr(CW.item, -3, 3) item
				,DATE_FORMAT(LPP.scan_time, '%d.%m %H:%i:%s') scan_time_format
				,substr(lpad(LPP.nextID, 8, '0'), -4, 4) last4dig
			FROM list__PackingPallet LPP
			JOIN CounterWeight CW ON CW.CW_ID = LPP.CW_ID
			WHERE LPP.scan_time IS NOT NULL
				AND LPP.shipment_time IS NULL
			ORDER BY LPP.scan_time
		";
		$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		while( $row = mysqli_fetch_array($res) ) {
			$i++;
			echo "
				<tr".($row["LPP_ID"] == $LPP_ID ? " style='background-color: yellow;'" : "").">
					<td><input type='hidden' name='lpp_id[]' value='{$row["LPP_ID"]}'>{$i}</td>
					<td>{$row["item"]}</td>
					<td>{$row["scan_time_format"]}</td>
					<td>{$row["last4dig"]}</td>
				</tr>
			";
		}
		echo "
				</tbody>
			</table>
		";
		if( $i == 22 ) {
			echo "<br><input type='submit' value='Отгрузить' style='background-color: red; font-size: 2em; color: white;'>";
			echo "<br><br><font color='red'>ВНИМАНИЕ! Отменить это действие не возможно.</font>";
			if( $status ) { // Если поддон не был в списке
				echo "
					<script>
						$('#do').html('<h2 style=\'color: red;\'>В списке 22 поддона. Добавление новых не возможно!</h2><h3>Нажмите \"Отгрузить\", чтобы очистить список.</h3>');
					</script>
				";
			}
		}
		echo "</form>";
		echo "</fieldset>";
		?>
	</body>
</html>
