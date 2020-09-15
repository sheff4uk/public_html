<?
include_once "../config.php";

// Сохранение/редактирование производственного плана
if( isset($_POST["CW_ID"]) ) {
	session_start();
	$pb_date = $_POST["pb_date"];
	$CW_ID = $_POST["CW_ID"];
	$batches = $_POST["batches"];

	if( $_POST["PB_ID"] ) { // Редактируем
		$query = "
			UPDATE plan__Batch
			SET pb_date = '{$pb_date}'
				,CW_ID = {$CW_ID}
				,batches = {$batches}
			WHERE PB_ID = {$_POST["PB_ID"]}
		";
		if( !mysqli_query( $mysqli, $query ) ) {
			$_SESSION["error"][] = "Invalid query: ".mysqli_error( $mysqli );
		}
		$PB_ID = $_POST["PB_ID"];
	}
	else { // Добавляем
		$query = "
			INSERT INTO plan__Batch
			SET pb_date = '{$pb_date}'
				,CW_ID = {$CW_ID}
				,batches = {$batches}
		";
		if( !mysqli_query( $mysqli, $query ) ) {
			$_SESSION["error"][] = "Invalid query: ".mysqli_error( $mysqli );
		}
		else {
			$add = 1;
			$PB_ID = mysqli_insert_id( $mysqli );
		}
	}

	if( count($_SESSION["error"]) == 0) {
		$_SESSION["success"][] = $add ? "Новыя запись успешно добавлена." : "Запись успешно отредактирована.";
	}

	// Получаем неделю
	$query = "SELECT YEARWEEK('{$pb_date}', 1) week";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$row = mysqli_fetch_array($res);
	$week = $row["week"];

	// Перенаправление в журнал
	if( $add ) {
		exit ('<meta http-equiv="refresh" content="0; url=/plan_batch.php?week='.$week.'&pb_date='.$pb_date.'&add#'.$PB_ID.'">');
	}
	else {
		exit ('<meta http-equiv="refresh" content="0; url=/plan_batch.php?week='.$week.'#'.$PB_ID.'">');
	}
}
?>

<style>
	#plan_batch_form table input,
	#plan_batch_form table select {
		font-size: 1.2em;
	}
</style>

<div id='plan_batch_form' title='Данные производственного плана' style='display:none;'>
	<form method='post' action="/forms/plan_batch_form.php" onsubmit="JavaScript:this.subbut.disabled=true;
this.subbut.value='Подождите, пожалуйста!';">
		<fieldset>
			<input type="hidden" name="PB_ID">

			<table style="width: 100%; table-layout: fixed;">
				<thead>
					<tr>
						<th>Дата</th>
						<th>Противовес</th>
						<th>Замесов</th>
						<th>Заливок</th>
						<th>План</th>
					</tr>
				</thead>
				<tbody style="text-align: center;">
					<tr>
						<td><input type="date" name="pb_date" required></td>
						<td>
							<select name="CW_ID" style="width: 150px;" required>
								<option value=""></option>
								<?
								$query = "
									SELECT CW.CW_ID, CW.item, CW.fillings, CW.in_cassette
									FROM CounterWeight CW
									ORDER BY CW.CW_ID
								";
								$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
								while( $row = mysqli_fetch_array($res) ) {
									echo "<option value='{$row["CW_ID"]}' fillings='{$row["fillings"]}' in_cassette='{$row["in_cassette"]}'>{$row["item"]}</option>";
								}
								?>
							</select>
						</td>
						<td><input type="number" name="batches" min="0" max="30" style="width: 70px;" required></td>
						<td><input type="number" name="fillings" style="width: 70px;" readonly></td>
						<td><input type="number" name="amount" style="width: 70px;" readonly></td>
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
//		<?
//		if( isset($_GET["add"]) ) {
//		?>
//		// Если было добавление, автоматичеки открывается форма для новой записи
//		$(document).ready(function() {
//			$('#add_btn').click();
//		});
//		<?
//		}
//		?>

		// Кнопка добавления
		$('.add_pb').click( function() {
			// Проверяем сессию
			$.ajax({ url: "check_session.php?script=1", dataType: "script", async: false });

			var PB_ID = $(this).attr("PB_ID"),
				pb_date = $(this).attr("pb_date");

			// В случае редактирования заполняем форму
			if( PB_ID ) {
				// Данные аяксом
				$.ajax({
					url: "/ajax/plan_batch_json.php?PB_ID=" + PB_ID,
					success: function(msg) { pb_data = msg; },
					dataType: "json",
					async: false
				});

				$('#plan_batch_form select[name="CW_ID"]').val(pb_data['CW_ID']);
				$('#plan_batch_form input[name="batches"]').val(pb_data['batches']);
				$('#plan_batch_form input[name="fillings"]').val(pb_data['fillings']);
				$('#plan_batch_form input[name="amount"]').val(pb_data['amount']);
				// В случае клонирования очищаем идентификатор и дату
				if( $(this).hasClass('clone') ) {
					$('#plan_batch_form input[name="PB_ID"]').val('');
					$('#plan_batch_form table input[name="pb_date"]').val('');
				}
				else {
					$('#plan_batch_form input[name="PB_ID"]').val(PB_ID);
					$('#plan_batch_form input[name="pb_date"]').val(pb_data['pb_date']);
				}
			}
			// Иначе очищаем форму
			else {
				$('#plan_batch_form input[name="PB_ID"]').val('');
				$('#plan_batch_form table input').val('');
				$('#plan_batch_form table select').val('');
				$('#plan_batch_form table input[name="pb_date"]').val(pb_date);
			}

			$('#plan_batch_form').dialog({
				resizable: false,
				width: 1000,
				modal: true,
				closeText: 'Закрыть'
			});

			return false;
		});

		// При изменении противовеса или замесов пересчитываем заливки и детали
		$('#plan_batch_form select[name="CW_ID"], #plan_batch_form input[name="batches"]').change(function() {
			var fillings = $('#plan_batch_form select[name="CW_ID"] option:selected').attr('fillings'),
				in_cassette = $('#plan_batch_form select[name="CW_ID"] option:selected').attr('in_cassette'),
				batches = $('#plan_batch_form input[name="batches"]').val();

			$('#plan_batch_form input[name="fillings"]').val(batches * fillings);
			$('#plan_batch_form input[name="amount"]').val(batches * fillings * in_cassette);
		});
	});
</script>
