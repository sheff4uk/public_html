<?
include_once "../config.php";

// Сохранение/редактирование заправки
if( isset($_POST["fuel_meter_value"]) ) {
	session_start();
	$ff_date = $_POST["ff_date"];
	$ff_time = $_POST["ff_time"];
	$fuel_meter_value = $_POST["fuel_meter_value"];
	$hour_meter_value = isset($_POST["hour_meter_value"]) ? $_POST["hour_meter_value"] : "NULL";
	$FD_ID = $_POST["FD_ID"];

	if( $_POST["FF_ID"] ) { // Редактируем
		$query = "
			UPDATE fuel__Filling
			SET ff_date = '{$ff_date}'
				,ff_time = '{$ff_time}'
				,fuel_meter_value = {$fuel_meter_value}
				,hour_meter_value = {$hour_meter_value}
				,FD_ID = {$FD_ID}
				,USR_ID = {$_SESSION['id']}
			WHERE FF_ID = {$_POST["FF_ID"]}
		";
		if( !mysqli_query( $mysqli, $query ) ) {
			$_SESSION["error"][] = mysqli_error( $mysqli );
		}
		$FF_ID = $_POST["FF_ID"];
	}
	else { // Добавляем
		$query = "
			INSERT INTO fuel__Filling
			SET ff_date = '{$ff_date}'
				,ff_time = '{$ff_time}'
				,fuel_meter_value = {$fuel_meter_value}
				,hour_meter_value = {$hour_meter_value}
				,FD_ID = {$FD_ID}
				,FT_ID = {$_POST["FT_ID"]}
				,USR_ID = {$_SESSION['id']}
		";
		if( !mysqli_query( $mysqli, $query ) ) {
			$_SESSION["error"][] = mysqli_error( $mysqli );
		}
		else {
			$add = 1;
			$FF_ID = mysqli_insert_id( $mysqli );
		}
	}

	if( count($_SESSION["error"]) == 0) {
		$_SESSION["success"][] = $add ? "Новыя запись успешно добавлена." : "Запись успешно отредактирована.";
	}

	// Перенаправление в журнал +-7 дней диапазон дат
	$date_from = new DateTime($ff_date);
	$date_to = new DateTime($ff_date);
	date_modify($date_from, '-7 day');
	date_modify($date_to, '+7 day');
	$date_from = date_format($date_from, 'Y-m-d');
	$date_to = date_format($date_to, 'Y-m-d');
	exit ('<meta http-equiv="refresh" content="0; url=/fuel_accounting.php?date_from='.$date_from.'&date_to='.$date_to.'#F'.$FF_ID.'">');
}

// Сохранение/редактирование приобретения топлива
if( isset($_POST["fa_cnt"]) ) {
	session_start();
	$fa_date = $_POST["fa_date"];
	$fa_time = $_POST["fa_time"];
	$fa_cnt = $_POST["fa_cnt"];

	if( $_POST["FA_ID"] ) { // Редактируем
		$query = "
			UPDATE fuel__Arrival
			SET fa_date = '{$fa_date}'
				,fa_time = '{$fa_time}'
				,fa_cnt = {$fa_cnt}
				,USR_ID = {$_SESSION['id']}
			WHERE FA_ID = {$_POST["FA_ID"]}
		";
		if( !mysqli_query( $mysqli, $query ) ) {
			$_SESSION["error"][] = "Invalid query1: ".mysqli_error( $mysqli );
		}
		$FA_ID = $_POST["FA_ID"];
	}
	else { // Добавляем
		$query = "
			INSERT INTO fuel__Arrival
			SET FT_ID = {$_POST["FT_ID"]}
				,fa_date = '{$fa_date}'
				,fa_time = '{$fa_time}'
				,fa_cnt = {$fa_cnt}
				,USR_ID = {$_SESSION['id']}
		";
		if( !mysqli_query( $mysqli, $query ) ) {
			$_SESSION["error"][] = "Invalid query: ".mysqli_error( $mysqli );
		}
		else {
			$add = 1;
			$FA_ID = mysqli_insert_id( $mysqli );
		}
	}

	if( count($_SESSION["error"]) == 0) {
		$_SESSION["success"][] = $add ? "Новыя запись успешно добавлена." : "Запись успешно отредактирована.";
	}

	// Перенаправление в журнал +-7 дней диапазон дат
	$date_from = new DateTime($fa_date);
	$date_to = new DateTime($fa_date);
	date_modify($date_from, '-7 day');
	date_modify($date_to, '+7 day');
	$date_from = date_format($date_from, 'Y-m-d');
	$date_to = date_format($date_to, 'Y-m-d');
	exit ('<meta http-equiv="refresh" content="0; url=/fuel_accounting.php?date_from='.$date_from.'&date_to='.$date_to.'#A'.$FA_ID.'">');
}
?>

<style>
	#fuel_filling_form table input,
	#fuel_filling_form table select,
	#fuel_arrival_form table input,
	#fuel_arrival_form table select {
		font-size: 1.2em;
	}

	.input-container input,
	.input-container select {
		border: none;
		box-sizing: border-box;
		outline: 0;
		padding: .5rem;
		position: relative;
		box-shadow: 5px 5px 8px #666;
		border-radius: .3rem;
	}

	input[type="date"]::-webkit-calendar-picker-indicator,
	input[type="time"]::-webkit-calendar-picker-indicator {
		background: transparent;
		bottom: 0;
		color: transparent;
		cursor: pointer;
		height: auto;
		left: 0;
		position: absolute;
		right: 0;
		top: 0;
		width: auto;
	}
</style>

<div id='fuel_filling_form' title='Заправка техники' style='display:none;'>
	<form method='post' action="/forms/fuel_accounting_form.php" onsubmit="JavaScript:this.subbut.disabled=true;
this.subbut.value='Подождите, пожалуйста!';">
		<fieldset>
			<input type="hidden" name="FF_ID">
			<input type="hidden" name="FT_ID" value="<?=$FT_ID?>">

			<table style="width: 100%; table-layout: fixed;">
				<thead>
					<tr>
						<th colspan="2">Дата/время заправки</th>
						<th>Показания счетчика до заправки</th>
						<th>Показания счетчика после заправки</th>
						<th>Кол-во заправленного топлива</th>
						<th>Техника</th>
						<th>Показания счетчика пробега на момент заправки</th>
					</tr>
				</thead>
				<tbody style="text-align: center;">
					<tr>
						<td colspan="2">
							<span class="input-container"><input type="date" name="ff_date" max="<?=date('Y-m-d')?>" onkeydown="return false" required></span>
							<span class="input-container"><input type="time" name="ff_time" onkeydown="return false" required></span>
						</td>
						<td><span class="input-container"><input type="number" id="fuel_meter_before"  style="width: 100px;" readonly></span></td>
						<td><span class="input-container"><input type="number" name="fuel_meter_value" style="width: 100px;" required></span></td>
						<td><span class="input-container"><input type="number" id="ff_cnt" style="width: 70px;" readonly></span></td>
						<td>
							<span class="input-container">
							<select name="FD_ID" style="width: 100%;" required>
								<option value="" hm="0"></option>
								<?
								$query = "
									SELECT FD.FD_ID
										,FD.fuel_device
										,CONCAT(' (пробег: ', FD.last_hour_meter_value, ')') last_hour_meter_value
									FROM fuel__Device FD
									ORDER BY FD.FD_ID
								";
								$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
								while( $row = mysqli_fetch_array($res) ) {
									echo "<option value='{$row["FD_ID"]}' hm='".($row["last_hour_meter_value"] ? "1" : "0")."'>{$row["fuel_device"]}{$row["last_hour_meter_value"]}</option>";
								}
								?>
							</select>
							</span>
						</td>
						<td><span class="input-container"><input type="number" name="hour_meter_value" min="0" style="width: 100px;" required></span></td>
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

<div id='fuel_arrival_form' title='Приобретение дизтоплива' style='display:none;'>
	<form method='post' action="/forms/fuel_accounting_form.php" onsubmit="JavaScript:this.subbut.disabled=true;
this.subbut.value='Подождите, пожалуйста!';">
		<fieldset>
			<input type="hidden" name="FA_ID">
			<input type="hidden" name="FT_ID" value="<?=$FT_ID?>">

			<table style="width: 100%; table-layout: fixed;">
				<thead>
					<tr>
						<th>Дата приобретения</th>
						<th>Время приобретения</th>
						<th>Кол-во приобретенного топлива</th>
					</tr>
				</thead>
				<tbody style="text-align: center;">
					<tr>
						<td><span class="input-container"><input type="date" name="fa_date" max="<?=date('Y-m-d')?>" onkeydown="return false" required></span></td>
						<td><span class="input-container"><input type="time" name="fa_time" onkeydown="return false" required></span></td>
						<td><span class="input-container"><input type="number" name="fa_cnt" min="0" max="1000" style="width: 100px;" required></span></td>
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
		// Кнопка заправки
		$('.add_filling').click( function() {
			// Проверяем сессию
			$.ajax({ url: "check_session.php?script=1", dataType: "script", async: false });

			var FF_ID = $(this).attr("FF_ID"),
				LFMV = $(this).attr("LFMV");

			// В случае редактирования заполняем форму
			if( FF_ID ) {
				// Данные аяксом
				$.ajax({
					url: "/ajax/fuel_accounting_json.php?FF_ID=" + FF_ID,
					success: function(msg) { FF_data = msg; },
					dataType: "json",
					async: false
				});

				// Проверяем чтобы это была последняя заправка
				if( FF_data['last'] ) {
					$('#fuel_filling_form input[name="FF_ID"]').val(FF_ID);
					$('#fuel_filling_form input[name="ff_date"]').val(FF_data['ff_date']);
					$('#fuel_filling_form input[name="ff_time"]').val(FF_data['ff_time']);
					$('#fuel_filling_form #fuel_meter_before').val(FF_data['last_fuel_meter_value']);
					$('#fuel_filling_form input[name="fuel_meter_value"]').val(FF_data['fuel_meter_value']);
					$('#fuel_filling_form #ff_cnt').val(FF_data['ff_cnt']);
					$('#fuel_filling_form select[name="FD_ID"]').val(FF_data['FD_ID']);
					$('#fuel_filling_form input[name="hour_meter_value"]').val(FF_data['hour_meter_value']);
					$('#fuel_filling_form input[name="fuel_meter_value"]').attr('min', FF_data['last_fuel_meter_value']);
					if( FF_data['hour_meter_value'] ) {
						$('#fuel_filling_form input[name="hour_meter_value"]').prop( "disabled", false );
						$('#fuel_filling_form input[name="hour_meter_value"]').show('fast');
					}
					else {
						$('#fuel_filling_form input[name="hour_meter_value"]').prop( "disabled", true );
						$('#fuel_filling_form input[name="hour_meter_value"]').hide('fast');
					}
				}
				else {
					location.reload();
					return false;
				}
			}
			// Иначе очищаем форму
			else {
				// Данные аяксом
				$.ajax({
					url: "/ajax/fuel_accounting_json.php?FT_ID=<?=$FT_ID?>",
					success: function(msg) { FT_data = msg; },
					dataType: "json",
					async: false
				});

				$('#fuel_filling_form input[name="FF_ID"]').val('');
				$('#fuel_filling_form table input').val('');
				$('#fuel_filling_form table select').val('');
				$('#fuel_filling_form #fuel_meter_before').val(FT_data['last_fuel_meter_value']);
				$('#fuel_filling_form input[name="fuel_meter_value"]').attr('min', FT_data['last_fuel_meter_value']);
				$('#fuel_filling_form input[name="hour_meter_value"]').prop( "disabled", true );
				$('#fuel_filling_form input[name="hour_meter_value"]').hide('fast');
			}

			$('#fuel_filling_form').dialog({
				resizable: false,
				width: 1000,
				modal: true,
				closeText: 'Закрыть'
			});

			return false;
		});

		// Кнопка приобретения
		$('.add_arrival').click( function() {
			// Проверяем сессию
			$.ajax({ url: "check_session.php?script=1", dataType: "script", async: false });

			var FA_ID = $(this).attr("FA_ID");

			// В случае редактирования заполняем форму
			if( FA_ID ) {
				// Данные аяксом
				$.ajax({
					url: "/ajax/fuel_accounting_json.php?FA_ID=" + FA_ID,
					success: function(msg) { FA_data = msg; },
					dataType: "json",
					async: false
				});

				$('#fuel_arrival_form input[name="FA_ID"]').val(FA_ID);
				$('#fuel_arrival_form input[name="fa_date"]').val(FA_data['fa_date']);
				$('#fuel_arrival_form input[name="fa_time"]').val(FA_data['fa_time']);
				$('#fuel_arrival_form input[name="fa_cnt"]').val(FA_data['fa_cnt']);
			}
			// Иначе очищаем форму
			else {
				$('#fuel_arrival_form input[name="FA_ID"]').val('');
				$('#fuel_arrival_form table input').val('');
				$('#fuel_filling_form table select').val('');
			}

			$('#fuel_arrival_form').dialog({
				resizable: false,
				width: 1000,
				modal: true,
				closeText: 'Закрыть'
			});

			return false;
		});

		// При изменении счетчика топлива пересчитываем заправленные литры в форме
		$('#fuel_filling_form input[name="fuel_meter_value"]').change(function() {
			var fuel_meter_before = $('#fuel_filling_form #fuel_meter_before').val(),
				fuel_meter_value = $(this).val();
			$('#fuel_filling_form #ff_cnt').val(fuel_meter_value - fuel_meter_before);
		});

		// При выборе техники активируем/деактивируем поле показаний моточасов в зависимости от наличия счетчика
		$('#fuel_filling_form select[name="FD_ID"]').change(function() {
			$('#fuel_filling_form input[name="hour_meter_value"]').val('');

			var hm = $('#fuel_filling_form select[name="FD_ID"] option:selected').attr('hm');
			if( hm == 1 ) {
				$('#fuel_filling_form input[name="hour_meter_value"]').prop( "disabled", false );
				$('#fuel_filling_form input[name="hour_meter_value"]').show('fast');
			}
			else {
				$('#fuel_filling_form input[name="hour_meter_value"]').prop( "disabled", true );
				$('#fuel_filling_form input[name="hour_meter_value"]').hide('fast');
			}
		});
	});
</script>
