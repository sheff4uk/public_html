<?php
include_once "../config.php";
$title = 'Брак';

$ip = $_SERVER['REMOTE_ADDR'];

// Узнаем участок
$query = "
	SELECT F_ID
	FROM factory
	WHERE from_ip = '{$ip}'
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
$row = mysqli_fetch_array($res);
$F_ID = $row["F_ID"];

if( !$F_ID ) die("Access denied");

//ID противовеса введен вручную
if( isset($_POST["barcode"]) ) {
	$WT_ID = substr($_POST["barcode"], 0, 8);
	$nextID = substr($_POST["barcode"], -8, 8);
	exit ('<meta http-equiv="refresh" content="0; url=/dct/cwreject.php?WT_ID='.$WT_ID.'&nextID='.$nextID.'">');
}

//Изменение статуса противовеса
if( isset($_POST["WT_ID"]) ) {
	$query = "
		UPDATE list__Weight
		SET goodsID = {$_POST["goodsID"]}
		WHERE WT_ID = {$_POST["WT_ID"]} AND nextID = {$_POST["nextID"]}
	";
	mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	exit ('<meta http-equiv="refresh" content="0; url=/dct/cwreject.php?WT_ID='.$_POST["WT_ID"].'&nextID='.$_POST["nextID"].'">');
}
include "header.php";
?>

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
		<h3>Отсканируйте противовес</h3>
		<?php
		if( isset($_GET["WT_ID"]) ) {
			$WT_ID = str_pad($_GET["WT_ID"], 8, "0", STR_PAD_LEFT);
			$nextID = str_pad($_GET["nextID"], 8, "0", STR_PAD_LEFT);
		}
		?>

		<form method="post">
			<fieldset>
				<legend><b>ID противовеса:</b></legend>
				<input type="text" name="barcode" style="width: 210px; font-size: 1.4em;" value="<?=$WT_ID?><?=$nextID?>">
				<input type="submit" style="font-size: 1.4em; background-color: yellow;" value="OK">
			</fieldset>
		</form>
		<br>

		<?php
		if( isset($_GET["WT_ID"]) ) {
			$query = "
				SELECT CW.item
					,LW.weight
					,DATE_FORMAT(LW.weighing_time, '%d.%m.%Y %H:%i') weighing_time_format
					,LW.goodsID
					,LO.cassette
					,DATE_FORMAT(LF.filling_time, '%d.%m.%Y %H:%i') filling_time_format
				FROM list__Weight LW
				JOIN list__Opening LO ON LO.LO_ID = LW.LO_ID
				JOIN list__Filling LF ON LF.LF_ID = LO.LF_ID
				JOIN list__Batch LB ON LB.LB_ID = LF.LB_ID
				JOIN plan__Batch PB ON PB.PB_ID = LB.PB_ID
				JOIN CounterWeight CW ON CW.CW_ID = PB.CW_ID
				WHERE LW.WT_ID = {$_GET["WT_ID"]} AND LW.nextID = {$_GET["nextID"]}
			";
			$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			$row = mysqli_fetch_array($res);

			//$WT_ID = str_pad($_GET["WT_ID"], 8, "0", STR_PAD_LEFT);
			//$nextID = str_pad($_GET["nextID"], 8, "0", STR_PAD_LEFT);
			//echo "<h1 style='text-align: center;'>{$WT_ID}{$nextID}</h1>";

			if( $row["weighing_time_format"] == "" ) die("<h1 style='color: red;'>Противовес с таким номером не найден!</h1>");

			//Форма изменения статуса противовеса
			?>
			<fieldset>
				<legend>Статус противовеса</legend>
				<form method="post" style="font-size: 2em;">
					<input type="hidden" name="WT_ID" value="<?=$_GET["WT_ID"]?>">
					<input type="hidden" name="nextID" value="<?=$_GET["nextID"]?>">
					<select name="goodsID" onchange="this.form.submit()" style="font-size: 1em;">
						<option value="1">OK</option>
						<option value="2">Непролив</option>
						<option value="3">Мех. трещина</option>
						<option value="4">Усад. трещина</option>
						<option value="5">Скол</option>
						<option value="6">Дефект формы</option>
						<option value="7">Дефект сборки</option>
					</select>
				</form>
			</fieldset>
			<script>
				$(function() {
					$('select[name="goodsID"]').val(<?=$row["goodsID"]?>);
				});
			</script>
			<?php

			echo "Код: <b>{$row["item"]}</b><br>";
			echo "Заливка: <b>{$row["filling_time_format"]}</b><br>";
			echo "Кассета: <b>{$row["cassette"]}</b><br>";
			echo "Вес: <b>{$row["weight"]}</b> г.<br>";
			echo "Регистрация: <b>{$row["weighing_time_format"]}</b><br>";
		}
		?>
	</body>
</html>
