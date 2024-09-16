<?php
// new filename
$filename = 'pic_'.date('YmdHis') . '.jpeg';

$url = '';
if( move_uploaded_file($_FILES['webcam']['tmp_name'],'upload/'.$filename) ){
	$url = dirname($_SERVER['REQUEST_URI']) . '/upload/' . $filename;

	include_once "../config.php";
	$query = "
		UPDATE TimeReg
		SET tr_photo = '{$filename}'
		WHERE TR_ID = {$_GET["tr_id"]}
	";
	mysqli_query( $mysqli, $query );
}

// Return image url
echo $url;
?>
