<?
include_once "../config.php";
include_once "../checkrights.php";

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
		if( $_POST["tr_time1"] or $_POST["tr_time2"] or $_POST["status"] != '' or $_POST["substitute"] or $_POST["payout"] ) {
			// Делаем запись в табеле
			$ts_date = $_POST["ts_date"];
			$USR_ID = $_POST["usr_id"];
			$query = "
				INSERT INTO Timesheet
				SET ts_date = '{$ts_date}'
					,USR_ID = {$USR_ID}
					,F_ID = {$F_ID}
			";
			mysqli_query( $mysqli, $query );
			$TS_ID = mysqli_insert_id( $mysqli );
		}
	}

	////////////////////////////
	// Записываем регистрации //
	////////////////////////////
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

	/////////////////////////
	// Замещение работника //
	/////////////////////////
	$query = "
		SELECT TS.ts_date
			,USR_ID
		FROM Timesheet TS
		WHERE TS.TS_ID = {$TS_ID}
	";
	$res = mysqli_query( $mysqli, $query );
	$row = mysqli_fetch_array($res);
	$ts_date = $row["ts_date"];
	$USR_ID = $row["USR_ID"];

	if( $_POST["substitute"] ) {
		// Проверяем, чтобы не было замещения самого себя
		if( $USR_ID == $_POST["substitute"] ) {
			$_SESSION["error"][] = "Работник не может замещать сам себя.";
		}
		else {
			// Проверяем чтобы у замещаемого не было действительных регистраций
			$query = "
				SELECT IFNULL(SUM(1), 0) is_sub_reg
				FROM TimeReg TR
				WHERE TR.TS_ID = (SELECT TS_ID FROM Timesheet WHERE ts_date = '{$ts_date}' AND F_ID = {$F_ID} AND USR_ID = {$_POST["substitute"]})
					AND TR.del_time IS NULL
			";
			$res = mysqli_query( $mysqli, $query );
			$row = mysqli_fetch_array($res);
			$is_sub_reg = $row["is_sub_reg"];

			if( $is_sub_reg ) {
				$_SESSION["error"][] = "У замещаемого работника есть действительные регистрации в этот день. Замещение невозможно.";
			}
			else {
				// Делаем запись в табеле у замещаемого и узнаем его TS_ID
				$query = "
					INSERT INTO Timesheet
					SET ts_date = '{$ts_date}'
						,F_ID = {$F_ID}
						,USR_ID = {$_POST["substitute"]}
					ON DUPLICATE KEY UPDATE
						TS_ID = LAST_INSERT_ID(TS_ID)
				";
				mysqli_query( $mysqli, $query );
				$sub_TS_ID = mysqli_insert_id( $mysqli );

				// Устанавливаем указатель на замещаемого работника
				$query = "
					UPDATE Timesheet
					SET sub_TS_ID = {$sub_TS_ID}
					WHERE TS_ID = {$TS_ID}
				";
				mysqli_query( $mysqli, $query );

				// Вычисляем повышающий коэффициент по числу указателей
				$query = "
					SELECT IFNULL(SUM(1), 0) sub_cnt
					FROM Timesheet
					WHERE sub_TS_ID = {$sub_TS_ID}
				";
				$res = mysqli_query( $mysqli, $query );
				$row = mysqli_fetch_array($res);
				$sub_cnt = $row["sub_cnt"];

				if( $sub_cnt > 0 ) {
					// Обновляем коэффициент у замещающих
					$query = "
						UPDATE Timesheet
						SET rate = 1 + IF({$sub_cnt} = 1, 1/2, 1/{$sub_cnt})
						WHERE sub_TS_ID = {$sub_TS_ID}
					";
					mysqli_query( $mysqli, $query );
				}
			}
		}
	}
	else {
		$query = "
			UPDATE Timesheet
			SET sub_TS_ID = NULL
				,rate = 1
			WHERE TS_ID = {$TS_ID}
		";
		mysqli_query( $mysqli, $query );
	}
	/////////////////////////////

	////////////////////////////////////
	// Помечаем удаленные регистрации //
	////////////////////////////////////
	foreach ($_POST["del_reg"] as $key => $value) {
		$query = "
			UPDATE TimeReg
			SET del_time = NOW()
				,del_author = {$_SESSION['id']}
			WHERE TR_ID = {$value}
		";
		mysqli_query( $mysqli, $query );
	}
	/////////////////////////////////////

	//////////////////////
	// Обновляем статус //
	//////////////////////
	if( $TS_ID ) {
		$status = ($_POST["status"] != '' ? $_POST["status"] : "NULL");
		$query = "
			UPDATE Timesheet
			SET status = {$status}
				,pay = IF({$status} = 5 AND pay IS NULL, -5000, IF(IFNULL({$status}, 0) != 5 AND pay < 0, NULL, pay))
			WHERE TS_ID = {$TS_ID}
		";
		mysqli_query( $mysqli, $query );
	}
	///////////////////////////////////

	////////////////////
	// Обновляем займ //
	////////////////////
	if( $TS_ID ) {
		$payout = ($_POST["payout"] != '' ? $_POST["payout"] : "NULL");
		$comment = convert_str($_POST["comment"]);
		$comment = mysqli_real_escape_string($mysqli, $comment);
		$query = "
			UPDATE Timesheet
			SET payout = {$payout}
				,comment = '{$comment}'
			WHERE TS_ID = {$TS_ID}
		";
		mysqli_query( $mysqli, $query );
	}
	///////////////////////////////////

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

			<fieldset>
				<legend style="font-size: 1.3em;">Займ:</legend>
				<div style="display: flex; justify-content: space-between; font-size: 1.3em;">
					<div style="display: flex; margin: 1em 0;">
						<input type="number" name="payout" min="0" placeholder="Сумма" style="width: 100px;">
					</div>
					<div style="display: flex; margin: 1em 0;">
						<input type="text" name="comment" placeholder="Комментарий"  style="width: 400px;" autocomplete="off">
					</div>
				</div>
			</fieldset>

			<div style="display: flex; justify-content: space-between; font-size: 1.3em;">
				<div style="display: flex; margin: 1em 0;">
					<label style="margin-right: .5em; line-height: 1.8em;">Статус:</label>
					<div>
						<select name="status" style="width: 150px;">
							<option value=""></option>
							<option value="0">Прочерк</option>
							<option value="1">Отпуск</option>
							<option value="2">Уволен</option>
							<option value="3">Больничный</option>
							<option value="4">Выходной</option>
							<option value="5">Прогул</option>
						</select>
					</div>
				</div>

				<div style="display: flex; margin: 1em 0;">
					<label style="margin-right: .5em; line-height: 1.8em;">Замещает:</label>
					<div>
						<select name="substitute" style="width: 150px;">
							<option value=""></option>
								<?
								$query = "
									SELECT TM.USR_ID
										,USR_Name(TM.USR_ID) name
									FROM TariffMonth TM
									WHERE TM.year = {$year}
										AND TM.month = {$month}
										AND TM.F_ID = {$F_ID}
									ORDER BY name
								";
								$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
								while( $row = mysqli_fetch_array($res) ) {
									echo "<option value='{$row["USR_ID"]}'>{$row["name"]}</option>";
								}
								?>
						</select>
					</div>
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
			$('#timesheet_form select[name=substitute]').val('');
			$('#timesheet_form input[name=payout]').val('');
			$('#timesheet_form input[name=comment]').val('');

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
					rate = $(this).attr('rate'),
					status = $(this).attr('status'),
					substitute = $(this).attr('substitute'),
					photo = $(this).attr('photo'),
					payout = $(this).attr('payout'),
					comment = $(this).attr('comment');

				$('#timesheet_form select[name=status]').val(status);
				$('#timesheet_form select[name=substitute]').val(substitute);
				$('#timesheet_form input[name=payout]').val(payout);
				$('#timesheet_form input[name=comment]').val(comment);

				var html_photo = '';
				if( photo ) {
					html_photo = "<img src='/time_tracking/upload/"+photo+"' style='width: 100%; border-radius: 5px;'>";
				}

				html_summary = html_summary + "<table style='width: 100%; table-layout: fixed; margin-bottom: 20px; border: 5px solid #999;'><thead><tr><th></th><th>Тариф</th><th>Продолжительность</th><th>Расчет</th></tr></thead><tbody style='text-align: center; font-size: 1.3em;'><tr>";

				var total;
				if( rate > 1 ) { total = pay+"<br>x"+Math.round(rate*100)/100+"<i class='fas fa-question-circle' title='Коэффициент замещения'></i><br>="+Math.round(pay*rate); }
				else { total = pay; }

				html_summary = html_summary
					+ "<td>"+html_photo+"</td>"
					+ "<td>"+tariff+"</td>"
					+ "<td>"+duration+"</td>"
					+ "<td>"+total+"</td>";
					+ "</tr></tbody></table>";

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

			html = html + "<tr><td><input type='time' name='tr_time1' style='margin: 10px; font-size: 1.5em;'><i class='fas fa-arrow-left'></i></td><td colspan='2' rowspan='2'>Чтобы добавить новую регистрацию, укажите время.</td></tr>";
			html = html + "<tr><td><input type='time' name='tr_time2' style='margin: 10px; font-size: 1.5em;'><i class='fas fa-arrow-left'></i></td></tr>";

			$('#timesheet_form #hide').html(html_hide);
			$('#timesheet_form #summary').html(html_summary);
			$('#timesheet_form #timereg').html(html);

			// Делаем форму неактивной в случае замещения работника
			if( $(this).attr('sub_is') > 0 ) {
				$('#timesheet_form input[name="tr_time1"]').attr('disabled', true);
				$('#timesheet_form input[name="tr_time2"]').attr('disabled', true);
				$('#timesheet_form select[name=substitute]').attr('disabled', true);
			}
			else {
				$('#timesheet_form input[name="tr_time1"]').attr('disabled', false);
				$('#timesheet_form input[name="tr_time2"]').attr('disabled', false);
				$('#timesheet_form select[name=substitute]').attr('disabled', false);
			}

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
