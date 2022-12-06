<?
include "config.php";
//require_once "vendor/autoload.php";

$data = json_decode(file_get_contents('php://input')); // получаем JSON
$text = $data->{'message'}->{'text'};

$query = "INSERT INTO test SET command = '{$text}'";
mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

//try {
//	$bot = new \TelegramBot\Api\Client(TELEGRAM_TOKEN);
//	// команда для start
//	$bot->command('start', function ($message) use ($bot) {
//		$answer = 'Добро пожаловать!';
//		$bot->sendMessage($message->getChat()->getId(), $answer);
//	});
//
//	// команда для помощи
//	$bot->command('help', function ($message) use ($bot) {
//		$answer = 'Команды:
//	/help - вывод справки';
//		$bot->sendMessage($message->getChat()->getId(), $answer);
//	});
//	$bot->run();
//}
//catch (\TelegramBot\Api\Exception $e) {
//	$e->getMessage();
//}

//try {
//	$bot = new \TelegramBot\Api\Client(TELEGRAM_TOKEN);
//
//
//	//Handle /ping command
//	$bot->command('ping', function ($message) use ($bot) {
//		$bot->sendMessage($message->getChat()->getId(), 'pong!');
//	});
//
//	//Handle text messages
//	$bot->on(function (\TelegramBot\Api\Types\Update $update) use ($bot) {
//		$message = $update->getMessage();
//		$id = $message->getChat()->getId();
//		$bot->sendMessage($id, 'Your message: ' . $message->getText());
//	}, function () {
//		return true;
//	});
//
//	$bot->run();

//} catch (\TelegramBot\Api\Exception $e) {
//	$e->getMessage();
//}
?>
