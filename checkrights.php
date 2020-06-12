<?
session_start();
include_once "../config.php";

// Функция обрабатывает строки перед сохранением в БД
function convert_str($src) {
	$src = trim($src);
	$src = str_replace('\\', '/', $src);
	return $src;
}

// Проверяем, активирована ли сессия и пользователь
if( empty($_SESSION['id']) ) {
	$act = 0;
}
else {
	$query = "SELECT act FROM Users WHERE USR_ID = {$_SESSION['id']}";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$act = mysqli_result($res,0,'act');
}

if( !$act ) {
	unset($_SESSION['id']);
	if( !strpos($_SERVER["REQUEST_URI"], 'login.php') ) {
		exit ('<meta http-equiv="refresh" content="0; url=/login.php">');
	}
}
else {
	// Узнаем атрибуты пользователя
	$query = "
		SELECT USR_Icon(USR.USR_ID) USR_Icon
		FROM Users USR
		WHERE USR_ID = {$_SESSION['id']}
	";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$USR_Role = mysqli_result($res,0,'RL_ID');
	$USR_Icon = mysqli_result($res,0,'USR_Icon');
}
?>
