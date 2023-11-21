<?
include "config.php";

$json = file_get_contents('php://input');
//$data = json_decode(file_get_contents('php://input')); // получаем JSON
//$text = $data->{'message'}->{'text'};

//$query = "INSERT INTO test SET command = '{$text}'";
$query = "INSERT INTO test SET command = '{$json}'";
mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

?>