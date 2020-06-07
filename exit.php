<?
	session_start();
	unset($_SESSION['id']);// уничтожаем переменные в сессиях
	exit("<html><head><meta http-equiv='Refresh' content='0; URL=/'></head></html>");
	// отправляем пользователя на главную страницу.
?>
