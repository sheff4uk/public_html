<?
include_once "../config.php";

// Сохранение/редактирование расформовки
if( isset($_POST["LO_ID"]) ) {
	session_start();
	$not_spill = $_POST["not_spill"] ? $_POST["not_spill"] : "NULL";
	$crack = $_POST["crack"] ? $_POST["crack"] : "NULL";
	$chipped = $_POST["chipped"] ? $_POST["chipped"] : "NULL";
	$def_form = $_POST["def_form"] ? $_POST["def_form"] : "NULL";
	$weight1 = $_POST["weight1"]*1000;
	$weight2 = $_POST["weight2"]*1000;
	$weight3 = $_POST["weight3"]*1000;

	if( $_POST["LO_ID"] ) { // Редактируем
		$query = "
			UPDATE list__Opening
			SET not_spill = {$not_spill}
				,crack = {$crack}
				,chipped = {$chipped}
				,def_form = {$def_form}
				,weight1 = {$weight1}
				,weight2 = {$weight2}
				,weight3 = {$weight3}
			WHERE LO_ID = {$_POST["LO_ID"]}
		";
		if( !mysqli_query( $mysqli, $query ) ) {
			$_SESSION["error"][] = "Invalid query: ".mysqli_error( $mysqli );
		}
		$LO_ID = $_POST["LO_ID"];
	}

	if( count($_SESSION["error"]) == 0) {
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
						<th rowspan="2">№ кассеты</th>
						<th rowspan="2">Время</th>
						<th colspan="4">Кол-во брака, шт</th>
						<th colspan="3">Взвешивания, кг</th>
					</tr>
					<tr>
						<th>Непролив</th>
						<th>Трещина</th>
						<th>Скол</th>
						<th>Дефект форм</th>
						<th>№1</th>
						<th>№2</th>
						<th>№3</th>
					</tr>
				</thead>
				<tbody style="text-align: center;">
					<tr>
						<td><b class="cassette" id="cassette"></b></td>
						<td><span id="o_time"></span></td>
						<td><input type="number" name="not_spill" min="0" style="width: 70px;"></td>
						<td><input type="number" name="crack" min="0" style="width: 70px;"></td>
						<td><input type="number" name="chipped" min="0" style="width: 70px;"></td>
						<td><input type="number" name="def_form" min="0" style="width: 70px;"></td>
						<td><input type="number" name="weight1" min="5" max="20" step="0.01" required></td>
						<td><input type="number" name="weight2" min="5" max="20" step="0.01" required></td>
						<td><input type="number" name="weight3" min="5" max="20" step="0.01" required></td>
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
				$('#opening_form input[name="chipped"]').val(opening_data['chipped']);
				$('#opening_form input[name="def_form"]').val(opening_data['def_form']);
				// Контрольные взвешивания
				$('#opening_form input[name="weight1"]').val(opening_data['weight1']);
				$('#opening_form input[name="weight2"]').val(opening_data['weight2']);
				$('#opening_form input[name="weight3"]').val(opening_data['weight3']);
			}

			$('#opening_form').dialog({
				resizable: false,
				width: 1000,
				modal: true,
				closeText: 'Закрыть'
			});

			return false;
		});
	});
</script>
