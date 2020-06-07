<?
	//ini_set("session.gc_maxlifetime",10);
	session_start();

	// Функция обрабатывает строки перед сохранением в БД
	function convert_str($src) {
		$src = trim($src);
		$src = str_replace('\\', '/', $src);
		return $src;
	}

	// Проверяем, активирована ли сессия
	if( empty($_SESSION['id']) ) {
		if( !strpos($_SERVER["REQUEST_URI"], 'login.php') ) {
			if( $_GET["ajax"] == 1 ) {
				echo "noty({timeout: 3000, text: 'Вы не авторизованы! Пожалуйста, перезагрузите страницу.', type: 'error'});";
			}
			else {
				exit ('<meta http-equiv="refresh" content="0; url=/login.php">');
			}
			die;
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
