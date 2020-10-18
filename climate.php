<?
include "config.php";
$title = 'Климат';
include "header.php";

$query = "
	SELECT DATE_FORMAT(date_time, '%d.%m.%Y %H:30') `time`
		,ROUND(AVG(temperature), 1) temperature
		,ROUND(AVG(humidity), 1) humidity
	FROM Climate
	WHERE date_time BETWEEN NOW() - INTERVAL 7 DAY AND NOW() - INTERVAL 1 HOUR
	GROUP BY `time`
	ORDER BY `time`
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	$temperature_data .= "{x: '{$row["time"]}', y: {$row["temperature"]}}, ";
	$humidity_data .= "{x: '{$row["time"]}', y: {$row["humidity"]}}, ";
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
				label: 'Температура',
				backgroundColor: 'rgba(255, 0, 0, .5)',
				//borderWidth: 2,
				borderColor: 'rgba(255, 0, 0, 1)',
				fill: false,
				data: [<?=$temperature_data?>],
			}, {
				label: 'Влажность',
				backgroundColor: 'rgba(0, 0, 255, .5)',
				//borderWidth: 2,
				borderColor: 'rgba(0, 0, 255, 1)',
				fill: false,
				data: [<?=$humidity_data?>],
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

<!--
<table class="main_table">
	<thead>
		<tr>
			<th>Дата</th>
			<th>Время</th>
			<th>Температура, C<sup>o</sup></th>
			<th>Влажность, %</th>
		</tr>
	</thead>
	<tbody style="text-align: center;">
		<?
		$query = "
			SELECT Friendly_date(date_time) friendly_date
				,DATE_FORMAT(date_time, '%H:%i') time
				,temperature
				,humidity
			FROM Climate
			ORDER BY date_time DESC
			LIMIT 120
		";
		$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		while( $row = mysqli_fetch_array($res) ) {
			?>
			<tr>
				<td><?=$row["friendly_date"]?></td>
				<td><?=$row["time"]?></td>
				<td><?=$row["temperature"]?></td>
				<td><?=$row["humidity"]?></td>
			</tr>
			<?
		}
		?>
	</tbody>
</table>
-->
<?
include "footer.php";
?>
