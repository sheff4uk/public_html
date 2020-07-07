<?
include_once "../config.php";

// Сохранение/редактирование чек листа оператора
if( isset($_POST["CW_ID"]) ) {
	session_start();

	$CW_ID = $_POST["CW_ID"];
	$OP_ID = $_POST["OP_ID"];
	$batch_date = $_POST["batch_date"];
	$batch_time = $_POST["batch_time"];
	$comp_density = $_POST["comp_density"];
	$mix_density = $_POST["mix_density"];
	$iron_oxide = $_POST["iron_oxide"] ? $_POST["iron_oxide"] : "NULL";
	$sand = $_POST["sand"] ? $_POST["sand"] : "NULL";
	$crushed_stone = $_POST["crushed_stone"] ? $_POST["crushed_stone"] : "NULL";
	$cement = $_POST["cement"];
	$water = $_POST["water"];
	$underfilling = $_POST["underfilling"] ? $_POST["underfilling"] : 0;

	// Редактируем маршрутный лист
	if( $_POST["LB_ID"] ) {
		// Редактируем замесы
		$query = "
			UPDATE list__Batches
			SET
				CW_ID = {$CW_ID},
				OP_ID = {$OP_ID},
				batch_date = '{$batch_date}',
				batch_time = '{$batch_time}',
				comp_density = {$comp_density},
				mix_density = {$mix_density},
				iron_oxide = {$iron_oxide},
				sand = {$sand},
				crushed_stone = {$crushed_stone},
				cement = {$cement},
				water = {$water},
				underfilling = {$underfilling}
			WHERE LB_ID = {$_POST["LB_ID"]}
		";
		if( !mysqli_query( $mysqli, $query ) ) {
			$_SESSION["error"][] = "Invalid query: ".mysqli_error( $mysqli );
		}
		else {
			$LB_ID = $_POST["LB_ID"];
			// Редактируем заливки
			foreach ($_POST["cassette"] as $key => $value) {
				$query = "
					UPDATE list__Pourings
					SET cassette = {$value}
					WHERE LB_ID = {$LB_ID} AND sort = {$key}
				";
				if( !mysqli_query( $mysqli, $query ) ) {
					$_SESSION["error"][] = "Invalid query: ".mysqli_error( $mysqli );
				}
			}
		}
	}
	// Сохраняем новый маршрутный лист
	else {
		// Проверяем наличие дублирующих ключей
		$cassette = implode(",", $_POST["cassette"]);
		$query = "
			SELECT
				DATE_FORMAT(LP.pouring_date, '%d.%m.%y') pouring_date,
				LP.cassette
			FROM list__Pourings LP
			WHERE LP.pouring_date = '{$batch_date}' AND LP.cassette IN ({$cassette})
		";
		$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		if( mysqli_num_rows($res) ) {
			while( $row = mysqli_fetch_array($res) ) {
				$_SESSION["error"][] = "Кассета №{$row["cassette"]} уже заливалась {$row["pouring_date"]}.";
			}
		}
		else {
			// Создаем замес
			$query = "
				INSERT INTO list__Batches
				SET
					CW_ID = {$CW_ID},
					OP_ID = {$OP_ID},
					batch_date = '{$batch_date}',
					batch_time = '{$batch_time}',
					comp_density = {$comp_density},
					mix_density = {$mix_density},
					iron_oxide = {$iron_oxide},
					sand = {$sand},
					crushed_stone = {$crushed_stone},
					cement = {$cement},
					water = {$water},
					underfilling = {$underfilling}
			";
			if( !mysqli_query( $mysqli, $query ) ) {
				$_SESSION["error"][] = "Invalid query: ".mysqli_error( $mysqli );
			}
			else {
				$add = 1;
				$LB_ID = mysqli_insert_id( $mysqli );
				// Записываем заливки
				foreach ($_POST["cassette"] as $key => $value) {
					$query = "
						INSERT INTO list__Pourings
						SET
							pouring_date = '{$batch_date}',
							cassette = {$value},
							LB_ID = {$LB_ID},
							sort = {$key}
					";
					if( !mysqli_query( $mysqli, $query ) ) {
						$_SESSION["error"][] = "Invalid query: ".mysqli_error( $mysqli );
					}
				}
			}
		}
	}

	if( count($_SESSION["error"]) == 0) {
		$_SESSION["success"][] = $add ? "Новыя запись успешно добавлена." : "Запись успешно отредактирована.";
	}

	// Перенаправление в журнал чек листов оператора
	if( $add ) {
		exit ('<meta http-equiv="refresh" content="0; url=/checklist.php?CW_ID='.$CW_ID.'&batch_date='.$batch_date.'&OP_ID='.$OP_ID.'&add#'.$LB_ID.'">');
	}
	else {
		exit ('<meta http-equiv="refresh" content="0; url=/checklist.php#'.$LB_ID.'">');
	}
}
///////////////////////////////////////////////////////
?>
<!-- Форма чек листа оператора -->
<style>
	#checklist_form table input,
	#checklist_form table select {
		font-size: 1.2em;
	}
</style>

<div id='checklist_form' title='Данные замеса' style='display:none;'>
	<form method='post' action="/forms/checklist_form.php" onsubmit="JavaScript:this.subbut.disabled=true;
this.subbut.value='Подождите, пожалуйста!';">
		<fieldset>
			<input type="hidden" name="LB_ID">

			<div class="nowrap" style="display: inline-block; margin-bottom: 10px; margin-right: 30px;">
				<span>Код противовеса:</span>
				<select name="CW_ID" style="font-size: 2em;" required>
					<option value=""></option>
					<?
					$query = "
						SELECT CW.CW_ID, CW.item, CW.fillings, CW.type
						FROM CounterWeight CW
					";
					$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
					while( $row = mysqli_fetch_array($res) ) {
						echo "<option value='{$row["CW_ID"]}' fillings='{$row["fillings"]}' type='{$row["type"]}'>{$row["item"]}</option>";
					}
					?>
				</select>
			</div>
			<div class="nowrap" style="display: inline-block; margin-bottom: 10px; margin-right: 30px;">
				<span>Дата замеса:</span>
				<input type="date" name="batch_date" required>
			</div>
			<div class="nowrap" style="display: inline-block; margin-bottom: 10px; margin-right: 30px;">
				<span>Оператор:</span>
				<select name="OP_ID" style="width: 100px;" required>
					<option value=""></option>
					<?
					$query = "
						SELECT OP.OP_ID, OP.name
						FROM Operator OP
					";
					$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
					while( $row = mysqli_fetch_array($res) ) {
						echo "<option value='{$row["OP_ID"]}'>{$row["name"]}</option>";
					}
					?>
				</select>
			</div>

			<table style="width: 100%; table-layout: fixed;">
				<thead>
					<tr>
						<th rowspan="2">Время</th>
						<th colspan="2">Масса кубика, х10 г</th>
						<th rowspan="2">Окалина, кг</th>
						<th rowspan="2">КМП, кг</th>
						<th rowspan="2">Отсев, кг</th>
						<th rowspan="2">Цемент, кг</th>
						<th rowspan="2">Вода, л</th>
						<th rowspan="2">№ кассеты</th>
						<th rowspan="2">Недолив</th>
					</tr>
					<tr>
						<th>Контрольный компонент</th>
						<th>Раствор</th>
					</tr>
				</thead>
				<tbody style="text-align: center;">
					<tr>
						<td><input type='time' name='batch_time' required></td>
						<td><input type='number' min='200' max='300' name='comp_density' style='width: 80px;' required></td>
						<td><input type='number' min='250' max='350' name='mix_density' style='width: 80px;' required></td>
						<td style='background-color: rgba(0, 0, 0, 0.2);'><input type='number' min='0' name='iron_oxide' style='width: 80px;' required></td>
						<td style='background-color: rgba(0, 0, 0, 0.2);'><input type='number' min='0' name='sand' style='width: 80px;' required></td>
						<td style='background-color: rgba(0, 0, 0, 0.2);'><input type='number' min='0' name='crushed_stone' style='width: 80px;' required></td>
						<td style='background-color: rgba(0, 0, 0, 0.2);'><input type='number' min='0' name='cement' style='width: 80px;' required></td>
						<td style='background-color: rgba(0, 0, 0, 0.2);'><input type='number' min='0' name='water' style='width: 80px;' required></td>
						<td id='fillings'></td>
						<td><input type="number" min="0" max="64" name="underfilling"></td>
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
		<?
		if( isset($_GET["add"]) ) {
		?>
		// Если было добавление замеса, автоматичеки открывается форма для новой записи
		$(document).ready(function() {
			$('#add_btn').click();
//			$('#checklist_form select[name="CW_ID"]').val(1).change();
//			$('#checklist_form input[name="batch_date"]').val('2020-07-03');
//			$('#checklist_form select[name="OP_ID"]').val(1);
		});
		<?
		}
		?>

		// Кнопка добавления маршрутного листа
		$('.add_checklist').click( function() {
			// Проверяем сессию
			$.ajax({ url: "check_session.php?script=1", dataType: "script", async: false });

			var LB_ID = $(this).attr("LB_ID"),
				CW_ID = $(this).attr("CW_ID"),
				batch_date = $(this).attr("batch_date"),
				OP_ID = $(this).attr("OP_ID");

			// В случае редактирования заполняем форму
			if( LB_ID ) {
				// Данные чек листа оператора аяксом
				$.ajax({
					url: "/ajax/checklist_json.php?LB_ID=" + LB_ID,
					success: function(msg) { data = msg; },
					dataType: "json",
					async: false
				});

				$('#checklist_form input[name="LB_ID"]').val(LB_ID);
				$('#checklist_form select[name="CW_ID"]').val(data['CW_ID']).change();
				$('#checklist_form select[name="OP_ID"]').val(data['OP_ID']);
				$('#checklist_form input[name="batch_date"]').val(data['batch_date']);
				$('#checklist_form input[name="batch_time"]').val(data['batch_time']);
				$('#checklist_form input[name="comp_density"]').val(data['comp_density']);
				$('#checklist_form input[name="mix_density"]').val(data['mix_density']);
				$('#checklist_form input[name="iron_oxide"]').val(data['iron_oxide']);
				$('#checklist_form input[name="sand"]').val(data['sand']);
				$('#checklist_form input[name="crushed_stone"]').val(data['crushed_stone']);
				$('#checklist_form input[name="cement"]').val(data['cement']);
				$('#checklist_form input[name="water"]').val(data['water']);
				$('#checklist_form input[name="underfilling"]').val(data['underfilling']);

				// Блокируем выбор противовеса
				$('#checklist_form select[name="CW_ID"] option:not(:selected)').attr('disabled', true);
				// Прячем инпуты для кассет
				//$('#fillings').html('Не редактируется');
				$.ajax({ url: "/ajax/checklist_cassette.php?LB_ID=" + LB_ID, dataType: "script", async: false });
			}
			// Иначе очищаем форму
			else {
				// Если после добавления записи, заполняем код, дату, оператора
				if( CW_ID ) {
					$('#checklist_form select[name="CW_ID"]').val(CW_ID).change();
					$('#checklist_form input[name="batch_date"]').val(batch_date);
					$('#checklist_form select[name="OP_ID"]').val(OP_ID);
				}
				else {
					$('#checklist_form select[name="CW_ID"]').val('').change();
				}

				// Разблокируем выбор противовеса
				$('#checklist_form select[name="CW_ID"] option').attr('disabled', false);

				$('#checklist_form table input').val('');
				$('#checklist_form table select').val('');
			}

			$('#checklist_form').dialog({
				resizable: false,
				width: 1000,
				modal: true,
				closeText: 'Закрыть'
			});

			return false;
		});

		// При выборе противовеса, узнаем кол-во заливок и тип смеси
		$('#checklist_form select[name="CW_ID"]').change( function() {
			var fillings = $('#checklist_form select[name="CW_ID"] option:selected').attr('fillings'),
				type = $('#checklist_form select[name="CW_ID"] option:selected').attr('type'),
				cassette = '';
			// Выводим инпуты для номеров кассет
			for (var i = 0; i < fillings; i++) {
				var cassette = cassette + '<input type="number" min="1" max="200" name="cassette[]" style="display: none;" required>';
			}
			$('#fillings').html(cassette);
			$('#checklist_form input[name="cassette[]"]').show('fast');

			// Скрываем не используемые компоненты смеси
			if( type == 1 ) {
				$('#checklist_form input[name="iron_oxide"]').show('fast');
				$('#checklist_form input[name="iron_oxide"]').attr("disabled", false);
				$('#checklist_form input[name="sand"]').hide('fast');
				$('#checklist_form input[name="sand"]').attr("disabled", true);
				$('#checklist_form input[name="crushed_stone"]').show('fast');
				$('#checklist_form input[name="crushed_stone"]').attr("disabled", false);
			}
			else if( type == 2 ) {
				$('#checklist_form input[name="iron_oxide"]').hide('fast');
				$('#checklist_form input[name="iron_oxide"]').attr("disabled", true);
				$('#checklist_form input[name="sand"]').show('fast');
				$('#checklist_form input[name="sand"]').attr("disabled", false);
				$('#checklist_form input[name="crushed_stone"]').hide('fast');
				$('#checklist_form input[name="crushed_stone"]').attr("disabled", true);
			}
			else if( type == 3 ) {
				$('#checklist_form input[name="iron_oxide"]').hide('fast');
				$('#checklist_form input[name="iron_oxide"]').attr("disabled", true);
				$('#checklist_form input[name="sand"]').show('fast');
				$('#checklist_form input[name="sand"]').attr("disabled", false);
				$('#checklist_form input[name="crushed_stone"]').show('fast');
				$('#checklist_form input[name="crushed_stone"]').attr("disabled", false);
			}
			else {
				$('#checklist_form input[name="iron_oxide"]').hide('fast');
				$('#checklist_form input[name="iron_oxide"]').attr("disabled", true);
				$('#checklist_form input[name="sand"]').hide('fast');
				$('#checklist_form input[name="sand"]').attr("disabled", true);
				$('#checklist_form input[name="crushed_stone"]').hide('fast');
				$('#checklist_form input[name="crushed_stone"]').attr("disabled", true);
			}
		});
	});
</script>