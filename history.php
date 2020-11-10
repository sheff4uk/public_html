<?
include "config.php";
$title = 'История кассеты';
include "header.php";

// Список событий
$query = "
	SELECT SUB.cassette
		,SUB.LF_ID
		,DATE_FORMAT(SUB.date_time, '%d.%m.%Y %H:%i') date_time_format
		,SUB.link
	FROM (
		SELECT cassette
			,LF_ID
			,ADDTIME(CONVERT(lf_date, DATETIME), lf_time) date_time
			,NULL `link`
		FROM list__Filling
		HAVING date_time BETWEEN NOW() - INTERVAL 7 DAY AND NOW()

		UNION ALL

		SELECT cassette
			,NULL
			,ADDTIME(CONVERT(o_date, DATETIME), o_time) date_time
			,LF_ID
		FROM list__Opening
		HAVING date_time BETWEEN NOW() - INTERVAL 7 DAY AND NOW()
	) SUB
	ORDER BY SUB.cassette, SUB.date_time
";
$cassette = 0;
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	// Когда сменилась кассета, делаем разрыв
	if( $row["cassette"] != $cassette ) {
		$cassette = $row["cassette"];
		$hist_data .= "{NaN},";
		$backgroundColor .= "'',";
	}
	// Перед заливкой делаем разрыв
	if( $row["LF_ID"] ) {
		$hist_data .= "{NaN},";
		$backgroundColor .= "'','red',";
	}
	else {
		$backgroundColor .= "'blue',";
	}
	$hist_data .= "{x:'{$row["date_time_format"]}', y:{$row["cassette"]}},";
}

for ($i = 1; $i <= $cassetts; $i++) {
	$yLabels .= "{$i},";
}

$filling_data = "{x:'10.11.2020 01:00', y:1},{x:'10.11.2020 00:01', y:1},{x:'08.11.2020 00:01', y:1},{NaN},{x:'06.11.2020 00:01', y:1},{x:'04.11.2020 00:01', y:1},";
?>

<div class="chart-container" style="position: relative; height:3200px;">
	<canvas id="canvas"></canvas>
</div>

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
		//type: 'scatter',
		type: 'line',
		data: {
			xLabels: [ // Date Objects
				newDate(0),
				newDate(-1),
				newDate(-2),
				newDate(-3),
				newDate(-4),
				newDate(-5),
				newDate(-6),
				newDate(-7)
			],
			yLabels: [<?=$yLabels?>],
			datasets: [{
				label: 'Кассета',
				borderColor: 'blue',
				backgroundColor: [<?=$backgroundColor?>],
				fill: false,
				data: [<?=$hist_data?>],
				pointRadius: 4,
			}]
		},
		options: {
			maintainAspectRatio: false,
			legend: {
				display: false
			},
			scales: {
				xAxes: [{
					type: 'time',
					time: {
						unit: 'day',
						parser: timeFormat,
						tooltipFormat: 'll HH:mm'
					},
					scaleLabel: {
						display: true,
						labelString: 'Время'
					},
					ticks: {
						reverse: false
					},
					position: 'top',
				}],
				yAxes: [{
					type: 'category',
					scaleLabel: {
						display: true,
						labelString: '№ кассеты'
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
