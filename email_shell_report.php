<?
include "config.php";

$to  = "sheff4uk@gmail.com" ;

$date = new DateTime();
$sr_date_format = date_format($date, 'd/m/Y');
$subject = "Shells report on {$sr_date_format}";

$message = "
	<table cellspacing='0' cellpadding='2' border='1' style='table-layout: fixed; width: 100%;'>
		<tr>
			<th><img src='https://konstanta.ltd/assets/images/logo.png' alt='KONSTANTA' style='width: 200px; margin: 5px;'></th>
			<th>Report date: <n style='font-size: 2em;'>{$sr_date_format}</n></th>
		</tr>
	</table>
";

$headers  = "Content-type: text/html; charset=utf-8 \r\n";
$headers .= "From: planner@konstanta.ltd\r\n";

mail($to, $subject, $message, $headers);
?>
