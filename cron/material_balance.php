<?
$path = dirname(dirname($argv[0]));
$key = $argv[1];
include $path."/config.php";
// Проверка доступа
if( $key != $script_key ) die('Access denied!');

// Цикл по производственным участкам
$query = "
	SELECT F_ID
		,notification_group
	FROM factory
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	$F_ID = $row["F_ID"];

	// Узнаем баланс материалов
	$query = "
        SELECT MN.material_name
            ,ROUND(MB.mb_balance, 2) mb_balance
        FROM material__Balance MB
        JOIN material__Name MN ON MN.MN_ID = MB.MN_ID
        WHERE F_ID = {$F_ID}
            AND MB.MN_ID = 1
	";
	$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$text = "<b>Баланс материалов:</b>\n";
	while( $subrow = mysqli_fetch_array($subres) ) {
        $text .= "{$subrow["material_name"]}: {$subrow["mb_balance"]}\n";

	}
	if( $text ) {
		message_to_telegram($text, $row["notification_group"]);
	}
}
?>
