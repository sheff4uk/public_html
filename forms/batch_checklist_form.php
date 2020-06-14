<?
include_once "../config.php";

// Сохранение/редактирование чек листа замеса
if( isset($_POST["CW_ID"]) ) {
	session_start();

	$CW_ID = $_POST["CW_ID"];
	$batch_date = $_POST["batch_date"];
	$batch_num = $_POST["batch_num"];
	$iron_oxide_weight = $_POST["iron_oxide_weight"];
	$iron_oxide = $_POST["iron_oxide"];
	$sand = $_POST["sand"];
	$cement = $_POST["cement"];
	$water = $_POST["water"];
	$mix_weight = $_POST["mix_weight"];

	// Редактируем маршрутный лист
	if( $_POST["BC_ID"] ) {
		$query = "
			UPDATE BatchChecklist
			SET CW_ID = {$CW_ID}
				,batch_date = '{$batch_date}'
				,batch_num = {$batch_num}
				,iron_oxide_weight = {$iron_oxide_weight}
				,iron_oxide = {$iron_oxide}
				,sand = {$sand}
				,cement = {$cement}
				,water = {$water}
				,mix_weight = {$mix_weight}
			WHERE BC_ID = {$_POST["BC_ID"]}
		";
		if( !mysqli_query( $mysqli, $query ) ) {
			$_SESSION["error"][] = "Ошибка в запросе: ".mysqli_error( $mysqli );
		}
		$BC_ID = $_POST["BC_ID"];
	}
	// Сохраняем новый маршрутный лист
	else {
		$query = "
			INSERT INTO BatchChecklist
			SET CW_ID = {$CW_ID}
				,batch_date = '{$batch_date}'
				,batch_num = {$batch_num}
				,iron_oxide_weight = {$iron_oxide_weight}
				,iron_oxide = {$iron_oxide}
				,sand = {$sand}
				,cement = {$cement}
				,water = {$water}
				,mix_weight = {$mix_weight}
		";
		if( !mysqli_query( $mysqli, $query ) ) {
			$_SESSION["error"][] = "Ошибка в запросе: ".mysqli_error( $mysqli );
		}
		$BC_ID = mysqli_insert_id( $mysqli );
	}

	// Перенаправление в журнал чек листов замеса
	exit ('<meta http-equiv="refresh" content="0; url=/batch_checklist.php#'.$BC_ID.'">');
}
///////////////////////////////////////////////////////
?>
<!-- Форма чек листа замеса -->
<style>
	#batch_checklist_form table input,
	#batch_checklist_form table select {
		font-size: 1.2em;
	}
</style>

<div id='batch_checklist_form' title='Чек лист замеса' style='display:none;'>
	<form method='post' action="/forms/batch_checklist_form.php" onsubmit="JavaScript:this.subbut.disabled=true;
this.subbut.value='Подождите, пожалуйста!';">
		<fieldset>
		<input type="hidden" name="BC_ID">
		<table>
			<thead>
				<tr>
					<th>Дата</th>
					<th>Противовес</th>
					<th>№ замеса</th>
					<th>Куб окалины</th>
					<th>Окалина</th>
					<th>Отсев</th>
					<th>Цемент</th>
					<th>Вода</th>
					<th>Куб смеси</th>
				</tr>
			</thead>
			<tbody style="text-align: center;">
				<tr>
					<td><input type="date" name="batch_date" required></td>
					<td>
						<select name="CW_ID" required>
							<option value=""></option>
							<?
							$query = "
								SELECT CW.CW_ID, CW.item, CW.min_weight, CW.max_weight, CW.in_cassette
								FROM CounterWeight CW
							";
							$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
							while( $row = mysqli_fetch_array($res) ) {
								echo "<option value='{$row["CW_ID"]}' in_cassette='{$row["in_cassette"]}' min_weight='{$row["min_weight"]}' max_weight='{$row["max_weight"]}'>{$row["item"]}</option>";
							}
							?>
						</select>
					</td>
					<td><input type="number" name="batch_num" min="1" max="50" style="width: 70px;" required></td>
					<td><input type="number" name="iron_oxide_weight" style="width: 70px;" required></td>
					<td><input type="number" name="iron_oxide" style="width: 70px;" required></td>
					<td><input type="number" name="sand" style="width: 70px;" required></td>
					<td><input type="number" name="cement" style="width: 70px;" required></td>
					<td><input type="number" name="water" style="width: 70px;" required></td>
					<td><input type="number" name="mix_weight" style="width: 70px;" required></td>
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
		// Кнопка добавления маршрутного листа
		$('.add_batch_checklist').click( function() {
			// Проверяем сессию
			$.ajax({ url: "check_session.php?script=1", dataType: "script", async: false });

			var BC_ID = $(this).attr("BC_ID");

			// В случае редактирования заполняем форму
			if( BC_ID ) {
				// Данные чек листа замеса аяксом
				$.ajax({
					url: "/ajax/batch_checklist_json.php?BC_ID=" + BC_ID,
					success: function(msg) { batch_checklist_data = msg; },
					dataType: "json",
					async: false
				});

				// Идентификатор замеса
				$('#batch_checklist_form input[name="BC_ID"]').val(BC_ID);

				$('#batch_checklist_form input[name="batch_date"]').val(batch_checklist_data['batch_date']);
				$('#batch_checklist_form select[name="CW_ID"]').val(batch_checklist_data['CW_ID']);
				$('#batch_checklist_form input[name="batch_num"]').val(batch_checklist_data['batch_num']);
				$('#batch_checklist_form input[name="iron_oxide_weight"]').val(batch_checklist_data['iron_oxide_weight']);
				$('#batch_checklist_form input[name="iron_oxide"]').val(batch_checklist_data['iron_oxide']);
				$('#batch_checklist_form input[name="sand"]').val(batch_checklist_data['sand']);
				$('#batch_checklist_form input[name="cement"]').val(batch_checklist_data['cement']);
				$('#batch_checklist_form input[name="water"]').val(batch_checklist_data['water']);
				$('#batch_checklist_form input[name="mix_weight"]').val(batch_checklist_data['mix_weight']);
			}
			// Иначе очищаем форму
			else {
				$('#batch_checklist_form table input').val('');
				$('#batch_checklist_form table select').val('');
			}

			$('#batch_checklist_form').dialog({
				resizable: false,
				width: 1000,
				modal: true,
				closeText: 'Закрыть'
			});

			return false;
		});
	});
</script>
