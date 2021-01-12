<?
include_once "../config.php";

// Сохранение/редактирование возврата поддонов
if( isset($_POST["pr_cnt"]) ) {
	session_start();
	$pr_date = $_POST["pr_date"];
	$CB_ID = $_POST["CB_ID"];
	$pr_cnt = $_POST["pr_cnt"];
	$pr_reject = $_POST["pr_reject"];
	$pr_wrong_format = $_POST["pr_wrong_format"];

	if( $_POST["PR_ID"] ) { // Редактируем
		$query = "
			UPDATE pallet__Return
			SET pr_date = '{$pr_date}'
				,CB_ID = {$CB_ID}
				,pr_cnt = {$pr_cnt}
				,pr_reject = {$pr_reject}
				,pr_wrong_format = {$pr_wrong_format}
			WHERE PR_ID = {$_POST["PR_ID"]}
		";
		if( !mysqli_query( $mysqli, $query ) ) {
			$_SESSION["error"][] = "Invalid query: ".mysqli_error( $mysqli );
		}
		$PR_ID = $_POST["PR_ID"];
	}
	else { // Добавляем
		$query = "
			INSERT INTO pallet__Return
			SET pr_date = '{$pr_date}'
				,CB_ID = {$CB_ID}
				,pr_cnt = {$pr_cnt}
				,pr_reject = {$pr_reject}
				,pr_wrong_format = {$pr_wrong_format}
		";
		if( !mysqli_query( $mysqli, $query ) ) {
			$_SESSION["error"][] = "Invalid query: ".mysqli_error( $mysqli );
		}
		else {
			$add = 1;
			$PR_ID = mysqli_insert_id( $mysqli );
		}
	}

	if( count($_SESSION["error"]) == 0) {
		$_SESSION["success"][] = $add ? "Новыя запись успешно добавлена." : "Запись успешно отредактирована.";
	}

	// Перенаправление в журнал
	if( $add ) {
		exit ('<meta http-equiv="refresh" content="0; url=/pallet_accounting.php?date_from='.$pr_date.'&date_to='.$pr_date.'&pr_date='.$pr_date.'&add#R'.$PR_ID.'">');
	}
	else {
		exit ('<meta http-equiv="refresh" content="0; url=/pallet_accounting.php?date_from='.$pr_date.'&date_to='.$pr_date.'#R'.$PR_ID.'">');
	}
}

// Сохранение/редактирование приобретения поддонов
if( isset($_POST["pa_cnt"]) ) {
	session_start();
	$pa_date = $_POST["pa_date"];
	$pa_cnt = $_POST["pa_cnt"];
	$pallet_cost = $_POST["pallet_cost"];

	if( $_POST["PA_ID"] ) { // Редактируем
		$query = "
			UPDATE pallet__Arrival
			SET pa_date = '{$pa_date}'
				,pa_cnt = {$pa_cnt}
				,pallet_cost = {$pallet_cost}
			WHERE PA_ID = {$_POST["PA_ID"]}
		";
		if( !mysqli_query( $mysqli, $query ) ) {
			$_SESSION["error"][] = "Invalid query1: ".mysqli_error( $mysqli );
		}
		$PA_ID = $_POST["PA_ID"];
	}
	else { // Добавляем
		$query = "
			INSERT INTO pallet__Arrival
			SET pa_date = '{$pa_date}'
				,pa_cnt = {$pa_cnt}
				,pallet_cost = {$pallet_cost}
		";
		if( !mysqli_query( $mysqli, $query ) ) {
			$_SESSION["error"][] = "Invalid query: ".mysqli_error( $mysqli );
		}
		else {
			$add = 1;
			$PA_ID = mysqli_insert_id( $mysqli );
		}
	}

	if( count($_SESSION["error"]) == 0) {
		$_SESSION["success"][] = $add ? "Новыя запись успешно добавлена." : "Запись успешно отредактирована.";
	}

	// Перенаправление в журнал
	if( $add ) {
		exit ('<meta http-equiv="refresh" content="0; url=/pallet_accounting.php?date_from='.$pa_date.'&date_to='.$pa_date.'&pa_date='.$pa_date.'&add#A'.$PA_ID.'">');
	}
	else {
		exit ('<meta http-equiv="refresh" content="0; url=/pallet_accounting.php?date_from='.$pa_date.'&date_to='.$pa_date.'#A'.$PA_ID.'">');
	}
}
?>

<style>
	#pallet_return_form table input,
	#pallet_return_form table select,
	#pallet_arrival_form table input,
	#pallet_arrival_form table select {
		font-size: 1.2em;
	}
</style>

<div id='pallet_return_form' title='Возврат поддонов' style='display:none;'>
	<form method='post' action="/forms/pallet_accounting_form.php" onsubmit="JavaScript:this.subbut.disabled=true;
this.subbut.value='Подождите, пожалуйста!';">
		<fieldset>
			<input type="hidden" name="PR_ID">

			<div class="nowrap" style="display: inline-block; margin-bottom: 10px; margin-right: 30px;">
				<span>Дата возврата:</span>
				<input type="date" name="pr_date" required>
			</div>

			<table style="width: 100%; table-layout: fixed;">
				<thead>
					<tr>
						<th>Клиент</th>
						<th>Возвращено поддонов</th>
						<th>Из них бракованных</th>
						<th>Из них другого формата</th>
					</tr>
				</thead>
				<tbody style="text-align: center;">
					<tr>
						<td>
							<select name="CB_ID" style="width: 150px;" required>
								<option value=""></option>
								<?
								$query = "
									SELECT CB.CB_ID, CB.brand
									FROM ClientBrand CB
									ORDER BY CB.CB_ID
								";
								$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
								while( $row = mysqli_fetch_array($res) ) {
									echo "<option value='{$row["CB_ID"]}'>{$row["brand"]}</option>";
								}
								?>
							</select>
						</td>
						<td><input type="number" name="pr_cnt" min="0" style="width: 70px;" required></td>
						<td><input type="number" name="pr_reject" min="0" style="width: 70px;" required></td>
						<td><input type="number" name="pr_wrong_format" min="0" style="width: 70px;" required></td>
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

<div id='pallet_arrival_form' title='Приобретение поддонов' style='display:none;'>
	<form method='post' action="/forms/pallet_accounting_form.php" onsubmit="JavaScript:this.subbut.disabled=true;
this.subbut.value='Подождите, пожалуйста!';">
		<fieldset>
			<input type="hidden" name="PA_ID">

			<div class="nowrap" style="display: inline-block; margin-bottom: 10px; margin-right: 30px;">
				<span>Дата приобретения:</span>
				<input type="date" name="pa_date" required>
			</div>

			<table style="width: 100%; table-layout: fixed;">
				<thead>
					<tr>
						<th>Приобретено поддонов</th>
						<th>Стоимость поддона, руб</th>
					</tr>
				</thead>
				<tbody style="text-align: center;">
					<tr>
						<td><input type="number" name="pa_cnt" min="1" style="width: 70px;" required></td>
						<td><input type="number" name="pallet_cost" min="0" style="width: 120px;" required></td>
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
		$('.add_return').click( function() {
			// Проверяем сессию
			$.ajax({ url: "check_session.php?script=1", dataType: "script", async: false });

			var PR_ID = $(this).attr("PR_ID"),
				pr_date = $(this).attr("pr_date");

			// В случае редактирования заполняем форму
			if( PR_ID ) {
				// Данные аяксом
				$.ajax({
					url: "/ajax/pallet_accounting_json.php?PR_ID=" + PR_ID,
					success: function(msg) { PR_data = msg; },
					dataType: "json",
					async: false
				});

				$('#pallet_return_form input[name="PR_ID"]').val(PR_ID);
				$('#pallet_return_form input[name="pr_date"]').val(PR_data['pr_date']);
				$('#pallet_return_form select[name="CB_ID"]').val(PR_data['CB_ID']);
				$('#pallet_return_form input[name="pr_cnt"]').val(PR_data['pr_cnt']);
				$('#pallet_return_form input[name="pr_reject"]').val(PR_data['pr_reject']);
				$('#pallet_return_form input[name="pr_wrong_format"]').val(PR_data['pr_wrong_format']);
			}
			// Иначе очищаем форму
			else {
				$('#pallet_return_form input[name="PR_ID"]').val('');
				$('#pallet_return_form input[name="pr_date"]').val(pr_date);
				$('#pallet_return_form table input').val('');
				$('#pallet_return_form table select').val('');
			}

			$('#pallet_return_form').dialog({
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

			var PA_ID = $(this).attr("PA_ID"),
				pa_date = $(this).attr("pa_date");

			// В случае редактирования заполняем форму
			if( PA_ID ) {
				// Данные аяксом
				$.ajax({
					url: "/ajax/pallet_accounting_json.php?PA_ID=" + PA_ID,
					success: function(msg) { PA_data = msg; },
					dataType: "json",
					async: false
				});

				$('#pallet_arrival_form input[name="PA_ID"]').val(PA_ID);
				$('#pallet_arrival_form input[name="pa_date"]').val(PA_data['pa_date']);
				$('#pallet_arrival_form input[name="pa_cnt"]').val(PA_data['pa_cnt']);
				$('#pallet_arrival_form input[name="pallet_cost"]').val(PA_data['pallet_cost']);
			}
			// Иначе очищаем форму
			else {
				$('#pallet_arrival_form input[name="PA_ID"]').val('');
				$('#pallet_arrival_form input[name="pa_date"]').val(pa_date);
				$('#pallet_arrival_form table input').val('');
			}

			$('#pallet_arrival_form').dialog({
				resizable: false,
				width: 1000,
				modal: true,
				closeText: 'Закрыть'
			});

			return false;
		});
	});
</script>
