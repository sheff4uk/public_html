<?
include_once "../config.php";

//Списание
if( isset($_POST["SI_ID"]) ) {
	if( $_POST["exfolation"] or $_POST["crack"] or $_POST["chipped"] ) {
		$query = "
			UPDATE shell__Item
			SET reject_date = CURDATE()
				".($_POST["exfolation"] ? ",exfolation = 1" : "")."
				".($_POST["crack"] ? ",crack = 1" : "")."
				".($_POST["chipped"] ? ",chipped = 1" : "")."
			WHERE SI_ID = {$_POST["SI_ID"]}
		";
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		exit ('<meta http-equiv="refresh" content="0; url=/dct/formreject.php?SI_ID='.$_POST["SI_ID"].'">');
	}
	else {
		echo "<h1 style='color: red;'>Укажите хотябы один дефект!</h1>";
	}
}
?>

<!DOCTYPE html>
<html lang="ru">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<title>Списание форм</title>
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
						// Если это форма
						if( barcode.length == 8 && barcode[0] == "2" ) {
							var SI_ID = Number(barcode.substr(1, 7));
							$(location).attr('href','/dct/formreject.php?SI_ID='+SI_ID);
							barcode="";
							return false;
						}
						barcode="";
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
		<h3>Отсканируйте штрихкод</h3>
		<?
		if( isset($_GET["SI_ID"]) ) {
			$query = "
				SELECT DATE_FORMAT(SA.sa_date, '%d.%m.%Y') sa_date_format
					,CW.item
					,DATE_FORMAT(SI.reject_date, '%d.%m.%Y') reject_date_format
				FROM shell__Item SI
				JOIN shell__Arrival SA ON SA.SA_ID = SI.SA_ID
				JOIN CounterWeight CW ON CW.CW_ID = SA.CW_ID
				WHERE SI.SI_ID = {$_GET["SI_ID"]}
			";
			$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			$row = mysqli_fetch_array($res);
			echo "<h1 style='text-align: center;'>{$_GET["SI_ID"]}</h1>";

			//Если списана, выводим дату списания
			if( $row["reject_date_format"] ) {
				echo "Дата списания: <b style='color: red;'>{$row["reject_date_format"]}</b><br>";
			}
			//Иначе выводим форму для списания
			else {
				?>
				<fieldset>
					<legend>Списание формы</legend>
					<form method="post" style="font-size: 2em;">
						<input type="hidden" name="SI_ID" value="<?=$_GET["SI_ID"]?>">
						<label><input type="checkbox" name="exfolation" value="1" style="width: 30px; height: 30px;">Расслоение</label><br>
						<label><input type="checkbox" name="crack" value="1" style="width: 30px; height: 30px;">Трещина</label><br>
						<label><input type="checkbox" name="chipped" value="1" style="width: 30px; height: 30px;">Скол</label><br>
						<input type="submit" value="Списать">
						<span style="color: red; font-size: .5em;">Это действие отменить невозможно!</span>
					</form>
				</fieldset>
				<?
			}

			echo "Код формы: <b>{$row["item"]}</b><br>";
			echo "Дата прихода: <b>{$row["sa_date_format"]}</b><br>";
			echo "<h4>Циклы:</h4>";

			$query = "
				SELECT DATE_FORMAT(SL.event_time, '%d.%m.%Y %h:%i') event_time_format
				FROM shell__Log SL
				WHERE SL.SI_ID = {$_GET["SI_ID"]}
				ORDER BY SL.event_time
			";
			$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			while( $row = mysqli_fetch_array($res) ) {
				echo "• {$row["event_time_format"]}<br>";
			}
		}
		?>
	</body>
</html>
