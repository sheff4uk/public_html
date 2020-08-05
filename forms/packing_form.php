<?
include_once "../config.php";

// Сохранение/редактирование расформовки
if( isset($_POST["LF_ID"]) ) {
	session_start();
	$LF_ID = $_POST["LF_ID"];
	$p_post = $_POST["p_post"];
	$p_date = $_POST["p_date"];
	$p_time = $_POST["p_time"];
	$p_not_spill = $_POST["p_not_spill"] ? $_POST["p_not_spill"] : "NULL";
	$p_crack = $_POST["p_crack"] ? $_POST["p_crack"] : "NULL";
	$p_chipped = $_POST["p_chipped"] ? $_POST["p_chipped"] : "NULL";
	$p_def_form = $_POST["p_def_form"] ? $_POST["p_def_form"] : "NULL";

	if( $_POST["LP_ID"] ) { // Редактируем
		$query = "
			UPDATE list__Packing
			SET LF_ID = {$LF_ID}
				,p_post = {$p_post}
				,p_date = '{$p_date}'
				,p_time = '{$p_time}'
				,p_not_spill = {$p_not_spill}
				,p_crack = {$p_crack}
				,p_chipped = {$p_chipped}
				,p_def_form = {$p_def_form}
			WHERE LP_ID = {$_POST["LP_ID"]}
		";
		if( !mysqli_query( $mysqli, $query ) ) {
			$_SESSION["error"][] = "Invalid query: ".mysqli_error( $mysqli );
		}
		$LP_ID = $_POST["LP_ID"];
	}
	else { // Добавляем
		$query = "
			INSERT INTO list__Packing
			SET LF_ID = {$LF_ID}
				,p_post = {$p_post}
				,p_date = '{$p_date}'
				,p_time = '{$p_time}'
				,p_not_spill = {$p_not_spill}
				,p_crack = {$p_crack}
				,p_chipped = {$p_chipped}
				,p_def_form = {$p_def_form}
		";
		if( !mysqli_query( $mysqli, $query ) ) {
			$_SESSION["error"][] = "Invalid query: ".mysqli_error( $mysqli );
		}
		else {
			$add = 1;
			$LP_ID = mysqli_insert_id( $mysqli );
		}
	}

	if( count($_SESSION["error"]) == 0) {
		$_SESSION["success"][] = $add ? "Новыя запись успешно добавлена." : "Запись успешно отредактирована.";
	}

	// Перенаправление в журнал маршрутных листов
	if( $add ) {
		exit ('<meta http-equiv="refresh" content="0; url=/packing.php?date_from='.$p_date.'&date_to='.$p_date.'&p_date='.$p_date.'&p_post='.$p_post.'&add#'.$LP_ID.'">');
	}
	else {
		exit ('<meta http-equiv="refresh" content="0; url=/packing.php?date_from='.$p_date.'&date_to='.$p_date.'#'.$LP_ID.'">');
	}
}
?>

<style>
	#packing_form table input,
	#packing_form table select {
		font-size: 1.2em;
	}
</style>

<div id='packing_form' title='Данные упаковки' style='display:none;'>
	<form method='post' action="/forms/packing_form.php" onsubmit="JavaScript:this.subbut.disabled=true;
this.subbut.value='Подождите, пожалуйста!';">
		<fieldset>
			<input type="hidden" name="LP_ID">

			<div class="nowrap" style="display: inline-block; margin-bottom: 10px; margin-right: 30px;">
				<span>Заливка:</span>
				<select name="LF_ID" id="filling_select" style="width: 300px;" required>
					<!--Данные аяксом-->
				</select>
			</div>
			<div class="nowrap" style="display: inline-block; margin-bottom: 10px; margin-right: 30px;">
				<span>Дата упаковки:</span>
				<input type="date" name="p_date" required>
			</div>
			<div class="nowrap" style="display: inline-block; margin-bottom: 10px; margin-right: 30px;">
				<span>№ поста:</span>
				<input type="number" min="1" max="10" name="p_post" required>
			</div>

			<table style="width: 100%; table-layout: fixed;">
				<thead>
					<tr>
						<th rowspan="2">Время</th>
						<th colspan="4">Кол-во брака, шт</th>
					</tr>
					<tr>
						<th>Непролив</th>
						<th>Трещина</th>
						<th>Скол</th>
						<th>Дефект форм</th>
					</tr>
				</thead>
				<tbody style="text-align: center;">
					<tr>
						<td><input type='time' name='p_time' required></td>
						<td><input type="number" name="p_not_spill" min="0" style="width: 70px;"></td>
						<td><input type="number" name="p_crack" min="0" style="width: 70px;"></td>
						<td><input type="number" name="p_chipped" min="0" style="width: 70px;"></td>
						<td><input type="number" name="p_def_form" min="0" style="width: 70px;"></td>
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
		$('#packing_form select[name="LF_ID"]').select2({ placeholder: 'Выберите заливку', language: 'ru' });
		// Костыль для Select2 чтобы работал поиск
		$.ui.dialog.prototype._allowInteraction = function (e) {
			return true;
		};

		<?
		if( isset($_GET["add"]) ) {
		?>
		// Если было добавление упаковки, автоматичеки открывается форма для новой записи
		$(document).ready(function() {
			$('#add_btn').click();
		});
		<?
		}
		?>

		// Кнопка добавления упаковки
		$('.add_packing').click( function() {
			// Проверяем сессию
			$.ajax({ url: "check_session.php?script=1", dataType: "script", async: false });

			var LP_ID = $(this).attr("LP_ID"),
				p_date = $(this).attr("p_date"),
				p_post = $(this).attr("p_post");

			// В случае редактирования заполняем форму
			if( LP_ID ) {
				// Данные аяксом
				$.ajax({
					url: "/ajax/packing_json.php?LP_ID=" + LP_ID,
					success: function(msg) { packing_data = msg; },
					dataType: "json",
					async: false
				});
				// Генерируем список свободных заливок
				$.ajax({ url: "/ajax/filling_select.php?LF_ID=" + packing_data['LF_ID'] + "&type=2", dataType: "script", async: false });

				// Идентификатор упаковки
				$('#packing_form input[name="LP_ID"]').val(LP_ID);
				// Заливка
				$('#packing_form select[name="LF_ID"]').val(packing_data['LF_ID']);
				// № поста
				$('#packing_form input[name="p_post"]').val(packing_data['p_post']);
				// Дата/время упаковки
				$('#packing_form input[name="p_date"]').val(packing_data['p_date']);
				$('#packing_form input[name="p_time"]').val(packing_data['p_time']);
				// Дефекты упаковки
				$('#packing_form input[name="p_not_spill"]').val(packing_data['p_not_spill']);
				$('#packing_form input[name="p_crack"]').val(packing_data['p_crack']);
				$('#packing_form input[name="p_chipped"]').val(packing_data['p_chipped']);
				$('#packing_form input[name="p_def_form"]').val(packing_data['p_def_form']);
			}
			// Иначе очищаем форму
			else {
				$('#packing_form input[name="LP_ID"]').val('');
				// Генерируем список свободных заливок
				$.ajax({ url: "/ajax/filling_select.php?type=2", dataType: "script", async: false });

				$('#packing_form input[name="p_date"]').val(p_date);
				$('#packing_form input[name="p_post"]').val(p_post);
				$('#packing_form table input').val('');
				$('#packing_form table select').val('');
			}

			$('#packing_form').dialog({
				resizable: false,
				width: 1000,
				modal: true,
				closeText: 'Закрыть'
			});

			return false;
		});
	});
</script>
