<?
include_once "../config.php";

// Сохранение/редактирование отгрузки
if( isset($_POST["CW_ID"]) ) {
	session_start();
	$ls_date = $_POST["ls_date"];
	$CW_ID = $_POST["CW_ID"];
	$pallets = $_POST["pallets"];
	$PN_ID = $_POST["PN_ID"];

	if( $_POST["LS_ID"] ) { // Редактируем
		$query = "
			UPDATE list__Shipment
			SET ls_date = '{$ls_date}'
				,CW_ID = {$CW_ID}
				,pallets = {$pallets}
				,PN_ID = {$PN_ID}
			WHERE LS_ID = {$_POST["LS_ID"]}
		";
		if( !mysqli_query( $mysqli, $query ) ) {
			$_SESSION["error"][] = "Invalid query: ".mysqli_error( $mysqli );
		}
		$LS_ID = $_POST["LS_ID"];
	}
	else { // Добавляем
		$query = "
			INSERT INTO list__Shipment
			SET ls_date = '{$ls_date}'
				,CW_ID = {$CW_ID}
				,pallets = {$pallets}
				,PN_ID = {$PN_ID}
		";
		if( !mysqli_query( $mysqli, $query ) ) {
			$_SESSION["error"][] = "Invalid query: ".mysqli_error( $mysqli );
		}
		else {
			$add = 1;
			$LS_ID = mysqli_insert_id( $mysqli );
		}
	}

	if( count($_SESSION["error"]) == 0) {
		$_SESSION["success"][] = $add ? "Новая запись успешно добавлена." : "Запись успешно отредактирована.";
	}

	// Получаем неделю
	$query = "SELECT YEARWEEK('{$ls_date}', 1) week";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$row = mysqli_fetch_array($res);
	$week = $row["week"];

	// Перенаправление в журнал
	if( $add ) {
		exit ('<meta http-equiv="refresh" content="0; url=/shipment.php?week='.$week.'&ls_date='.$ls_date.'&add#'.$LS_ID.'">');
	}
	else {
		exit ('<meta http-equiv="refresh" content="0; url=/shipment.php?week='.$week.'#'.$LS_ID.'">');
	}
}
?>

<style>
	#shipment_form table input,
	#shipment_form table select {
		font-size: 1.2em;
	}
</style>

<div id='shipment_form' title='Данные отгрузки' style='display:none;'>
	<form method='post' action="/forms/shipment_form.php" onsubmit="JavaScript:this.subbut.disabled=true;
this.subbut.value='Подождите, пожалуйста!';">
		<fieldset>
			<input type="hidden" name="LS_ID">

			<table style="width: 100%; table-layout: fixed;">
				<thead>
					<tr>
						<th>Дата</th>
						<th>Противовес</th>
						<th>Поддон</th>
						<th>Кол-во</th>
						<th>Деталей</th>
					</tr>
				</thead>
				<tbody style="text-align: center;">
					<tr>
						<td><input type="date" name="ls_date" required></td>
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
						<td>
							<select name="PN_ID" style="width: 150px;" required>
								<option value=""></option>
								<?
								$query = "
									SELECT PN.PN_ID
										,PN.pallet_name
									FROM pallet__Name PN
								";
								$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
								while( $row = mysqli_fetch_array($res) ) {
									echo "<option value='{$row["PN_ID"]}'>{$row["pallet_name"]}</option>";
								}
								?>
							</select>
						</td>
						<td><input type="number" name="pallets" min="0" max="80" style="width: 70px;" required></td>
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
		// Кнопка добавления
		$('.add_ps').click( function() {
			// Проверяем сессию
			$.ajax({ url: "check_session.php?script=1", dataType: "script", async: false });

			var LS_ID = $(this).attr("LS_ID"),
				ls_date = $(this).attr("ls_date");

			// В случае редактирования заполняем форму
			if( LS_ID ) {
				// Данные аяксом
				$.ajax({
					url: "/ajax/shipment_json.php?LS_ID=" + LS_ID,
					success: function(msg) { ps_data = msg; },
					dataType: "json",
					async: false
				});

				$('#shipment_form select[name="CW_ID"]').val(ps_data['CW_ID']);
				$('#shipment_form select[name="PN_ID"]').val(ps_data['PN_ID']);
				$('#shipment_form input[name="pallets"]').val(ps_data['pallets']);
				$('#shipment_form input[name="amount"]').val(ps_data['amount']);
				// В случае клонирования очищаем идентификатор и дату
				if( $(this).hasClass('clone') ) {
					$('#shipment_form input[name="LS_ID"]').val('');
					$('#shipment_form table input[name="ls_date"]').val(ls_date);
				}
				else {
					$('#shipment_form input[name="LS_ID"]').val(LS_ID);
					$('#shipment_form input[name="ls_date"]').val(ps_data['ls_date']);
				}
			}
			// Иначе очищаем форму
			else {
				$('#shipment_form input[name="LS_ID"]').val('');
				$('#shipment_form table input').val('');
				$('#shipment_form table select').val('');
				$('#shipment_form table input[name="ls_date"]').val(ls_date);
			}

			$('#shipment_form').dialog({
				resizable: false,
				width: 1000,
				modal: true,
				closeText: 'Закрыть'
			});

			return false;
		});

		// При изменении противовеса или поддонов пересчитываем детали
		$('#shipment_form select[name="CW_ID"], #shipment_form input[name="pallets"]').change(function() {
			var in_pallet = $('#shipment_form select[name="CW_ID"] option:selected').attr('in_pallet'),
				pallets = $('#shipment_form input[name="pallets"]').val();

			$('#shipment_form input[name="amount"]').val(pallets * in_pallet);
		});
	});
</script>
