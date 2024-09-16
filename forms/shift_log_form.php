<?php
include_once "../config.php";

// Сохранение/редактирование расформовки
if( isset($_POST["day"]) ) {
	session_start();
	$F_ID = $_POST["F_ID"];
	$day = $_POST["day"];
	$week = $_POST["week"];

	foreach ($_POST["master"] as $key => $value) {
		$master = ($_POST["master"][$key] != '') ? $_POST["master"][$key] : "NULL";
		$operator = ($_POST["operator"][$key] != '') ? $_POST["operator"][$key] : "NULL";

		$query = "
			INSERT INTO ShiftLog
			SET F_ID = {$F_ID}
				,working_day = STR_TO_DATE('{$day}', '%d.%m.%Y')
				,shift = {$key}
				,master = {$master}
				,operator = {$operator}
			ON DUPLICATE KEY UPDATE
				master = {$master}
				,operator = {$operator}
	";
		if( !mysqli_query( $mysqli, $query ) ) {
			$_SESSION["error"][] = "Invalid query: ".mysqli_error( $mysqli );
		}
	}

	if( !isset($_SESSION["error"]) ) {
		$_SESSION["success"][] = "Запись успешно отредактирована.";
	}

	// Перенаправление в журнал
	exit ('<meta http-equiv="refresh" content="0; url=/shift_log.php?F_ID='.$F_ID.'&week='.$week.'#'.$day.'">');
}
?>

<div id='shift_log_form' title='Смены' style='display:none;'>
	<form method='post' action="/forms/shift_log_form.php" onsubmit="JavaScript:this.subbut.disabled=true;
this.subbut.value='Подождите, пожалуйста!';">
		<fieldset>
			<!--Содержимое формы аяксом-->
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
		$('.add_shift_log').click( function() {
			// Проверяем сессию
			$.ajax({ url: "check_session.php?script=1", dataType: "script", async: false });

			var f_id = $(this).attr("f_id"),
				day = $(this).attr("day"),
				week = $(this).attr("week");

			//Рисуем форму
			$.ajax({ url: "/ajax/shift_log_form_ajax.php?F_ID="+f_id+"&day="+day+"&week="+week, dataType: "script", async: false });

			$('#shift_log_form').dialog({
				resizable: false,
				width: 1000,
				modal: true,
				closeText: 'Закрыть'
			});

			return false;
		});
	});
</script>
