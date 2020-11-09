<?
include "config.php";
$title = 'История кассеты';
include "header.php";

// Массив заливок
$query = "
	SELECT cassette
		,DATE_FORMAT(ADDTIME(CONVERT(lf_date, DATETIME), lf_time), '%d.%m.%Y %H:%i') `time`
	FROM list__Filling
	WHERE ADDTIME(CONVERT(lf_date, DATETIME), lf_time) BETWEEN NOW() - INTERVAL 7 DAY AND NOW()
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	$filling_data .= "{x:'[{$row["cassette"]}]', y: '{$row["time"]}'},";
}

// Массив расформовок
$query = "
	SELECT cassette
		,DATE_FORMAT(ADDTIME(CONVERT(o_date, DATETIME), o_time), '%d.%m.%Y %H:%i') `time`
	FROM list__Opening
	WHERE ADDTIME(CONVERT(o_date, DATETIME), o_time) BETWEEN NOW() - INTERVAL 7 DAY AND NOW()
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	$opening_data .= "{x:'[{$row["cassette"]}]', y: '{$row["time"]}'},";
}

for ($i = 1; $i <= $cassetts; $i++) {
	$xLabels .= "'[{$i}]', ";
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
		type: 'scatter',
		data: {
			xLabels: [<?=$xLabels?>],
			yLabels: [ // Date Objects
				newDate(0),
				newDate(-1),
				newDate(-2),
				newDate(-3),
				newDate(-4),
				newDate(-5),
				newDate(-6),
				newDate(-7)
			],
			datasets: [{
				label: 'Заливка',
				backgroundColor: 'rgba(255, 0, 0, .5)',
				//borderWidth: 2,
				borderColor: 'rgba(255, 0, 0, 1)',
				fill: false,
				data: [<?=$filling_data?>],
			}, {
				label: 'Расформовка',
				backgroundColor: 'rgba(0, 0, 255, .5)',
				//borderWidth: 2,
				borderColor: 'rgba(0, 0, 255, 1)',
				fill: false,
				data: [<?=$opening_data?>],
			}]
		},
		options: {
			title: {
				//display: true,
				text: 'История кассет'
			},
			scales: {
				xAxes: [{
					type: 'category',
					scaleLabel: {
						display: true,
						labelString: '№ кассеты'
					}
				}],
				yAxes: [{
					type: 'time',
					time: {
						parser: timeFormat,
						//round: 'hour',
						tooltipFormat: 'll HH:mm'
					},
					scaleLabel: {
						display: true,
						labelString: 'Время'
					},
					ticks: {
						reverse: true
					}
				}]
			}
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
