<?
include_once "../config.php";

// Сохранение/редактирование расформовки
if( isset($_POST["LB_ID"]) ) {
	session_start();
	$LB_ID = $_POST["LB_ID"];
	$test_date = $_POST["test_date"];
	$test_time = $_POST["test_time"];
	$cube_weight = $_POST["cube_weight"]*1000;
	$pressure = $_POST["pressure"];

	if( $_POST["LCT_ID"] ) { // Редактируем
		$query = "
			UPDATE list__CubeTest
			SET LB_ID = {$LB_ID}
				,test_date = '{$test_date}'
				,test_time = '{$test_time}'
				,cube_weight = {$cube_weight}
				,pressure = {$pressure}
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
			SET LB_ID = {$LB_ID}
				,test_date = '{$test_date}'
				,test_time = '{$test_time}'
				,cube_weight = {$cube_weight}
				,pressure = {$pressure}
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
				<span>Замес:</span>
				<select name="LB_ID" id="batch_select" style="width: 300px;" required>
					<!--Данные аяксом-->
				</select>
			</div>

			<table style="width: 100%; table-layout: fixed;">
				<thead>
					<tr>
						<th>Дата испытания</th>
						<th>Время испытания</th>
						<th>Масса куба, кг</th>
						<th>Давление, МПа</th>
					</tr>
				</thead>
				<tbody style="text-align: center;">
					<tr>
						<td><input type='date' name='test_date' required></td>
						<td><input type='time' name='test_time' required></td>
						<td><input type='number' min='1' max='4' step='0.01' name='cube_weight' style='width: 80px;' required></td>
						<td><input type='number' name='pressure' style='width: 80px;' required></td>
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
		$('#cubetest_form select[name="LB_ID"]').select2({ placeholder: 'Выберите замес', language: 'ru' });
		// Костыль для Select2 чтобы работал поиск
		$.ui.dialog.prototype._allowInteraction = function (e) {
			return true;
		};

//		<?
//		if( isset($_GET["add"]) ) {
//		?>
//		// Если было добавление, автоматичеки открывается форма для новой записи
//		$(document).ready(function() {
//			$('#add_btn').click();
//		});
//		<?
//		}
//		?>

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
				// Генерируем список свободных заливок
				$.ajax({ url: "/ajax/batch_select.php?LB_ID=" + test_data['LB_ID'], dataType: "script", async: false });

				$('#cubetest_form input[name="LCT_ID"]').val(LCT_ID);
				$('#cubetest_form select[name="LB_ID"]').val(test_data['LB_ID']);
				$('#cubetest_form input[name="test_date"]').val(test_data['test_date']);
				$('#cubetest_form input[name="test_time"]').val(test_data['test_time']);
				$('#cubetest_form input[name="cube_weight"]').val(test_data['cube_weight']);
				$('#cubetest_form input[name="pressure"]').val(test_data['pressure']);
			}
			// Иначе очищаем форму
			else {
				// Генерируем список свободных заливок
				$.ajax({ url: "/ajax/batch_select.php", dataType: "script", async: false });

				$('#cubetest_form input[name="LCT_ID"]').val('');
				$('#cubetest_form table input').val('');
				$('#cubetest_form input[name="test_date"]').val(test_date);
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
