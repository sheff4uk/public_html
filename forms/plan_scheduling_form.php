<?
include_once "../config.php";

// Сохранение/редактирование плана отгрузки
if( isset($_POST["CW_ID"]) ) {
	session_start();
	$ps_date = $_POST["ps_date"];
	$CW_ID = $_POST["CW_ID"];
	$pallets = $_POST["pallets"];

	if( $_POST["PS_ID"] ) { // Редактируем
		$query = "
			UPDATE plan__Scheduling
			SET ps_date = '{$ps_date}'
				,CW_ID = {$CW_ID}
				,pallets = {$pallets}
			WHERE PS_ID = {$_POST["PS_ID"]}
		";
		if( !mysqli_query( $mysqli, $query ) ) {
			$_SESSION["error"][] = "Invalid query: ".mysqli_error( $mysqli );
		}
		$PS_ID = $_POST["PS_ID"];
	}
	else { // Добавляем
		$query = "
			INSERT INTO plan__Scheduling
			SET ps_date = '{$ps_date}'
				,CW_ID = {$CW_ID}
				,pallets = {$pallets}
		";
		if( !mysqli_query( $mysqli, $query ) ) {
			$_SESSION["error"][] = "Invalid query: ".mysqli_error( $mysqli );
		}
		else {
			$add = 1;
			$PS_ID = mysqli_insert_id( $mysqli );
		}
	}

	if( count($_SESSION["error"]) == 0) {
		$_SESSION["success"][] = $add ? "Новая запись успешно добавлена." : "Запись успешно отредактирована.";
	}

	// Получаем неделю
	$query = "SELECT YEARWEEK('{$ps_date}', 1) week";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$row = mysqli_fetch_array($res);
	$week = $row["week"];

	// Перенаправление в журнал
	if( $add ) {
		exit ('<meta http-equiv="refresh" content="0; url=/plan_scheduling.php?week='.$week.'&ps_date='.$ps_date.'&add#'.$PS_ID.'">');
	}
	else {
		exit ('<meta http-equiv="refresh" content="0; url=/plan_scheduling.php?week='.$week.'#'.$PS_ID.'">');
	}
}
?>

<style>
	#plan_scheduling_form table input,
	#plan_scheduling_form table select {
		font-size: 1.2em;
	}
</style>

<div id='plan_scheduling_form' title='Данные плана отгрузки' style='display:none;'>
	<form method='post' action="/forms/plan_scheduling_form.php" onsubmit="JavaScript:this.subbut.disabled=true;
this.subbut.value='Подождите, пожалуйста!';">
		<fieldset>
			<input type="hidden" name="PS_ID">

			<table style="width: 100%; table-layout: fixed;">
				<thead>
					<tr>
						<th>Дата</th>
						<th>Противовес</th>
						<th>Паллетов</th>
						<th>План</th>
					</tr>
				</thead>
				<tbody style="text-align: center;">
					<tr>
						<td><input type="date" name="ps_date" required></td>
						<td>
							<select name="CW_ID" style="width: 150px;" required>
								<option value=""></option>
								<?
								$query = "
									SELECT CW.CW_ID, CW.item, CW.in_pallet
									FROM CounterWeight CW
									ORDER BY CW.CW_ID
								";
								$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
								while( $row = mysqli_fetch_array($res) ) {
									echo "<option value='{$row["CW_ID"]}' in_pallet='{$row["in_pallet"]}'>{$row["item"]}</option>";
								}
								?>
							</select>
						</td>
						<td><input type="number" name="pallets" min="0" max="50" style="width: 70px;" required></td>
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
		$('.add_ps').click( function() {
			// Проверяем сессию
			$.ajax({ url: "check_session.php?script=1", dataType: "script", async: false });

			var PS_ID = $(this).attr("PS_ID"),
				ps_date = $(this).attr("ps_date");

			// В случае редактирования заполняем форму
			if( PS_ID ) {
				// Данные аяксом
				$.ajax({
					url: "/ajax/plan_scheduling_json.php?PS_ID=" + PS_ID,
					success: function(msg) { ps_data = msg; },
					dataType: "json",
					async: false
				});

				$('#plan_scheduling_form select[name="CW_ID"]').val(ps_data['CW_ID']);
				$('#plan_scheduling_form input[name="pallets"]').val(ps_data['pallets']);
				$('#plan_scheduling_form input[name="amount"]').val(ps_data['amount']);
				// В случае клонирования очищаем идентификатор и дату
				if( $(this).hasClass('clone') ) {
					$('#plan_scheduling_form input[name="PS_ID"]').val('');
					$('#plan_scheduling_form table input[name="ps_date"]').val(ps_date);
				}
				else {
					$('#plan_scheduling_form input[name="PS_ID"]').val(PS_ID);
					$('#plan_scheduling_form input[name="ps_date"]').val(ps_data['ps_date']);
				}
			}
			// Иначе очищаем форму
			else {
				$('#plan_scheduling_form input[name="PS_ID"]').val('');
				$('#plan_scheduling_form table input').val('');
				$('#plan_scheduling_form table select').val('');
				$('#plan_scheduling_form table input[name="ps_date"]').val(ps_date);
			}

			$('#plan_scheduling_form').dialog({
				resizable: false,
				width: 1000,
				modal: true,
				closeText: 'Закрыть'
			});

			return false;
		});

		// При изменении противовеса или паллетов пересчитываем детали
		$('#plan_scheduling_form select[name="CW_ID"], #plan_scheduling_form input[name="pallets"]').change(function() {
			var in_pallet = $('#plan_scheduling_form select[name="CW_ID"] option:selected').attr('in_pallet'),
				pallets = $('#plan_scheduling_form input[name="pallets"]').val();

			$('#plan_scheduling_form input[name="amount"]').val(pallets * in_pallet);
		});
	});
</script>
