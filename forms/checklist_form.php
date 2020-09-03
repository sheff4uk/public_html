<?
include_once "../config.php";

// Сохранение/редактирование чек листа оператора
if( isset($_POST["CW_ID"]) ) {
	session_start();

	$CW_ID = $_POST["CW_ID"];
	$OP_ID = $_POST["OP_ID"];
	$batch_date = $_POST["batch_date"];
	$batch_time = $_POST["batch_time"];
	$io_density = $_POST["io_density"] ? $_POST["io_density"]*1000 : "NULL";
	$sn_density = $_POST["sn_density"] ? $_POST["sn_density"]*1000 : "NULL";
	$cs_density = $_POST["cs_density"] ? $_POST["cs_density"]*1000 : "NULL";
	$mix_density = $_POST["mix_density"]*1000;
	$iron_oxide = $_POST["iron_oxide"] ? $_POST["iron_oxide"] : "NULL";
	$sand = $_POST["sand"] ? $_POST["sand"] : "NULL";
	$crushed_stone = $_POST["crushed_stone"] ? $_POST["crushed_stone"] : "NULL";
	$cement = $_POST["cement"];
	$water = $_POST["water"];
	$underfilling = $_POST["underfilling"] ? $_POST["underfilling"] : 0;
	$test = $_POST["test"] ? 1 : 0;

	// Редактируем замес
	if( $_POST["LB_ID"] ) {
				// Проверяем нет ли повторяющихся кассет
		if( count(array_unique($_POST["cassette"])) != count($_POST["cassette"]) ) {
			$_SESSION["error"][] = "Введенные номера кассет повторяются.";
		}
		else {
			// Проверяем наличие таких же кассет в этот день
			$cassette = implode(",", $_POST["cassette"]);
			$query = "
				SELECT
					DATE_FORMAT(LB.batch_date, '%d.%m.%y') batch_date,
					DATE_FORMAT(LB.batch_time, '%H:%i') batch_time,
					LF.cassette
				FROM list__Batch LB
				JOIN list__Filling LF ON LF.LB_ID = LB.LB_ID
				WHERE LB.batch_date = '{$batch_date}' AND LF.cassette IN ({$cassette}) AND LB.LB_ID != {$_POST["LB_ID"]}
			";
			$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			if( mysqli_num_rows($res) ) {
				while( $row = mysqli_fetch_array($res) ) {
					$_SESSION["alert"][] = "Кассета №{$row["cassette"]} уже заливалась {$row["batch_date"]} в {$row["batch_time"]}.";
				}
			}
			// Редактируем замесы
			$query = "
				UPDATE list__Batch
				SET
					CW_ID = {$CW_ID},
					OP_ID = {$OP_ID},
					batch_date = '{$batch_date}',
					batch_time = '{$batch_time}',
					io_density = {$io_density},
					sn_density = {$sn_density},
					cs_density = {$cs_density},
					mix_density = {$mix_density},
					iron_oxide = {$iron_oxide},
					sand = {$sand},
					crushed_stone = {$crushed_stone},
					cement = {$cement},
					water = {$water},
					underfilling = {$underfilling},
					test = {$test}
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
						UPDATE list__Filling
						SET cassette = {$value}
						WHERE LF_ID = {$key}
					";
					if( !mysqli_query( $mysqli, $query ) ) {
						$_SESSION["error"][] = "Invalid query: ".mysqli_error( $mysqli );
					}
				}
			}
		}
	}
	// Сохраняем новый замес
	else {
		// Проверяем нет ли повторяющихся кассет
		if( count(array_unique($_POST["cassette"])) != count($_POST["cassette"]) ) {
			$_SESSION["error"][] = "Введенные номера кассет повторяются.";
		}
		else {
			// Проверяем наличие таких же кассет в этот день
			$cassette = implode(",", $_POST["cassette"]);
			$query = "
				SELECT
					DATE_FORMAT(LB.batch_date, '%d.%m.%y') batch_date,
					DATE_FORMAT(LB.batch_time, '%H:%i') batch_time,
					LF.cassette
				FROM list__Batch LB
				JOIN list__Filling LF ON LF.LB_ID = LB.LB_ID
				WHERE LB.batch_date = '{$batch_date}' AND LF.cassette IN ({$cassette})
			";
			$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			if( mysqli_num_rows($res) ) {
				while( $row = mysqli_fetch_array($res) ) {
					$_SESSION["alert"][] = "Кассета №{$row["cassette"]} уже заливалась {$row["batch_date"]} в {$row["batch_time"]}.";
				}
			}
			// Создаем замес
			$query = "
				INSERT INTO list__Batch
				SET
					CW_ID = {$CW_ID},
					OP_ID = {$OP_ID},
					batch_date = '{$batch_date}',
					batch_time = '{$batch_time}',
					io_density = {$io_density},
					sn_density = {$sn_density},
					cs_density = {$cs_density},
					mix_density = {$mix_density},
					iron_oxide = {$iron_oxide},
					sand = {$sand},
					crushed_stone = {$crushed_stone},
					cement = {$cement},
					water = {$water},
					underfilling = {$underfilling},
					test = {$test}
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
						INSERT INTO list__Filling
						SET
							cassette = {$value},
							LB_ID = {$LB_ID}
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
		exit ('<meta http-equiv="refresh" content="0; url=/checklist.php?CW_ID='.$CW_ID.'&date_from='.$batch_date.'&date_to='.$batch_date.'&batch_date='.$batch_date.'&OP_ID='.$OP_ID.'&add#'.$LB_ID.'">');
	}
	else {
		exit ('<meta http-equiv="refresh" content="0; url=/checklist.php?date_from='.$batch_date.'&date_to='.$batch_date.'#'.$LB_ID.'">');
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

<div id='checklist_form' title='Данные заливок' style='display:none;'>
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
						SELECT CW.CW_ID
							,CW.item
							,CW.fillings
							,CW.type
							,SUM(MF.io_min) io
							,SUM(MF.sn_min) sn
							,SUM(MF.cs_min) cs
						FROM CounterWeight CW
						LEFT JOIN MixFormula MF ON MF.CW_ID = CW.CW_ID
						GROUP BY CW.CW_ID
						ORDER BY CW.CW_ID
					";
					$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
					while( $row = mysqli_fetch_array($res) ) {
						echo "<option value='{$row["CW_ID"]}' fillings='{$row["fillings"]}' type='{$row["type"]}' io='{$row["io"]}' sn='{$row["sn"]}' cs='{$row["cs"]}'>{$row["item"]}</option>";
					}
					?>
				</select>
			</div>
			<div class="nowrap" style="display: inline-block; margin-bottom: 10px; margin-right: 30px;">
				<span>Дата заливки:</span>
				<input type="date" name="batch_date" required>
				<i id="date_notice" class="fas fa-question-circle" title="Дата не редактируется так как есть связанные этапы расформовки или упаковки."></i>
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
						<th>Время</th>
						<th>Куб раствора, кг</th>
						<th>Окалина, кг</th>
						<th>КМП, кг</th>
						<th>Отсев, кг</th>
						<th>Цемент, кг</th>
						<th>Вода, л</th>
						<th>№ кассеты</th>
						<th>Недолив</th>
						<th>Испытиния кубов</th>
					</tr>
				</thead>
				<tbody style="text-align: center;">
					<tr>
						<td><input type='time' name='batch_time' required></td>
<!--						<td><input type='number' min='1' max='4' step='0.01' name='comp_density' style='width: 80px;' required></td>-->
						<td><input type='number' min='2' max='4' step='0.01' name='mix_density' style='width: 80px;' required></td>
						<td style='background-color: rgba(0, 0, 0, 0.2);'><input type='number' min='0' name='iron_oxide' style='width: 80px;' required></td>
						<td style='background-color: rgba(0, 0, 0, 0.2);'><input type='number' min='0' name='sand' style='width: 80px;' required></td>
						<td style='background-color: rgba(0, 0, 0, 0.2);'><input type='number' min='0' name='crushed_stone' style='width: 80px;' required></td>
						<td style='background-color: rgba(0, 0, 0, 0.2);'><input type='number' min='0' name='cement' style='width: 80px;' required></td>
						<td style='background-color: rgba(0, 0, 0, 0.2);'><input type='number' min='0' name='water' style='width: 80px;' required></td>
						<td id='fillings' style='position: relative;'></td>
						<td><input type="number" min="0" max="64" name="underfilling"></td>
						<td><input type="checkbox" name="test" value="1"><i id="test_notice" class="fas fa-question-circle" title="Не редактируется так как есть связанные испытания куба."></i></td>
					</tr>
					<tr>
						<td colspan="2"><b>Масса куба, кг:</b></td>
						<td style='background-color: rgba(0, 0, 0, 0.2);'><input type='number' min='2' max='3' step='0.01' name='io_density' style='width: 80px;' required></td>
						<td style='background-color: rgba(0, 0, 0, 0.2);'><input type='number' min='1' max='2' step='0.01' name='sn_density' style='width: 80px;' required></td>
						<td style='background-color: rgba(0, 0, 0, 0.2);'><input type='number' min='1' max='2' step='0.01' name='cs_density' style='width: 80px;' required></td>
						<td style='background-color: rgba(0, 0, 0, 0.2);'></td>
						<td style='background-color: rgba(0, 0, 0, 0.2);'></td>
						<td></td>
						<td></td>
						<td></td>
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
//		// Если было добавление замеса, автоматичеки открывается форма для новой записи
//		$(document).ready(function() {
//			$('#add_btn').click();
//		});
//		<?
//		}
//		?>

		// Кнопка добавления замеса
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
				$('#checklist_form input[name="io_density"]').val(data['io_density']);
				$('#checklist_form input[name="sn_density"]').val(data['sn_density']);
				$('#checklist_form input[name="cs_density"]').val(data['cs_density']);
				$('#checklist_form input[name="mix_density"]').val(data['mix_density']);
				$('#checklist_form input[name="iron_oxide"]').val(data['iron_oxide']);
				$('#checklist_form input[name="sand"]').val(data['sand']);
				$('#checklist_form input[name="crushed_stone"]').val(data['crushed_stone']);
				$('#checklist_form input[name="cement"]').val(data['cement']);
				$('#checklist_form input[name="water"]').val(data['water']);
				$('#checklist_form input[name="underfilling"]').val(data['underfilling']);
				// Чекбокс контрольного куба
				if( data['test'] == 1 ) {
					$('#checklist_form input[name="test"]').prop('checked', true);
				}
				else {
					$('#checklist_form input[name="test"]').prop('checked', false);
				}
				// Если произведено испытание, чекбокс блокируется
				if( data['is_test'] == 1 ) {
					$('#checklist_form input[name="test"]').prop('disabled', true);
					$('#test_notice').show('fast');
				}
				else {
					$('#checklist_form input[name="test"]').prop('disabled', false);
					$('#test_notice').hide('fast');
				}
				// Если связаны расформовка или упаковка, блокируем дату
				if( data['is_link'] == 1 ) {
					$('#checklist_form input[name="batch_date"]').attr('readonly', true);
					$('#date_notice').show('fast');
				}
				else {
					$('#checklist_form input[name="batch_date"]').attr('readonly', false);
					$('#date_notice').hide('fast');
				}

				// Блокируем выбор противовеса
				$('#checklist_form select[name="CW_ID"] option:not(:selected)').attr('disabled', true);
				// Выводим инпуты для кассет аяксом
				$.ajax({ url: "/ajax/checklist_cassette.php?LB_ID=" + LB_ID, dataType: "script", async: false });
			}
			// Иначе очищаем форму
			else {
				$('#checklist_form input[name="LB_ID"]').val('');
				$('#checklist_form input[name="test"]').prop('checked', false);
				$('#checklist_form input[name="test"]').prop('disabled', false);
				$('#test_notice').hide('fast');
				// Если после добавления записи, заполняем код, дату, оператора
				if( CW_ID ) {
					$('#checklist_form select[name="CW_ID"]').val(CW_ID).change();
					$('#checklist_form input[name="batch_date"]').val(batch_date);
					$('#checklist_form select[name="OP_ID"]').val(OP_ID);
				}
				else {
					$('#checklist_form select[name="CW_ID"]').val('').change();
					$('#checklist_form input[name="batch_date"]').val('');
					$('#checklist_form select[name="OP_ID"]').val('');
				}

				// Разблокируем выбор противовеса
				$('#checklist_form select[name="CW_ID"] option').attr('disabled', false);
				// Разблокируем выбор даты
				$('#checklist_form input[name="batch_date"]').attr('readonly', false);
				$('#date_notice').hide('fast');

				$('#checklist_form table input:not([type="checkbox"])').val('');
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
				io = $('#checklist_form select[name="CW_ID"] option:selected').attr('io'),
				sn = $('#checklist_form select[name="CW_ID"] option:selected').attr('sn'),
				cs = $('#checklist_form select[name="CW_ID"] option:selected').attr('cs'),
				cassette = '';
			// Выводим инпуты для номеров кассет
			for (var i = 0; i < fillings; i++) {
				var cassette = cassette + '<input type="number" min="1" max="<?=$cassetts?>" name="cassette[]" style="display: none;" required>';
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

			// Отображаем поля ввода плотности компонентов
			if( io ) {
				$('#checklist_form input[name="io_density"]').show('fast');
				$('#checklist_form input[name="io_density"]').attr("disabled", false);
			}
			else {
				$('#checklist_form input[name="io_density"]').hide('fast');
				$('#checklist_form input[name="io_density"]').attr("disabled", true);
			}
			if( sn ) {
				$('#checklist_form input[name="sn_density"]').show('fast');
				$('#checklist_form input[name="sn_density"]').attr("disabled", false);
			}
			else {
				$('#checklist_form input[name="sn_density"]').hide('fast');
				$('#checklist_form input[name="sn_density"]').attr("disabled", true);
			}
			if( cs ) {
				$('#checklist_form input[name="cs_density"]').show('fast');
				$('#checklist_form input[name="cs_density"]').attr("disabled", false);
			}
			else {
				$('#checklist_form input[name="cs_density"]').hide('fast');
				$('#checklist_form input[name="cs_density"]').attr("disabled", true);
			}
		});
	});
</script>
