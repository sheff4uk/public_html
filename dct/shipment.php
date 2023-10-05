<?
include_once "../config.php";
$ip = $_SERVER['REMOTE_ADDR'];

// Узнаем участок
$query = "
	SELECT F_ID
		,shipment_group
	FROM factory
	WHERE from_ip = '{$ip}'
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
$row = mysqli_fetch_array($res);
$F_ID = $row["F_ID"];

if( !$F_ID ) die("Access denied");

$shipment_group = $row["shipment_group"];

//ID паллета введен вручную
if( isset($_POST["barcode"]) ) {
	$WT_ID = substr($_POST["barcode"], 0, 8);
	$nextID = substr($_POST["barcode"], -8, 8);
	exit ('<meta http-equiv="refresh" content="0; url=/dct/shipment.php?WT_ID='.$WT_ID.'&nextID='.$nextID.'">');
}

//Изменение статуса паллета из формы
if( isset($_POST["WT_ID"]) ) {
	$query = "
		UPDATE list__PackingPallet
		SET scan_time = IF('{$_POST["subbut"]}' = 'В список', NOW(), NULL)
		WHERE WT_ID = {$_POST["WT_ID"]} AND nextID = {$_POST["nextID"]}
	";
	mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	exit ('<meta http-equiv="refresh" content="0; url=/dct/shipment.php?WT_ID='.$_POST["WT_ID"].'&nextID='.$_POST["nextID"].'">');
}

//Отгрузка машины
if( isset($_POST["lpp_id"]) ) {
	$LPP_IDs = "0";
	foreach ($_POST["lpp_id"] as $key => $value) {
		$LPP_IDs .= ",{$value}";
	}

	// Время отгрузки
	$query = "
		SELECT NOW() `now`
	";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query1: " .mysqli_error( $mysqli ));
	$row = mysqli_fetch_array($res);
	$now = $row["now"];

	if( isset($_POST["ps_id"]) ) {
		$query = "
			UPDATE plan__Shipment
			SET shipment_time = '{$now}'
			WHERE PS_ID = {$_POST["ps_id"]}
		";
		mysqli_query( $mysqli, $query ) or die("Invalid query2: " .mysqli_error( $mysqli ));
	}

	$query = "
		UPDATE list__PackingPallet
		SET shipment_time = '{$now}'
			,removal_time = NULL
		WHERE LPP_ID IN ({$LPP_IDs}) AND shipment_time IS NULL
	";
	mysqli_query( $mysqli, $query ) or die("Invalid query3: " .mysqli_error( $mysqli ));

	if( mysqli_affected_rows($mysqli) ) {
		// Сообщение в телеграм об отгрузке машины
		//$message = "🚛";
		$message = "";
		$query = "
			SELECT CONCAT(CW.item, ' (', CWP.in_pallet, 'шт)') item
				,SUM(1) cnt
			FROM list__PackingPallet LPP
			JOIN CounterWeightPallet CWP ON CWP.CWP_ID = LPP.CWP_ID
			JOIN CounterWeight CW ON CW.CW_ID = CWP.CW_ID
			WHERE LPP.LPP_ID IN ({$LPP_IDs})
			GROUP BY LPP.CWP_ID
			ORDER BY LPP.CWP_ID
		";
		$res = mysqli_query( $mysqli, $query ) or die("Invalid query4: " .mysqli_error( $mysqli ));
		while( $row = mysqli_fetch_array($res) ) {
			$message .= "\n{$row["item"]} x {$row["cnt"]}";
		}
		message_to_telegram($message, $shipment_group);
	}

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
		<h3>Отсканируйте паллет</h3>
			<?
			if( $_GET["WT_ID"] ) {
				$WT_ID = str_pad($_GET["WT_ID"], 8, "0", STR_PAD_LEFT);
				$nextID = str_pad($_GET["nextID"], 8, "0", STR_PAD_LEFT);
			}
			?>

			<fieldset>
				<legend><b>ID паллета:</b></legend>
				<form method="post">
					<input type="text" name="barcode" style="width: 210px; font-size: 1.4em;" value="<?=$WT_ID?><?=$nextID?>">
					<input type="submit" style="font-size: 1.4em; background-color: yellow;" value="OK">
				</form>
			</fieldset>
			<br>

		<?
		if( $_GET["WT_ID"] ) {
			// Если было сканирование
			$query = "
				SELECT LPP.LPP_ID
					,CONCAT(CW.item, ' (', CWP.in_pallet, 'шт)') item
					,DATE_FORMAT(LPP.packed_time, '%d.%m.%Y %H:%i:%s') packed_time_format
					,DATE_FORMAT(LPP.scan_time, '%d.%m.%Y %H:%i:%s') scan_time_format
					,DATE_FORMAT(LPP.shipment_time, '%d.%m.%Y %H:%i:%s') shipment_time_format
					,(114 - TIMESTAMPDIFF(HOUR, LPP.packed_time, NOW())) duration
					#,0 duration
				FROM list__PackingPallet LPP
				JOIN CounterWeightPallet CWP ON CWP.CWP_ID = LPP.CWP_ID
				JOIN CounterWeight CW ON CW.CW_ID = CWP.CW_ID
				WHERE LPP.WT_ID = {$_GET["WT_ID"]}
					AND LPP.nextID = {$_GET["nextID"]}
					AND LPP.F_ID = {$F_ID}
			";
			$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			$row = mysqli_fetch_array($res);

			$LPP_ID = $row["LPP_ID"];

			if( $row["packed_time_format"] == "" ) {
				echo "<h1 style='color: red;'>Паллет с таким номером не найден!</h1>";
			}
			else {
				echo "
					<span style='display: inline-block; width: 120px;'>Код:</span><b style='font-size: 2em;'>{$row["item"]}</b><br>
					<span style='display: inline-block; width: 120px;'>Контроль:</span><b>{$row["packed_time_format"]}</b><br>
					".($row["scan_time_format"] ? "<span style='display: inline-block; width: 120px;'>Сканирование:</span><span style='color: green;'><b>{$row["scan_time_format"]}</b></span><br>" : "")."
					".($row["shipment_time_format"] ? "<span style='display: inline-block; width: 120px;'>Отгрузка:</span><span style='color: red;'><b>{$row["shipment_time_format"]}</b></span>" : "")."
					<br>
				";
	
				echo "<fieldset id='do'>";
				echo "<form method='post'>";
				if( $row["scan_time_format"] ) {
					if( $row["shipment_time_format"] ) {
						echo "<font color='red'>Данный паллет отгружен</font>";
					}
					else {
						?>
						<font color="red">Данный паллет в списке на отгрузку</font><br><br>
						<input type="hidden" name="WT_ID" value="<?=$_GET["WT_ID"]?>">
						<input type="hidden" name="nextID" value="<?=$_GET["nextID"]?>">
						<input type="submit" name="subbut" value="Из списка" style="background-color: red; font-size: 2em; color: white;">
						<?
					}
				}
				else {
					if( $row["duration"] > 0 and $F_ID == 1) {
						echo "<span style='color: #f00; font-size: 2em; font-weight: bold;'>Отгрузка запрещена!</span><br>";
						echo "<span>До полного созревания необходимо <b>{$row["duration"]}</b> ч.</span>";
					}
					else {
					?>
						<input type="hidden" name="WT_ID" value="<?=$_GET["WT_ID"]?>">
						<input type="hidden" name="nextID" value="<?=$_GET["nextID"]?>">
						<input type="submit" name="subbut" value="В список" style="background-color: green; font-size: 2em; color: white;">
					<?
					}
				}
				echo "</form>";
				echo "</fieldset>";
			}
		}

		$validation = 1;

		// График отгрузки
		if( $F_ID == 2 ) {
			echo "<fieldset>";
			echo "<legend><b>График отгрузки:</b></legend>";
			// Находим очередной график огрузки
			$query = "
				SELECT PS.PS_ID
					,SUM(PSC.quantity) pallets
				FROM plan__Shipment PS
				JOIN plan__ShipmentCWP PSC ON PSC.PS_ID = PS.PS_ID
				WHERE PS.F_ID = {$F_ID}
					AND PS.shipment_time IS NULL
					AND PS.ps_date <= CURDATE()
				GROUP BY PS.PS_ID
				HAVING pallets > 0
				ORDER BY PS.ps_date, PS.priority
				LIMIT 1
			";
			$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			$row = mysqli_fetch_array($res);
			$PS_ID = $row["PS_ID"];

			if( !$PS_ID ) {
				echo "<font color='red'>На сегодня отгрузок не запланировано!</font>";
				$validation = 0;
			}

			// Таблица план-факт отгрузки
			echo "<table cellspacing='0' cellpadding='2' border='1'>";
			echo "<thead>";
			echo "<tr>";
			echo "<th>Код</th>";
			echo "<th>План</th>";
			echo "<th>Факт</th>";
			echo "</tr>";
			echo "</thead>";
			echo "<tbody>";

			// Получаем список кодов запланированных и сканированных поддонов
			$query = "
				SELECT SUB.CWP_ID
					,CW.item
				FROM (
					SELECT PSC.CWP_ID
					FROM plan__ShipmentCWP PSC
					WHERE PSC.PS_ID = ".($PS_ID ? $PS_ID : 0)."
						AND PSC.quantity > 0

					UNION

					SELECT LPP.CWP_ID
					FROM list__PackingPallet LPP
					WHERE LPP.scan_time IS NOT NULL
						AND LPP.shipment_time IS NULL
						AND LPP.F_ID = {$F_ID}
					GROUP BY LPP.CWP_ID
				) SUB
				JOIN CounterWeightPallet CWP ON CWP.CWP_ID = SUB.CWP_ID
				JOIN CounterWeight CW ON CW.CW_ID = CWP.CW_ID
				ORDER BY CWP.CW_ID
			";
			$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			while( $row = mysqli_fetch_array($res) ) {
				echo "<tr>";
				echo "<td><b>{$row["item"]}</b></td>";
				// Построчное получение и вывод данных
				$query = "
					SELECT PSC.quantity plan
					FROM plan__ShipmentCWP PSC
					WHERE PSC.PS_ID = ".($PS_ID ? $PS_ID : 0)."
						AND PSC.CWP_ID = {$row["CWP_ID"]}
				";
				$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
				$subrow = mysqli_fetch_array($subres);
				$plan = $subrow["plan"];
				echo "<td>{$plan}</td>";

				$query = "
					SELECT SUM(1) fact
					FROM list__PackingPallet LPP
					WHERE LPP.scan_time IS NOT NULL
						AND LPP.shipment_time IS NULL
						AND LPP.F_ID = {$F_ID}
						AND LPP.CWP_ID = {$row["CWP_ID"]}
					GROUP BY LPP.CWP_ID
				";
				$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
				$subrow = mysqli_fetch_array($subres);
				$fact = $subrow["fact"];
				if( $plan != $fact ) {
					$validation = 0;
				}
				echo "<td ".($plan == $fact ? "style='background: green;'" : "").">{$fact}</td>";

				echo "</tr>";
			}
			echo "</tbody>";
			echo "</table>";
			echo "</fieldset>";
		}

		// Список подготовленных к отгрузке паллетов
		$i = 0;
		echo "<fieldset><legend><b>Сканированные паллеты:</b></legend>";
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
				,CONCAT(CW.item, ' (', CWP.in_pallet, 'шт)') item
				,DATE_FORMAT(LPP.scan_time, '%d.%m %H:%i:%s') scan_time_format
				,substr(lpad(LPP.nextID, 8, '0'), -4, 4) last4dig
			FROM list__PackingPallet LPP
			JOIN CounterWeightPallet CWP ON CWP.CWP_ID = LPP.CWP_ID
			JOIN CounterWeight CW ON CW.CW_ID = CWP.CW_ID
			WHERE LPP.scan_time IS NOT NULL
				AND LPP.shipment_time IS NULL
				AND LPP.F_ID = {$F_ID}
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

		if( $i > 0 ) {
			// Узнаем ограничение на количество паллетов в машине
			$query = "
				SELECT CB.limit_pallets
				FROM list__PackingPallet LPP
				JOIN CounterWeightPallet CWP ON CWP.CWP_ID = LPP.CWP_ID
				JOIN CounterWeight CW ON CW.CW_ID = CWP.CW_ID
				JOIN ClientBrand CB ON CB.CB_ID = CW.CB_ID
				WHERE LPP.scan_time IS NOT NULL
					AND LPP.shipment_time IS NULL
					AND LPP.F_ID = {$F_ID}
				GROUP BY CB.CB_ID
			";
			$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			$row = mysqli_fetch_array($res);
			$limit_pallets = $row["limit_pallets"];

			if( $limit_pallets > 0 ) {
				if( $i == $limit_pallets ) {
					$validation = 1;
				}
				else {
					$validation = 0;
				}
			}

			if( $validation == 1 ) {
				if( $F_ID == 2 ) {
					echo "<input type='hidden' name='ps_id' value='{$PS_ID}'>";
				}
				echo "<br><input type='submit' value='Отгрузить' style='background-color: red; font-size: 2em; color: white;'>";
				echo "<br><br><font color='red'>ВНИМАНИЕ! Отменить это действие не возможно.</font>";
			}
		}
		echo "</form>";
		echo "</fieldset>";
		?>
	</body>
</html>
