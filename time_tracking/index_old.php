<?
include_once "../config.php";
$ip = $_SERVER['REMOTE_ADDR'];
//if( $ip != $from_ip ) die("Access denied");

if( isset($_POST["id"]) ) {
	// Верифицируем работника
	$query = "
		SELECT USR.USR_ID
		FROM Users USR
		WHERE USR.USR_ID = {$_POST["id"]}
			AND USR.PIN LIKE '{$_POST["pin"]}'
	";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$row = mysqli_fetch_array($res);
	$USR_ID = $row["USR_ID"];
	// Если работник верифицирован
	if( $USR_ID ) {
		// Узнаем есть ли начатая смена
		$query = "
			SELECT TT.TT_ID
			FROM TimeTracking TT
			WHERE TT.stop IS NULL
				AND TT.USR_ID = {$USR_ID}
			ORDER BY TT.TT_ID DESC
		";
		$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		$row = mysqli_fetch_array($res);
		$TT_ID = $row["TT_ID"];

		// Если смена начата, завершаем её
		if( $TT_ID ) {
			$query = "
				UPDATE TimeTracking
				SET stop = NOW()
				WHERE TT_ID = {$TT_ID}
			";
			mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		}
		// Иначе начинаем новую смену
		else {
			$query = "
				INSERT INTO TimeTracking
				SET USR_ID = {$USR_ID}
					,start = NOW()
			";
			mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		}

		exit ('<meta http-equiv="refresh" content="0; url=/time_tracking/?id='.$USR_ID.'">');
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
		<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.3.1/css/all.css" integrity="sha384-mzrmE5qonljUremFsqc01SB46JvROS7bZs3IO2EmfFsd15uHvIt+Y8vEf7N7fWAU" crossorigin="anonymous">
		<script src="../js/jquery-1.11.3.min.js"></script>
		<script src="../js/ui/jquery-ui.js"></script>
		<script>
			$(function() {
				//disable input from typing
				$("#user_id").keypress(function () {
					return false;
				});
				$("#password").keypress(function () {
					return false;
				});

				//add password
				$(".calc").click(function () {
					let value = $(this).val(),
						input_value = $(this).parent('form').children('.input_value');
					field(value, input_value);
				});

				function field(value, input_value) {
					input_value.val(input_value.val() + value);
				}

				$(".clear").click(function () {
					$(this).parent('form').children('.input_value').val("");
				});

				$("#enter_id").click(function () {
					if( $("#user_id").val() != "" ) {
						$('#pinpad_id').hide( "drop", "slow" );
						$('#pinpad_pin').show( "fade", "slow" );
						$('#pinpad_pin input[type=hidden]').val($('#user_id').val());
					}
					else {
						$("#user_id").effect( 'highlight', {color: 'red'}, 1000 );
					}
				});

				$("#enter_pin").click(function () {
					if( $("#password").val() != "" ) {
						//$('#pinpad_pin').hide( "drop", "slow" );
						$('#pinpad_pin form').submit();
					}
					else {
						$("#password").effect( 'highlight', {color: 'red'}, 1000 );
					}
				});

				$('.back').click(function() {
					$('#pinpad_pin').hide( "fade", "slow" );
					$('#pinpad_id').show( "drop", "slow" );
				});
			});
		</script>
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
			  margin: 50px auto;
			  background: #fdce46bf;
			  padding: 35px 25px;
			  text-align: center;
			  box-shadow: 0px 5px 5px -0px rgba(0, 0, 0, 0.3);
			  border-radius: 5px;
				position: relative;
			}

			.title {
			  font-size: 1.5em;
			}

			#user_id,
			#password{
			  border-radius: 5px;
			  width: 350px;
			  margin: auto;
			  border: 1px solid rgb(228, 220, 220);
			  outline: none;
			  font-size: 60px;
			  text-align: center;
			}

			input:focus {
			  outline: none;
			}

			.pinButton {
			  border: none;
			  background: none;
			  font-size: 1.5em;
			  border-radius: 50%;
			  height: 60px;
			  font-weight: 550;
			  width: 60px;
			  margin: 7px 20px;
			}

			.clear,
			.enter {
			  font-size: 1em !important;
			}

			.pinButton:hover {
			  box-shadow: #fdce46 0 0 2px 2px;
			}
			.pinButton:active {
			  background: #fdce46;
			  color: #fff;
			}

			.clear:hover {
			  box-shadow: #ff3c41 0 0 2px 2px;
			}

			.clear:active {
			  background: #ff3c41;
			  color: #fff;
			}

			.enter:hover {
			  box-shadow: #47cf73 0 0 2px 2px;
			}

			.enter:active {
			  background: #47cf73;
			  color: #fff;
			}

			.back {
				position: absolute;
				left: 0px;
				top: -50px
			}

			.btn {
				background-color: #fff;
			  border: none;
			  font-size: 1.5em;
			  border-radius: 5px;
			  height: 40px;
			  font-weight: 550;
			}

			.btn:hover {
				box-shadow: #fdce46 0 0 2px 2px;
			}

			.btn:active {
			  background: #fdce46;
			  color: #fff;
			}
		</style>

		<?
		if( isset($_GET["id"]) ) {
			?>
			<div id="shift_info" style="width: 100%; position: fixed;">
				<form method="post">
					<?
						if( $_GET["id"] > 0 ) {
							// Узнаем статус смены
							$query = "
								SELECT TT.TT_ID
									,IF(TT.stop IS NULL, 0, 1) `status`
									,TIMESTAMPDIFF(MINUTE, TT.start, TT.stop) `interval`
									,USR_Name({$_GET["id"]}) `name`
								FROM TimeTracking TT
								WHERE TT.USR_ID = {$_GET["id"]}
								ORDER BY TT.TT_ID DESC
							";
							$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
							$row = mysqli_fetch_array($res);
							$TT_ID = $row["TT_ID"];
							$status = $row["status"];
							$interval = $row["interval"];
							$hours = intdiv($interval, 60);
							$minutes = fmod($interval, 60);
							$name = $row["name"];
							?>
							<div id="my_camera" style="display: none;"></div>
							<div id="results" style="width: 320px; height: 240px; margin: auto;"></div>

							<!--https://makitweb.com/how-to-capture-picture-from-webcam-with-webcam-js/-->
							<script src="../js/webcam.min.js"></script>

							<script>
								// Configure a few settings and attach camera
								Webcam.set({
									width: 320,
									height: 240,
									image_format: 'jpeg',
									jpeg_quality: 90
								});
								Webcam.attach( '#my_camera' );

								Webcam.on( 'load', function() {
									// preload shutter audio clip
									var shutter = new Audio();
									shutter.autoplay = false;
									shutter.src = navigator.userAgent.match(/Firefox/) ? 'shutter.ogg' : 'shutter.mp3';

									// play sound effect
									shutter.play();

									// take snapshot and get image data
									Webcam.snap( function(data_uri) {
										// display results in page
										document.getElementById('results').innerHTML =
											'<img id="imageprev" src="'+data_uri+'"/>';
									});

									Webcam.reset();

									// Get base64 value from <img id='imageprev'> source
									var base64image = document.getElementById("imageprev").src;

									Webcam.upload( base64image, 'upload.php?tt_id=<?=$TT_ID?>&status=<?=$status?>', function(code, text) {
										console.log('Save successfully');
										console.log(text);
									});
								});

								setTimeout(function(){
									$(location).attr('href', "/time_tracking");
								}, 15000);
							</script>

							<?
							echo "<h1>{$name}</h1>";
							if( $status ) {
								echo "<p class='title'>Рабочая смена завершена</p>";
								echo "<p>Продолжительность: <b>{$hours}</b> ч. <b>{$minutes}</b> мин.</p>";
								echo "<i class='fas fa-door-closed fa-4x'></i>";
							}
							else {
								echo "<p class='title'>Рабочая смена начата</p>";
								echo "<i class='fas fa-door-open fa-4x'></i>";
							}
						}
						else {
							echo "<p class='title'>Не верные данные!</p>";
						}
						echo "<p><a href='/time_tracking/' class='btn' style='padding: 5px 20px;'>На главную</a></p>";
					?>
				</form>
			</div>
			<?
		}
		else {
			?>
			<div id="pinpad_id" style="width: 100%; position: fixed;">
				<form >
					<p class="title"><b>1</b> Введите Ваш ID</p>
					<input type="text" id="user_id" class="input_value" placeholder="ID"/><br>
					<input type="button" value="1" class="pinButton calc"/>
					<input type="button" value="2" class="pinButton calc"/>
					<input type="button" value="3" class="pinButton calc"/><br>
					<input type="button" value="4" class="pinButton calc"/>
					<input type="button" value="5" class="pinButton calc"/>
					<input type="button" value="6" class="pinButton calc"/><br>
					<input type="button" value="7" class="pinButton calc"/>
					<input type="button" value="8" class="pinButton calc"/>
					<input type="button" value="9" class="pinButton calc"/><br>
					<input type="button" value="CLEAR" class="pinButton clear"/>
					<input type="button" value="0" class="pinButton calc"/>
					<input type="button" value="ENTER" id="enter_id" class="pinButton enter"/>
				</form>
			</div>

			<div id="pinpad_pin" style="width: 100%; position: fixed; display: none;">
				<form method="post">
					<input type="button" value="❮ назад" class="back btn"/>
					<p class="title"><b>2</b> Введите пин-код</p>
					<input type="hidden" name="id">
					<input type="password" id="password" name="pin" class="input_value" placeholder="PIN"/><br>
					<input type="button" value="1" class="pinButton calc"/>
					<input type="button" value="2" class="pinButton calc"/>
					<input type="button" value="3" class="pinButton calc"/><br>
					<input type="button" value="4" class="pinButton calc"/>
					<input type="button" value="5" class="pinButton calc"/>
					<input type="button" value="6" class="pinButton calc"/><br>
					<input type="button" value="7" class="pinButton calc"/>
					<input type="button" value="8" class="pinButton calc"/>
					<input type="button" value="9" class="pinButton calc"/><br>
					<input type="button" value="CLEAR" class="pinButton clear"/>
					<input type="button" value="0" class="pinButton calc"/>
					<input type="button" value="ENTER" id="enter_pin" class="pinButton enter"/>
				</form>
			</div>
			<?
		}
		?>
	</body>
</html>
