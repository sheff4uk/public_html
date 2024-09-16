<?php
include_once "../config.php";
include_once "../checkrights.php";

// Сохранение/редактирование прихода
if( isset($_POST["ma_date"]) ) {
	session_start();
	$ma_date = $_POST["ma_date"];
	$F_ID = $_POST["F_ID"];
	$MN_ID = $_POST["MN_ID"];
	$MS_ID = $_POST["MS_ID"];
	$MC_ID = $_POST["MC_ID"] ? $_POST["MC_ID"] : "NULL";
	$invoice_number = trim($_POST["invoice_number"]) ? "'".mysqli_real_escape_string($mysqli, convert_str($_POST["invoice_number"]))."'" : "NULL";
	$car_number = trim($_POST["car_number"]) ? "'".mysqli_real_escape_string($mysqli, convert_str($_POST["car_number"]))."'" : "NULL";
	$ma_cnt = $_POST["ma_cnt"];
	$ma_cost = $_POST["ma_cost"] ? $_POST["ma_cost"] : "NULL";

	if( $_POST["MA_ID"] ) { // Редактируем
		$query = "
			UPDATE material__Arrival
			SET ma_date = '{$ma_date}'
				,F_ID = {$F_ID}
				,MN_ID = {$MN_ID}
				,MS_ID = {$MS_ID}
				,MC_ID = {$MC_ID}
				,invoice_number = {$invoice_number}
				,car_number = {$car_number}
				,ma_cnt = {$ma_cnt}
				,ma_cost = {$ma_cost}
				,USR_ID = {$_SESSION['id']}
			WHERE MA_ID = {$_POST["MA_ID"]}
		";
		if( !mysqli_query( $mysqli, $query ) ) {
			$_SESSION["error"][] = "Invalid query: ".mysqli_error( $mysqli );
		}
		$MA_ID = $_POST["MA_ID"];
	}
	else { // Добавляем
		$query = "
			INSERT INTO material__Arrival
			SET ma_date = '{$ma_date}'
				,F_ID = {$F_ID}
				,MN_ID = {$MN_ID}
				,MS_ID = {$MS_ID}
				,MC_ID = {$MC_ID}
				,invoice_number = {$invoice_number}
				,car_number = {$car_number}
				,ma_cnt = {$ma_cnt}
				,ma_cost = {$ma_cost}
				,USR_ID = {$_SESSION['id']}
		";
		if( !mysqli_query( $mysqli, $query ) ) {
			$_SESSION["error"][] = "Invalid query: ".mysqli_error( $mysqli );
		}
		else {
			$add = 1;
			$MA_ID = mysqli_insert_id( $mysqli );
		}
	}

	if( !isset($_SESSION["error"]) ) {
		$_SESSION["success"][] = $add ? "Новыя запись успешно добавлена." : "Запись успешно отредактирована.";
	}

	// Перенаправление в журнал
	exit ('<meta http-equiv="refresh" content="0; url=/material_accounting.php?date_from='.$ma_date.'&date_to='.$ma_date.'#'.$MA_ID.'">');
}

// Редактирование остатков
if( isset($_POST["balance"]) ) {
	session_start();

	$F_ID = $_POST["F_ID"];
	$MN_ID = $_POST["MN_ID"];
	$balance = $_POST["balance"];

	$query = "
		SELECT mb_balance FROM material__Balance WHERE F_ID = {$F_ID} AND MN_ID = {$MN_ID}
	";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$row = mysqli_fetch_array($res);
	$old_balance = $row["mb_balance"];

	$query = "
		INSERT INTO material__Arrival
		SET ma_date = CURDATE()
			,F_ID = {$F_ID}
			,MN_ID = {$MN_ID}
			,ma_cnt = {$balance} - {$old_balance}
			,USR_ID = {$_SESSION['id']}
	";
	mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

	// Перенаправление в журнал
	exit ('<meta http-equiv="refresh" content="0; url='.$location.'">');
}

// Перемещение между участками
if( isset($_POST["to_F_ID"]) ) {
	session_start();

	$F_ID = $_POST["F_ID"];
	$MN_ID = $_POST["MN_ID"];
	$ma_cnt = $_POST["ma_cnt"];
	$to_F_ID = $_POST["to_F_ID"];

	$query = "
		INSERT INTO material__Arrival
		SET ma_date = CURDATE()
			,F_ID = {$F_ID}
			,MN_ID = {$MN_ID}
			,ma_cnt = ma_cnt - {$ma_cnt}
			,USR_ID = {$_SESSION['id']}
	";
	mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

	$query = "
		INSERT INTO material__Arrival
		SET ma_date = CURDATE()
			,F_ID = {$to_F_ID}
			,MN_ID = {$MN_ID}
			,ma_cnt = ma_cnt + {$ma_cnt}
			,USR_ID = {$_SESSION['id']}
	";
	mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

	// Перенаправление в журнал
	exit ('<meta http-equiv="refresh" content="0; url='.$location.'">');
}

// Сохранение/редактирование списка продукции
if( isset($_POST["material_name_add"]) ) {
	session_start();

	foreach ($_POST["MN_ID"] as $key => $value) {
		$material_name = mysqli_real_escape_string($mysqli, convert_str($_POST["material_name"][$key]));
		$query = "
			UPDATE material__Name
			SET material_name = '{$material_name}'
			WHERE MN_ID = {$value}
		";
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	}

	if( trim($_POST["material_name_add"]) ) {
		$material_name_add = mysqli_real_escape_string($mysqli, convert_str($_POST["material_name_add"]));
		$query = "
			INSERT INTO material__Name
			SET material_name = '{$material_name_add}'
		";
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	}
	// Перенаправление в журнал
	exit ('<meta http-equiv="refresh" content="0; url='.$location.'">');
}

// Сохранение/редактирование поставщика
if( isset($_POST["supplier_add"]) ) {
	session_start();

	foreach ($_POST["MS_ID"] as $key => $value) {
		$supplier = mysqli_real_escape_string($mysqli, convert_str($_POST["supplier"][$key]));
		$query = "
			UPDATE material__Supplier
			SET supplier = '{$supplier}'
			WHERE MS_ID = {$value}
		";
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	}

	if( trim($_POST["supplier_add"]) ) {
		$supplier_add = mysqli_real_escape_string($mysqli, convert_str($_POST["supplier_add"]));
		$query = "
			INSERT INTO material__Supplier
			SET supplier = '{$supplier_add}'
		";
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	}
	// Перенаправление в журнал
	exit ('<meta http-equiv="refresh" content="0; url='.$location.'">');
}

// Сохранение/редактирование перевозчика
if( isset($_POST["carrier_add"]) ) {
	session_start();

	foreach ($_POST["MC_ID"] as $key => $value) {
		$carrier = mysqli_real_escape_string($mysqli, convert_str($_POST["carrier"][$key]));
		$query = "
			UPDATE material__Carrier
			SET carrier = '{$carrier}'
			WHERE MC_ID = {$value}
		";
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	}

	if( trim($_POST["carrier_add"]) ) {
		$carrier_add = mysqli_real_escape_string($mysqli, convert_str($_POST["carrier_add"]));
		$query = "
			INSERT INTO material__Carrier
			SET carrier = '{$carrier_add}'
		";
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	}
	// Перенаправление в журнал
	exit ('<meta http-equiv="refresh" content="0; url='.$location.'">');
}
?>

<div id='material_arrival_form' title='Данные приемки сырья' style='display:none;'>
	<form method='post' action="/forms/material_accounting_form.php" onsubmit="JavaScript:this.subbut.disabled=true;
this.subbut.value='Подождите, пожалуйста!';">
		<fieldset>
			<input type="hidden" name="MA_ID">
			<div class="nowrap" style="display: inline-block; margin-bottom: 10px; margin-right: 30px;">
				<span>Дата приемки:</span>
				<input type="date" name="ma_date" min="<?=date("Y-m-d", strtotime("-1 months"))?>" max="<?=date("Y-m-d")?>" required>
			</div>

			<div class="nowrap" style="display: inline-block; margin-bottom: 10px; margin-right: 30px;">
				<span>Участок:</span>
				<select name="F_ID" required>
					<option value=""></option>
					<?php
					$query = "
						SELECT F_ID
							,f_name
						FROM factory
						ORDER BY F_ID
					";
					$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
					while( $row = mysqli_fetch_array($res) ) {
						echo "<option value='{$row["F_ID"]}'>{$row["f_name"]}</option>";
					}
					?>
				</select>
			</div>

			<table style="width: 100%; table-layout: fixed;">
				<thead>
					<tr>
						<th>Наименование продукции</th>
						<th>Поставщик</th>
						<th>Перевозчик</th>
						<th>№ттн, №тн</th>
						<th>№ автомобиля</th>
						<th>Кол-во</th>
						<th>Стоимость</th>
					</tr>
				</thead>
				<tbody style="text-align: center;">
					<tr>
						<td>
							<select name="MN_ID" style="width: 100%;" required>
								<option value=""></option>
								<?php
								$query = "
									SELECT MN.MN_ID, MN.material_name
									FROM material__Name MN
									ORDER BY MN.material_name
								";
								$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
								while( $row = mysqli_fetch_array($res) ) {
									echo "<option value='{$row["MN_ID"]}'>{$row["material_name"]}</option>";
								}
								?>
							</select>
						</td>
						<td>
							<select name="MS_ID" style="width: 100%;" required>
								<option value=""></option>
								<?php
								$query = "
									SELECT MS.MS_ID, MS.supplier
									FROM material__Supplier MS
									ORDER BY MS.supplier
								";
								$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
								while( $row = mysqli_fetch_array($res) ) {
									echo "<option value='{$row["MS_ID"]}'>{$row["supplier"]}</option>";
								}
								?>
							</select>
						</td>
						<td>
							<select name="MC_ID" style="width: 100%;">
								<option value=""></option>
								<?php
								$query = "
									SELECT MC.MC_ID, MC.carrier
									FROM material__Carrier MC
									ORDER BY MC.carrier
								";
								$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
								while( $row = mysqli_fetch_array($res) ) {
									echo "<option value='{$row["MC_ID"]}'>{$row["carrier"]}</option>";
								}
								?>
							</select>
						</td>
						<td><input type='text' name='invoice_number' style="width: 100%;"></td>
						<td><input type='text' id='car_number' name='car_number' style="width: 100%;" oninput="this.value = this.value.toUpperCase()"></td>
						<td><input type='number' min='0' step='0.01' name='ma_cnt' style="width: 100%;" required></td>
						<td><input type='number' min='0' name='ma_cost' style="width: 100%;"></td>
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

<!-- Форма добавления/редактирования приобретаемой продукции -->
<div id='material_list_form' style='display:none;' title="Редактирование списка продукции">
	<form method='post' onsubmit="JavaScript:this.subbut.disabled=true;
this.subbut.value='Подождите, пожалуйста!';">
		<fieldset>
			<table style="width: 100%; table-layout: fixed;">
				<thead>
					<tr>
						<th>Наименование продукции</th>
					</tr>
				</thead>
				<tbody>
					<?php
					$query = "SELECT MN_ID, material_name FROM material__Name ORDER BY material_name";
					$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
					while( $row = mysqli_fetch_array($res) )
					{
						echo "<tr>";
						echo "<td>";
							echo "<input type='hidden' name='MN_ID[]' value='{$row["MN_ID"]}'>";
							echo "<input type='text' name='material_name[]' value='{$row["material_name"]}' autocomplete='off' style='width: 100%;'>";
						echo "</td>";
						echo "</tr>";
					}

					echo "<tr style='background: #6f6;'>";
					echo "<td><input type='text' name='material_name_add' autocomplete='off' style='width: 100%;' placeholder='Новая продукция'></td>";
					echo "</tr>";
					?>
				</tbody>
			</table>
		</fieldset>
		<div>
			<hr>
			<input type='submit' name="subbut" value='Сохранить' style='float: right;'>
		</div>
	</form>
</div>

<!-- Форма добавления/редактирования поставщика -->
<div id='supplier_list_form' style='display:none;' title="Редактирование списка поставщиков">
	<form method='post' onsubmit="JavaScript:this.subbut.disabled=true;
this.subbut.value='Подождите, пожалуйста!';">
		<fieldset>
			<table style="width: 100%; table-layout: fixed;">
				<thead>
					<tr>
						<th>Поставщик</th>
					</tr>
				</thead>
				<tbody>
					<?php
					$query = "SELECT MS_ID, supplier FROM material__Supplier ORDER BY supplier";
					$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
					while( $row = mysqli_fetch_array($res) )
					{
						echo "<tr>";
						echo "<td>";
							echo "<input type='hidden' name='MS_ID[]' value='{$row["MS_ID"]}'>";
							echo "<input type='text' name='supplier[]' value='{$row["supplier"]}' autocomplete='off' style='width: 100%;'>";
						echo "</td>";
						echo "</tr>";
					}

					echo "<tr style='background: #6f6;'>";
					echo "<td><input type='text' name='supplier_add' autocomplete='off' style='width: 100%;' placeholder='Новый поставщик'></td>";
					echo "</tr>";
					?>
				</tbody>
			</table>
		</fieldset>
		<div>
			<hr>
			<input type='submit' name="subbut" value='Сохранить' style='float: right;'>
		</div>
	</form>
</div>

<!-- Форма добавления/редактирования перевозчика -->
<div id='carrier_list_form' style='display:none;' title="Редактирование списка перевозчиков">
	<form method='post' onsubmit="JavaScript:this.subbut.disabled=true;
this.subbut.value='Подождите, пожалуйста!';">
		<fieldset>
			<table style="width: 100%; table-layout: fixed;">
				<thead>
					<tr>
						<th>Перевозчик</th>
					</tr>
				</thead>
				<tbody>
					<?php
					$query = "SELECT MC_ID, carrier FROM material__Carrier ORDER BY carrier";
					$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
					while( $row = mysqli_fetch_array($res) )
					{
						echo "<tr>";
						echo "<td>";
							echo "<input type='hidden' name='MC_ID[]' value='{$row["MC_ID"]}'>";
							echo "<input type='text' name='carrier[]' value='{$row["carrier"]}' autocomplete='off' style='width: 100%;'>";
						echo "</td>";
						echo "</tr>";
					}

					echo "<tr style='background: #6f6;'>";
					echo "<td><input type='text' name='carrier_add' autocomplete='off' style='width: 100%;' placeholder='Новый перевозчик'></td>";
					echo "</tr>";
					?>
				</tbody>
			</table>
		</fieldset>
		<div>
			<hr>
			<input type='submit' name="subbut" value='Сохранить' style='float: right;'>
		</div>
	</form>
</div>

<!-- Форма корректировки остатков сырья -->
<div id='material_balance_form' title='Корректировка остатков сырья' style='display:none;'>
	<form method='post' onsubmit="JavaScript:this.subbut.disabled=true;
this.subbut.value='Подождите, пожалуйста!';">
		<fieldset>
			<input type="hidden" name="F_ID">
			<input type="hidden" name="MN_ID">
			<table style="width: 100%; table-layout: fixed;">
				<thead>
					<tr>
						<th>Участок</th>
						<th>Наименование продукции</th>
						<th>Складской запас</th>
					</tr>
				</thead>
				<tbody style="text-align: center;">
					<tr>
						<td><span name="f_name"></span></td>
						<td><span name="material_name"></span></td>
						<td><input type="number" min="0" step="0.01" name="balance" style="width: 100%;" required=""></td>
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

<!-- Форма перемещения сырья между участками -->
<div id='material_movement_form' title='Перемещение сырья между участками ' style='display:none;'>
	<form method='post' onsubmit="JavaScript:this.subbut.disabled=true;
this.subbut.value='Подождите, пожалуйста!';">
		<fieldset>
		<input type="hidden" name="F_ID">
		<input type="hidden" name="MN_ID">
		<table style="width: 100%; table-layout: fixed;">
				<thead>
					<tr>
						<th>Участок отправления</th>
						<th>Наименование продукции</th>
						<th>Кол-во</th>
						<th>Участок назначения</th>
					</tr>
				</thead>
				<tbody style="text-align: center;">
					<tr>
						<td><span name="f_name"></span></td>
						<td><span name="material_name"></span></td>
						<td><input type='number' min='0' step='0.01' name='ma_cnt' style="width: 100%;" required></td>
						<td>
							<select name="to_F_ID" required>
								<option value=""></option>
								<?php
								$query = "
									SELECT F_ID
										,f_name
									FROM factory
									ORDER BY F_ID
								";
								$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
								while( $row = mysqli_fetch_array($res) ) {
									echo "<option value='{$row["F_ID"]}'>{$row["f_name"]}</option>";
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
		// Кнопка добавления
		$('.add_material_arrival').click( function() {
			// Проверяем сессию
			$.ajax({ url: "check_session.php?script=1", dataType: "script", async: false });

			var MA_ID = $(this).attr("MA_ID");

			// В случае редактирования заполняем форму
			if( MA_ID ) {
				// Данные аяксом
				$.ajax({
					url: "/ajax/material_accounting_json.php?MA_ID=" + MA_ID,
					success: function(msg) { ma_data = msg; },
					dataType: "json",
					async: false
				});
				$('#material_arrival_form input[name="MA_ID"]').val(MA_ID);
				$('#material_arrival_form input[name="ma_date"]').val(ma_data['ma_date']);
				$('#material_arrival_form select[name="F_ID"]').val(ma_data['F_ID']);
				$('#material_arrival_form select[name="MN_ID"]').val(ma_data['MN_ID']);
				$('#material_arrival_form select[name="MS_ID"]').val(ma_data['MS_ID']);
				$('#material_arrival_form select[name="MC_ID"]').val(ma_data['MC_ID']);
				$('#material_arrival_form input[name="invoice_number"]').val(ma_data['invoice_number']);
				$('#material_arrival_form input[name="car_number"]').val(ma_data['car_number']);
				$('#material_arrival_form input[name="ma_cnt"]').val(ma_data['ma_cnt']);
				$('#material_arrival_form input[name="ma_cost"]').val(ma_data['ma_cost']);
			}
			// Иначе очищаем форму
			else {
				$('#material_arrival_form input[name="MA_ID"]').val('');
				$('#material_arrival_form input[name="ma_date"]').val('');
				$('#material_arrival_form select[name="F_ID"]').val('');
				$('#material_arrival_form table input').val('');
				$('#material_arrival_form table select').val('');
			}

			$('#material_arrival_form').dialog({
				resizable: false,
				width: 1000,
				modal: true,
				closeText: 'Закрыть'
			});

			return false;
		});

		// Кнопка перемещения сырья между участками
		$('.material_movement').click( function() {
			// Проверяем сессию
			$.ajax({ url: "check_session.php?script=1", dataType: "script", async: false });

			var F_ID = $(this).attr("F_ID"),
				MN_ID = $(this).attr("MN_ID"),
				balance = $(this).attr("balance"),
				f_name = $(this).attr("f_name"),
				material_name = $(this).attr("material_name");

			if( balance <= 0 ) {
				balance = 0;
			}

			$('#material_movement_form input[name="F_ID"]').val(F_ID);
			$('#material_movement_form input[name="MN_ID"]').val(MN_ID);
			$('#material_movement_form input[name="ma_cnt"]').attr("max", balance);
			$('#material_movement_form input[name="ma_cnt"]').val('');
			$('#material_movement_form span[name="f_name"]').text(f_name);
			$('#material_movement_form span[name="material_name"]').text(material_name);
			$('#material_movement_form select[name="to_F_ID"]').val('');

			$('#material_movement_form').dialog({
				resizable: false,
				width: 800,
				modal: true,
				closeText: 'Закрыть'
			});

			return false;
		});

		// Кнопка редактирования списка продукции
		$('#add_material_list').click( function() {
			// Проверяем сессию
			$.ajax({ url: "check_session.php?script=1", dataType: "script", async: false });

			$('#material_list_form').dialog({
				resizable: false,
				width: 1000,
				modal: true,
				closeText: 'Закрыть'
			});

			return false;
		});

		// Кнопка редактирования списка поставщиков
		$('#add_supplier_list').click( function() {
			// Проверяем сессию
			$.ajax({ url: "check_session.php?script=1", dataType: "script", async: false });

			$('#supplier_list_form').dialog({
				resizable: false,
				width: 1000,
				modal: true,
				closeText: 'Закрыть'
			});

			return false;
		});

		// Кнопка редактирования списка перевозчиков
		$('#add_carrier_list').click( function() {
			// Проверяем сессию
			$.ajax({ url: "check_session.php?script=1", dataType: "script", async: false });

			$('#carrier_list_form').dialog({
				resizable: false,
				width: 1000,
				modal: true,
				closeText: 'Закрыть'
			});

			return false;
		});

		// Кнопка редактирования остатков
		$('.edit_material_balance').click( function() {
			// Проверяем сессию
			$.ajax({ url: "check_session.php?script=1", dataType: "script", async: false });

			var F_ID = $(this).attr("F_ID"),
				MN_ID = $(this).attr("MN_ID"),
				balance = $(this).attr("balance"),
				f_name = $(this).attr("f_name"),
				material_name = $(this).attr("material_name");

			$('#material_balance_form input[name="F_ID"]').val(F_ID);
			$('#material_balance_form input[name="MN_ID"]').val(MN_ID);
			$('#material_balance_form input[name="balance"]').val(balance);
			$('#material_balance_form span[name="f_name"]').text(f_name);
			$('#material_balance_form span[name="material_name"]').text(material_name);

			$('#material_balance_form').dialog({
				resizable: false,
				width: 600,
				modal: true,
				closeText: 'Закрыть'
			});

			return false;
		});
	});

	$(function() {
		$("#car_number").mask("А000АА", {
			translation:  {'А': {pattern: /[АВЕКМНОРСТУХ]/}},
			placeholder: "А000АА",
			clearIfNotMatch: true
		});
	});
</script>
