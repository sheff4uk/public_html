<?
$path = dirname(dirname($argv[0]));
$key = $argv[1];
$to = $argv[2];

include $path."/config.php";
// Проверка доступа
if( $key != $script_key ) die('Access denied!');

$date = date_create();
$date_format = date_format($date, 'd.m.Y');
$subject = "=?utf-8?b?". base64_encode("[KONSTANTA] Stock report {$date_format}"). "?=";

$message = "
	<table cellspacing='0' cellpadding='2' border='1' style='table-layout: fixed; width: 100%;'>
		<tr>
			<th><img src='https://konstanta.ltd/assets/images/logo.png' alt='KONSTANTA' style='width: 200px; margin: 5px;'></th>
			<th><n style='font-size: 2em;'>Stock report</n></th>
			<th>{$date_format}</th>
		</tr>
	</table>

	<table cellspacing='0' cellpadding='2' border='1' style='table-layout: fixed; width: 100%;'>
		<thead style='word-wrap: break-word;'>
			<tr>
				<th>Item</th>
				<th>Pallets</th>
				<th>Details</th>
			</tr>
		</thead>
		<tbody style='text-align: center;'>
";

$query = "
	SELECT CW.item
		,SUM(1) pallets
		,SUM(CW.in_pallet) details
	FROM list__PackingPallet LPP
	JOIN CounterWeight CW ON CW.CW_ID = LPP.CW_ID AND CW.CB_ID = 2
	WHERE LPP.packed_time > NOW() - INTERVAL 4 WEEK AND LPP.shipment_time IS NULL
	GROUP BY LPP.CW_ID
	ORDER BY LPP.CW_ID ASC
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query1: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	$message .= "
		<tr>
			<td>{$row["item"]}</td>
			<td>{$row["pallets"]}</td>
			<td>{$row["details"]}</td>
		</tr>
	";
}

$message .= "
		</tbody>
	</table>
";

$headers = "Content-type: text/html; charset=\"utf-8\"\n";
$headers .= "From: planner@konstanta.ltd\r\n";

mail($to, $subject, $message, $headers);
?>
