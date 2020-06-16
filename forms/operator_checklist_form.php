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
	$OP_ID = $_POST["OP_ID"];
	$sOP_ID = $_POST["sOP_ID"] ? $_POST["sOP_ID"] : "NULL";

	// Редактируем маршрутный лист
	if( $_POST["OC_ID"] ) {
		$query = "
			UPDATE OperatorChecklist
			SET CW_ID = {$CW_ID}
				,batch_date = '{$batch_date}'
				,batch_num = {$batch_num}
				,iron_oxide_weight = {$iron_oxide_weight}
				,iron_oxide = {$iron_oxide}
				,sand = {$sand}
				,cement = {$cement}
				,water = {$water}
				,mix_weight = {$mix_weight}
				,OP_ID = {$OP_ID}
				,sOP_ID = {$sOP_ID}
			WHERE OC_ID = {$_POST["OC_ID"]}
		";
		if( !mysqli_query( $mysqli, $query ) ) {
			$_SESSION["error"][] = "Ошибка в запросе: ".mysqli_error( $mysqli );
		}
		$OC_ID = $_POST["OC_ID"];
	}
	// Сохраняем новый маршрутный лист
	else {
		$query = "
			INSERT INTO OperatorChecklist
			SET CW_ID = {$CW_ID}
				,batch_date = '{$batch_date}'
				,batch_num = {$batch_num}
				,iron_oxide_weight = {$iron_oxide_weight}
				,iron_oxide = {$iron_oxide}
				,sand = {$sand}
				,cement = {$cement}
				,water = {$water}
				,mix_weight = {$mix_weight}
				,OP_ID = {$OP_ID}
				,sOP_ID = {$sOP_ID}
		";
		if( !mysqli_query( $mysqli, $query ) ) {
			$_SESSION["error"][] = "Ошибка в запросе: ".mysqli_error( $mysqli );
		}
		$OC_ID = mysqli_insert_id( $mysqli );
	}

	// Перенаправление в журнал чек листов замеса
	exit ('<meta http-equiv="refresh" content="0; url=/operator_checklist.php#'.$OC_ID.'">');
}
///////////////////////////////////////////////////////
?>
<!-- Форма чек листа замеса -->
<style>
	#operator_checklist_form table input,
	#operator_checklist_form table select {
		font-size: 1.2em;
	}
</style>

<div id='operator_checklist_form' title='Чек лист замеса' style='display:none;'>
	<form method='post' action="/forms/operator_checklist_form.php" onsubmit="JavaScript:this.subbut.disabled=true;
this.subbut.value='Подождите, пожалуйста!';">
		<fieldset>
		<input type="hidden" name="OC_ID">
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
					<th>Оператор</th>
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
					<td>
						<select name="batch_num" style="width: 70px;" required>
							<option value=""></option>
							<?
							for ($i = 1; $i <= 30; $i++) {
								echo "<option value='{$i}'>{$i}</option>";
							}
							?>
						</select>
					</td>
					<td><input type="number" name="iron_oxide_weight" style="width: 70px;" required></td>
					<td style="background: sandybrown;"><input type="number" name="iron_oxide" style="width: 70px;" required></td>
					<td style="background: palegoldenrod;"><input type="number" name="sand" style="width: 70px;" required></td>
					<td style="background: darkgrey;"><input type="number" name="cement" style="width: 70px;" required></td>
					<td style="background: lightskyblue;"><input type="number" name="water" style="width: 70px;" required></td>
					<td><input type="number" name="mix_weight" style="width: 70px;" required></td>
					<td>
						<select name="OP_ID" style="width: 80px;" required>
							<option value=""></option>
							<?
							$query = "
								SELECT OP.OP_ID, OP.name
								FROM Operator OP
							";
							$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
							while( $row = mysqli_fetch_array($res) ) {
								echo "<option value='{$row["OP_ID"]}'>{$row["name"]}</option>";
							}
							?>
						</select>
						<select name="sOP_ID" style="width: 80px;">
							<option value=""></option>
							<?
							$query = "
								SELECT OP.OP_ID, OP.name
								FROM Operator OP
							";
							$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
							while( $row = mysqli_fetch_array($res) ) {
								echo "<option value='{$row["OP_ID"]}'>{$row["name"]}</option>";
							}
							?>
						</select>
					</td>
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
		$('.add_operator_checklist').click( function() {
			// Проверяем сессию
			$.ajax({ url: "check_session.php?script=1", dataType: "script", async: false });

			var OC_ID = $(this).attr("OC_ID");

			// В случае редактирования заполняем форму
			if( OC_ID ) {
				// Данные чек листа замеса аяксом
				$.ajax({
					url: "/ajax/operator_checklist_json.php?OC_ID=" + OC_ID,
					success: function(msg) { operator_checklist_data = msg; },
					dataType: "json",
					async: false
				});

				// Идентификатор замеса
				$('#operator_checklist_form input[name="OC_ID"]').val(OC_ID);

				$('#operator_checklist_form input[name="batch_date"]').val(operator_checklist_data['batch_date']);
				$('#operator_checklist_form select[name="CW_ID"]').val(operator_checklist_data['CW_ID']);
				$('#operator_checklist_form select[name="batch_num"]').val(operator_checklist_data['batch_num']);
				$('#operator_checklist_form input[name="iron_oxide_weight"]').val(operator_checklist_data['iron_oxide_weight']);
				$('#operator_checklist_form input[name="iron_oxide"]').val(operator_checklist_data['iron_oxide']);
				$('#operator_checklist_form input[name="sand"]').val(operator_checklist_data['sand']);
				$('#operator_checklist_form input[name="cement"]').val(operator_checklist_data['cement']);
				$('#operator_checklist_form input[name="water"]').val(operator_checklist_data['water']);
				$('#operator_checklist_form input[name="mix_weight"]').val(operator_checklist_data['mix_weight']);
				// Оператор + помошник
				$('#operator_checklist_form select[name="OP_ID"]').val(operator_checklist_data['OP_ID']);
				$('#operator_checklist_form select[name="sOP_ID"]').val(operator_checklist_data['sOP_ID']);
			}
			// Иначе очищаем форму
			else {
				$('#operator_checklist_form table input').val('');
				$('#operator_checklist_form table select').val('');
			}

			$('#operator_checklist_form').dialog({
				resizable: false,
				width: 1000,
				modal: true,
				closeText: 'Закрыть'
			});

			return false;
		});
	});
</script>
