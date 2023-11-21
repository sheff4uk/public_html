<?
include "config.php";

$json = file_get_contents('php://input');
//$data = json_decode(file_get_contents('php://input')); // получаем JSON
//$text = $data->{'message'}->{'text'};

//$query = "INSERT INTO test SET command = '{$text}'";
$query = "INSERT INTO test SET command = '{$json}'";
mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

?>

{
	"update_id":984154314,
	"message":{
		"message_id":20266,
		"from":{
			"id":217756119,
			"is_bot":false,
			"first_name":"u0410u043bu0435u043au0441u0430u043du0434u0440",
			"last_name":"u0428u0435u0432u0447u0435u043du043au043e",
			"username":"sheff4uk",
			"language_code":"ru"
		},
		"chat":{
			"id":217756119,
			"first_name":"u0410u043bu0435u043au0441u0430u043du0434u0440",
			"last_name":"u0428u0435u0432u0447u0435u043du043au043e",
			"username":"sheff4uk",
			"type":"private"
		},
		"date":1670390515,
		"text":"qqq"
	}
}