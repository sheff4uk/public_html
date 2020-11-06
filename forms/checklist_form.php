<?
include_once "../config.php";

// Сохранение/редактирование чек листа оператора
if( isset($_POST["PB_ID"]) ) {
	session_start();

	//Узнаем есть ли связанные с планом чеклисты и число замесов
	$query = "
		SELECT PB.pb_date, PB.fakt
		FROM plan__Batch PB
		WHERE PB.PB_ID = {$_POST["PB_ID"]}
	";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$row = mysqli_fetch_array($res);
	$pb_date = $row["pb_date"];
	$fakt = $row["fakt"];

	// Сохраняем данные из формы
	if( $fakt <= $_POST["fakt"] ) { // Новое число замесов не может быть меньше уже существующего кол-ва
		// Построчное считывание формы
		foreach ($_POST["batch_time"] as $key => $value) {
			$batch_time = $value;
			$io_density = $_POST["io_density"][$key] ? $_POST["io_density"][$key]*1000 : "NULL";
			$sn_density = $_POST["sn_density"][$key] ? $_POST["sn_density"][$key]*1000 : "NULL";
			$cs_density = $_POST["cs_density"][$key] ? $_POST["cs_density"][$key]*1000 : "NULL";
			$mix_density = $_POST["mix_density"][$key]*1000;
			$iron_oxide = $_POST["iron_oxide"][$key] ? $_POST["iron_oxide"][$key] : "NULL";
			$sand = $_POST["sand"][$key] ? $_POST["sand"][$key] : "NULL";
			$crushed_stone = $_POST["crushed_stone"][$key] ? $_POST["crushed_stone"][$key] : "NULL";
			$cement = $_POST["cement"][$key];
			$water = $_POST["water"][$key];
			$underfilling = $_POST["underfilling"][$key] ? $_POST["underfilling"][$key] : 0;
			$test = $_POST["test"][$key] ? 1 : 0;
			$OP_ID = $_POST["OP_ID"][$key];

			if( strpos($key,"n_") === 0 ) { // Добавляем замес
				$query = "
					INSERT INTO list__Batch
					SET PB_ID = {$_POST["PB_ID"]}
						,batch_time = '{$batch_time}'
						,io_density = {$io_density}
						,sn_density = {$sn_density}
						,cs_density = {$cs_density}
						,mix_density = {$mix_density}
						,iron_oxide = {$iron_oxide}
						,sand = {$sand}
						,crushed_stone = {$crushed_stone}
						,cement = {$cement}
						,water = {$water}
						,underfilling = {$underfilling}
						,test = {$test}
						,OP_ID = {$OP_ID}
				";
				mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

				$LB_ID = mysqli_insert_id( $mysqli );

				// Записываем номера кассет
				foreach ($_POST["cassette"][$key] as $k => $v) {
					$query = "
						INSERT INTO list__Filling
						SET cassette = {$v}
							,LB_ID = {$LB_ID}
					";
					mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
				}
			}
			else { // Редактируем замес
				$query = "
					UPDATE list__Batch
					SET PB_ID = {$_POST["PB_ID"]}
						,batch_time = '{$batch_time}'
						,io_density = {$io_density}
						,sn_density = {$sn_density}
						,cs_density = {$cs_density}
						,mix_density = {$mix_density}
						,iron_oxide = {$iron_oxide}
						,sand = {$sand}
						,crushed_stone = {$crushed_stone}
						,cement = {$cement}
						,water = {$water}
						,underfilling = {$underfilling}
						,test = {$test}
						,OP_ID = {$OP_ID}
					WHERE LB_ID = {$key}
				";
				mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

				// Редактируем номера кассет
				foreach ($_POST["cassette"][$key] as $k => $v) {
					$query = "
						UPDATE list__Filling
						SET
							cassette = {$v}
						WHERE LF_ID = {$k}
					";
					mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
				}
			}
		}
		// Обновляем фактическое число замесов
		$query = "
			UPDATE plan__Batch
			SET fakt = {$_POST["fakt"]}
			WHERE PB_ID = {$_POST["PB_ID"]}
		";
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	}
	else {
		$_SESSION["error"][] = "Что-то пошло не так. Пожалуйста, повторите попытку.";
	}

	if( count($_SESSION["error"]) == 0) {
		$_SESSION["success"][] = "Данные чек-листа оператора успешно сохранены.";
	}

	// Получаем неделю
	$query = "
		SELECT YEARWEEK(pb_date, 1) week
		FROM plan__Batch
		WHERE PB_ID = {$_POST["PB_ID"]}
		";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$row = mysqli_fetch_array($res);
	$week = $row["week"];

	// Перенаправление в журнал чек листов оператора
	exit ('<meta http-equiv="refresh" content="0; url=/checklist.php?week='.$week.'&#PB'.$_POST["PB_ID"].'">');
}
///////////////////////////////////////////////////////
?>
<!-- Форма чек листа оператора -->
<style>
	#checklist_form table input,
	#checklist_form table select {
/*		font-size: 1.2em;*/
	}
</style>

<div id='checklist_form' title='Чеклист оператора' style='display:none;'>
	<form method='post' action="/forms/checklist_form.php" onsubmit="JavaScript:this.subbut.disabled=true;
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
		// Функция открытия формы чеклиста оператора
		function checklist_form(PB_ID) {
			// Проверяем сессию
			$.ajax({ url: "check_session.php?script=1", dataType: "script", async: false });

			//Рисуем форму
			$.ajax({ url: "/ajax/checklist_form_ajax.php?PB_ID="+PB_ID, dataType: "script", async: false });

			$('#checklist_form').dialog({
				resizable: false,
				width: 1000,
				modal: true,
				closeText: 'Закрыть'
			});

			$('#checklist_form #rows').change();
			$('#checklist_form input[type=time]').change();

			return false;
		}

		// Считывание штрихкода
		var barcode="";
		$(document).keydown(function(e)
		{
			var code = (e.keyCode ? e.keyCode : e.which);
			if( code==13 || code==9 )// Enter key hit. Tab key hit.
			{
				console.log(barcode);
				if( barcode.length == 8 ) {
					checklist_form(Number(barcode));
					barcode="";
					return false;
				}
				barcode="";
			}
			else
			{
				barcode=barcode+String.fromCharCode(code);
			}
		});

		// Кнопка добавления чеклиста оператора
		$('.add_checklist').click( function() {
			var PB_ID = $(this).attr("PB_ID");

			checklist_form(PB_ID);

			return false;
		});

		// Изменение числа строк в форме
		$('#checklist_form').on('change', '#rows', function() {
			var val = parseInt($(this).val());
			$('.batch_row').each(function(){
				var num = parseInt($(this).attr('num'));
				if( num <= val ) {
					$(this).show('fast');
					$(this).find('input').prop('disabled', false);
					$(this).find('select option').prop('disabled', false);
					$(this).find('select').prop('required', true);
				}
				else {
					$(this).hide('fast');
					$(this).find('input').prop('disabled', true);
					$(this).find('select option').prop('disabled', true);
					$(this).find('select').prop('required', false);
				}
			});
		});

		// Ограничения при выборе времени
		$('#checklist_form').on('change', 'input[type=time]', function() {
			var val = $(this).val();
			var max = moment.utc(val,'HH:mm').add(-1,'minutes').format('HH:mm');
			var min = moment.utc(val,'HH:mm').add(1,'minutes').format('HH:mm');
			$(this).parents('tr').prev().children().children('input[type=time]').attr('max', max);
			$(this).parents('tr').next().children().children('input[type=time]').attr('min', min);
		});
	});
</script>
