<?
include_once "../config.php";
$ip = $_SERVER['REMOTE_ADDR'];
//if( $ip != $from_ip ) die("Access denied");

?>

<!DOCTYPE html>
<html lang="ru">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<title>Журнал учета рабочего времени</title>
		<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.3.1/css/all.css" integrity="sha384-mzrmE5qonljUremFsqc01SB46JvROS7bZs3IO2EmfFsd15uHvIt+Y8vEf7N7fWAU" crossorigin="anonymous">
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
				width: 790px;
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

			.time {
				align-self: flex-end;
				margin:5px;
				-webkit-filter: drop-shadow(0px 0px 2px #000);
				filter: drop-shadow(0px 0px 2px #000);
			}
		</style>

		<div>
			<form>
				<h1>Журнал учета рабочего времени</h1>
				<?
				$query = "
					SELECT USR_Name(TT.USR_ID) Worker
						,DATE_FORMAT(TT.start, '%d.%m.%Y') `date`
						,DATE_FORMAT(TT.start, '%H:%i') `start_time`
						,DATE_FORMAT(TT.stop, '%H:%i') `stop_time`
						,TT.photo_start
						,TT.photo_stop
						#,TIMESTAMPDIFF(MINUTE, TT.start, TT.stop) duration
						#,TIMESTAMPDIFF(MINUTE, IF(TT.USR_ID = 30, TT.start, TIMESTAMP(DATE(TT.start), '08:00:00')), TT.stop) duration
						,GREATEST(TIMESTAMPDIFF(MINUTE, GREATEST(TIMESTAMP(DATE(TT.start), '08:00:00'), TT.start), LEAST(TIMESTAMP(DATE(TT.start), '12:00:00'), TT.stop)), 0) + GREATEST(TIMESTAMPDIFF(MINUTE, GREATEST(TIMESTAMP(DATE(TT.start), '13:00:00'), TT.start), TT.stop), 0) duration
						,USR.RL_ID
					FROM TimeTracking TT
					JOIN Users USR ON USR.USR_ID = TT.USR_ID
					#ORDER BY TT.TT_ID DESC
					ORDER BY DATE(TT.start) DESC, Worker, TT.start DESC
					LIMIT 200
				";
				$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
				while( $row = mysqli_fetch_array($res) ) {
					if( $date != $row["date"] ) {
						$date = $row["date"];
						echo "<h3>{$date}</h3>";
					}
					echo "<div style='display:flex; flex-direction: row; justify-content: space-around; padding: 5px; margin: 5px; border: 1px dotted;'>";
					echo "<div style='display:flex; width: 300px; height: 60px; font-size: 1.6em;'><span style='align-self: center'>{$row["Worker"]}</span></div>";
					echo "<div style='display:flex; width: 80px; height: 60px; color: #fff; background-image: url(/time_tracking/upload/{$row["photo_start"]}); background-size: contain;'><span class='time'>{$row["start_time"]}</span></div>";
					echo "<div style='display:flex; width: 80px; height: 60px; color: #fff; background-image: url(/time_tracking/upload/{$row["photo_stop"]}); background-size: contain;'><span class='time'>{$row["stop_time"]}</span></div>";

//					$duration = ($row["duration"] < 60 ? 0 : $row["duration"] - 60);
//					$duration_div = intdiv(round($duration / 15), 4);
//					$duration_mod = round($duration / 15) % 4;
//					echo "<div style='display:flex; width: 160px; height: 60px; justify-content: flex-end;'><span style='align-self: center;'>".($row["stop_time"] ? "<b style='color:green; font-size: 3em;'>".($duration_div > 0 ? $duration_div : ($duration_mod == 0 ? "0" : "")).($duration_mod == 1 ? "&frac14;" : ($duration_mod == 2 ? "&frac12;" : ($duration_mod == 3 ? "&frac34;" : "")))."</b> <span style='font-size:1.5em;'>ч<span>" : "")."</span></div>";

					$duration = $row["duration"];
					$duration = ceil($duration / 15) * 15; //Продолжительность с шагом в 15 минут в пользу работника
					$duration_hrs = intdiv($duration, 60);
					$duration_min = $duration % 60;
					$duration_min = str_pad($duration_min, 2, "0", STR_PAD_LEFT);
					echo "<div style='display:flex; width: 160px; height: 60px; justify-content: flex-end;'><span style='align-self: center;'>".($row["stop_time"] ? "<b style='color:green; font-size: 3em;'>".($row["duration"] == 4 ? "{$duration_hrs}:{$duration_min}" : "Смена")."</b>" : "")."</span></div>";

					echo "</div>";
				}
				?>
			</form>
		</div>
	</body>
</html>
