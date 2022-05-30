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
								SELECT IF(TT.stop IS NULL, 0, 1) `status`
									,TIMESTAMPDIFF(MINUTE, TT.start, TT.stop) `interval`
								FROM TimeTracking TT
								WHERE TT.USR_ID = {$_GET["id"]}
								ORDER BY TT.TT_ID DESC
							";
							$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
							$row = mysqli_fetch_array($res);
							$status = $row["status"];
							$interval = $row["interval"];
							$hours = intdiv($interval, 60);
							$minutes = fmod($interval, 60);
							if( $status ) {
								echo "<p class='title'>Рабочая смена завершена</p>";
								echo "<p>Продолжительность: <b>{$hours}</b> ч. <b>{$minutes}</b> мин.</p>";
								echo "<i class='fas fa-door-closed fa-8x'></i>";
							}
							else {
								echo "<p class='title'>Рабочая смена начата</p>";
								echo "<i class='fas fa-door-open fa-8x'></i>";
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
					<input type="button" value="1" id="1" class="pinButton calc"/>
					<input type="button" value="2" id="2" class="pinButton calc"/>
					<input type="button" value="3" id="3" class="pinButton calc"/><br>
					<input type="button" value="4" id="4" class="pinButton calc"/>
					<input type="button" value="5" id="5" class="pinButton calc"/>
					<input type="button" value="6" id="6" class="pinButton calc"/><br>
					<input type="button" value="7" id="7" class="pinButton calc"/>
					<input type="button" value="8" id="8" class="pinButton calc"/>
					<input type="button" value="9" id="9" class="pinButton calc"/><br>
					<input type="button" value="CLEAR" class="pinButton clear"/>
					<input type="button" value="0" id="0 " class="pinButton calc"/>
					<input type="button" value="ENTER" id="enter_id" class="pinButton enter"/>
				</form>
			</div>

			<div id="pinpad_pin" style="width: 100%; position: fixed; display: none;">
				<form method="post">
					<input type="button" value="❮ назад" class="back btn"/>
					<p class="title"><b>2</b> Введите пин-код</p>
					<input type="hidden" name="id">
					<input type="password" id="password" name="pin" class="input_value" placeholder="PIN"/><br>
					<input type="button" value="1" id="1" class="pinButton calc"/>
					<input type="button" value="2" id="2" class="pinButton calc"/>
					<input type="button" value="3" id="3" class="pinButton calc"/><br>
					<input type="button" value="4" id="4" class="pinButton calc"/>
					<input type="button" value="5" id="5" class="pinButton calc"/>
					<input type="button" value="6" id="6" class="pinButton calc"/><br>
					<input type="button" value="7" id="7" class="pinButton calc"/>
					<input type="button" value="8" id="8" class="pinButton calc"/>
					<input type="button" value="9" id="9" class="pinButton calc"/><br>
					<input type="button" value="CLEAR" class="pinButton clear"/>
					<input type="button" value="0" id="0 " class="pinButton calc"/>
					<input type="button" value="ENTER" id="enter_pin" class="pinButton enter"/>
				</form>
			</div>
			<?
		}
		?>
	</body>
</html>
