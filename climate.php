<?
include "config.php";
$title = 'Климат';
include "header.php";

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

echo "<h2>Последние показания: <span style='color: rgba(255, 100, 50, 1);' title='Участок созревания'>{$t1}&#8451;</span>&nbsp;&nbsp;&nbsp;<span style='color: rgba(255, 200, 0, 1);' title='Участок расформовки'>{$t2}&#8451;</span>&nbsp;&nbsp;&nbsp;<span style='color: rgba(100, 100, 255, 1);' title='Участок созревания'>{$h1}%</span>&nbsp;&nbsp;&nbsp;<span style='color: rgba(200, 0, 255, 1);' title='Участок расформовки'>{$h2}%</span></h2>";

// Узнаем время начала и время окончания
$query = "
	SELECT DATE_FORMAT(NOW() - INTERVAL 7 DAY, '%d.%m.%Y %H:30') `start`
		,DATE_FORMAT(NOW() - INTERVAL 30 MINUTE, '%d.%m.%Y %H:30') `end`
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
$row = mysqli_fetch_array($res);
$start = $row["start"];
$end = $row["end"];

$query = "
	SELECT DATE_FORMAT(date_time, '%d.%m.%Y %H:30') `time`
		,IFNULL(ROUND(AVG(t1), 1), 'NaN') t1
		,IFNULL(ROUND(AVG(t2), 1), 'NaN') t2
		,IFNULL(ROUND(AVG(h1), 1), 'NaN') h1
		,IFNULL(ROUND(AVG(h2), 1), 'NaN') h2
	FROM Climate
	WHERE date_time BETWEEN NOW() - INTERVAL 7 DAY AND NOW() - INTERVAL 30 MINUTE
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
				newDate(-7),
				newDate(-6),
				newDate(-5),
				newDate(-4),
				newDate(-3),
				newDate(-2),
				newDate(-1)
			],
			datasets: [{
				label: 't, min',
				fill: false,
				backgroundColor: 'rgba(255, 0, 0, 1)',
				borderWidth: 2,
				pointRadius: 0,
				pointHoverRadius: 0,
				borderColor: 'rgba(255, 0, 0, 1)',
				data: [{x: '<?=$start?>', y: 15}, {x: '<?=$end?>', y: 15}, ],
			}, {
				label: 'h, min',
				fill: false,
				backgroundColor: 'rgba(0, 0, 255, 1)',
				borderWidth: 2,
				pointRadius: 0,
				pointHoverRadius: 0,
				borderColor: 'rgba(0, 0, 255, 1)',
				data: [{x: '<?=$start?>', y: 75}, {x: '<?=$end?>', y: 75}, ],
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
