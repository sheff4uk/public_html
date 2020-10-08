#!/usr/bin/php
<?
include "../config.php";
// Проверка доступа
if( $_GET["key"] != $script_key ) die('Access denied!');

$mtel = $_GET["mtel"];
echo $mtel;
$query = "
	SELECT CW.item
		,DATE_FORMAT(LB.batch_time, '%H:%i') time
		,24 delay
	FROM list__Batch LB
	JOIN plan__Batch PB ON PB.PB_ID = LB.PB_ID
	JOIN CounterWeight CW ON CW.CW_ID = PB.CW_ID
	LEFT JOIN list__CubeTest LCT ON LCT.LB_ID = LB.LB_ID AND LCT.delay = 24
	WHERE LB.test = 1
		AND LCT.LCT_ID IS NULL
		AND PB.pb_date + INTERVAL 1 DAY = CURDATE()
		#AND HOUR(LB.batch_time) = HOUR(CURTIME())
		AND HOUR(LB.batch_time) = 14
	UNION ALL
	SELECT CW.item
		,DATE_FORMAT(LB.batch_time, '%H:%i') time
		,72 delay
	FROM list__Batch LB
	JOIN plan__Batch PB ON PB.PB_ID = LB.PB_ID
	JOIN CounterWeight CW ON CW.CW_ID = PB.CW_ID
	LEFT JOIN list__CubeTest LCT ON LCT.LB_ID = LB.LB_ID AND LCT.delay = 72
	WHERE LB.test = 1
		AND LCT.LCT_ID IS NULL
		AND PB.pb_date + INTERVAL 3 DAY = CURDATE()
		#AND HOUR(LB.batch_time) = HOUR(CURTIME())
		AND HOUR(LB.batch_time) = 14
	ORDER BY time
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	$text .= "{$row["item"]} {$row["time"]} [{$row["delay"]}]\n";
}
if( $text ) {
	echo $text;
	//$body = file_get_contents("https://sms.ru/sms/send?api_id=".($api_id)."&to=".($mtel)."&msg=".urlencode($text)."&json=1");
}
//$json = json_decode($body);
//if( $json ) { // Получен ответ от сервера
//	if( $json->status == "OK" ) { // Запрос выполнился
//		$_SESSION["sms_code"] = $sms_code;
//	}
//	else $_SESSION["error"][] = "Запрос не выполнился (возможно ошибка авторизации, параметрах, итд...) Код ошибки: $json->status_code Текст ошибки: $json->status_text";
//}
//else $_SESSION["error"][] = "Запрос не выполнился Не удалось установить связь с сервером.";
?>
