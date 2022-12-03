<?
include_once "../config.php";
$ip = $_SERVER['REMOTE_ADDR'];
// Узнаем участок
$query = "
	SELECT F_ID
	FROM factory
	WHERE from_ip = '{$ip}'
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
$row = mysqli_fetch_array($res);
$F_ID = $row["F_ID"];

if( !$F_ID ) die("Access denied");

if( isset($_POST["cardcode"]) ) {
	// Выделяем префикс и номер карты
	$prefix = substr($_POST["cardcode"], 0, 1);
	$cardcode = substr($_POST["cardcode"], 1, 10);

	// Верифицируем работника
	$query = "
		SELECT USR.USR_ID
		FROM Users USR
		WHERE USR.cardcode LIKE '{$cardcode}'
			AND USR.act = 1
	";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$row = mysqli_fetch_array($res);
	$USR_ID = $row["USR_ID"];

	// Если работник верифицирован
	if( $USR_ID ) {
		// Обновляем участок работника
		$query = "
			UPDATE Users
			SET F_ID = {$F_ID}
			WHERE USR_ID = {$USR_ID}
		";
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

		// Проверяем корректность статуса регистрации (открытие - закрытие)
		$query = "
			SELECT TR.tr_minute
			FROM TimeReg TR
			JOIN TimesheetShift TSS ON TSS.TSS_ID = TR.TSS_ID
				AND TSS.duration IS NULL
			JOIN Timesheet TS ON TS.TS_ID = TSS.TS_ID
				AND TS.F_ID = {$F_ID}
				AND TS.USR_ID = {$USR_ID}
			WHERE TR.del_time IS NULL
		";
		$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		if( $row = mysqli_fetch_array($res) ) {
			$last_prefix = 1;
			$shift_start = $row["tr_minute"];
		}
		else {
			$last_prefix = 0;
		}

		// Если префиксы разные
		if( $last_prefix != $prefix ) {

			// Преобразуем время регистрации в минуты от начала суток
			$query = "SELECT HOUR(NOW()) * 60 + MINUTE(NOW()) tr_minute";
			$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			$row = mysqli_fetch_array($res);
			$tr_minute = $row["tr_minute"];

			// Если новая смена
			if( $prefix == 1 ) {
				// По времени регистрации вычисляем дату и номер смены
				$query = "
					SELECT CURDATE() ts_date
						,WS.shift_num
					FROM WorkingShift WS
					WHERE WS.F_ID = {$F_ID}
						AND {$tr_minute} + 120 BETWEEN WS.shift_start AND WS.shift_end
						AND CURDATE() BETWEEN WS.valid_from AND IFNULL(WS.valid_to, CURDATE())
				";
				$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
				if( $row = mysqli_fetch_array($res) ) {
					$ts_date = $row["ts_date"];
					$shift_num = $row["shift_num"];
				}
				else {
					$query = "
						SELECT CURDATE() - INTERVAL 1 DAY ts_date
							,WS.shift_num
						FROM WorkingShift WS
						WHERE WS.F_ID = {$F_ID}
							AND {$tr_minute} + 1440 + 120 BETWEEN WS.shift_start AND WS.shift_end
							AND CURDATE() BETWEEN WS.valid_from AND IFNULL(WS.valid_to, CURDATE())
					";
					$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
					if( $row = mysqli_fetch_array($res) ) {
						$tr_minute = $tr_minute + 1440;
						$ts_date = $row["ts_date"];
						$shift_num = $row["shift_num"];
					}
					else{
						// Не найдена подходящая смена (нерабочее время).
						exit ('<meta http-equiv="refresh" content="0; url=/time_tracking/?id='.$USR_ID.'&error=2">');
					}
				}


				// Проверяем была ли эта смена уже реализована
				$query = "
					SELECT TSS.TSS_ID
					FROM Timesheet TS
					JOIN TimesheetShift TSS ON TSS.TS_ID = TS.TS_ID
						AND TSS.shift_num = {$shift_num}
						AND TSS.duration IS NOT NULL
					WHERE TS.ts_date = '{$ts_date}'
				";
				$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
				if( $row = mysqli_fetch_array($res) ) {
					// Попытка открытия реализованной смены
					exit ('<meta http-equiv="refresh" content="0; url=/time_tracking/?id='.$USR_ID.'&error=3">');
				}
			}
			// Закрытие смены
			else {
				// Узнаем дату и номер смены, которую хотим закрыть
				$query = "
					SELECT TS.ts_date
						,TSS.shift_num
						,TIMESTAMPDIFF(MINUTE, TS.ts_date, CURDATE()) minute_shift
					FROM TimeReg TR
					JOIN TimesheetShift TSS ON TSS.TSS_ID = TR.TSS_ID
						AND TSS.duration IS NULL
					JOIN Timesheet TS ON TS.TS_ID = TSS.TS_ID
						AND TS.F_ID = {$F_ID}
						AND TS.USR_ID = {$USR_ID}
					WHERE TR.del_time IS NULL
				";
				$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
				$row = mysqli_fetch_array($res);
				$ts_date = $row["ts_date"];
				$shift_num = $row["shift_num"];
				$tr_minute = $tr_minute + $row["minute_shift"];

				// Продолжительность рабочего периода
				$duration = $tr_minute - $shift_start;
			}

			// Новая строка в табеле, если еще не было
			$query = "
				INSERT INTO TariffMonth
				SET year = YEAR(CURDATE())
					,month = MONTH(CURDATE())
					,USR_ID = {$USR_ID}
					,F_ID = {$F_ID}
				ON DUPLICATE KEY UPDATE
					F_ID = F_ID
			";
			mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

			// Убираем возможные пустые записи из табеля
			$query = "
				DELETE TM
				FROM TariffMonth AS TM
				WHERE TM.year = YEAR(CURDATE())
					AND TM.month = MONTH(CURDATE())
					AND TM.USR_ID = {$USR_ID}
					AND TM.F_ID NOT IN({$F_ID})
					AND (
						SELECT IFNULL(SUM(1), 0)
						FROM Timesheet TS
						WHERE TS.TM_ID = TM.TM_ID
					) = 0
			";
			mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

			// Добавляем запись в табель
			$query = "
				INSERT INTO Timesheet
				SET ts_date = '{$ts_date}'
					,USR_ID = {$USR_ID}
					,F_ID = {$F_ID}
				ON DUPLICATE KEY UPDATE
					TS_ID = LAST_INSERT_ID(TS_ID)
			";
			mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			$TS_ID = mysqli_insert_id( $mysqli );

			// Добавляем запись номера смены
			$query = "
				INSERT INTO TimesheetShift
				SET TS_ID = {$TS_ID}
					,shift_num = {$shift_num}
				ON DUPLICATE KEY UPDATE
					TSS_ID = LAST_INSERT_ID(TSS_ID)
			";
			mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			$TSS_ID = mysqli_insert_id( $mysqli );

			// Добавляем регистрацию работника
			$query = "
				INSERT INTO TimeReg
				SET TSS_ID = {$TSS_ID}
					,prefix = {$prefix}
					,cardcode = '{$cardcode}'
					,tr_minute = {$tr_minute}
			";
			mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			$TR_ID = mysqli_insert_id( $mysqli );

			if( isset($duration) ) {
				exit ('<meta http-equiv="refresh" content="0; url=/time_tracking/?id='.$USR_ID.'&tr_id='.$TR_ID.'&duration='.$duration.'">');
			}
			else {
				exit ('<meta http-equiv="refresh" content="0; url=/time_tracking/?id='.$USR_ID.'&tr_id='.$TR_ID.'">');
			}
		}
		elseif( $prefix == 1 ) {
			// Попытка открытия смены, когда уже есть открытая
			exit ('<meta http-equiv="refresh" content="0; url=/time_tracking/?id='.$USR_ID.'&error=1">');
		}
		else {
			// Попытка закрытия смены, когда нет открытой
			exit ('<meta http-equiv="refresh" content="0; url=/time_tracking/?id='.$USR_ID.'&error=0">');
		}
	}
	// Не верифицирован
	{
		exit ('<meta http-equiv="refresh" content="0; url=/time_tracking/?id=0">');
	}
}

?>

<!DOCTYPE html>
<html lang="ru">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<title>Учет рабочего времени</title>
		<script src="https://kit.fontawesome.com/020f21ae61.js" crossorigin="anonymous"></script>
		<script src="../js/jquery-1.11.3.min.js"></script>
		<script src="../js/ui/jquery-ui.js"></script>
	</head>
	<body>
		<style>
			body {
				background: #333333 url(../js/ui/images/ui-bg_diagonals-thick_8_333333_40x40.png) 50% 50% repeat !important;
				font-family: Arial, sans-serif;
				color: #333;
			}

			input, a {
				color: #333;
			}

			a {
				text-decoration: none;
				padding: 0 5px;
			}

			form {
				width: 390px;
/*				margin: 50px auto;*/
				margin: 20px auto;
				background: #fdce46bf;
/*				padding: 35px 25px;*/
				padding: 20px;
				text-align: center;
				box-shadow: 0px 5px 5px -0px rgba(0, 0, 0, 0.3);
				border-radius: 5px;
				position: relative;
			}

			.title {
				font-size: 1.5em;
			}
		</style>

		<form method="post" id="target" style="display: none;" onsubmit="JavaScript:this.subbut.disabled=true;">
			<input type="hidden" name="cardcode">
			<input type="submit" name="subbut">
		</form>

		<?
		if( isset($_GET["id"]) ) {
			?>
			<div id="shift_info" style="width: 100%; position: fixed;">
				<form method="post">
					<?
						if( $_GET["id"] > 0 ) {
							// Узнаем имя работника
							$query = "
								SELECT USR_Name({$_GET["id"]}) `name`
							";
							$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
							$row = mysqli_fetch_array($res);
							$name = $row["name"];

							echo "<h1>{$name}</h1>";

							// Если есть ошибки
							if( isset($_GET["error"]) ) {
								if( $_GET["error"] == '0' ) {
									// Закрыть закрытую
									echo "<p class='title' style='color: #911;'>ОШИБКА!</p>";
									echo "<p class='title' style='color: #911;'>Вы пытаетесь закрыть неоткрытую смену.</p>";
								}
								elseif( $_GET["error"] == '1' ) {
									// Открыть открытую
									echo "<p class='title' style='color: #911;'>ОШИБКА!</p>";
									echo "<p class='title' style='color: #911;'>Вы пытаетесь открыть уже окрытую смену.</p>";
								}
								elseif( $_GET["error"] == '2' ) {
									// Нерабочее время
									echo "<p class='title' style='color: #911;'>Нерабочее время!</p>";
									echo "<p class='title' style='color: #911;'>Регистрация возможна за 2 часа до начала смены.</p>";
								}
								elseif( $_GET["error"] == '3' ) {
									// Попытка открыть реализованную смену
									echo "<p class='title' style='color: #911;'>Текущая смена уже закрыта. Повторное открытие невозможно.</p>";
									echo "<p class='title' style='color: #911;'>Чтобы открыть следующую смену, дождитесь ее начала.</p>";
								}
							}
							else {
								// Фотографирование
								?>
								<div id="my_camera" style="margin: auto;"></div>
								<div id="results" style="width: 320px; height: 240px; margin: auto; display: none;"></div>

								<!--https://makitweb.com/how-to-capture-picture-from-webcam-with-webcam-js/-->
								<script src="../js/webcam.min.js"></script>

								<script>
									// Configure a few settings and attach camera
									Webcam.set({
										width: 320,
										height: 240,
										image_format: 'jpeg',
										jpeg_quality: 70
									});
									Webcam.attach( '#my_camera' );

									Webcam.on( 'load', function() {
										// Обратный отсчет
										//timerDecrement();

										setTimeout(function(){
											// preload shutter audio clip
											var shutter = new Audio();
											shutter.autoplay = false;
											shutter.src = navigator.userAgent.match(/Firefox/) ? 'shutter.ogg' : 'shutter.mp3';

											// play sound effect
											shutter.play();

											$('#my_camera').hide();
											$('#results').show();

											// take snapshot and get image data
											Webcam.snap( function(data_uri) {
												// display results in page
												$('#results').html('<img id="imageprev" src="'+data_uri+'"/>');
											});

											// Get base64 value from <img id='imageprev'> source
											var base64image = document.getElementById("imageprev").src;


											Webcam.upload( base64image, 'upload.php?tr_id=<?=$_GET["tr_id"]?>', function(code, text) {
												console.log('Save successfully');
												console.log(text);
											});

											Webcam.reset();

											// Автоматический возврат на главный экран после фотографирования
											setTimeout(function(){
												$(location).attr('href', "/time_tracking");
											}, 500);
										}, 500);
									});
								</script>

								<?
								if( isset($_GET["duration"]) ) {
									if( $_GET["duration"] < 120 ) {
										echo "<p class='title' style='color: #911;'>Продолжительность смены составила менее 2-х часов.</p>";
										echo "<p class='title' style='color: #911;'>Смена не засчитана.</p>";
									}
								}
							}
						}
						else {
							echo "<p class='title' style='color: #911;'>Карта не действительна!</p>";
						}
					?>
					<script>
						// Автоматический возврат на главный экран по истечению времени
						setTimeout(function(){
							$(location).attr('href', "/time_tracking");
						}, 5000);
					</script>
				</form>
			</div>
			<?
		}
		else {
			?>
			<script>
				$(function() {
					// Считывание карточки
					var cardcode="";
					$(document).keydown(function(e)
					{
						var code = (e.keyCode ? e.keyCode : e.which);
						if (code==0) cardcode="";
						if( code==13 || code==9 )// Enter key hit. Tab key hit.
						{
							console.log(cardcode);
							if( cardcode.length == 11 ) {
								$("input[name=cardcode]").val(cardcode);
								$( "#target input[name=subbut]" ).click();
								cardcode="";
								return false;
							}
							cardcode="";
						}
						else
						{
							if (code >= 48 && code <= 57) {
								cardcode=cardcode+String.fromCharCode(code);
							}
						}
					});
				});
			</script>
			<div style="display: flex; flex-direction: row; flex-wrap: wrap; padding: 5px; margin: 5px;">
			<?
			// Получаем список активных смен
			$query = "
				SELECT TS.ts_date
					,DATE_FORMAT(TS.ts_date, '%d.%m.%Y') ts_date_format
					,TSS.shift_num
					,CONCAT(LPAD((WS.shift_start DIV 60) % 24, 2, '0'), ':', LPAD(WS.shift_start % 60, 2, '0')) shift_start
					,CONCAT(LPAD((WS.shift_end DIV 60) % 24, 2, '0'), ':', LPAD(WS.shift_end % 60, 2, '0')) shift_end
					,CONCAT(LPAD(((WS.shift_end + 120) DIV 60) % 24, 2, '0'), ':', LPAD((WS.shift_end + 120) % 60, 2, '0')) deadline
				FROM TimeReg TR
				JOIN TimesheetShift TSS ON TSS.TSS_ID = TR.TSS_ID
					AND TSS.duration IS NULL
				JOIN Timesheet TS ON TS.TS_ID = TSS.TS_ID
					AND TS.F_ID = {$F_ID}
				JOIN WorkingShift WS ON WS.F_ID = {$F_ID}
					AND WS.shift_num = TSS.shift_num
					AND TS.ts_date BETWEEN WS.valid_from AND IFNULL(WS.valid_to, CURDATE())
				WHERE TR.del_time IS NULL
				GROUP BY TS.ts_date, TSS.shift_num
				ORDER BY TS.ts_date, TSS.shift_num
			";
			$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			while( $row = mysqli_fetch_array($res) ) {
				echo "<fieldset style='width: 100%; display: flex; flex-direction: row; flex-wrap: wrap; padding: 5px; margin: 0px;'>";
				echo "<legend style='color: #fff; font-size: 1.5em;'><b>{$row["shift_num"]}</b> смена ({$row["shift_start"]} - {$row["shift_end"]}). <font color='red'>Закрыть до {$row["deadline"]}, иначе не будет засчитана.</font></legend>";

				// Выводим список работников на смене
				$query = "
					SELECT USR_Name(TS.USR_ID) `name`
						,TR.tr_photo
						,CONCAT(LPAD((TR.tr_minute DIV 60) % 24, 2, '0'), ':', LPAD(TR.tr_minute % 60, 2, '0')) tr_time
					FROM TimeReg TR
					JOIN TimesheetShift TSS ON TSS.TSS_ID = TR.TSS_ID
						AND TSS.duration IS NULL
						AND TSS.shift_num = {$row["shift_num"]}
					JOIN Timesheet TS ON TS.TS_ID = TSS.TS_ID
						AND TS.F_ID = {$F_ID}
						AND TS.ts_date = '{$row["ts_date"]}'
					WHERE TR.del_time IS NULL
					ORDER BY `name`
				";
				$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
				while( $subrow = mysqli_fetch_array($subres) ) {
					?>
					<div style="display: flex; width: 160px; height: 120px; font-size: 1.0em; background-color: #fdce46bf; background-image: url(/time_tracking/upload/<?=$subrow["tr_photo"]?>); background-size: contain; box-shadow: 0px 5px 5px 0px rgb(0 0 0 / 30%); border-radius: 5px; margin: 10px; overflow: hidden; position: relative;">
	<!--					<div style=" width: 20px; height: 20px; display: inline-block; margin: 15px; border-radius: 50%; background: green; box-shadow: 0 0 3px 3px green; position: absolute;"></div>-->
						<span style="-webkit-filter: drop-shadow(0px 0px 2px #000); filter: drop-shadow(0px 0px 2px #000); color: #fff; position: absolute; top: 10px; right: 10px;"><?=$subrow["tr_time"]?></span>
						<span style="align-self: flex-end; margin: 10px; -webkit-filter: drop-shadow(0px 0px 2px #000); filter: drop-shadow(0px 0px 2px #000); color: #fff;"><?=$subrow["name"]?></span>
					</div>
					<?
				}
				echo "</fieldset>";
			}

			?>
			</div>
			<div>
				<form>
					<h2>Приложите карту к считывателю</h2>
				</form>
			</div>
			<?
		}
		?>
	</body>
</html>
