<?
include_once "../config.php";

// Сохранение/редактирование
if( isset($_POST["F_ID"]) ) {
	session_start();
	$F_ID = $_POST["F_ID"];
	$month = $_POST["month"];
	$outsrc = $_POST["outsrc"];

	if( $_POST["TS_ID"] ) {
		$TS_ID = $_POST["TS_ID"];
	}
	else {
		if( $_POST["tr_time1"] or $_POST["tr_time2"] or $_POST["status"] != '' ) {
			// Делаем запись в табеле
			$ts_date = $_POST["ts_date"];
			$USR_ID = $_POST["usr_id"];
			$query = "
				INSERT INTO Timesheet
				SET ts_date = '{$ts_date}'
					,USR_ID = {$USR_ID}
					,F_ID = {$F_ID}
					,TM_ID = (
						SELECT TM_ID
						FROM TariffMonth
						WHERE year = YEAR('{$ts_date}')
							AND month = MONTH('{$ts_date}')
							AND USR_ID = {$USR_ID}
							AND F_ID = {$F_ID}
					)
				ON DUPLICATE KEY UPDATE
					TM_ID = (
						SELECT TM_ID
						FROM TariffMonth
						WHERE year = YEAR('{$ts_date}')
							AND month = MONTH('{$ts_date}')
							AND USR_ID = {$USR_ID}
							AND F_ID = {$F_ID}
					)
			";
			mysqli_query( $mysqli, $query );
			$TS_ID = mysqli_insert_id( $mysqli );
		}
	}

	// Записываем регистрации
	if( $_POST["tr_time1"] ) {
		$tr_time1 = $_POST["tr_time1"];
		$query = "
			INSERT INTO TimeReg
			SET TS_ID = {$TS_ID}
				,tr_time = '{$tr_time1}'
				,add_time = NOW()
				,add_author = {$_SESSION['id']}
		";
		mysqli_query( $mysqli, $query );
	}
	if( $_POST["tr_time2"] ) {
		$tr_time2 = $_POST["tr_time2"];
		$query = "
			INSERT INTO TimeReg
			SET TS_ID = {$TS_ID}
				,tr_time = '{$tr_time2}'
				,add_time = NOW()
				,add_author = {$_SESSION['id']}
		";
		mysqli_query( $mysqli, $query );
	}
	//////////////////////////////

	// Обновляем статус
	if( $TS_ID ) {
		$status = ($_POST["status"] != '' ? $_POST["status"] : "NULL");
		$query = "
			UPDATE Timesheet
			SET status = {$status}
			WHERE TS_ID = {$TS_ID}
		";
		mysqli_query( $mysqli, $query );
	}
	/////////////////////////////////////////

	// Помечаем удаленные регистрации
	foreach ($_POST["del_reg"] as $key => $value) {
		$query = "
			UPDATE TimeReg
			SET del_time = NOW()
				,del_author = {$_SESSION['id']}
			WHERE TR_ID = {$value}
		";
		mysqli_query( $mysqli, $query );
	}

	// Перенаправление в табель
	exit ('<meta http-equiv="refresh" content="0; url=/timesheet.php?F_ID='.$F_ID.'&month='.$month.'&outsrc='.$outsrc.'#'.$TS_ID.'">');
}
?>

<style>
	.wr_photo {
		transition: all 0.3s ease-in-out;
		display: flex;
		width: 160px;
		height: 40px;
		background-color: #fdce46;
		background-size: cover;
		border-radius: 5px;
		margin: 5px auto;
		overflow: hidden;
		position: relative;
		box-shadow: 5px 5px 8px rgb(0 0 0 / 60%);
	}
	.wr_photo:hover {
		height: 120px !important;
	}
</style>

<div id='timesheet_form' style='display:none;'>
	<form method='post' action="/forms/timesheet_form.php" onsubmit="JavaScript:this.subbut.disabled=true;
this.subbut.value='Подождите, пожалуйста!';">
		<fieldset>
			<input type="hidden" name="F_ID" value="<?=$F_ID?>">
			<input type="hidden" name="month" value="<?=$_GET["month"]?>">
			<input type="hidden" name="outsrc" value="<?=$_GET["outsrc"]?>">

			<div id="hide"><!--Формируется скриптом--></div>
			<div id="summary"><!--Формируется скриптом--></div>

			<div style="display: flex; width: 100%; margin: 1em 0; font-size: 1.5em;">
				<label style="width: 100px; line-height: 36px;">Статус:</label>
				<div>
					<select name="status">
						<option value=""></option>
						<option value="0">Прочерк</option>
						<option value="1">Отпуск</option>
						<option value="2">Уволен</option>
						<option value="3">Больничный</option>
						<option value="4">Отгул</option>
						<option value="5">Прогул</option>
					</select>
				</div>
			</div>

			<table style="width: 100%; table-layout: fixed;">
				<thead>
					<tr>
						<th>Время регистрации</th>
						<th>Время добавления</th>
						<th>Время отмены</th>
					</tr>
				</thead>
				<tbody id="timereg" style="text-align: center;">
					<!--Формируется скриптом-->
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
		$('.tscell').on('click', function(){
			// Проверяем сессию
			$.ajax({ url: "check_session.php?script=1", dataType: "script", async: false });

			$('#timesheet_form select[name=status]').val('');

			var ts_id = $(this).attr('ts_id'),
				date_format = $(this).attr('date_format'),
				usr_name = $(this).attr('usr_name'),
				html = '',
				html_hide = '',
				html_summary = '';

			if( ts_id > 0 ) {
				// Формируем скрытые поля
				html_hide = html_hide + "<input type='hidden' name='TS_ID' value='"+ts_id+"'>";

				var arr_reg = TimeReg[ts_id];
				var tariff = $(this).attr('tariff'),
					duration = $(this).attr('duration'),
					pay = $(this).attr('pay'),
					status = $(this).attr('status');

				$('#timesheet_form select[name=status]').val(status);

				html_summary = html_summary + "<table style='width: 100%; table-layout: fixed; margin-bottom: 20px; border: 5px solid #999;'><thead><tr><th>Тариф</th><th>Продолжительность</th><th>Расчет</th></tr></thead><tbody style='text-align: center; font-size: 1.5em;'><tr>";
				html_summary = html_summary + "<td>"+tariff+"</td><td>"+duration+"</td><td>"+pay+"</td>";
				html_summary = html_summary + "</tr></tbody></table>";

				if( arr_reg ) {
					$.each(arr_reg, function(key, val){
						var del_style = '',
							photo_style = '';

						if( val["del_time"] != '' ) {
							del_style = 'background: #0006;';
						}

						if( val["tr_photo"] ) {
							photo_style = "background-image: url(/time_tracking/upload/"+val["tr_photo"]+");";
						}

						html = html + "<tr>";
						html = html + "<td style='"+del_style+"'><div style='display: flex;'><div class='wr_photo' style='"+photo_style+"'><span style='-webkit-filter: drop-shadow(0px 0px 2px #000); filter: drop-shadow(0px 0px 2px #000); color: #fff; font-size: 1.5em; margin: 5px;'>"+val["tr_time"]+"</span></div></div></td>";
						html = html + "<td style='"+del_style+"'><div style='height: 50px; display: flex; align-items: center; justify-content: space-evenly;'><div>"+val["add_author"]+"</div><div>"+val["add_time"]+"</div></div></td>";
						if( val["del_time"] != '' ) {
							html = html + "<td style='"+del_style+" color: #911;'><div style='height: 50px; display: flex; align-items: center; justify-content: space-evenly;'><div>"+val["del_author"]+"</div><div>"+val["del_time"]+"</div></div></td>";
						}
						else {
							html = html + "<td style='"+del_style+"'><div style='height: 50px; display: flex; align-items: center; justify-content: space-evenly;'><label>Отменить<input type='checkbox' class='del_reg' name='del_reg[]' value='"+val["TR_ID"]+"'></label></div></td>";
						}
						html = html + "</tr>";
					});
				}
			}
			else {
				var ts_date = $(this).attr('ts_date'),
					usr_id = $(this).attr('usr_id');

				// Формируем скрытые поля
				html_hide = html_hide + "<input type='hidden' name='ts_date' value='"+ts_date+"'>";
				html_hide = html_hide + "<input type='hidden' name='usr_id' value='"+usr_id+"'>";
			}

			html = html + "<tr><td><input type='time' name='tr_time1' step='1' style='margin: 10px; font-size: 1.5em;'><i class='fas fa-arrow-left'></i></td><td colspan='2' rowspan='2'>Чтобы добавить новую регистрацию, укажите время.</td></tr>";
			html = html + "<tr><td><input type='time' name='tr_time2' step='1' style='margin: 10px; font-size: 1.5em;'><i class='fas fa-arrow-left'></i></td></tr>";

			$('#timesheet_form #hide').html(html_hide);
			$('#timesheet_form #summary').html(html_summary);
			$('#timesheet_form #timereg').html(html);

			$('#timesheet_form').dialog({
				title: date_format + ' | ' + usr_name,
				resizable: false,
				width: 650,
				modal: true,
				closeText: 'Закрыть'
			});

			return false;
		});

		$('#timesheet_form tbody').on('change', '.del_reg', function(event) {
			if( $(this).is(":checked") ) {
				$(this).parents('td').css('background', '#f005');
			}
			else {
				$(this).parents('td').css('background', 'none');
			}
		});
	});
</script>
