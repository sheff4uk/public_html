<?
include_once "../config.php";

// Сохранение/редактирование расформовки
if( isset($_POST["LF_ID"]) ) {
	session_start();
	$LF_ID = $_POST["LF_ID"];
	$o_post = $_POST["o_post"];
	$o_date = $_POST["o_date"];
	$o_time = $_POST["o_time"];
	$o_not_spill = $_POST["o_not_spill"] ? $_POST["o_not_spill"] : "NULL";
	$o_crack = $_POST["o_crack"] ? $_POST["o_crack"] : "NULL";
	$o_chipped = $_POST["o_chipped"] ? $_POST["o_chipped"] : "NULL";
	$o_def_form = $_POST["o_def_form"] ? $_POST["o_def_form"] : "NULL";
	$weight1 = $_POST["weight1"]*1000;
	$weight2 = $_POST["weight2"]*1000;
	$weight3 = $_POST["weight3"]*1000;

	if( $_POST["LO_ID"] ) { // Редактируем
		$query = "
			UPDATE list__Opening
			SET LF_ID = {$LF_ID}
				,o_post = {$o_post}
				,o_date = '{$o_date}'
				,o_time = '{$o_time}'
				,o_not_spill = {$o_not_spill}
				,o_crack = {$o_crack}
				,o_chipped = {$o_chipped}
				,o_def_form = {$o_def_form}
				,weight1 = {$weight1}
				,weight2 = {$weight2}
				,weight3 = {$weight3}
			WHERE LO_ID = {$_POST["LO_ID"]}
		";
		if( !mysqli_query( $mysqli, $query ) ) {
			$_SESSION["error"][] = "Invalid query: ".mysqli_error( $mysqli );
		}
		$LO_ID = $_POST["LO_ID"];
	}
	else { // Добавляем
		$query = "
			INSERT INTO list__Opening
			SET LF_ID = {$LF_ID}
				,o_post = {$o_post}
				,o_date = '{$o_date}'
				,o_time = '{$o_time}'
				,o_not_spill = {$o_not_spill}
				,o_crack = {$o_crack}
				,o_chipped = {$o_chipped}
				,o_def_form = {$o_def_form}
				,weight1 = {$weight1}
				,weight2 = {$weight2}
				,weight3 = {$weight3}
		";
		if( !mysqli_query( $mysqli, $query ) ) {
			$_SESSION["error"][] = "Invalid query: ".mysqli_error( $mysqli );
		}
		else {
			$add = 1;
			$LO_ID = mysqli_insert_id( $mysqli );
		}
	}

	if( count($_SESSION["error"]) == 0) {
		$_SESSION["success"][] = $add ? "Новыя запись успешно добавлена." : "Запись успешно отредактирована.";
	}

	// Перенаправление в журнал маршрутных листов
	if( $add ) {
		exit ('<meta http-equiv="refresh" content="0; url=/opening.php?o_date='.$o_date.'&o_post='.$o_post.'&add#'.$LO_ID.'">');
	}
	else {
		exit ('<meta http-equiv="refresh" content="0; url=/opening.php#'.$LO_ID.'">');
	}
}
?>

<style>
	#opening_form table input,
	#opening_form table select {
		font-size: 1.2em;
	}
</style>

<div id='opening_form' title='Данные расформовки' style='display:none;'>
	<form method='post' action="/forms/opening_form.php" onsubmit="JavaScript:this.subbut.disabled=true;
this.subbut.value='Подождите, пожалуйста!';">
		<fieldset>
			<input type="hidden" name="LO_ID">

			<div class="nowrap" style="display: inline-block; margin-bottom: 10px; margin-right: 30px;">
				<span>Заливка:</span>
				<select name="LF_ID" id="filling_select" style="width: 200px;" required>
					<!--Данные аяксом-->
				</select>
			</div>
			<div class="nowrap" style="display: inline-block; margin-bottom: 10px; margin-right: 30px;">
				<span>Дата расформовки:</span>
				<input type="date" name="o_date" required>
			</div>
			<div class="nowrap" style="display: inline-block; margin-bottom: 10px; margin-right: 30px;">
				<span>№ поста:</span>
				<input type="number" min="1" max="10" name="o_post" required>
			</div>

			<table style="width: 100%; table-layout: fixed;">
				<thead>
					<tr>
						<th rowspan="2">Время</th>
						<th colspan="4">Кол-во брака, шт</th>
						<th colspan="3">Взвешивания, кг</th>
					</tr>
					<tr>
						<th>Непролив</th>
						<th>Трещина</th>
						<th>Скол</th>
						<th>Дефект форм</th>
						<th>№1</th>
						<th>№2</th>
						<th>№3</th>
					</tr>
				</thead>
				<tbody style="text-align: center;">
					<tr>
						<td><input type='time' name='o_time' required></td>
						<td><input type="number" name="o_not_spill" class="o_defect" min="0" style="width: 50px;"></td>
						<td><input type="number" name="o_crack" class="o_defect" min="0" style="width: 50px;"></td>
						<td><input type="number" name="o_chipped" class="o_defect" min="0" style="width: 50px;"></td>
						<td><input type="number" name="o_def_form" class="o_defect" min="0" style="width: 50px;"></td>
						<td><input type="number" name="weight1" min="5" max="20" step="0.01" required></td>
						<td><input type="number" name="weight2" min="5" max="20" step="0.01" required></td>
						<td><input type="number" name="weight3" min="5" max="20" step="0.01" required></td>
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
//		$('#opening_form select[name="LF_ID"]').select2({ placeholder: 'Выберите заливку', language: 'ru' });
//		// Костыль для Select2 чтобы работал поиск
//		$.ui.dialog.prototype._allowInteraction = function (e) {
//			return true;
//		};

		<?
		if( isset($_GET["add"]) ) {
		?>
		// Если было добавление расформовки, автоматичеки открывается форма для новой записи
		$(document).ready(function() {
			$('#add_btn').click();
		});
		<?
		}
		?>

		// Кнопка добавления расформовки
		$('.add_opening').click( function() {
			// Проверяем сессию
			$.ajax({ url: "check_session.php?script=1", dataType: "script", async: false });

			var LO_ID = $(this).attr("LO_ID");

			// В случае редактирования заполняем форму
			if( LO_ID ) {
				// Данные аяксом
				$.ajax({
					url: "/ajax/opening_json.php?LO_ID=" + LO_ID,
					success: function(msg) { opening_data = msg; },
					dataType: "json",
					async: false
				});
				// Генерируем список свободных заливок
				$.ajax({ url: "/ajax/filling_select.php?LF_ID=" + opening_data['LF_ID'] + "&type=1", dataType: "script", async: false });

				// Идентификатор расформовки
				$('#opening_form input[name="LO_ID"]').val(LO_ID);
				// Заливка
				$('#opening_form select[name="LF_ID"]').val(opening_data['LF_ID']);
				// № поста
				$('#opening_form input[name="o_post"]').val(opening_data['o_post']);
				// Дата/время расформовки
				$('#opening_form input[name="o_date"]').val(opening_data['o_date']);
				$('#opening_form input[name="o_time"]').val(opening_data['o_time']);
				// Дефекты расформовки
				$('#opening_form input[name="o_not_spill"]').val(opening_data['o_not_spill']);
				$('#opening_form input[name="o_crack"]').val(opening_data['o_crack']);
				$('#opening_form input[name="o_chipped"]').val(opening_data['o_chipped']);
				$('#opening_form input[name="o_def_form"]').val(opening_data['o_def_form']);
				// Контрольные взвешивания
				$('#opening_form input[name="weight1"]').val(opening_data['weight1']);
				$('#opening_form input[name="weight2"]').val(opening_data['weight2']);
				$('#opening_form input[name="weight3"]').val(opening_data['weight3']);
			}
			// Иначе очищаем форму
			else {
				// Генерируем список свободных заливок
				$.ajax({ url: "/ajax/filling_select.php?type=1", dataType: "script", async: false });

				$('#opening_form input[name="o_date"]').val('');
				$('#opening_form input[name="o_post"]').val('');
				$('#opening_form table input').val('');
				$('#opening_form table select').val('');
			}

			$('#opening_form').dialog({
				resizable: false,
				width: 1000,
				modal: true,
				closeText: 'Закрыть'
			});

			return false;
		});
	});
</script>
