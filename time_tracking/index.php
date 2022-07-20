<?
include_once "../config.php";
$ip = $_SERVER['REMOTE_ADDR'];
//if( $ip != $from_ip ) die("Access denied");

if( isset($_POST["cardcode"]) ) {
	// Верифицируем работника
	$query = "
		SELECT USR.USR_ID
		FROM Users USR
		WHERE USR.cardcode LIKE '{$_POST["cardcode"]}'
	";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$row = mysqli_fetch_array($res);
	$USR_ID = $row["USR_ID"];
	// Если работник верифицирован
	if( $USR_ID ) {
		// Узнаем сколько секунд прошло с последнего считывания
		$query = "
			SELECT TIMESTAMPDIFF(SECOND, IFNULL(TT.stop, TT.start), NOW()) seconds
			FROM TimeTracking TT
			WHERE TT.USR_ID = {$USR_ID}
			ORDER BY TT.TT_ID DESC
			LIMIT 1
		";
		$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		$row = mysqli_fetch_array($res);
		$seconds = ($row["seconds"] > 0 ? $row["seconds"] : 100); // Если считывание первое

		// Если прошла минута с последнего считывания
		if( $seconds > 60 ) {

			// Узнаем есть ли начатая смена
			$query = "
				SELECT TT.TT_ID
				FROM TimeTracking TT
				WHERE TT.stop IS NULL
					AND TT.USR_ID = {$USR_ID}
				ORDER BY TT.TT_ID DESC
				LIMIT 1
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
//			setTimeout(function(){
//				$(location).attr('href', "/time_tracking");
//			}, 60000);

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
						if( cardcode.length == 10 ) {
							$("input[name=cardcode]").val(cardcode);
							$( "#target" ).submit();
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

		<form method="post" id="target" style="display: none;">
			<input type="hidden" name="cardcode">
		</form>

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
							echo "<p class='title' style='color: #911;'>Карта не действительна!</p>";
						}
					?>
					<script>
						// Автоматический возврат на главный экран после авторизации
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
			<div style="display: flex; flex-direction: row; flex-wrap: wrap; padding: 5px; margin: 5px;">
			<?
			// Выводим список открытых сегодня смен
			$query = "
				SELECT USR_Name(TT.USR_ID) `name`
					,TT.photo_start
				FROM TimeTracking TT
				WHERE TT.start >= CURDATE()
					AND TT.stop IS NULL
				ORDER BY `name`
			";
			$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			while( $row = mysqli_fetch_array($res) ) {
				?>
				<div style="display: flex; width: 160px; height: 120px; font-size: 1.0em; background-color: #fdce46bf; background-image: url(/time_tracking/upload/<?=$row["photo_start"]?>); background-size: contain; box-shadow: 0px 5px 5px 0px rgb(0 0 0 / 30%); border-radius: 5px; margin: 10px; overflow: hidden;">
					<div style=" width: 20px; height: 20px; display: inline-block; margin: 15px; border-radius: 50%; background: green; box-shadow: 0 0 3px 3px green; position: absolute;"></div>
					<span style="align-self: flex-end; margin: 10px; -webkit-filter: drop-shadow(0px 0px 2px #000); filter: drop-shadow(0px 0px 2px #000); color: #fff;"><?=$row["name"]?></span>
				</div>
				<?
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
