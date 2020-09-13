<?
include_once "../config.php";

// Сохранение/редактирование чек листа оператора
if( isset($_POST["PB_ID"]) ) {
	session_start();

	//Узнаем есть ли связанные с планом чеклисты и число замесов
	$query = "
		SELECT PB.pb_date, PB.batches, PB.fakt
		FROM plan__Batch PB
		WHERE PB.PB_ID = {$_POST["PB_ID"]}
	";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$row = mysqli_fetch_array($res);
	$pb_date = $row["pb_date"];
	$batches = $row["batches"];
	$fakt = $row["fakt"];

	// Редактируем чеклист
	if( $fakt ) {
		// Сравниваем наличие чеклиста на момент сохранения формы и на момент открытия
		if( $fakt == $_POST["fakt"] ) {
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

				// Редактируем замес
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
		else {
			$_SESSION["error"][] = "Этот чеклист оператора уже был добавлен. Данные не сохранены!";
		}
	}
	// Добавляем чеклист
	else {
		// Должно совпадать число замесов на момент сохранения
		if( $_POST["batches"] == $batches ) {
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

				// Создаем замес
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
				$add = 1;

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

			// Помечаем план как выполненый
			$query = "
				UPDATE plan__Batch
				SET fakt = 1
				WHERE PB_ID = {$_POST["PB_ID"]}
			";
			mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		}
		else {
			$_SESSION["error"][] = "Количество замесов не совпадает с планом. Данные не сохранены!";
		}
	}

	if( count($_SESSION["error"]) == 0) {
		$_SESSION["success"][] = $add ? "Чеклист оператора успешно добавлен." : "Чеклист оператора успешно отредактирован.";
	}

	// Перенаправление в журнал чек листов оператора
	exit ('<meta http-equiv="refresh" content="0; url=/checklist.php?date_from='.$pb_date.'&date_to='.$pb_date.'&#PB'.$_POST["PB_ID"].'">');
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
		// Кнопка добавления чеклиста оператора
		$('.add_checklist').click( function() {
			// Проверяем сессию
			$.ajax({ url: "check_session.php?script=1", dataType: "script", async: false });

			var PB_ID = $(this).attr("PB_ID");

			//Рисуем форму
			$.ajax({ url: "/ajax/checklist_form_ajax.php?PB_ID="+PB_ID, dataType: "script", async: false });

			$('#checklist_form').dialog({
				resizable: false,
				width: 1000,
				modal: true,
				closeText: 'Закрыть'
			});

			return false;
		});
	});
</script>
