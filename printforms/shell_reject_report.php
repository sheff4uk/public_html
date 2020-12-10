<?
include "../config.php";
?>

<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">

<?
$sr_date = $_GET["sr_date"];

$date = new DateTime($sr_date);
$sr_date_format = date_format($date, 'd/m/Y');

echo "<title>Shells replacement report on {$sr_date}</title>";
?>
	<style type="text/css" media="print">
		@page { size: portrait; }
	</style>

	<style>
		body, td {
			font-family: Trebuchet MS, Tahoma, Verdana, Arial, sans-serif;
			font-size: 10pt;
		}
		table {
			table-layout: fixed;
			width: 100%;
			border-collapse: collapse;
			border-spacing: 0px;
		}
		.thead {
			text-align: center;
			font-weight: bold;
		}
		td, th {
			padding: 3px;
			border: 1px solid black;
			line-height: 1em;
		}
		.nowrap {
			white-space: nowrap;
		}
		.total {
			font-weight: bold;
		}
	</style>
</head>
<body>

<table>
	<thead>
		<tr>
			<th><img src="/img/logo.png" alt="KONSTANTA" style="width: 200px; margin: 5px;"></th>
			<th>Report date: <n style="font-size: 2em;"><?=$sr_date_format?></n></th>
		</tr>
	</thead>
</table>

<table>
	<thead style="word-wrap: break-word;">
		<tr>
			<th>ITEM</th>
			<th>Number of rejected shells</th>
			<th>Exfolation of the work surface material</th>
			<th>Crack on the working surface of the shell</th>
		</tr>
	</thead>
	<tbody style="text-align: center;">

<?
$query = "
	SELECT SR.SR_ID
		,CW.item
		,SR.sr_cnt
		,SR.exfolation
		,SR.crack
	FROM ShellReject SR
	JOIN CounterWeight CW ON CW.CW_ID = SR.CW_ID
	WHERE SR.sr_date = '{$sr_date}'
		".($_GET["CB_ID"] ? "AND SR.CW_ID IN (SELECT CW_ID FROM CounterWeight WHERE CB_ID = {$_GET["CB_ID"]})" : "")."
	ORDER BY SR.sr_date DESC, SR.CW_ID
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	$sr_cnt += $row["sr_cnt"];
	$exfolation += $row["exfolation"];
	$crack += $row["crack"];
	?>
	<tr>
		<td><?=$row["item"]?></td>
		<td><?=$row["sr_cnt"]?></td>
		<td><?=$row["exfolation"]?></td>
		<td><?=$row["crack"]?></td>
	</tr>
	<?
}
?>
		<tr class="total">
			<td>Total:</td>
			<td><?=$sr_cnt?></td>
			<td><?=$exfolation?></td>
			<td><?=$crack?></td>
		</tr>
	</tbody>
</table>

</body>
</html>
