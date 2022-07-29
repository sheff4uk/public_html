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

//if( !$F_ID ) die("Access denied");

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
		// Добавляем запись в табель
		$query = "
			INSERT INTO Timesheet
			SET ts_date = CURDATE()
				,USR_ID = {$USR_ID}
				,F_ID = {$F_ID}
				,T_ID = (
					SELECT T_ID
					FROM Tariff
					WHERE USR_ID = {$USR_ID}
						AND from_date < CURDATE()
					ORDER BY from_date DESC, T_ID ASC
					LIMIT 1
				)
			ON DUPLICATE KEY UPDATE
				T_ID = (
					SELECT T_ID
					FROM Tariff
					WHERE USR_ID = {$USR_ID}
						AND from_date < CURDATE()
					ORDER BY from_date DESC, T_ID ASC
					LIMIT 1
				)
		";
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

		// Узнаем TS_ID
		$query = "
			SELECT TS_ID
			FROM Timesheet
			WHERE ts_date = CURDATE()
				AND USR_ID = {$USR_ID}
				AND F_ID = {$F_ID}
		";
		$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		$row = mysqli_fetch_array($res);
		$TS_ID = $row["TS_ID"];

		// Добавляем регистрацию работника
		$query = "
			INSERT INTO TimeReg
			SET TS_ID = {$TS_ID}
				,tr_time = TIME(NOW())
		";
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		$TR_ID = mysqli_insert_id( $mysqli );

		exit ('<meta http-equiv="refresh" content="0; url=/time_tracking/?id='.$USR_ID.'&tr_id='.$TR_ID.'">');
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
							// Узнаем имя работника
							$query = "
								SELECT USR_Name({$_GET["id"]}) `name`
							";
							$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
							$row = mysqli_fetch_array($res);
							$name = $row["name"];

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
									jpeg_quality: 90
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
										}, 1000);
									}, 1000);
								});
							</script>

							<?
							echo "<h1>{$name}</h1>";
						}
						else {
							echo "<p class='title' style='color: #911;'>Карта не действительна!</p>";
						}
					?>
					<script>
						// Автоматический возврат на главный экран после авторизации
						setTimeout(function(){
							$(location).attr('href', "/time_tracking");
						}, 10000);
					</script>
				</form>
			</div>
			<?
		}
		else {
			?>
			<div style="display: flex; flex-direction: row; flex-wrap: wrap; padding: 5px; margin: 5px;">
			<?
			// Выводим список зарегистрированных сегодня работников
			$query = "
				SELECT USR_Name(TS.USR_ID) `name`
					,TR.tr_photo
					,TR.tr_time
				FROM TimeReg TR
				JOIN Timesheet TS ON TS.TS_ID = TR.TS_ID AND TS.ts_date = CURDATE()
				ORDER BY `name`, TR.tr_time
			";
			$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			while( $row = mysqli_fetch_array($res) ) {
				?>
				<div style="display: flex; width: 160px; height: 120px; font-size: 1.0em; background-color: #fdce46bf; background-image: url(/time_tracking/upload/<?=$row["tr_photo"]?>); background-size: contain; box-shadow: 0px 5px 5px 0px rgb(0 0 0 / 30%); border-radius: 5px; margin: 10px; overflow: hidden; position: relative;">
<!--					<div style=" width: 20px; height: 20px; display: inline-block; margin: 15px; border-radius: 50%; background: green; box-shadow: 0 0 3px 3px green; position: absolute;"></div>-->
					<span style="-webkit-filter: drop-shadow(0px 0px 2px #000); filter: drop-shadow(0px 0px 2px #000); color: #fff; position: absolute; top: 10px; right: 10px;"><?=$row["tr_time"]?></span>
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
