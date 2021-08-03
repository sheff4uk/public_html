<?
include_once "../config.php";

// Сохранение/редактирование поступления поддонов
if( isset($_POST["cnt"]) ) {
	session_start();
	$pref = substr($_POST["source"], 0, 1);

	// Если возврат
	if( $pref == "R" ) {
		$pr_date = $_POST["date"];
		$CB_ID = substr($_POST["source"], 1);
		$pr_cnt = $_POST["cnt"];
		$pr_reject = $_POST["broken"];

		if( $_POST["incoming_ID"] ) { // Редактируем
			$query = "
				UPDATE pallet__Return
				SET pr_date = '{$pr_date}'
					,CB_ID = {$CB_ID}
					,pr_cnt = {$pr_cnt}
					,pr_reject = {$pr_reject}
				WHERE PR_ID = {$_POST["incoming_ID"]}
			";
			if( !mysqli_query( $mysqli, $query ) ) {
				$_SESSION["error"][] = "Invalid query: ".mysqli_error( $mysqli );
			}
			$PR_ID = $_POST["incoming_ID"];
		}
		else { // Добавляем
			$query = "
				INSERT INTO pallet__Return
				SET pr_date = '{$pr_date}'
					,CB_ID = {$CB_ID}
					,pr_cnt = {$pr_cnt}
					,pr_reject = {$pr_reject}
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
		$date_edit = date_create($pr_date);
		$date_from = date_create($_POST["date_from"]);
		$date_to = date_create($_POST["date_to"]);
		$date_from = min($date_from, $date_edit);
		$date_to = max($date_to, $date_edit);
		$date_from = date_format($date_from, 'Y-m-d');
		$date_to = date_format($date_to, 'Y-m-d');
		exit ('<meta http-equiv="refresh" content="0; url=/pallet_accounting.php?date_from='.$date_from.'&date_to='.$date_to.'#R'.$PR_ID.'">');
	}
	// Приобретение
	elseif( $pref == "A" ) {
		$pa_date = $_POST["date"];
		$PS_ID = substr($_POST["source"], 1);
		$pa_cnt = $_POST["cnt"];
		$pa_reject = $_POST["broken"];
		$pallet_cost = $_POST["cost"];

		if( $_POST["incoming_ID"] ) { // Редактируем
			$query = "
				UPDATE pallet__Arrival
				SET pa_date = '{$pa_date}'
					,PS_ID = {$PS_ID}
					,pa_cnt = {$pa_cnt}
					,pa_reject = {$pa_reject}
					,pallet_cost = {$pallet_cost}
				WHERE PA_ID = {$_POST["incoming_ID"]}
			";
			if( !mysqli_query( $mysqli, $query ) ) {
				$_SESSION["error"][] = "Invalid query1: ".mysqli_error( $mysqli );
			}
			$PA_ID = $_POST["incoming_ID"];
		}
		else { // Добавляем
			$query = "
				INSERT INTO pallet__Arrival
				SET pa_date = '{$pa_date}'
					,PS_ID = {$PS_ID}
					,pa_cnt = {$pa_cnt}
					,pa_reject = {$pa_reject}
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
		$date_edit = date_create($pa_date);
		$date_from = date_create($_POST["date_from"]);
		$date_to = date_create($_POST["date_to"]);
		$date_from = min($date_from, $date_edit);
		$date_to = max($date_to, $date_edit);
		$date_from = date_format($date_from, 'Y-m-d');
		$date_to = date_format($date_to, 'Y-m-d');
		exit ('<meta http-equiv="refresh" content="0; url=/pallet_accounting.php?date_from='.$date_from.'&date_to='.$date_to.'#A'.$PA_ID.'">');
	}
	// Из ремонта
	elseif( $pref == "F" ) {
		$PN_ID = $_POST["PN_ID"];
		$pd_date = $_POST["date"];
		$pd_cnt = $_POST["cnt"];

		if( $_POST["incoming_ID"] ) { // Редактируем
			$query = "
				UPDATE pallet__Disposal
				SET PN_ID = {$PN_ID}
					,pd_date = '{$pd_date}'
					,pd_cnt = -{$pd_cnt}
				WHERE PD_ID = {$_POST["incoming_ID"]}
			";
			if( !mysqli_query( $mysqli, $query ) ) {
				$_SESSION["error"][] = "Invalid query: ".mysqli_error( $mysqli );
			}
			$PD_ID = $_POST["incoming_ID"];
		}
		else { // Добавляем
			$query = "
				INSERT INTO pallet__Disposal
				SET PN_ID = {$PN_ID}
					,pd_date = '{$pd_date}'
					,pd_cnt = -{$pd_cnt}
			";
			if( !mysqli_query( $mysqli, $query ) ) {
				$_SESSION["error"][] = "Invalid query: ".mysqli_error( $mysqli );
			}
			else {
				$add = 1;
				$PD_ID = mysqli_insert_id( $mysqli );
			}
		}

		if( count($_SESSION["error"]) == 0) {
			$_SESSION["success"][] = $add ? "Новыя запись успешно добавлена." : "Запись успешно отредактирована.";
		}

		// Перенаправление в журнал
		$date_edit = date_create($pd_date);
		$date_from = date_create($_POST["date_from"]);
		$date_to = date_create($_POST["date_to"]);
		$date_from = min($date_from, $date_edit);
		$date_to = max($date_to, $date_edit);
		$date_from = date_format($date_from, 'Y-m-d');
		$date_to = date_format($date_to, 'Y-m-d');
		exit ('<meta http-equiv="refresh" content="0; url=/pallet_accounting.php?date_from='.$date_from.'&date_to='.$date_to.'#F'.$PD_ID.'">');
	}
}

// Сохранение/редактирование утилизации поддонов
if( isset($_POST["pd_cnt"]) ) {
	session_start();
	$pd_date = $_POST["pd_date"];
	$PN_ID = $_POST["PN_ID"];
	$pd_cnt = $_POST["pd_cnt"];

	if( $_POST["PD_ID"] ) { // Редактируем
		$query = "
			UPDATE pallet__Disposal
			SET PN_ID = {$PN_ID}
				,pd_date = '{$pd_date}'
				,pd_cnt = {$pd_cnt}
			WHERE PD_ID = {$_POST["PD_ID"]}
		";
		if( !mysqli_query( $mysqli, $query ) ) {
			$_SESSION["error"][] = "Invalid query: ".mysqli_error( $mysqli );
		}
		$PD_ID = $_POST["PD_ID"];
	}
	else { // Добавляем
		$query = "
			INSERT INTO pallet__Disposal
			SET PN_ID = {$PN_ID}
				,pd_date = '{$pd_date}'
				,pd_cnt = {$pd_cnt}
		";
		if( !mysqli_query( $mysqli, $query ) ) {
			$_SESSION["error"][] = "Invalid query: ".mysqli_error( $mysqli );
		}
		else {
			$add = 1;
			$PD_ID = mysqli_insert_id( $mysqli );
		}
	}

	if( count($_SESSION["error"]) == 0) {
		$_SESSION["success"][] = $add ? "Новыя запись успешно добавлена." : "Запись успешно отредактирована.";
	}

	// Перенаправление в журнал
	$date_edit = date_create($pd_date);
	$date_from = date_create($_POST["date_from"]);
	$date_to = date_create($_POST["date_to"]);
	$date_from = min($date_from, $date_edit);
	$date_to = max($date_to, $date_edit);
	$date_from = date_format($date_from, 'Y-m-d');
	$date_to = date_format($date_to, 'Y-m-d');
	exit ('<meta http-equiv="refresh" content="0; url=/pallet_accounting.php?date_from='.$date_from.'&date_to='.$date_to.'#D'.$PD_ID.'">');
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

<div id='pallet_incoming_form' title='Поступление поддонов' style='display:none;'>
	<form method='post' action="/forms/pallet_accounting_form.php" onsubmit="JavaScript:this.subbut.disabled=true;
this.subbut.value='Подождите, пожалуйста!';">
		<fieldset style="background: #16A08588;">
			<input type="hidden" name="incoming_ID">
			<input type="hidden" name="date_from" value="<?=$_GET["date_from"]?>">
			<input type="hidden" name="date_to" value="<?=$_GET["date_to"]?>">

			<table style="width: 100%; table-layout: fixed;">
				<thead>
					<tr>
						<th>Дата</th>
						<th>Источник</th>
						<th class="pallet">Поддон</th>
						<th>Кол-во поддонов</th>
						<th class="broken">Из них бракованных</th>
						<th class="cost">Стоимость поддона, руб</th>
					</tr>
				</thead>
				<tbody style="text-align: center;">
					<tr>
						<td><input type="date" name="date" max="<?=date('Y-m-d')?>" required></td>
						<td>
							<select name="source" style="width: 150px;" required>
								<option value=""></option>
								<option value="F" class="fix">Ремонт</option>
								<optgroup label="Возврат">
									<?
									$query = "
										SELECT CB.CB_ID, CB.brand
										FROM ClientBrand CB
										ORDER BY CB.CB_ID
									";
									$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
									while( $row = mysqli_fetch_array($res) ) {
										echo "<option value='R{$row["CB_ID"]}' class='rtrn'>{$row["brand"]}</option>";
									}
									?>
								</optgroup>
								<optgroup label="Приобретение">
									<?
									$query = "
										SELECT PS.PS_ID, PS.pallet_supplier
										FROM pallet__Supplier PS
										ORDER BY PS.PS_ID
									";
									$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
									while( $row = mysqli_fetch_array($res) ) {
										echo "<option value='A{$row["PS_ID"]}' class='obt'>{$row["pallet_supplier"]}</option>";
									}
									?>
								</optgroup>
							</select>
						</td>
						<td class="pallet">
							<select name="PN_ID" style="width: 200px;">
								<option value=""></option>
								<?
								$query = "
									SELECT PN.PN_ID, PN.pallet_name
									FROM pallet__Name PN
									ORDER BY PN.PN_ID
								";
								$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
								while( $row = mysqli_fetch_array($res) ) {
									echo "<option value='{$row["PN_ID"]}'>{$row["pallet_name"]}</option>";
								}
								?>
							</select>
						</td>
						<td><input type="number" name="cnt" min="0" style="width: 70px;" required></td>
						<td class="broken"><input type="number" name="broken" min="0" style="width: 70px;"></td>
						<td class="cost"><input type="number" name="cost" min="0" style="width: 120px;"></td>
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

<div id='pallet_disposal_form' title='Списание поддонов' style='display:none;'>
	<form method='post' action="/forms/pallet_accounting_form.php" onsubmit="JavaScript:this.subbut.disabled=true;
this.subbut.value='Подождите, пожалуйста!';">
		<fieldset style="background: #db443788;">
			<input type="hidden" name="PD_ID">
			<input type="hidden" name="date_from" value="<?=$_GET["date_from"]?>">
			<input type="hidden" name="date_to" value="<?=$_GET["date_to"]?>">

			<table style="width: 100%; table-layout: fixed;">
				<thead>
					<tr>
						<th>Дата</th>
						<th>Поддон</th>
						<th>Кол-во поддонов</th>
					</tr>
				</thead>
				<tbody style="text-align: center;">
					<tr>
						<td><input type="date" max="<?=date('Y-m-d')?>" name="pd_date" required></td>
						<td>
							<select name="PN_ID" style="width: 200px;" required>
								<option value=""></option>
								<?
								$query = "
									SELECT PN.PN_ID, PN.pallet_name
									FROM pallet__Name PN
									ORDER BY PN.PN_ID
								";
								$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
								while( $row = mysqli_fetch_array($res) ) {
									echo "<option value='{$row["PN_ID"]}'>{$row["pallet_name"]}</option>";
								}
								?>
							</select>
						</td>
						<td><input type="number" name="pd_cnt" min="0" style="width: 70px;" required></td>
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
		// Кнопка поступления поддонов
		$('.add_incoming').click( function() {
			// Проверяем сессию
			$.ajax({ url: "check_session.php?script=1", dataType: "script", async: false });

			var incoming_ID = $(this).attr("incoming_ID"),
				type = $(this).attr("type");

			// Активируем селект
			$('#pallet_incoming_form select[name="source"] option').prop('disabled', false);

			// В случае редактирования заполняем форму
			if( incoming_ID ) {
				// Данные аяксом
				$.ajax({
					url: "/ajax/pallet_accounting_json.php?ID=" + incoming_ID + "&type=" + type,
					success: function(msg) { incoming_data = msg; },
					dataType: "json",
					async: false
				});

				$('#pallet_incoming_form input[name="incoming_ID"]').val(incoming_ID);
				$('#pallet_incoming_form input[name="date"]').val(incoming_data['date']);
				$('#pallet_incoming_form select[name="source"]').val(type + incoming_data['source']).trigger('change');
				$('#pallet_incoming_form select[name="PN_ID"]').val(incoming_data['PN_ID']);
				$('#pallet_incoming_form input[name="cnt"]').val(incoming_data['cnt']);
				$('#pallet_incoming_form input[name="broken"]').val(incoming_data['broken']);
				$('#pallet_incoming_form input[name="cost"]').val(incoming_data['cost']);
				// Доступны только элементы в группе
				if( type == "R" ) {
					$('#pallet_incoming_form select[name="source"] option:not(.rtrn)').prop('disabled', true);
				}
				else if( type == "A" ) {
					$('#pallet_incoming_form select[name="source"] option:not(.obt)').prop('disabled', true);
				}
				else if( type == "F" ) {
					$('#pallet_incoming_form select[name="source"] option:not(.fix)').prop('disabled', true);
				}
			}
			// Иначе очищаем форму
			else {
				$('#pallet_incoming_form input[name="incoming_ID"]').val('');
				$('#pallet_incoming_form table input').val('');
				$('#pallet_incoming_form table select').val('').trigger('change');
				$('#pallet_incoming_form .pallet').hide('fast');
				$('#pallet_incoming_form select[name="PN_ID"]').prop('required', false);
			}

			$('#pallet_incoming_form').dialog({
				resizable: false,
				width: 1000,
				modal: true,
				closeText: 'Закрыть'
			});

			return false;
		});

		// При изменении источника, меняем форму
		$('#pallet_incoming_form select[name="source"]').change(function() {
			var cls =  $('#pallet_incoming_form select[name="source"] option:selected').attr('class');

			if( cls == 'rtrn' ) {
				$('#pallet_incoming_form .pallet').hide('fast');
				$('#pallet_incoming_form select[name="PN_ID"]').prop('required', false);
				$('#pallet_incoming_form .broken').show('fast');
				$('#pallet_incoming_form input[name="broken"]').prop('required', true);
				$('#pallet_incoming_form .cost').hide('fast');
				$('#pallet_incoming_form input[name="cost"]').prop('required', false);
			}
			else if( cls == 'obt' ) {
				$('#pallet_incoming_form .pallet').hide('fast');
				$('#pallet_incoming_form select[name="PN_ID"]').prop('required', false);
				$('#pallet_incoming_form .broken').show('fast');
				$('#pallet_incoming_form input[name="broken"]').prop('required', true);
				$('#pallet_incoming_form .cost').show('fast');
				$('#pallet_incoming_form input[name="cost"]').prop('required', true);
			}
			else {
				$('#pallet_incoming_form .pallet').show('fast');
				$('#pallet_incoming_form select[name="PN_ID"]').prop('required', true);
				$('#pallet_incoming_form .broken').hide('fast');
				$('#pallet_incoming_form input[name="broken"]').prop('required', false);
				$('#pallet_incoming_form .cost').hide('fast');
				$('#pallet_incoming_form input[name="cost"]').prop('required', false);
			}
		});

		// Кнопка списания поддонов
		$('.add_disposal').click( function() {
			// Проверяем сессию
			$.ajax({ url: "check_session.php?script=1", dataType: "script", async: false });

			var PD_ID = $(this).attr("PD_ID");

			// В случае редактирования заполняем форму
			if( PD_ID ) {
				// Данные аяксом
				$.ajax({
					url: "/ajax/pallet_accounting_json.php?ID=" + PD_ID + "&type=D",
					success: function(msg) { PD_data = msg; },
					dataType: "json",
					async: false
				});

				$('#pallet_disposal_form input[name="PD_ID"]').val(PD_ID);
				$('#pallet_disposal_form select[name="PN_ID"]').val(PD_data['PN_ID']);
				$('#pallet_disposal_form input[name="pd_date"]').val(PD_data['pd_date']);
				$('#pallet_disposal_form input[name="pd_cnt"]').val(PD_data['pd_cnt']);
			}
			// Иначе очищаем форму
			else {
				$('#pallet_disposal_form input[name="PD_ID"]').val('');
				$('#pallet_disposal_form table input').val('');
				$('#pallet_disposal_form table select').val('');
			}

			$('#pallet_disposal_form').dialog({
				resizable: false,
				width: 1000,
				modal: true,
				closeText: 'Закрыть'
			});

			return false;
		});
	});
</script>
