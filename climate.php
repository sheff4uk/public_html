<?
include "config.php";
$title = 'Климат';
include "header.php";

$query = "
	SELECT DATE_FORMAT(date_time, '%d.%m.%Y %H:30') `time`
		,IFNULL(ROUND(AVG(t1), 1), 'NaN') t1
		,IFNULL(ROUND(AVG(h1), 1), 'NaN') h1
		,IFNULL(ROUND(AVG(t2), 1), 'NaN') t2
		,IFNULL(ROUND(AVG(h2), 1), 'NaN') h2
	FROM Climate
	WHERE date_time BETWEEN NOW() - INTERVAL 7 DAY AND NOW() - INTERVAL 30 MINUTE
	GROUP BY `time`
	ORDER BY `time`
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	$t1_data .= "{x: '{$row["time"]}', y: {$row["t1"]}}, ";
	$h1_data .= "{x: '{$row["time"]}', y: {$row["h1"]}}, ";
	$t2_data .= "{x: '{$row["time"]}', y: {$row["t2"]}}, ";
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
				label: '<< Температура',
				backgroundColor: 'rgba(255, 0, 0, .5)',
				//borderWidth: 2,
				borderColor: 'rgba(255, 0, 0, 1)',
				fill: false,
				data: [<?=$t1_data?>],
			}, {
				label: '<< Влажность',
				backgroundColor: 'rgba(0, 0, 255, .5)',
				//borderWidth: 2,
				borderColor: 'rgba(0, 0, 255, 1)',
				fill: false,
				data: [<?=$h1_data?>],
			}, {
				label: 'Температура >>',
				backgroundColor: 'rgba(255, 100, 0, .5)',
				//borderWidth: 2,
				borderColor: 'rgba(255, 100, 0, 1)',
				fill: false,
				data: [<?=$t2_data?>],
			}, {
				label: 'Влажность >>',
				backgroundColor: 'rgba(100, 0, 255, .5)',
				//borderWidth: 2,
				borderColor: 'rgba(100, 0, 255, 1)',
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
						parser: timeFormat,
						//round: 'hour',
						tooltipFormat: 'll HH:mm'
					},
					scaleLabel: {
						display: true,
						labelString: 'Время'
					}
				}],
				yAxes: [{
					scaleLabel: {
						display: true,
						labelString: 'Значение'
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
