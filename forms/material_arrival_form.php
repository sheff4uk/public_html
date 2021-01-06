<?
include_once "../config.php";
include_once "../checkrights.php";

// Сохранение/редактирование
if( isset($_POST["ma_date"]) ) {
	session_start();
	$ma_date = $_POST["ma_date"];
	$material_name = trim($_POST["material_name"]) ? "'".mysqli_real_escape_string($mysqli, convert_str($_POST["material_name"]))."'" : "NULL";
	$supplier = trim($_POST["supplier"]) ? "'".mysqli_real_escape_string($mysqli, convert_str($_POST["supplier"]))."'" : "NULL";
	$invoice_number = trim($_POST["invoice_number"]) ? "'".mysqli_real_escape_string($mysqli, convert_str($_POST["invoice_number"]))."'" : "NULL";
	$car_number = trim($_POST["car_number"]) ? "'".mysqli_real_escape_string($mysqli, convert_str($_POST["car_number"]))."'" : "NULL";
	$batch_number = trim($_POST["batch_number"]) ? "'".mysqli_real_escape_string($mysqli, convert_str($_POST["batch_number"]))."'" : "NULL";
	$certificate_number = trim($_POST["certificate_number"]) ? "'".mysqli_real_escape_string($mysqli, convert_str($_POST["certificate_number"]))."'" : "NULL";
	$ma_cnt = $_POST["ma_cnt"];

	if( $_POST["MA_ID"] ) { // Редактируем
		$query = "
			UPDATE material__Arrival
			SET ma_date = '{$ma_date}'
				,material_name = {$material_name}
				,supplier = {$supplier}
				,invoice_number = {$invoice_number}
				,car_number = {$car_number}
				,batch_number = {$batch_number}
				,certificate_number = {$certificate_number}
				,ma_cnt = {$ma_cnt}
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
				,material_name = {$material_name}
				,supplier = {$supplier}
				,invoice_number = {$invoice_number}
				,car_number = {$car_number}
				,batch_number = {$batch_number}
				,certificate_number = {$certificate_number}
				,ma_cnt = {$ma_cnt}
		";
		if( !mysqli_query( $mysqli, $query ) ) {
			$_SESSION["error"][] = "Invalid query: ".mysqli_error( $mysqli );
		}
		else {
			$add = 1;
			$MA_ID = mysqli_insert_id( $mysqli );
		}
	}

	if( count($_SESSION["error"]) == 0) {
		$_SESSION["success"][] = $add ? "Новыя запись успешно добавлена." : "Запись успешно отредактирована.";
	}

	// Перенаправление в журнал
	exit ('<meta http-equiv="refresh" content="0; url=/material_arrival.php?date_from='.$ma_date.'&date_to='.$ma_date.'#'.$MA_ID.'">');
}
?>

<style>
	#material_arrival_form table input,
	#material_arrival_form table select {
		font-size: 1.2em;
	}
</style>

<div id='material_arrival_form' title='Данные приемки сырья' style='display:none;'>
	<form method='post' action="/forms/material_arrival_form.php" onsubmit="JavaScript:this.subbut.disabled=true;
this.subbut.value='Подождите, пожалуйста!';">
		<fieldset>
			<input type="hidden" name="MA_ID">
			<div class="nowrap" style="display: inline-block; margin-bottom: 10px; margin-right: 30px;">
				<span>Дата приемки:</span>
				<input type="date" name="ma_date" required>
			</div>

			<table style="width: 100%; table-layout: fixed;">
				<thead>
					<tr>
						<th>Наименование продукции</th>
						<th>Поставщик</th>
						<th>№ттн, №тн</th>
						<th>№ автомобиля</th>
						<th>№ партии</th>
						<th>№ сертификата качества</th>
						<th>Кол-во, т</th>
					</tr>
				</thead>
				<tbody style="text-align: center;">
					<tr>
						<td><input type='text' name='material_name' style="width: 100%;" required></td>
						<td><input type='text' name='supplier' style="width: 100%;"></td>
						<td><input type='text' name='invoice_number' style="width: 100%;"></td>
						<td><input type='text' name='car_number' style="width: 100%;"></td>
						<td><input type='text' name='batch_number' style="width: 100%;"></td>
						<td><input type='text' name='certificate_number' style="width: 100%;"></td>
						<td><input type='number' min='0' step='0.01' name='ma_cnt' style="width: 100%;" required></td>
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
		// Кнопка добавления расформовки
		$('.add_material_arrival').click( function() {
			// Проверяем сессию
			$.ajax({ url: "check_session.php?script=1", dataType: "script", async: false });

			var MA_ID = $(this).attr("MA_ID");

			// В случае редактирования заполняем форму
			if( MA_ID ) {
				// Данные аяксом
				$.ajax({
					url: "/ajax/material_arrival_json.php?MA_ID=" + MA_ID,
					success: function(msg) { ma_data = msg; },
					dataType: "json",
					async: false
				});
				$('#material_arrival_form input[name="MA_ID"]').val(MA_ID);
				$('#material_arrival_form input[name="ma_date"]').val(ma_data['ma_date']);
				$('#material_arrival_form input[name="material_name"]').val(ma_data['material_name']);
				$('#material_arrival_form input[name="supplier"]').val(ma_data['supplier']);
				$('#material_arrival_form input[name="invoice_number"]').val(ma_data['invoice_number']);
				$('#material_arrival_form input[name="car_number"]').val(ma_data['car_number']);
				$('#material_arrival_form input[name="batch_number"]').val(ma_data['batch_number']);
				$('#material_arrival_form input[name="certificate_number"]').val(ma_data['certificate_number']);
				$('#material_arrival_form input[name="ma_cnt"]').val(ma_data['ma_cnt']);
			}
			// Иначе очищаем форму
			else {
				$('#material_arrival_form input[name="MA_ID"]').val('');
				$('#material_arrival_form input[name="ma_date"]').val('');
				$('#material_arrival_form table input').val('');
			}

			$('#material_arrival_form').dialog({
				resizable: false,
				width: 1000,
				modal: true,
				closeText: 'Закрыть'
			});

			return false;
		});
	});
</script>
