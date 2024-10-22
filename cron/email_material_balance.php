<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

$path = dirname(dirname($argv[0]));
$key = $argv[1];
$F_ID = $argv[2];
$to = $argv[3];

include $path."/config.php";
// Проверка доступа
if( $key != $script_key ) die('Access denied!');

require $path.'/PHPMailer/PHPMailer.php';
require $path.'/PHPMailer/SMTP.php';
require $path.'/PHPMailer/Exception.php';

$date = date_create();
$date_format = date_format($date, 'd/m/Y');
$subject = "[KONSTANTA] Остатки сырья {$date_format}";

$message = "
	<html>
	<body>
		<table cellspacing='0' cellpadding='2' border='1' style='table-layout: fixed; width: 100%;'>
			<tr>
                <th><img src='https://konstanta.ltd/assets/images/logo.png' alt='KONSTANTA' style='width: 200px; margin: 5px;'></th>
                <th><n style='font-size: 2em;'>Остатки сырья</n></th>
                <th>{$date_format}</th>
			</tr>
		</table>
";

$message .= "
	<table cellspacing='0' cellpadding='2' border='1' style='table-layout: fixed; width: 100%;'>
		<thead style='word-wrap: break-word;'>
			<tr>
				<th rowspan='2'>Наименование</th>
				<th rowspan='2'>Приход</th>
				<th colspan='2'>Средний расход</th>
				<th rowspan='2'>Остаток</th>
                <th rowspan='2'>Запас в днях</th>
			</tr>
			<tr>
				<th>День</th>
				<th>Месяц</th>
			</tr>
		</thead>
		<tbody style='text-align: center;'>
";
    
// Узнаем баланс материалов
$query = "
    SELECT MB.MN_ID
        ,MN.material_name
        ,(SELECT ROUND(SUM(ma_cnt), 2) FROM material__Arrival WHERE F_ID = {$F_ID} AND MN_ID = MB.MN_ID AND ma_date = CURDATE()) income
        ,ROUND(MB.mb_balance, 2) mb_balance
        ,FLOOR(MB.mb_balance / MB.daily_consumption) inDays
    FROM material__Balance MB
    JOIN material__Name MN ON MN.MN_ID = MB.MN_ID
    WHERE MB.F_ID = {$F_ID}
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

while( $row = mysqli_fetch_array($res) ) {

    $query = "
        SELECT ROUND(SUM(LBM.quantity) * MN.adjustment / 30000, 2) dayly_avg
        FROM material__Name MN
        LEFT JOIN list__BatchMaterial LBM ON LBM.MN_ID = MN.MN_ID
            AND LBM.LB_ID IN (
                SELECT LB_ID
                FROM list__Batch
                WHERE PB_ID IN (SELECT PB_ID FROM plan__Batch WHERE F_ID = {$F_ID})
                    AND batch_date >= CURDATE() - INTERVAL 31 DAY
                    AND batch_date <= CURDATE() - INTERVAL 1 DAY
            )
        WHERE MN.MN_ID = {$row["MN_ID"]}
    ";
    $subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
    $subrow = mysqli_fetch_array($subres);
    $dayly_avg = $subrow["dayly_avg"];

    $query = "
        SELECT ROUND(SUM(LBM.quantity) * MN.adjustment / 12000, 2) weekly_avg
        FROM material__Name MN
        LEFT JOIN list__BatchMaterial LBM ON LBM.MN_ID = MN.MN_ID
            AND LBM.LB_ID IN (
                SELECT LB_ID
                FROM list__Batch
                WHERE PB_ID IN (SELECT PB_ID FROM plan__Batch WHERE F_ID = {$F_ID})
                    AND batch_date >= CURDATE() - INTERVAL 12 MONTH
                    AND batch_date <= CURDATE() - INTERVAL 0 MONTH
            )
        WHERE MN.MN_ID = {$row["MN_ID"]}
    ";
    $subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
    $subrow = mysqli_fetch_array($subres);
    $weekly_avg = $subrow["weekly_avg"];

    $message .= "
        <tr>
            <td>{$row["material_name"]}</td>
            <td>{$row["income"]}</td>
            <td>{$dayly_avg}</td>
            <td>{$weekly_avg}</td>
            <td>{$row["mb_balance"]}</td>
            <td>{$row["inDays"]}</td>
        </tr>
    ";
}
$message .= "
        </tbody>
    </table>
";

$message .= "
        </body>
    </html>
";

$mail = new PHPMailer();

$mail->isSMTP();
$mail->Host			= 'exchange.atservers.net'; 
$mail->SMTPAuth		= true;
$mail->Username		= $phpmailer_email;
$mail->Password		= $phpmailer_secret;
$mail->SMTPSecure	= 'tls';
$mail->Port			= 587;

$mail->setFrom($phpmailer_email, 'KONSTANTA');

foreach (explode(",", $to) as &$value) {
$mail->addBCC($value);
//$mail->addAddress($value);
}

$mail->CharSet = 'UTF-8';
$mail->isHTML(true);
$mail->Subject = $subject;
$mail->Body    = $message;

$mail->send();
?>
