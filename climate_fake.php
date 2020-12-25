<?
include "config.php";
$title = 'Климат';
include "header.php";

// Если в фильтре не установлена неделя, показываем текущую
if( !$_GET["week"] ) {
	$query = "SELECT YEARWEEK(NOW(), 1) week";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$row = mysqli_fetch_array($res);
	$_GET["week"] = $row["week"];
}

// Узнаем последние показатели
$query = "
	SELECT t1, t2, h1, h2
	FROM Climate
	ORDER BY date_time DESC
	LIMIT 1
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
$row = mysqli_fetch_array($res);
$t1 = $row["t1"];
$t2 = $row["t2"];
$h1 = $row["h1"];
$h2 = $row["h2"];
?>

<form method="get" style="font-size: 1.5em;">
	<span>Неделя:</span>
	<select name="week" class="<?=$_GET["week"] ? "filtered" : ""?>" onchange="this.form.submit()">
		<?
		$query = "
			SELECT YEAR(NOW()) year
			UNION
			SELECT YEAR(date_time) year
			FROM Climate
			ORDER BY year DESC
		";
		$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		while( $row = mysqli_fetch_array($res) ) {
			echo "<optgroup label='{$row["year"]}'>";
			$query = "
				SELECT YEARWEEK(NOW(), 1) week
					,WEEK(NOW(), 1) week_format
					,DATE_FORMAT(adddate(CURDATE(), INTERVAL 0-WEEKDAY(CURDATE()) DAY), '%e %b') WeekStart
					,DATE_FORMAT(adddate(CURDATE(), INTERVAL 6-WEEKDAY(CURDATE()) DAY), '%e %b') WeekEnd
				UNION
				SELECT YEARWEEK(date_time, 1) week
					,WEEK(date_time, 1) week_format
					,DATE_FORMAT(adddate(date_time, INTERVAL 0-WEEKDAY(date_time) DAY), '%e %b') WeekStart
					,DATE_FORMAT(adddate(date_time, INTERVAL 6-WEEKDAY(date_time) DAY), '%e %b') WeekEnd
				FROM Climate
				WHERE YEAR(date_time) = {$row["year"]}
				GROUP BY week
				ORDER BY week DESC
			";
			$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			while( $subrow = mysqli_fetch_array($subres) ) {
				$selected = ($subrow["week"] == $_GET["week"]) ? "selected" : "";
				echo "<option value='{$subrow["week"]}' {$selected}>{$subrow["week_format"]} [{$subrow["WeekStart"]} - {$subrow["WeekEnd"]}]</option>";
			}
			echo "</optgroup>";
		}
		?>
	</select>
</form>

<?
echo "<h2>Последние показания: <span style='color: rgba(255, 100, 50, 1);' title='Участок созревания'>{$t1}&#8451;</span>&nbsp;&nbsp;&nbsp;<span style='color: rgba(255, 200, 0, 1);' title='Участок расформовки'>{$t2}&#8451;</span>&nbsp;&nbsp;&nbsp;<span style='color: rgba(100, 100, 255, 1);' title='Участок созревания'>{$h1}%</span>&nbsp;&nbsp;&nbsp;<span style='color: rgba(200, 0, 255, 1);' title='Участок расформовки'>{$h2}%</span></h2>";

// Узнаем время начала и время окончания
$query = "
	SELECT DATE_FORMAT(adddate(date_time, INTERVAL 0-WEEKDAY(date_time) DAY), '%d.%m.%Y %00:30') `start_format`
		,DATE_FORMAT(adddate(date_time, INTERVAL 6-WEEKDAY(date_time) DAY), '%d.%m.%Y %23:30') `end_format`
		,DATE_FORMAT(adddate(date_time, INTERVAL 0-WEEKDAY(date_time) DAY), '%Y-%m-%d %00:00') `start`
		,DATE_FORMAT(adddate(date_time, INTERVAL 6-WEEKDAY(date_time) DAY), '%Y-%m-%d %23:59') `end`
		,DATEDIFF(adddate(date_time, INTERVAL 0-WEEKDAY(date_time) DAY), CURDATE()) `diff`
	FROM Climate
	WHERE YEARWEEK(date_time, 1) = {$_GET["week"]}
	UNION
	SELECT DATE_FORMAT(adddate(CURDATE(), INTERVAL 0-WEEKDAY(CURDATE()) DAY), '%d.%m.%Y %00:30') `start_format`
		,DATE_FORMAT(adddate(CURDATE(), INTERVAL 6-WEEKDAY(CURDATE()) DAY), '%d.%m.%Y %23:30') `end_format`
		,DATE_FORMAT(adddate(CURDATE(), INTERVAL 0-WEEKDAY(CURDATE()) DAY), '%Y-%m-%d %00:00') `start`
		,DATE_FORMAT(adddate(CURDATE(), INTERVAL 6-WEEKDAY(CURDATE()) DAY), '%Y-%m-%d %23:59') `end`
		,DATEDIFF(adddate(CURDATE(), INTERVAL 0-WEEKDAY(CURDATE()) DAY), CURDATE()) `diff`
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
$row = mysqli_fetch_array($res);
$start_format = $row["start_format"];
$end_format = $row["end_format"];
$start = $row["start"];
$end = $row["end"];
$diff = $row["diff"];

$query = "
	SELECT DATE_FORMAT(date_time, '%d.%m.%Y %H:30') `time`
		,IFNULL(ROUND(AVG(t1), 1), 'NaN') t1
		,IFNULL(ROUND(AVG(t2), 1), 'NaN') t2
		,IFNULL(ROUND(AVG(h1), 1), 'NaN') h1
		,IFNULL(ROUND(AVG(h2), 1), 'NaN') h2
	FROM Climate
	WHERE date_time BETWEEN '{$start}' AND '{$end}'
	GROUP BY `time`
	ORDER BY date_time
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	$t1_data .= "{x: '{$row["time"]}', y: {$row["t1"]}}, ";
	$t2_data .= "{x: '{$row["time"]}', y: {$row["t2"]}}, ";
	$h1_data .= "{x: '{$row["time"]}', y: {$row["h1"]}}, ";
	$h2_data .= "{x: '{$row["time"]}', y: {$row["h2"]}}, ";
}
?>

<canvas id="canvas"></canvas>

<script>
	var timeFormat = 'DD.MM.YYYY HH:mm';

	function newDate(days) {
		return moment().add(days, 'd').toDate();
	}

	function newDateString(days) {
		return moment().add(days, 'd').format(timeFormat);
	}

	var color = Chart.helpers.color;
	var config = {
		type: 'line',
		data: {
			labels: [ // Date Objects
			<?
				for ($i = 0; $i <= 6; $i++) {
					echo "newDate(".($diff + $i)."),";
				}
			?>
			],
			datasets: [{
				label: 't, min',
				fill: false,
				backgroundColor: 'rgba(255, 0, 0, 1)',
				borderWidth: 2,
				pointRadius: 0,
				pointHoverRadius: 0,
				borderColor: 'rgba(255, 0, 0, 1)',
				data: [{x: '<?=$start_format?>', y: 15}, {x: '<?=$end_format?>', y: 15}, ],
			}, {
				label: 'h, min',
				fill: false,
				backgroundColor: 'rgba(0, 0, 255, 1)',
				borderWidth: 2,
				pointRadius: 0,
				pointHoverRadius: 0,
				borderColor: 'rgba(0, 0, 255, 1)',
				data: [{x: '<?=$start_format?>', y: 75}, {x: '<?=$end_format?>', y: 75}, ],
			}, {
				label: 't, Созревание',
				backgroundColor: 'rgba(255, 100, 50, .5)',
				//borderWidth: 2,
				pointRadius: 0,
				borderColor: 'rgba(255, 100, 50, 1)',
				fill: false,
				data: [<?=$t1_data?>],
			}, {
				label: 't, Расформовка',
				backgroundColor: 'rgba(255, 200, 0, .5)',
				//borderWidth: 2,
				pointRadius: 0,
				borderColor: 'rgba(255, 200, 0, 1)',
				fill: false,
				data: [<?=$t2_data?>],
			}, {
				label: 'h, Созревание',
				backgroundColor: 'rgba(100, 100, 255, .5)',
				//borderWidth: 2,
				pointRadius: 0,
				borderColor: 'rgba(100, 100, 255, 1)',
				fill: false,
				data: [<?=$h1_data?>],
			}, {
				label: 'h, Расформовка',
				backgroundColor: 'rgba(200, 0, 255, .5)',
				//borderWidth: 2,
				pointRadius: 0,
				borderColor: 'rgba(200, 0, 255, 1)',
				fill: false,
				data: [<?=$h2_data?>],
			}]
		},
		options: {
			title: {
				//display: true,
				text: 'Климатические показатели'
			},
			scales: {
				xAxes: [{
					type: 'time',
					time: {
						unit: 'day',
						parser: timeFormat,
						//round: 'hour',
						tooltipFormat: 'll HH:mm'
					},
					scaleLabel: {
						display: true
					}
				}],
				yAxes: [{
					scaleLabel: {
						display: true
					}
				}]
			},
		}
	};

	$(function() {
		var ctx = $('#canvas');
		var myChart = new Chart(ctx, config);
	});

</script>

<?
include "footer.php";
?>
