<?
include_once "../config.php";

// Сохранение/редактирование чек листа оператора
if( isset($_POST["PB_ID"]) ) {
	session_start();

	//Узнаем есть ли связанные с планом чеклисты и число замесов
	$query = "
		SELECT PB.fact_batches
		FROM plan__Batch PB
		WHERE PB.PB_ID = {$_POST["PB_ID"]}
	";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$row = mysqli_fetch_array($res);
	$fact_batches = $row["fact_batches"];

	// Сохраняем данные из формы
	if( $fact_batches <= $_POST["fact_batches"] ) { // Новое число замесов не может быть меньше уже существующего кол-ва
		// Построчное считывание формы
		foreach ($_POST["batch_time"] as $key => $value) {
			$batch_date = substr($value, 0, 10);
			$batch_time = substr($value, -5);
			$mix_density = $_POST["mix_density"][$key]*1000;
			$temp = $_POST["temp"][$key] ? $_POST["temp"][$key] : "NULL";
			$s_fraction = $_POST["s_fraction"][$key] ? $_POST["s_fraction"][$key] : "NULL";
			$l_fraction = $_POST["l_fraction"][$key] ? $_POST["l_fraction"][$key] : "NULL";
			$iron_oxide = $_POST["iron_oxide"][$key] ? $_POST["iron_oxide"][$key] : "NULL";
			$sand = $_POST["sand"][$key] ? $_POST["sand"][$key] : "NULL";
			$crushed_stone = $_POST["crushed_stone"][$key] ? $_POST["crushed_stone"][$key] : "NULL";
			$cement = $_POST["cement"][$key];
			$plasticizer = $_POST["plasticizer"][$key] ? $_POST["plasticizer"][$key] : "NULL";
			$water = $_POST["water"][$key];
			$underfilling = $_POST["underfilling"][$key] ? $_POST["underfilling"][$key] : 0;
			$test = $_POST["test"][$key] ? 1 : 0;

			if( strpos($key,"n_") === 0 ) { // Добавляем замес
				$query = "
					INSERT INTO list__Batch
					SET PB_ID = {$_POST["PB_ID"]}
						,batch_date = '{$batch_date}'
						,batch_time = '{$batch_time}'
						,mix_density = {$mix_density}
						,temp = {$temp}
						,s_fraction = {$s_fraction}
						,l_fraction = {$l_fraction}
						,iron_oxide = {$iron_oxide}
						,sand = {$sand}
						,crushed_stone = {$crushed_stone}
						,cement = {$cement}
						,plasticizer = {$plasticizer}
						,water = {$water}
						,test = {$test}
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
					// ID последней залитой кассеты
					$LF_ID = mysqli_insert_id( $mysqli );
				}
				// Записываем недоливы в последнюю кассету
				$query = "
					UPDATE list__Filling
					SET underfilling = {$underfilling}
					WHERE LF_ID = {$LF_ID}
				";
				mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			}
			else { // Редактируем замес
				$query = "
					UPDATE list__Batch
					SET PB_ID = {$_POST["PB_ID"]}
						,batch_date = '{$batch_date}'
						,batch_time = '{$batch_time}'
						,mix_density = {$mix_density}
						,temp = {$temp}
						,s_fraction = {$s_fraction}
						,l_fraction = {$l_fraction}
						,iron_oxide = {$iron_oxide}
						,sand = {$sand}
						,crushed_stone = {$crushed_stone}
						,cement = {$cement}
						,plasticizer = {$plasticizer}
						,water = {$water}
						,test = {$test}
					WHERE LB_ID = {$key}
				";
				mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

				// Редактируем номера кассет
				foreach ($_POST["cassette"][$key] as $k => $v) {
					$query = "
						UPDATE list__Filling
						SET
							cassette = {$v}
							,underfilling = 0
						WHERE LF_ID = {$k}
					";
					mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
					// ID последней залитой кассеты
					$LF_ID = $k;
				}
				// Записываем недоливы в последнюю кассету
				$query = "
					UPDATE list__Filling
					SET underfilling = {$underfilling}
					WHERE LF_ID = {$LF_ID}
				";
				mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			}
		}
		// Обновляем фактическое число замесов и число заливок на замес
		$sf_density = $_POST["sf_density"] ? $_POST["sf_density"]*1000 : "NULL";
		$lf_density = $_POST["lf_density"] ? $_POST["lf_density"]*1000 : "NULL";
		$io_density = $_POST["io_density"] ? $_POST["io_density"]*1000 : "NULL";
		$sn_density = $_POST["sn_density"] ? $_POST["sn_density"]*1000 : "NULL";
		$cs_density = $_POST["cs_density"] ? $_POST["cs_density"]*1000 : "NULL";

		$query = "
			UPDATE plan__Batch
			SET fact_batches = {$_POST["fact_batches"]}
				,fillings_per_batch = {$_POST["fillings_per_batch"]}
				,in_cassette = {$_POST["in_cassette"]}
				,sf_density = {$sf_density}
				,lf_density = {$lf_density}
				,io_density = {$io_density}
				,sn_density = {$sn_density}
				,cs_density = {$cs_density}
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
		SELECT YEARWEEK(LB.batch_date, 1) week
		FROM plan__Batch PB
		JOIN list__Batch LB ON LB.PB_ID = PB.PB_ID
		WHERE PB.PB_ID = {$_POST["PB_ID"]}
		";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$row = mysqli_fetch_array($res);
	$week = $row["week"];

	// Перенаправление в журнал чек листов оператора
	exit ('<meta http-equiv="refresh" content="0; url=/filling.php?week='.$week.'&#PB'.$_POST["PB_ID"].'">');
}
///////////////////////////////////////////////////////
?>
<!-- Форма чек листа оператора -->
<style>
	#filling_form table input,
	#filling_form table select {
/*		font-size: 1.2em;*/
	}
</style>

<div id='filling_form' title='Чеклист оператора' style='display:none;'>
	<form method='post' action="/forms/filling_form.php" onsubmit="JavaScript:this.subbut.disabled=true;
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
		function filling_form(PB_ID) {
			// Проверяем сессию
			$.ajax({ url: "check_session.php?script=1", dataType: "script", async: false });

			//Рисуем форму
			$.ajax({ url: "/ajax/filling_form_ajax.php?PB_ID="+PB_ID, dataType: "script", async: false });

			$('#filling_form').dialog({
				resizable: false,
				width: 1000,
				modal: true,
				closeText: 'Закрыть'
			});

			$('#filling_form #rows').change();
			$('#filling_form input[type=time]').change();
			$('#filling_form input.cassette').trigger('focusout');

			return false;
		}

		// Считывание штрихкода
		var barcode="";
		$(document).keydown(function(e)
		{
			var code = (e.keyCode ? e.keyCode : e.which);
			if (code==0) barcode="";
			if( code==13 || code==9 )// Enter key hit. Tab key hit.
			{
				console.log(barcode);
				if( barcode.length == 8 ) {
					filling_form(Number(barcode));
					barcode="";
					return false;
				}
				barcode="";
			}
			else
			{
				if (code >= 48 && code <= 57) {
					barcode=barcode+String.fromCharCode(code);
				}
			}
		});

		// Кнопка добавления чеклиста оператора
		$('.add_filling').click( function() {
			var PB_ID = $(this).attr("PB_ID");

			filling_form(PB_ID);

			return false;
		});

		// Сохраняем номер кассеты до его изменения
		$('#filling_form').on('focusin', 'input.cassette', function(){
			$(this).data('val', $(this).val());
		});

		// Отмечаем введенные кассеты в списке вероятных номеров кассет
		$('#filling_form').on('focusout', 'input.cassette', function() {
			var prev = $(this).data('val');
			var current = $(this).val();

			var cnt = 0;
			$('input.cassette').each(function (index, el){
				var v = $(el).val();
				if( v == prev ) cnt = cnt + 1;
			});
			// Если бывший номер больше не задействован
			if( cnt == 0 ) {
				$('#c_' + prev).css({'background-color': '', 'border-color': ''});
			}
			// Если остался только один экземпляр с бывшим номером
			else if( cnt == 1 ) {
				$('input.cassette').each(function (index, el){
					if( $(el).val() == prev ) {
						$(el).css({'background-color': 'green', 'border-color': 'green'});
					}
				});
			}

			$('#c_' + current).css({'background-color': 'green', 'border-color': 'green'});
			if( $('#c_' + current).text() ) {
				$(this).css({'background-color': 'green', 'border-color': 'green'});
			}
			else {
				$(this).css({'background-color': '', 'border-color': ''});
			}

			// Дубликаты красным цветом
			var cnt = 0;
			$('input.cassette').each(function (index, el){
				var v = $(el).val();
				if( v == current ) {
					cnt = cnt + 1;
				}
			});
			if( cnt > 1 ) {
				$('input.cassette').each(function (index, el){
					if( $(el).val() == current ) {
						$(el).css({'background-color': 'red', 'border-color': 'red'});
					}
				});
			}
		});

		// Изменение числа строк в форме
		$('#filling_form').on('change', '#rows', function() {
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
		$('#filling_form').on('change', 'input[type=datetime-local]', function() {
			var val = $(this).val();
			var max = moment.utc(val,'YYYY-MM-DDTHH:mm').add(-1,'minutes').format('YYYY-MM-DDTHH:mm');
			var min = moment.utc(val,'YYYY-MM-DDTHH:mm').add(1,'minutes').format('YYYY-MM-DDTHH:mm');
			$(this).parents('tr').prev().children().children('input[type=datetime-local]').attr('max', max);
			$(this).parents('tr').next().children().children('input[type=datetime-local]').attr('min', min);
		});

//		// Предупреждение при переходе на следующие сутки
//		$('#filling_form').on('change', 'input[type=time]', function() {
//			var val = $(this).val();
//			var prev = $(this).parents('tr').prev().children().children('input[type=time]').val();
//			var next = $(this).parents('tr').next().children().children('input[type=time]').val();
//			var att = '<i class="fas fa-exclamation-triangle" style="color: red;" title="Переход на следующие сутки"></i>';
//
//			// Если время стало меньше предыдущего, предупреждение о переходе на новые сутки
//			if( moment.utc(val,'HH:mm') < moment.utc(prev,'HH:mm') ) {
//				$(this).parents('tr').children().children('att').html(att);
//			}
//			else {
//				$(this).parents('tr').children().children('att').html('');
//			}
//
//			// Если время стало больше следующего, у следующего времени предупреждение
//			if( moment.utc(val,'HH:mm') > moment.utc(next,'HH:mm') ) {
//				$(this).parents('tr').next().children().children('att').html(att);
//			}
//			else {
//				$(this).parents('tr').next().children().children('att').html('');
//			}
//
////			var max = moment.utc(val,'HH:mm').add(-1,'minutes').format('HH:mm');
////			var min = moment.utc(val,'HH:mm').add(1,'minutes').format('HH:mm');
////			$(this).parents('tr').prev().children().children('input[type=time]').attr('max', max);
////			$(this).parents('tr').next().children().children('input[type=time]').attr('min', min);
//		});
	});
</script>
