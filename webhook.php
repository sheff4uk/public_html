<?
include "config.php";
require_once "vendor/autoload.php";

//try {
	$bot = new \TelegramBot\Api\Client(TELEGRAM_TOKEN);
	// команда для start
	$bot->command('start', function ($message) use ($bot) {
		$answer = 'Добро пожаловать!';
		$bot->sendMessage($message->getChat()->getId(), $answer);
	});

	// команда для помощи
	$bot->command('help', function ($message) use ($bot) {
		$answer = 'Команды:
	/help - вывод справки';
		$bot->sendMessage($message->getChat()->getId(), $answer);
	});
	$bot->run();
//}
//catch (\TelegramBot\Api\Exception $e) {
//	$e->getMessage();
//}
?>
