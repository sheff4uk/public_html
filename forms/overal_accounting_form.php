<?
include_once "../config.php";

// Сохранение/редактирование операции
if( isset($_POST["oa_cnt"]) ) {
	session_start();

	if( $_POST["OA_ID"] ) { // Редактируем
		$query = "
			UPDATE overal__Accounting
			SET oa_date = '{$_POST["oa_date"]}'
				,OI_ID = {$_POST["OI_ID"]}
				,oa_cnt = {$_POST["oa_cnt"]} * {$_POST["sign"]}
				,correction = ".($_POST["correction"] ? "1" : "NULL")."
			WHERE OA_ID = {$_POST["OA_ID"]}
		";
		if( !mysqli_query( $mysqli, $query ) ) {
			$_SESSION["error"][] = "Invalid query: ".mysqli_error( $mysqli );
		}
		$OA_ID = $_POST["OA_ID"];
	}
	else { // Добавляем
		$query = "
			INSERT INTO overal__Accounting
			SET oa_date = '{$_POST["oa_date"]}'
				,OI_ID = {$_POST["OI_ID"]}
				,F_ID = {$_POST["F_ID"]}
				,oa_cnt = {$_POST["oa_cnt"]} * {$_POST["sign"]}
				,correction = ".($_POST["correction"] ? "1" : "NULL")."
		";
		if( !mysqli_query( $mysqli, $query ) ) {
			$_SESSION["error"][] = "Invalid query: ".mysqli_error( $mysqli );
		}
		else {
			$add = 1;
			$OA_ID = mysqli_insert_id( $mysqli );
		}
	}

	if( !isset($_SESSION["error"]) ) {
		$_SESSION["success"][] = $add ? "Новыя запись успешно добавлена." : "Запись успешно отредактирована.";
	}

	// Перенаправление в журнал
	$date_edit = date_create($oa_date);
	$date_from = date_create($_POST["date_from"]);
	$date_to = date_create($_POST["date_to"]);
	$date_from = min($date_from, $date_edit);
	$date_to = max($date_to, $date_edit);
	$date_from = date_format($date_from, 'Y-m-d');
	$date_to = date_format($date_to, 'Y-m-d');
	exit ('<meta http-equiv="refresh" content="0; url=/overal_accounting.php?F_ID='.$_POST["F_ID"].'&date_from='.$date_from.'&date_to='.$date_to.'#'.$OA_ID.'">');
}
?>

<div id='outcoming_form' title='Выдача СИЗ в <?=$f_name?>' style='display:none;'>
	<form method='post' action="/forms/overal_accounting_form.php" onsubmit="JavaScript:this.subbut.disabled=true;
this.subbut.value='Подождите, пожалуйста!';">
		<fieldset style="background: #db443788;">
			<input type="hidden" name="OA_ID">
			<input type="hidden" name="sign" value="-1">
			<input type="hidden" name="date_from" value="<?=$_GET["date_from"]?>">
			<input type="hidden" name="date_to" value="<?=$_GET["date_to"]?>">
			<input type="hidden" name="F_ID" value="<?=$F_ID?>">

			<table style="width: 100%; table-layout: fixed;">
				<thead>
					<tr>
						<th>Участок</th>
						<th>Дата выдачи</th>
						<th>Наименование СИЗ</th>
						<th>Количество</th>
						<th>Корректировка</th>
					</tr>
				</thead>
				<tbody style="text-align: center;">
					<tr>
						<td><b><?=$f_name?></b></td>
						<td><input type="date" name="oa_date" max="<?=date('Y-m-d')?>" required></td>
						<td>
							<select name="OI_ID" style="width: 100%;" required>
								<option value=""></option>
								<?
								$query = "
									SELECT OI.OI_ID
										,OI.overal
									FROM overal__Item OI
									ORDER BY OI.overal
								";
								$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
								while( $row = mysqli_fetch_array($res) ) {
									echo "<option value='{$row["OI_ID"]}'>{$row["overal"]}</option>";
								}
								?>
							</select>
						</td>
						<td><input type="number" name="oa_cnt" min="0" style="width: 70px;" required></td>
						<td><input type="checkbox" name="correction" value="1"></td>
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

<div id='incoming_form' title='Приход СИЗ в <?=$f_name?>' style='display:none;'>
	<form method='post' action="/forms/overal_accounting_form.php" onsubmit="JavaScript:this.subbut.disabled=true;
this.subbut.value='Подождите, пожалуйста!';">
		<fieldset style="background: #16A08588;">
			<input type="hidden" name="OA_ID">
			<input type="hidden" name="sign" value="1">
			<input type="hidden" name="date_from" value="<?=$_GET["date_from"]?>">
			<input type="hidden" name="date_to" value="<?=$_GET["date_to"]?>">
			<input type="hidden" name="F_ID" value="<?=$F_ID?>">

			<table style="width: 100%; table-layout: fixed;">
				<thead>
					<tr>
						<th>Участок</th>
						<th>Дата поступления</th>
						<th>Наименование СИЗ</th>
						<th>Количество</th>
						<th>Корректировка</th>
					</tr>
				</thead>
				<tbody style="text-align: center;">
					<tr>
						<td><b><?=$f_name?></b></td>
						<td><input type="date" name="oa_date" max="<?=date('Y-m-d')?>" required></td>
						<td>
							<select name="OI_ID" style="width: 100%;" required>
								<option value=""></option>
								<?
								$query = "
									SELECT OI.OI_ID
										,OI.overal
									FROM overal__Item OI
									ORDER BY OI.overal
								";
								$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
								while( $row = mysqli_fetch_array($res) ) {
									echo "<option value='{$row["OI_ID"]}'>{$row["overal"]}</option>";
								}
								?>
							</select>
						</td>
						<td><input type="number" name="oa_cnt" min="0" style="width: 70px;" required></td>
						<td><input type="checkbox" name="correction" value="1"></td>
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
		// Кнопка выдачи
		$('.add_outcoming').click( function() {
			// Проверяем сессию
			$.ajax({ url: "check_session.php?script=1", dataType: "script", async: false });

			var OA_ID = $(this).attr("OA_ID");

			// В случае редактирования заполняем форму
			if( OA_ID ) {
				// Данные аяксом
				$.ajax({
					url: "/ajax/overal_accounting_json.php?OA_ID=" + OA_ID,
					success: function(msg) { OA_data = msg; },
					dataType: "json",
					async: false
				});

				$('#outcoming_form input[name="OA_ID"]').val(OA_ID);
				$('#outcoming_form input[name="oa_date"]').val(OA_data['oa_date']);
				$('#outcoming_form select[name="OI_ID"]').val(OA_data['OI_ID']);
				$('#outcoming_form input[name="oa_cnt"]').val(OA_data['oa_cnt']);
				$('#outcoming_form input[name="correction"]').prop('checked', Number(OA_data['correction']));
			}
			// Иначе очищаем форму
			else {
				$('#outcoming_form input[name="OA_ID"]').val('');
				$('#outcoming_form table input[name="oa_date"]').val('');
				$('#outcoming_form table input[name="oa_cnt"]').val('');
				$('#outcoming_form table select').val('');
				$('#outcoming_form input[name="correction"]').prop('checked', 0);
			}

			$('#outcoming_form').dialog({
				resizable: false,
				width: 1000,
				modal: true,
				closeText: 'Закрыть'
			});

			return false;
		});

		// Кнопка прихода
		$('.add_incoming').click( function() {
			// Проверяем сессию
			$.ajax({ url: "check_session.php?script=1", dataType: "script", async: false });

			var OA_ID = $(this).attr("OA_ID");

			// В случае редактирования заполняем форму
			if( OA_ID ) {
				// Данные аяксом
				$.ajax({
					url: "/ajax/overal_accounting_json.php?OA_ID=" + OA_ID,
					success: function(msg) { OA_data = msg; },
					dataType: "json",
					async: false
				});

				$('#incoming_form input[name="OA_ID"]').val(OA_ID);
				$('#incoming_form input[name="oa_date"]').val(OA_data['oa_date']);
				$('#incoming_form select[name="OI_ID"]').val(OA_data['OI_ID']);
				$('#incoming_form input[name="oa_cnt"]').val(OA_data['oa_cnt']);
				$('#incoming_form input[name="correction"]').prop('checked', Number(OA_data['correction']));
			}
			// Иначе очищаем форму
			else {
				$('#incoming_form input[name="OA_ID"]').val('');
				$('#incoming_form table input[name="oa_date"]').val('');
				$('#incoming_form table input[name="oa_cnt"]').val('');
				$('#incoming_form table select').val('');
				$('#incoming_form input[name="correction"]').prop('checked', 0);
			}

			$('#incoming_form').dialog({
				resizable: false,
				width: 1000,
				modal: true,
				closeText: 'Закрыть'
			});

			return false;
		});
	});
</script>
