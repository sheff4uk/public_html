<?php
include_once "../config.php";

// Сохранение/редактирование расформовки
if( isset($_POST["LO_ID"]) ) {
	session_start();
	$not_spill = $_POST["not_spill"] ? $_POST["not_spill"] : "NULL";
	$crack = $_POST["crack"] ? $_POST["crack"] : "NULL";
	$crack_drying = $_POST["crack_drying"] ? $_POST["crack_drying"] : "NULL";
	$chipped = $_POST["chipped"] ? $_POST["chipped"] : "NULL";
	$def_form = $_POST["def_form"] ? $_POST["def_form"] : "NULL";
	$def_assembly = $_POST["def_assembly"] ? $_POST["def_assembly"] : "NULL";
	$reject = $_POST["reject"] ? $_POST["reject"] : "NULL";

	if( $_POST["LO_ID"] ) { // Редактируем
		$query = "
			UPDATE list__Opening
			SET not_spill = {$not_spill}
				,crack = {$crack}
				,crack_drying = {$crack_drying}
				,chipped = {$chipped}
				,def_form = {$def_form}
				,def_assembly = {$def_assembly}
				,reject = {$relect}
			WHERE LO_ID = {$_POST["LO_ID"]}
		";
		if( !mysqli_query( $mysqli, $query ) ) {
			$_SESSION["error"][] = "Invalid query: ".mysqli_error( $mysqli );
		}
		$LO_ID = $_POST["LO_ID"];
	}

	if( !isset($_SESSION["error"]) ) {
		$_SESSION["success"][] = "Запись успешно отредактирована.";
	}

	if( $LO_ID ) {
		// Получаем неделю
		$query = "
			SELECT YEARWEEK(opening_time, 1) week
			FROM list__Opening
			WHERE LO_ID = {$LO_ID}
		";
		$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		$row = mysqli_fetch_array($res);
		$week = $row["week"];

		// Перенаправление в журнал маршрутных листов
		exit ('<meta http-equiv="refresh" content="0; url=/opening.php?week='.$week.'#'.$LO_ID.'">');
	}
	else {
		exit ('<meta http-equiv="refresh" content="0; url=/opening.php">');
	}
}
?>

<style>
	#opening_form table input,
	#opening_form table select {
		font-size: 1.2em;
	}
</style>

<div id='opening_form' title='Данные расформовки' style='display:none;'>
	<form method='post' action="/forms/opening_form.php" onsubmit="JavaScript:this.subbut.disabled=true;
this.subbut.value='Подождите, пожалуйста!';">
		<fieldset>
			<input type="hidden" name="LO_ID">
			<div class="nowrap" style="display: inline-block; margin-bottom: 10px; margin-right: 30px;">
				<span>Дата расформовки:</span>
				<span id="o_date" style="font-size: 1.5em;"></span>
			</div>

			<table style="width: 100%; table-layout: fixed;">
				<thead>
					<tr>
						<th>№ кассеты</th>
						<th>Время</th>
						<th>Непролив</th>
						<th>Мех. трещина</th>
						<th>усад. трещина</th>
						<th>Скол</th>
						<th>Дефект формы</th>
						<th>Дефект сборки</th>
						<th>Брак</th>
					</tr>
				</thead>
				<tbody style="text-align: center;">
					<tr>
						<td><b class="cassette" id="cassette"></b></td>
						<td><span id="o_time"></span></td>
						<td><input type="number" name="not_spill" min="0" style="width: 70px;"></td>
						<td><input type="number" name="crack" min="0" style="width: 70px;"></td>
						<td><input type="number" name="crack_drying" min="0" style="width: 70px;"></td>
						<td><input type="number" name="chipped" min="0" style="width: 70px;"></td>
						<td><input type="number" name="def_form" min="0" style="width: 70px;"></td>
						<td><input type="number" name="def_assembly" min="0" style="width: 70px;"></td>
						<td><input type="number" name="reject" min="0" style="width: 70px;"></td>
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

<div id='weight_form' title='Регистрации противовесов' style='display:none;'>
	<form method='post' action="/forms/opening_form.php" onsubmit="JavaScript:this.subbut.disabled=true;
this.subbut.value='Подождите, пожалуйста!';">
		<fieldset>
			<!--Содержимое формы аяксом-->
		</fieldset>
<!--
		<div>
			<hr>
			<input type='submit' name="subbut" value='Записать' style='float: right;'>
		</div>
-->
	</form>
</div>

<script>
	$(function() {
		// Кнопка добавления расформовки
		$('.add_opening').click( function() {
			// Проверяем сессию
			$.ajax({ url: "check_session.php?script=1", dataType: "script", async: false });

			var LO_ID = $(this).attr("LO_ID");

			// В случае редактирования заполняем форму
			if( LO_ID ) {
				// Данные аяксом
				$.ajax({
					url: "/ajax/opening_json.php?LO_ID=" + LO_ID,
					success: function(msg) { opening_data = msg; },
					dataType: "json",
					async: false
				});
				// Идентификатор расформовки
				$('#opening_form input[name="LO_ID"]').val(LO_ID);
				// № кассеты
				$('#opening_form #cassette').text(opening_data['cassette']);
				// Дата/время расформовки
				$('#opening_form #o_date').text(opening_data['o_date']);
				$('#opening_form #o_time').text(opening_data['o_time']);
				// Дефекты расформовки
				$('#opening_form input[name="not_spill"]').val(opening_data['not_spill']);
				$('#opening_form input[name="crack"]').val(opening_data['crack']);
				$('#opening_form input[name="crack_drying"]').val(opening_data['crack_drying']);
				$('#opening_form input[name="chipped"]').val(opening_data['chipped']);
				$('#opening_form input[name="def_form"]').val(opening_data['def_form']);
				$('#opening_form input[name="def_assembly"]').val(opening_data['def_assembly']);
				$('#opening_form input[name="reject"]').val(opening_data['reject']);
			}

			$('#opening_form').dialog({
				resizable: false,
				width: 1000,
				modal: true,
				closeText: 'Закрыть'
			});

			return false;
		});

		// Кнопка редактирования регистраций
		$('.edit_transactions').click( function() {
			// Проверяем сессию
			$.ajax({ url: "check_session.php?script=1", dataType: "script", async: false });

			var LO_ID = $(this).attr("LO_ID");

			//Рисуем форму
			$.ajax({ url: "/ajax/opening_form_ajax.php?LO_ID="+LO_ID, dataType: "script", async: false });

			$('#weight_form').dialog({
				resizable: false,
				width: 1000,
				modal: true,
				closeText: 'Закрыть'
			});

			return false;
		});
	});
</script>
