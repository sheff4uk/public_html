<?
include_once "../config.php";

// Сохранение/редактирование расформовки
if( isset($_POST["CW_ID"]) ) {
	session_start();
	$test_date = $_POST["test_date"];
	$CW_ID = $_POST["CW_ID"];
	$h24_test_time = $_POST["24_test_time"];
	$h24_cube_weight = $_POST["24_cube_weight"]*1000;
	$h24_pressure = $_POST["24_pressure"];
	$h72_test_time = $_POST["72_test_time"];
	$h72_cube_weight = $_POST["72_cube_weight"]*1000;
	$h72_pressure = $_POST["72_pressure"];

	if( $_POST["LCT_ID"] ) { // Редактируем
		$query = "
			UPDATE list__CubeTest
			SET test_date = '{$test_date}'
				,CW_ID = {$CW_ID}
				,24_test_time = '{$h24_test_time}'
				,24_cube_weight = {$h24_cube_weight}
				,24_pressure = {$h24_pressure}
				,72_test_time = '{$h72_test_time}'
				,72_cube_weight = {$h72_cube_weight}
				,72_pressure = {$h72_pressure}
			WHERE LCT_ID = {$_POST["LCT_ID"]}
		";
		if( !mysqli_query( $mysqli, $query ) ) {
			$_SESSION["error"][] = "Invalid query: ".mysqli_error( $mysqli );
		}
		$LCT_ID = $_POST["LCT_ID"];
	}
	else { // Добавляем
		$query = "
			INSERT INTO list__CubeTest
			SET test_date = '{$test_date}'
				,CW_ID = {$CW_ID}
				,24_test_time = '{$h24_test_time}'
				,24_cube_weight = {$h24_cube_weight}
				,24_pressure = {$h24_pressure}
				,72_test_time = '{$h72_test_time}'
				,72_cube_weight = {$h72_cube_weight}
				,72_pressure = {$h72_pressure}
		";
		if( !mysqli_query( $mysqli, $query ) ) {
			$_SESSION["error"][] = "Invalid query: ".mysqli_error( $mysqli );
		}
		else {
			$add = 1;
			$LCT_ID = mysqli_insert_id( $mysqli );
		}
	}

	if( count($_SESSION["error"]) == 0) {
		$_SESSION["success"][] = $add ? "Новыя запись успешно добавлена." : "Запись успешно отредактирована.";
	}

	// Перенаправление в журнал
	if( $add ) {
		exit ('<meta http-equiv="refresh" content="0; url=/cubetest.php?test_date='.$test_date.'&add#'.$LCT_ID.'">');
	}
	else {
		exit ('<meta http-equiv="refresh" content="0; url=/cubetest.php#'.$LCT_ID.'">');
	}
}
?>

<style>
	#cubetest_form table input,
	#cubetest_form table select {
		font-size: 1.2em;
	}
</style>

<div id='cubetest_form' title='Данные протокола испытаний куба' style='display:none;'>
	<form method='post' action="/forms/cubetest_form.php" onsubmit="JavaScript:this.subbut.disabled=true;
this.subbut.value='Подождите, пожалуйста!';">
		<fieldset>
			<input type="hidden" name="LCT_ID">

			<div class="nowrap" style="display: inline-block; margin-bottom: 10px; margin-right: 30px;">
				<span>Дата испытания:</span>
				<input type="date" name="test_date" required>
			</div>

			<table style="width: 100%; table-layout: fixed;">
				<thead>
					<tr>
						<th rowspan="2">Противовес</th>
						<th colspan="3">24 часа</th>
						<th colspan="3">72 часа</th>
					</tr>
					<tr>
						<th>Время теста</th>
						<th>Масса куба, кг</th>
						<th>Давление, МПа</th>
						<th>Время теста</th>
						<th>Масса куба, кг</th>
						<th>Давление, МПа</th>
					</tr>
				</thead>
				<tbody style="text-align: center;">
					<tr>
						<td>
							<select name="CW_ID" style="width: 150px;" required>
								<option value=""></option>
								<?
								$query = "
									SELECT CW.CW_ID, CW.item
									FROM CounterWeight CW
									ORDER BY CW.CW_ID
								";
								$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
								while( $row = mysqli_fetch_array($res) ) {
									echo "<option value='{$row["CW_ID"]}'>{$row["item"]}</option>";
								}
								?>
							</select>
						</td>
						<td><input type='time' name='24_test_time' required></td>
						<td><input type='number' min='1' max='4' step='0.01' name='24_cube_weight' style='width: 80px;' required></td>
						<td><input type='number' name='24_pressure' style='width: 80px;' required></td>
						<td><input type='time' name='72_test_time' required></td>
						<td><input type='number' min='1' max='4' step='0.01' name='72_cube_weight' style='width: 80px;' required></td>
						<td><input type='number' name='72_pressure' style='width: 80px;' required></td>
					</tr>
				</tbody>
			</table>
		</fieldset>
		<div>
			<hr>
			<input type='submit' name="subbut" value='Записать' style='float: right;'>
		</div>
	</form>
</div>

<script>
	$(function() {
		<?
		if( isset($_GET["add"]) ) {
		?>
		// Если было добавление, автоматичеки открывается форма для новой записи
		$(document).ready(function() {
			$('#add_btn').click();
		});
		<?
		}
		?>

		// Кнопка добавления расформовки
		$('.add_cubetest').click( function() {
			// Проверяем сессию
			$.ajax({ url: "check_session.php?script=1", dataType: "script", async: false });

			var LCT_ID = $(this).attr("LCT_ID"),
				test_date = $(this).attr("test_date");

			// В случае редактирования заполняем форму
			if( LCT_ID ) {
				// Данные аяксом
				$.ajax({
					url: "/ajax/cubetest_json.php?LCT_ID=" + LCT_ID,
					success: function(msg) { test_data = msg; },
					dataType: "json",
					async: false
				});

				$('#cubetest_form input[name="LCT_ID"]').val(LCT_ID);
				$('#cubetest_form input[name="test_date"]').val(test_data['test_date']);
				$('#cubetest_form select[name="CW_ID"]').val(test_data['CW_ID']);
				$('#cubetest_form input[name="24_test_time"]').val(test_data['24_test_time']);
				$('#cubetest_form input[name="24_cube_weight"]').val(test_data['24_cube_weight']);
				$('#cubetest_form input[name="24_pressure"]').val(test_data['24_pressure']);
				$('#cubetest_form input[name="72_test_time"]').val(test_data['72_test_time']);
				$('#cubetest_form input[name="72_cube_weight"]').val(test_data['72_cube_weight']);
				$('#cubetest_form input[name="72_pressure"]').val(test_data['72_pressure']);
			}
			// Иначе очищаем форму
			else {
				$('#cubetest_form input[name="LCT_ID"]').val('');
				$('#cubetest_form input[name="test_date"]').val(test_date);
				$('#cubetest_form select[name="CW_ID"]').val('');
				$('#cubetest_form table input').val('');
				$('#cubetest_form table select').val('');
			}

			$('#cubetest_form').dialog({
				resizable: false,
				width: 1000,
				modal: true,
				closeText: 'Закрыть'
			});

			return false;
		});
	});
</script>
