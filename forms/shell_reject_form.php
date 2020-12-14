<?
include_once "../config.php";

// Сохранение/редактирование
if( isset($_POST["CW_ID"]) ) {
	session_start();
	$sr_date = $_POST["sr_date"];
	$CW_ID = $_POST["CW_ID"];
	$sr_cnt = $_POST["sr_cnt"];
	$exfolation = $_POST["exfolation"] ? $_POST["exfolation"] : "NULL";
	$crack = $_POST["crack"] ? $_POST["crack"] : "NULL";
	$chipped = $_POST["chipped"] ? $_POST["chipped"] : "NULL";

	if( $_POST["SR_ID"] ) { // Редактируем
		$query = "
			UPDATE ShellReject
			SET sr_date = '{$sr_date}'
				,CW_ID = {$CW_ID}
				,sr_cnt = {$sr_cnt}
				,exfolation = {$exfolation}
				,crack = {$crack}
				,chipped = {$chipped}
			WHERE SR_ID = {$_POST["SR_ID"]}
		";
		if( !mysqli_query( $mysqli, $query ) ) {
			$_SESSION["error"][] = "Invalid query: ".mysqli_error( $mysqli );
		}
		$SR_ID = $_POST["SR_ID"];
	}
	else { // Добавляем
		$query = "
			INSERT INTO ShellReject
			SET sr_date = '{$sr_date}'
				,CW_ID = {$CW_ID}
				,sr_cnt = {$sr_cnt}
				,exfolation = {$exfolation}
				,crack = {$crack}
				,chipped = {$chipped}
		";
		if( !mysqli_query( $mysqli, $query ) ) {
			$_SESSION["error"][] = "Invalid query: ".mysqli_error( $mysqli );
		}
		else {
			$add = 1;
			$SR_ID = mysqli_insert_id( $mysqli );
		}
	}

	if( count($_SESSION["error"]) == 0) {
		$_SESSION["success"][] = $add ? "Новыя запись успешно добавлена." : "Запись успешно отредактирована.";
	}

	// Перенаправление в журнал
	if( $add ) {
		exit ('<meta http-equiv="refresh" content="0; url=/shell_reject.php?date='.$sr_date.'&sr_date='.$sr_date.'&add#'.$SR_ID.'">');
	}
	else {
		exit ('<meta http-equiv="refresh" content="0; url=/shell_reject.php?date='.$sr_date.'#'.$SR_ID.'">');
	}
}
?>

<style>
	#shell_reject_form table input,
	#shell_reject_form table select {
		font-size: 1.2em;
	}
</style>

<div id='shell_reject_form' title='Списание форм' style='display:none;'>
	<form method='post' action="/forms/shell_reject_form.php" onsubmit="JavaScript:this.subbut.disabled=true;
this.subbut.value='Подождите, пожалуйста!';">
		<fieldset>
			<input type="hidden" name="SR_ID">

			<div class="nowrap" style="display: inline-block; margin-bottom: 10px; margin-right: 30px;">
				<span>Дата списания:</span>
				<input type="date" name="sr_date" required>
			</div>

			<table style="width: 100%; table-layout: fixed;">
				<thead>
					<tr>
						<th>Противовес</th>
						<th>Кол-во дефектных форм</th>
						<th>Отслоения</th>
						<th>Трещины</th>
						<th>Сколы</th>
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
						<td><input type="number" name="sr_cnt" min="1" style="width: 70px;" required></td>
						<td><input type="number" name="exfolation" min="0" style="width: 70px;"></td>
						<td><input type="number" name="crack" min="0" style="width: 70px;"></td>
						<td><input type="number" name="chipped" min="0" style="width: 70px;"></td>
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
		// Кнопка добавления
		$('.add_reject').click( function() {
			// Проверяем сессию
			$.ajax({ url: "check_session.php?script=1", dataType: "script", async: false });

			var SR_ID = $(this).attr("SR_ID"),
				sr_date = $(this).attr("sr_date");

			// В случае редактирования заполняем форму
			if( SR_ID ) {
				// Данные аяксом
				$.ajax({
					url: "/ajax/shell_reject_json.php?SR_ID=" + SR_ID,
					success: function(msg) { SR_data = msg; },
					dataType: "json",
					async: false
				});

				$('#shell_reject_form input[name="SR_ID"]').val(SR_ID);
				$('#shell_reject_form input[name="sr_date"]').val(SR_data['sr_date']);
				$('#shell_reject_form select[name="CW_ID"]').val(SR_data['CW_ID']);
				$('#shell_reject_form input[name="sr_cnt"]').val(SR_data['sr_cnt']);
				$('#shell_reject_form input[name="exfolation"]').val(SR_data['exfolation']);
				$('#shell_reject_form input[name="crack"]').val(SR_data['crack']);
				$('#shell_reject_form input[name="chipped"]').val(SR_data['chipped']);
			}
			// Иначе очищаем форму
			else {
				$('#shell_reject_form input[name="SR_ID"]').val('');
				$('#shell_reject_form input[name="sr_date"]').val(sr_date);
				$('#shell_reject_form table input').val('');
				$('#shell_reject_form table select').val('');
			}

			$('#shell_reject_form').dialog({
				resizable: false,
				width: 1000,
				modal: true,
				closeText: 'Закрыть'
			});

			return false;
		});
	});
</script>
