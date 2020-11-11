<?
include "config.php";
$title = 'История кассет';
include "header.php";

// Ранняя дата
$query = "SELECT DATE_FORMAT(NOW() - INTERVAL 7 DAY, '%d.%m.%Y %H:%i') start";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
$row = mysqli_fetch_array($res);
$start = $row["start"];

// Список событий большен 24 часов
$query = "
SELECT SUB.cassette
		,SUB.LF_ID
		,DATE_FORMAT(SUB.date_time, '%d.%m.%Y %H:%i') date_time_format
		,SUB.link
		,SUB.item
		,SUB.interval
	FROM (
		SELECT LF.cassette
			,LF.LF_ID
			,ADDTIME(CONVERT(LF.lf_date, DATETIME), LF.lf_time) date_time
			,NULL `link`
			,CW.item
			,NULL `interval`
		FROM list__Filling LF
		JOIN list__Batch LB ON LB.LB_ID = LF.LB_ID
		JOIN plan__Batch PB ON PB.PB_ID = LB.PB_ID
		JOIN CounterWeight CW ON CW.CW_ID = PB.CW_ID
		LEFT JOIN list__Opening LO ON LO.LF_ID = LF.LF_ID
		GROUP BY LF.LF_ID
		HAVING date_time BETWEEN NOW() - INTERVAL 7 DAY AND NOW()
			AND IFNULL(MAX(o_interval(LO.LO_ID)), 24) >= 24

		UNION ALL

		SELECT LO.cassette
			,NULL
			,ADDTIME(CONVERT(LO.o_date, DATETIME), LO.o_time) date_time
			,LO.LF_ID
			,CW.item
			,o_interval(LO.LO_ID)
		FROM list__Opening LO
		LEFT JOIN list__Filling LF ON LF.LF_ID = LO.LF_ID
		LEFT JOIN list__Batch LB ON LB.LB_ID = LF.LB_ID
		LEFT JOIN plan__Batch PB ON PB.PB_ID = LB.PB_ID
		LEFT JOIN CounterWeight CW ON CW.CW_ID = PB.CW_ID
		WHERE o_interval(LO.LO_ID) >= 24
		HAVING date_time BETWEEN NOW() - INTERVAL 7 DAY AND NOW()
	) SUB
	ORDER BY SUB.cassette, SUB.date_time
";
$cassette = 0;
$i = 0;
$items = array();
$interval = array();
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	// Когда сменилась кассета, делаем разрыв
	if( $row["cassette"] != $cassette ) {
		$cassette = $row["cassette"];
		$hist_data .= "{NaN},";
		$pointRadius .= "4,";
		$backgroundColor .= "'',";
		$items[1][$i] = $row["item"];
		$interval[1][$i] = $row["interval"];
		$i++;
		// Если новая строка началась с расформовки, рисуем линию сначала
		if( $row["link"] ) {
			$backgroundColor .= "'blue',";
			$hist_data .= "{x:'{$start}', y:{$row["cassette"]}},";
			$pointRadius .= "0,";
			$items[1][$i] = $row["item"];
			$interval[1][$i] = $row["interval"];
			$i++;
		}
	}
	// Перед заливкой делаем разрыв
	if( $row["LF_ID"] ) {
		$hist_data .= "{NaN},";
		$pointRadius .= "4,";
		$backgroundColor .= "'','red',";
		$items[1][$i] = $row["item"];
		$interval[1][$i] = $row["interval"];
		$i++;
	}
	else {
		$backgroundColor .= "'blue',";
	}
	$hist_data .= "{x:'{$row["date_time_format"]}', y:{$row["cassette"]}},";
	$pointRadius .= "4,";
	$items[1][$i] = $row["item"];
	$interval[1][$i] = $row["interval"];
	$i++;
}

// Список событий меньше 24 часов
$query = "
SELECT SUB.cassette
		,SUB.LF_ID
		,DATE_FORMAT(SUB.date_time, '%d.%m.%Y %H:%i') date_time_format
		,SUB.link
		,SUB.item
		,SUB.interval
	FROM (
		SELECT LF.cassette
			,LF.LF_ID
			,ADDTIME(CONVERT(LF.lf_date, DATETIME), LF.lf_time) date_time
			,NULL `link`
			,CW.item
			,NULL `interval`
		FROM list__Filling LF
		JOIN list__Batch LB ON LB.LB_ID = LF.LB_ID
		JOIN plan__Batch PB ON PB.PB_ID = LB.PB_ID
		JOIN CounterWeight CW ON CW.CW_ID = PB.CW_ID
		JOIN list__Opening LO ON LO.LF_ID = LF.LF_ID
		GROUP BY LF.LF_ID
		HAVING date_time BETWEEN NOW() - INTERVAL 7 DAY AND NOW()
			AND MIN(o_interval(LO.LO_ID)) < 24

		UNION ALL

		SELECT LO.cassette
			,NULL
			,ADDTIME(CONVERT(LO.o_date, DATETIME), LO.o_time) date_time
			,LO.LF_ID
			,CW.item
			,o_interval(LO.LO_ID)
		FROM list__Opening LO
		LEFT JOIN list__Filling LF ON LF.LF_ID = LO.LF_ID
		LEFT JOIN list__Batch LB ON LB.LB_ID = LF.LB_ID
		LEFT JOIN plan__Batch PB ON PB.PB_ID = LB.PB_ID
		LEFT JOIN CounterWeight CW ON CW.CW_ID = PB.CW_ID
		WHERE o_interval(LO.LO_ID) < 24
		HAVING date_time BETWEEN NOW() - INTERVAL 7 DAY AND NOW()
	) SUB
	ORDER BY SUB.cassette, SUB.date_time
";
$cassette = 0;
$i = 0;
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	// Когда сменилась кассета, делаем разрыв
	if( $row["cassette"] != $cassette ) {
		$cassette = $row["cassette"];
		$hist_dataErr .= "{NaN},";
		$pointRadiusErr .= "4,";
		$backgroundColorErr .= "'',";
		$items[0][$i] = $row["item"];
		$interval[0][$i] = $row["interval"];
		$i++;
		// Если новая строка началась с расформовки, рисуем линию сначала
		if( $row["link"] ) {
			$backgroundColorErr .= "'blue',";
			$hist_dataErr .= "{x:'{$start}', y:{$row["cassette"]}},";
			$pointRadiusErr .= "0,";
			$items[0][$i] = $row["item"];
			$interval[0][$i] = $row["interval"];
			$i++;
		}
	}
	// Перед заливкой делаем разрыв
	if( $row["LF_ID"] ) {
		$hist_dataErr .= "{NaN},";
		$pointRadiusErr .= "4,";
		$backgroundColorErr .= "'','red',";
		$items[0][$i] = $row["item"];
		$interval[0][$i] = $row["interval"];
		$i++;
	}
	else {
		$backgroundColorErr .= "'blue',";
	}
	$hist_dataErr .= "{x:'{$row["date_time_format"]}', y:{$row["cassette"]}},";
	$pointRadiusErr .= "4,";
	$items[0][$i] = $row["item"];
	$interval[0][$i] = $row["interval"];
	$i++;
}

for ($i = 1; $i <= $cassetts; $i++) {
	$yLabels .= "{$i},";
}
?>

<div class="chart-container" style="position: relative; height:3200px;">
	<canvas id="canvas"></canvas>
</div>

<script>
	var items = <?= json_encode($items); ?>;
	var interval = <?= json_encode($interval); ?>;

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
				borderColor: 'red',
				backgroundColor: [<?=$backgroundColorErr?>],
				fill: false,
				data: [<?=$hist_dataErr?>],
				pointRadius: [<?=$pointRadiusErr?>],
			},{
				label: 'Кассета',
				borderColor: 'blue',
				backgroundColor: [<?=$backgroundColor?>],
				fill: false,
				data: [<?=$hist_data?>],
				pointRadius: [<?=$pointRadius?>],
			}]
		},
		options: {
			maintainAspectRatio: false,
			legend: {
				display: false
			},
			tooltips: {
				callbacks: {
					afterLabel: function(tooltipItem, data) {
						var label = 'Код: ' + items[tooltipItem.datasetIndex][tooltipItem.index];
						if( interval[tooltipItem.datasetIndex][tooltipItem.index] ) {
							label += '\nИнтервал: ' +interval[tooltipItem.datasetIndex][tooltipItem.index]+ ' ч.';
						}
						return label;
					},
				}
			},
			scales: {
				xAxes: [{
					type: 'time',
					gridLines: {
						color: 'red',
						zeroLineColor: 'red',
						drawBorder: false
					},
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
