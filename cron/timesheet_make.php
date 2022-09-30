<?
$path = dirname(dirname($argv[0]));
$key = $argv[1];
include $path."/config.php";
// Проверка доступа
if( $key != $script_key ) die('Access denied!');

// Генерация табеля на следующий месяц
$query = "
	INSERT INTO TariffMonth(year, month, USR_ID, F_ID, tariff, type)
	SELECT YEAR(CURDATE()), MONTH(CURDATE()), TM.USR_ID, TM.F_ID, TM.tariff, TM.type
	FROM TariffMonth TM
	WHERE TM.year = YEAR(CURDATE() - INTERVAL 1 MONTH)
		AND TM.month = MONTH(CURDATE() - INTERVAL 1 MONTH)
		AND TM.USR_ID IN (SELECT USR_ID FROM Users WHERE act = 1 AND IFNULL(cardcode, '') != '' )
	ON DUPLICATE KEY UPDATE
		F_ID = TM.F_ID
";
mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
?>
