<?
$path = dirname(dirname($argv[0]));
$key = $argv[1];
$to = $argv[2];

include $path."/config.php";
// Проверка доступа
if( $key != $script_key ) die('Access denied!');

$from_date = date_create( '-1 days' );
$from_format = date_format($from_date, 'd.m.Y');
$to_date = date_create();
$to_format = date_format($to_date, 'd.m.Y');
$subject = "=?utf-8?b?". base64_encode("[KONSTANTA] Производственный отчет за {$from_format}"). "?=";

$message = "
	<html>
		<head>
			<title>[KONSTANTA] Производственный отчет за {$from_format}</title>
		</head>
		<body>
			<table cellspacing='0' cellpadding='2' border='1' style='table-layout: fixed; width: 100%;'>
				<tr>
					<th><img src='https://konstanta.ltd/assets/images/logo.png' alt='KONSTANTA' style='width: 200px; margin: 5px;'></th>
					<th><n style='font-size: 2em;'>Ответственность мастеров</n></th>
					<th>Производственные сутки:<br>c 07:00 {$from_format} до 07:00 {$to_format}</th>
				</tr>
			</table>
";
$message .= "
	<table cellspacing='0' cellpadding='2' border='1' style='table-layout: fixed; width: 100%;'>
		<thead style='word-wrap: break-word;'>
			<tr>
				<th>Смена</th>
				<th>Расформовка</th>
				<th>Кассета</th>
				<th>Противовес</th>
				<th>Деталей</th>
				<th>Дефект формы</th>
				<th>Дефект сборки</th>
				<th>Сборка кассеты</th>
				<th>Мастер</th>
			</tr>
		</thead>
		<tbody style='text-align: center;'>
";

$query = "
	SELECT IF(HOUR(LO.opening_time - INTERVAL 7 HOUR) < 12, 1, 2) shift
		,DATE_FORMAT(LO.opening_time, '%d.%m.%Y %H:%i') opening_time_format
		,LO.cassette
		,CW.item
		,SUM(1) details
		,SUM(IF(LW.goodsID = 6, 1, NULL)) d_shell
		,SUM(IF(LW.goodsID = 7, 1, NULL)) d_assembly
		,DATE_FORMAT(LA.assembling_time, '%d.%m.%Y %H:%i') assembling_time_format
		,USR_Name(LA.assembling_master) assembling_master
	FROM list__Opening LO
	JOIN list__Filling LF ON LF.LF_ID = LO.LF_ID
	JOIN list__Assembling LA ON LA.LA_ID = LF.LA_ID
	JOIN list__Batch LB ON LB.LB_ID = LF.LB_ID
	JOIN plan__Batch PB ON PB.PB_ID = LB.PB_ID AND PB.F_ID = 1
	JOIN CounterWeight CW ON CW.CW_ID = PB.CW_ID
	JOIN list__Weight LW ON LW.LO_ID = LO.LO_ID
	WHERE DATE(LO.opening_time - INTERVAL 7 HOUR) = CURDATE() - INTERVAL 1 DAY
	GROUP BY LO.LO_ID
	HAVING d_shell OR d_assembly
	ORDER BY LO.opening_time
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query1: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	$message .= "
		<tr>
			<td>{$row["shift"]}</td>
			<td>{$row["opening_time_format"]}</td>
			<td>{$row["cassette"]}</td>
			<td>{$row["item"]}</td>
			<td>{$row["details"]}</td>
			<td>{$row["d_shell"]}</td>
			<td>{$row["d_assembly"]}</td>
			<td>{$row["assembling_time_format"]}</td>
			<td>{$row["assembling_master"]}</td>
		</tr>
	";
}

$message .= "
		</tbody>
	</table>
	<br>
	<br>
";

$message .= "
	<table cellspacing='0' cellpadding='2' border='1' style='table-layout: fixed; width: 100%;'>
		<tr>
			<th><img src='https://konstanta.ltd/assets/images/logo.png' alt='KONSTANTA' style='width: 200px; margin: 5px;'></th>
			<th><n style='font-size: 2em;'>Ответственность операторов</n></th>
			<th>Производственные сутки:<br>c 07:00 {$from_format} до 07:00 {$to_format}</th>
		</tr>
	</table>

	<table cellspacing='0' cellpadding='2' border='1' style='table-layout: fixed; width: 100%;'>
		<thead style='word-wrap: break-word;'>
			<tr>
				<th>Смена</th>
				<th>Расформовка</th>
				<th>Кассета</th>
				<th>Противовес</th>
				<th>Деталей</th>
				<th>Непролив</th>
				<th>Усадочная трещина</th>
				<th>Легкие детали</th>
				<th>Тяжелые детали</th>
				<th>Заливка</th>
				<th>Оператор</th>
			</tr>
		</thead>
		<tbody style='text-align: center;'>
			<tr>
";

$query = "
	SELECT IF(HOUR(LO.opening_time - INTERVAL 7 HOUR) < 12, 1, 2) shift
		,DATE_FORMAT(LO.opening_time, '%d.%m.%Y %H:%i') opening_time_format
		,LO.cassette
		,CW.item
		,SUM(1) details
		,SUM(IF(LW.goodsID = 2, 1, NULL)) not_spill
		,SUM(IF(LW.goodsID = 4, 1, NULL)) crack_drying
		,SUM(IF(LW.weight < ROUND(CW.min_weight/100*101), 1, NULL)) light
		,SUM(IF(LW.weight > ROUND(CW.max_weight/100*101), 1, NULL)) heavy
		,DATE_FORMAT(LF.filling_time, '%d.%m.%Y %H:%i') filling_time_format
		,USR_Name(LB.operator) name
	FROM list__Opening LO
	JOIN list__Filling LF ON LF.LF_ID = LO.LF_ID
	JOIN list__Batch LB ON LB.LB_ID = LF.LB_ID
	JOIN plan__Batch PB ON PB.PB_ID = LB.PB_ID AND PB.F_ID = 1
	JOIN CounterWeight CW ON CW.CW_ID = PB.CW_ID
	JOIN list__Weight LW ON LW.LO_ID = LO.LO_ID
	WHERE DATE(LO.opening_time - INTERVAL 7 HOUR) = CURDATE() - INTERVAL 1 DAY
	GROUP BY LO.LO_ID
	HAVING not_spill OR crack_drying OR light OR heavy
	ORDER BY LO.opening_time
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query2: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	$message .= "
		<tr>
			<td>{$row["shift"]}</td>
			<td>{$row["opening_time_format"]}</td>
			<td>{$row["cassette"]}</td>
			<td>{$row["item"]}</td>
			<td>{$row["details"]}</td>
			<td>{$row["not_spill"]}</td>
			<td>{$row["crack_drying"]}</td>
			<td>{$row["light"]}</td>
			<td>{$row["heavy"]}</td>
			<td>{$row["filling_time_format"]}</td>
			<td>{$row["name"]}</td>
		</tr>
	";
}

$message .= "
				</tbody>
			</table>
		</body>
	</html>
";

// $headers  = "Content-type: text/html; charset=\"utf-8\"\n";
// $headers .= "From: planner@konstanta.ltd\r\n";

// mail($to, $subject, $message, $headers);
$headers[] = 'MIME-Version: 1.0';
$headers[] = 'Content-type: text/html; charset=iso-8859-1';
$headers[] = 'From: Konstanta <info@konstanta.ltd>';
$headers[] = 'Reply-To: Konstanta <info@konstanta.ltd>';
$headers[] = 'X-Mailer: PHP/' . phpversion();

mail($to, $subject, $message, implode("\r\n", $headers));
?>
