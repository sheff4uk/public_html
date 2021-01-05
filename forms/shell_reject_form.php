<?
include_once "../config.php";

// Сохранение/редактирование списания
if( isset($_POST["sr_cnt"]) ) {
	session_start();
	$sr_date = $_POST["sr_date"];
	$CW_ID = $_POST["CW_ID"];
	$sr_cnt = $_POST["sr_cnt"];
	$exfolation = $_POST["exfolation"] ? $_POST["exfolation"] : "NULL";
	$crack = $_POST["crack"] ? $_POST["crack"] : "NULL";
	$chipped = $_POST["chipped"] ? $_POST["chipped"] : "NULL";
	$batch_number = $_POST["batch_number"] ? $_POST["batch_number"] : "NULL";

	if( $_POST["SR_ID"] ) { // Редактируем
		$query = "
			UPDATE ShellReject
			SET sr_date = '{$sr_date}'
				,CW_ID = {$CW_ID}
				,sr_cnt = {$sr_cnt}
				,exfolation = {$exfolation}
				,crack = {$crack}
				,chipped = {$chipped}
				,batch_number = {$batch_number}
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
				,batch_number = {$batch_number}
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
		exit ('<meta http-equiv="refresh" content="0; url=/shell_reject.php?date_from='.$sr_date.'&date_to='.$sr_date.'&sr_date='.$sr_date.'&add#R'.$SR_ID.'">');
	}
	else {
		exit ('<meta http-equiv="refresh" content="0; url=/shell_reject.php?date_from='.$sr_date.'&date_to='.$sr_date.'#R'.$SR_ID.'">');
	}
}

// Сохранение/редактирование прихода
if( isset($_POST["sa_cnt"]) ) {
	session_start();
	$sa_date = $_POST["sa_date"];
	$CW_ID = $_POST["CW_ID"];
	$sa_cnt = $_POST["sa_cnt"];
	$batch_number = $_POST["batch_number"] ? $_POST["batch_number"] : "NULL";

	if( $_POST["SA_ID"] ) { // Редактируем
		$query = "
			UPDATE ShellArrival
			SET sa_date = '{$sa_date}'
				,CW_ID = {$CW_ID}
				,sa_cnt = {$sa_cnt}
				,batch_number = {$batch_number}
			WHERE SA_ID = {$_POST["SA_ID"]}
		";
		if( !mysqli_query( $mysqli, $query ) ) {
			$_SESSION["error"][] = "Invalid query: ".mysqli_error( $mysqli );
		}
		$SA_ID = $_POST["SA_ID"];
	}
	else { // Добавляем
		$query = "
			INSERT INTO ShellArrival
			SET sa_date = '{$sa_date}'
				,CW_ID = {$CW_ID}
				,sa_cnt = {$sa_cnt}
				,batch_number = {$batch_number}
		";
		if( !mysqli_query( $mysqli, $query ) ) {
			$_SESSION["error"][] = "Invalid query: ".mysqli_error( $mysqli );
		}
		else {
			$add = 1;
			$SA_ID = mysqli_insert_id( $mysqli );
		}
	}

	if( count($_SESSION["error"]) == 0) {
		$_SESSION["success"][] = $add ? "Новыя запись успешно добавлена." : "Запись успешно отредактирована.";
	}

	// Перенаправление в журнал
	if( $add ) {
		exit ('<meta http-equiv="refresh" content="0; url=/shell_reject.php?date_from='.$sa_date.'&date_to='.$sa_date.'&sa_date='.$sa_date.'&add#A'.$SA_ID.'">');
	}
	else {
		exit ('<meta http-equiv="refresh" content="0; url=/shell_reject.php?date_from='.$sa_date.'&date_to='.$sa_date.'#A'.$SA_ID.'">');
	}
}
?>

<style>
	#shell_reject_form table input,
	#shell_reject_form table select,
	#shell_arrival_form table input,
	#shell_arrival_form table select {
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
						<th>Списанных форм</th>
						<th>Отслоения</th>
						<th>Трещины</th>
						<th>Сколы</th>
						<th>№ партии</th>
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
						<td><input type="number" name="batch_number" min="1" style="width: 120px;"></td>
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

<div id='shell_arrival_form' title='Приход форм' style='display:none;'>
	<form method='post' action="/forms/shell_reject_form.php" onsubmit="JavaScript:this.subbut.disabled=true;
this.subbut.value='Подождите, пожалуйста!';">
		<fieldset>
			<input type="hidden" name="SA_ID">

			<div class="nowrap" style="display: inline-block; margin-bottom: 10px; margin-right: 30px;">
				<span>Дата прихода:</span>
				<input type="date" name="sa_date" required>
			</div>

			<table style="width: 100%; table-layout: fixed;">
				<thead>
					<tr>
						<th>Противовес</th>
						<th>Пришедших форм</th>
						<th>№ партии</th>
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
						<td><input type="number" name="sa_cnt" min="1" style="width: 70px;" required></td>
						<td><input type="number" name="batch_number" min="1" style="width: 120px;"></td>
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
		// Кнопка списания
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
				$('#shell_reject_form input[name="batch_number"]').val(SR_data['batch_number']);
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

		// Кнопка прихода
		$('.add_arrival').click( function() {
			// Проверяем сессию
			$.ajax({ url: "check_session.php?script=1", dataType: "script", async: false });

			var SA_ID = $(this).attr("SA_ID"),
				sa_date = $(this).attr("sa_date");

			// В случае редактирования заполняем форму
			if( SA_ID ) {
				// Данные аяксом
				$.ajax({
					url: "/ajax/shell_reject_json.php?SA_ID=" + SA_ID,
					success: function(msg) { SA_data = msg; },
					dataType: "json",
					async: false
				});

				$('#shell_arrival_form input[name="SA_ID"]').val(SA_ID);
				$('#shell_arrival_form input[name="sa_date"]').val(SA_data['sa_date']);
				$('#shell_arrival_form select[name="CW_ID"]').val(SA_data['CW_ID']);
				$('#shell_arrival_form input[name="sa_cnt"]').val(SA_data['sa_cnt']);
				$('#shell_arrival_form input[name="batch_number"]').val(SA_data['batch_number']);
			}
			// Иначе очищаем форму
			else {
				$('#shell_arrival_form input[name="SA_ID"]').val('');
				$('#shell_arrival_form input[name="sa_date"]').val(sa_date);
				$('#shell_arrival_form table input').val('');
				$('#shell_arrival_form table select').val('');
			}

			$('#shell_arrival_form').dialog({
				resizable: false,
				width: 1000,
				modal: true,
				closeText: 'Закрыть'
			});

			return false;
		});
	});
</script>
