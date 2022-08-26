<?
include_once "../config.php";

// Сохранение/редактирование расформовки
if( isset($_POST["day"]) ) {
	session_start();
	$day = $_POST["day"];
	$week = $_POST["week"];
	$master1 = ($_POST["master1"] != '') ? $_POST["master1"] : "NULL";
	$operator1 = ($_POST["operator1"] != '') ? $_POST["operator1"] : "NULL";
	$master2 = ($_POST["master2"] != '') ? $_POST["master2"] : "NULL";
	$operator2 = ($_POST["operator2"] != '') ? $_POST["operator2"] : "NULL";

	// Первая смена
	$query = "
		INSERT INTO ShiftLog
		SET working_day = STR_TO_DATE('{$day}', '%d.%m.%Y')
			,shift = 1
			,master = {$master1}
			,operator = {$operator1}
		ON DUPLICATE KEY UPDATE
			master = {$master1}
			,operator = {$operator1}
	";
	if( !mysqli_query( $mysqli, $query ) ) {
		$_SESSION["error"][] = "Invalid query: ".mysqli_error( $mysqli );
	}

	// Вторая смена
	$query = "
		INSERT INTO ShiftLog
		SET working_day = STR_TO_DATE('{$day}', '%d.%m.%Y')
			,shift = 2
			,master = {$master2}
			,operator = {$operator2}
		ON DUPLICATE KEY UPDATE
			master = {$master2}
			,operator = {$operator2}
	";
	if( !mysqli_query( $mysqli, $query ) ) {
		$_SESSION["error"][] = "Invalid query: ".mysqli_error( $mysqli );
	}

	if( !isset($_SESSION["error"]) ) {
		$_SESSION["success"][] = "Запись успешно отредактирована.";
	}

	// Перенаправление в журнал
	exit ('<meta http-equiv="refresh" content="0; url=/shift_log.php?week='.$week.'#'.$day.'">');
}
?>

<div id='shift_log_form' title='Смены' style='display:none;'>
	<form method='post' action="/forms/shift_log_form.php" onsubmit="JavaScript:this.subbut.disabled=true;
this.subbut.value='Подождите, пожалуйста!';">
		<fieldset>
			<table style="width: 100%; table-layout: fixed;">
				<thead>
					<tr>
						<th>День</th>
						<th>Смена</th>
						<th>Мастер</th>
						<th>Оператор</th>
					</tr>
				</thead>
				<tbody style="text-align: center;">
					<input type="hidden" name="day">
					<input type="hidden" name="week">
					<tr>
						<td rowspan="2" id="day"></td>
						<td><h1>1</h1></td>
						<td>
							<select name="master1">
								<option value=""></option>
								<?
								$query = "
									SELECT USR_ID
										,USR_Name(USR_ID) name
									FROM Users
									WHERE RL_ID = 2
										AND act = 1
									ORDER BY USR_ID
								";
								$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
								while( $row = mysqli_fetch_array($res) ) {
									echo "<option value='{$row["USR_ID"]}'>{$row["name"]}</option>";
								}
								?>
								<optgroup label="уволены:">
									<?
									$query = "
										SELECT USR_ID
											,USR_Name(USR_ID) name
										FROM Users
										WHERE RL_ID = 2
											AND act = 0
										ORDER BY USR_ID
									";
									$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
									while( $row = mysqli_fetch_array($res) ) {
										echo "<option value='{$row["USR_ID"]}'>{$row["name"]}</option>";
									}
									?>
								</optgroup>
							</select>
						</td>
						<td>
							<select name="operator1">
								<option value=""></option>
								<?
								$query = "
									SELECT USR_ID
										,USR_Name(USR_ID) name
									FROM Users
									WHERE RL_ID = 3
										AND act = 1
									ORDER BY USR_ID
								";
								$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
								while( $row = mysqli_fetch_array($res) ) {
									echo "<option value='{$row["USR_ID"]}'>{$row["name"]}</option>";
								}
								?>
								<optgroup label="уволены:">
									<?
									$query = "
										SELECT USR_ID
											,USR_Name(USR_ID) name
										FROM Users
										WHERE RL_ID = 3
											AND act = 0
										ORDER BY USR_ID
									";
									$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
									while( $row = mysqli_fetch_array($res) ) {
										echo "<option value='{$row["USR_ID"]}'>{$row["name"]}</option>";
									}
									?>
								</optgroup>
							</select>
						</td>
					</tr>
					<tr>
						<td><h1>2</h1></td>
						<td>
							<select name="master2">
								<option value=""></option>
								<?
								$query = "
									SELECT USR_ID
										,USR_Name(USR_ID) name
									FROM Users
									WHERE RL_ID = 2
										AND act = 1
									ORDER BY USR_ID
								";
								$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
								while( $row = mysqli_fetch_array($res) ) {
									echo "<option value='{$row["USR_ID"]}'>{$row["name"]}</option>";
								}
								?>
								<optgroup label="уволены:">
									<?
									$query = "
										SELECT USR_ID
											,USR_Name(USR_ID) name
										FROM Users
										WHERE RL_ID = 2
											AND act = 0
										ORDER BY USR_ID
									";
									$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
									while( $row = mysqli_fetch_array($res) ) {
										echo "<option value='{$row["USR_ID"]}'>{$row["name"]}</option>";
									}
									?>
								</optgroup>
							</select>
						</td>
						<td>
							<select name="operator2">
								<option value=""></option>
								<?
								$query = "
									SELECT USR_ID
										,USR_Name(USR_ID) name
									FROM Users
									WHERE RL_ID = 3
										AND act = 1
									ORDER BY USR_ID
								";
								$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
								while( $row = mysqli_fetch_array($res) ) {
									echo "<option value='{$row["USR_ID"]}'>{$row["name"]}</option>";
								}
								?>
								<optgroup label="уволены:">
									<?
									$query = "
										SELECT USR_ID
											,USR_Name(USR_ID) name
										FROM Users
										WHERE RL_ID = 3
											AND act = 0
										ORDER BY USR_ID
									";
									$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
									while( $row = mysqli_fetch_array($res) ) {
										echo "<option value='{$row["USR_ID"]}'>{$row["name"]}</option>";
									}
									?>
								</optgroup>
							</select>
						</td>
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
		$('.add_shift_log').click( function() {
			// Проверяем сессию
			$.ajax({ url: "check_session.php?script=1", dataType: "script", async: false });

			var day = $(this).attr("day"),
				week = $(this).attr("week");

			// Заполняем форму
			// Данные аяксом
			$.ajax({
				url: "/ajax/shift_log_json.php?day=" + day,
				success: function(msg) { sl_data = msg; },
				dataType: "json",
				async: false
			});

			$('#shift_log_form #day').text(day);
			$('#shift_log_form input[name="day"]').val(day);
			$('#shift_log_form input[name="week"]').val(week);
			$('#shift_log_form select[name="master1"]').val(sl_data['1']['master']);
			$('#shift_log_form select[name="operator1"]').val(sl_data['1']['operator']);
			$('#shift_log_form select[name="master2"]').val(sl_data['2']['master']);
			$('#shift_log_form select[name="operator2"]').val(sl_data['2']['operator']);

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
