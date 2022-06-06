<?
// new filename
$filename = 'pic_'.date('YmdHis') . '.jpeg';

$url = '';
if( move_uploaded_file($_FILES['webcam']['tmp_name'],'upload/'.$filename) ){
	$url = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/upload/' . $filename;

	include_once "../config.php";
	$query = "
		UPDATE TimeTracking
		SET ".($_GET["status"] ? "photo_start" : "photo_stop")." = '{$filename}'
		WHERE TT_ID = {$_GET["tt_id"]}
	";
	mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
}

// Return image url
echo $url;
?>
