<?
include_once "../config.php";

// Сохранение/редактирование расформовки
if( isset($_POST["CW_ID"]) ) {
	session_start();
	$reject_date = $_POST["reject_date"];
	$CW_ID = $_POST["CW_ID"];
	$o_reject_cnt = $_POST["o_reject_cnt"];
	$p_reject_cnt = $_POST["p_reject_cnt"];

	if( $_POST["LDR_ID"] ) { // Редактируем
		$query = "
			UPDATE list__DailyReject
			SET reject_date = '{$reject_date}'
				,CW_ID = {$CW_ID}
				,o_reject_cnt = {$o_reject_cnt}
				,p_reject_cnt = {$p_reject_cnt}
			WHERE LDR_ID = {$_POST["LDR_ID"]}
		";
		if( !mysqli_query( $mysqli, $query ) ) {
			$_SESSION["error"][] = "Invalid query: ".mysqli_error( $mysqli );
		}
		$LDR_ID = $_POST["LDR_ID"];
	}
	else { // Добавляем
		$query = "
			INSERT INTO list__DailyReject
			SET reject_date = '{$reject_date}'
				,CW_ID = {$CW_ID}
				,o_reject_cnt = {$o_reject_cnt}
				,p_reject_cnt = {$p_reject_cnt}
		";
		if( !mysqli_query( $mysqli, $query ) ) {
			$_SESSION["error"][] = "Invalid query: ".mysqli_error( $mysqli );
		}
		else {
			$add = 1;
			$LDR_ID = mysqli_insert_id( $mysqli );
		}
	}

	if( count($_SESSION["error"]) == 0) {
		$_SESSION["success"][] = $add ? "Новыя запись успешно добавлена." : "Запись успешно отредактирована.";
	}

	// Перенаправление в журнал
	if( $add ) {
		exit ('<meta http-equiv="refresh" content="0; url=/daily_reject.php?date_from='.$reject_date.'&date_to='.$reject_date.'&reject_date='.$reject_date.'&add#'.$LDR_ID.'">');
	}
	else {
		exit ('<meta http-equiv="refresh" content="0; url=/daily_reject.php?date_from='.$reject_date.'&date_to='.$reject_date.'#'.$LDR_ID.'">');
	}
}
?>

<style>
	#daily_reject_form table input,
	#daily_reject_form table select {
		font-size: 1.2em;
	}
</style>

<div id='daily_reject_form' title='Данные суточного брака' style='display:none;'>
	<form method='post' action="/forms/daily_reject_form.php" onsubmit="JavaScript:this.subbut.disabled=true;
this.subbut.value='Подождите, пожалуйста!';">
		<fieldset>
			<input type="hidden" name="LDR_ID">

			<div class="nowrap" style="display: inline-block; margin-bottom: 10px; margin-right: 30px;">
				<span>Дата учета брака:</span>
				<input type="date" name="reject_date" required>
			</div>

			<table style="width: 100%; table-layout: fixed;">
				<thead>
					<tr>
						<th>Противовес</th>
						<th>Расформовка</th>
						<th>Упаковка</th>
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
						<td><input type="number" name="o_reject_cnt" min="0" max="9999" style="width: 70px;" required></td>
						<td><input type="number" name="p_reject_cnt" min="0" max="9999" style="width: 70px;" required></td>
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

		// Кнопка добавления расформовки
		$('.add_reject').click( function() {
			// Проверяем сессию
			$.ajax({ url: "check_session.php?script=1", dataType: "script", async: false });

			var LDR_ID = $(this).attr("LDR_ID"),
				reject_date = $(this).attr("reject_date");

			// В случае редактирования заполняем форму
			if( LDR_ID ) {
				// Данные аяксом
				$.ajax({
					url: "/ajax/daily_reject_json.php?LDR_ID=" + LDR_ID,
					success: function(msg) { reject_data = msg; },
					dataType: "json",
					async: false
				});

				$('#daily_reject_form input[name="LDR_ID"]').val(LDR_ID);
				$('#daily_reject_form input[name="reject_date"]').val(reject_data['reject_date']);
				$('#daily_reject_form select[name="CW_ID"]').val(reject_data['CW_ID']);
				$('#daily_reject_form input[name="o_reject_cnt"]').val(reject_data['o_reject_cnt']);
				$('#daily_reject_form input[name="p_reject_cnt"]').val(reject_data['p_reject_cnt']);
			}
			// Иначе очищаем форму
			else {
				$('#daily_reject_form input[name="LDR_ID"]').val('');
				$('#daily_reject_form input[name="reject_date"]').val(reject_date);
				$('#daily_reject_form table input').val('');
				$('#daily_reject_form table select').val('');
			}

			$('#daily_reject_form').dialog({
				resizable: false,
				width: 1000,
				modal: true,
				closeText: 'Закрыть'
			});

			return false;
		});
	});
</script>
