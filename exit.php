<?
	session_start();
	// уничтожаем переменные в сессиях
	unset($_SESSION['id']);
	unset($_SESSION['F_ID']);
	exit("<html><head><meta http-equiv='Refresh' content='0; URL=/'></head></html>");
	// отправляем пользователя на главную страницу.
?>
