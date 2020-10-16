<?
include "config.php";
$title = 'Климат';
include "header.php";
?>

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

<?
include "footer.php";
?>
