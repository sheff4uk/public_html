<?
include_once "../config.php";

// Сохранение/редактирование производственного плана
if( isset($_POST["CW_ID"]) ) {
	session_start();
	$pp_date = $_POST["pp_date"];
	$CW_ID = $_POST["CW_ID"];
	$batches = $_POST["batches"];

	if( $_POST["PP_ID"] ) { // Редактируем
		$query = "
			UPDATE plan__Production
			SET pp_date = '{$pp_date}'
				,CW_ID = {$CW_ID}
				,batches = {$batches}
			WHERE PP_ID = {$_POST["PP_ID"]}
		";
		if( !mysqli_query( $mysqli, $query ) ) {
			$_SESSION["error"][] = "Invalid query: ".mysqli_error( $mysqli );
		}
		$PP_ID = $_POST["PP_ID"];
	}
	else { // Добавляем
		$query = "
			INSERT INTO plan__Production
			SET pp_date = '{$pp_date}'
				,CW_ID = {$CW_ID}
				,batches = {$batches}
		";
		if( !mysqli_query( $mysqli, $query ) ) {
			$_SESSION["error"][] = "Invalid query: ".mysqli_error( $mysqli );
		}
		else {
			$add = 1;
			$PP_ID = mysqli_insert_id( $mysqli );
		}
	}

	if( count($_SESSION["error"]) == 0) {
		$_SESSION["success"][] = $add ? "Новыя запись успешно добавлена." : "Запись успешно отредактирована.";
	}

	// Перенаправление в журнал
	if( $add ) {
		exit ('<meta http-equiv="refresh" content="0; url=/plan_production.php?date_from='.$pp_date.'&date_to='.$pp_date.'&pp_date='.$pp_date.'&add#'.$PP_ID.'">');
	}
	else {
		exit ('<meta http-equiv="refresh" content="0; url=/plan_production.php?date_from='.$pp_date.'&date_to='.$pp_date.'#'.$PP_ID.'">');
	}
}
?>

<style>
	#plan_production_form table input,
	#plan_production_form table select {
		font-size: 1.2em;
	}
</style>

<div id='plan_production_form' title='Данные производственного плана' style='display:none;'>
	<form method='post' action="/forms/plan_production_form.php" onsubmit="JavaScript:this.subbut.disabled=true;
this.subbut.value='Подождите, пожалуйста!';">
		<fieldset>
			<input type="hidden" name="PP_ID">

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
						<td><input type="date" name="pp_date" required></td>
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
						<td><input type="number" name="batches" min="1" max="30" style="width: 70px;" required></td>
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
		$('.add_pp').click( function() {
			// Проверяем сессию
			$.ajax({ url: "check_session.php?script=1", dataType: "script", async: false });

			var PP_ID = $(this).attr("PP_ID"),
				pp_date = $(this).attr("pp_date");

			// В случае редактирования заполняем форму
			if( PP_ID ) {
				// Данные аяксом
				$.ajax({
					url: "/ajax/plan_production_json.php?PP_ID=" + PP_ID,
					success: function(msg) { pp_data = msg; },
					dataType: "json",
					async: false
				});

				$('#plan_production_form input[name="PP_ID"]').val(PP_ID);
				$('#plan_production_form input[name="pp_date"]').val(pp_data['pp_date']);
				$('#plan_production_form select[name="CW_ID"]').val(pp_data['CW_ID']);
				$('#plan_production_form input[name="batches"]').val(pp_data['batches']);
				$('#plan_production_form input[name="fillings"]').val(pp_data['fillings']);
				$('#plan_production_form input[name="amount"]').val(pp_data['amount']);
			}
			// Иначе очищаем форму
			else {
				$('#plan_production_form table input').val('');
				$('#plan_production_form table select').val('');
				$('#plan_production_form table input[name="pp_date"]').val(pp_date);
			}

			$('#plan_production_form').dialog({
				resizable: false,
				width: 1000,
				modal: true,
				closeText: 'Закрыть'
			});

			return false;
		});

		// При изменении противовеса или замесов пересчитываем заливки и детали
		$('#plan_production_form select[name="CW_ID"], #plan_production_form input[name="batches"]').change(function() {
			var fillings = $('#plan_production_form select[name="CW_ID"] option:selected').attr('fillings'),
				in_cassette = $('#plan_production_form select[name="CW_ID"] option:selected').attr('in_cassette'),
				batches = $('#plan_production_form input[name="batches"]').val();

			$('#plan_production_form input[name="fillings"]').val(batches * fillings);
			$('#plan_production_form input[name="amount"]').val(batches * fillings * in_cassette);
		});
	});
</script>
