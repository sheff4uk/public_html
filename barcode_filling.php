<?php
$ip = $_SERVER['REMOTE_ADDR'];
include "config.php";

if( $ip == '78.138.173.64' ) {
	message_to_telegram("NBC filling", '217756119');
	die();
}
?>
