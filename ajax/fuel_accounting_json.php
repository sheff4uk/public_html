<?php
include_once "../checkrights.php";

if( isset($_GET["FF_ID"]) ) {
	$FF_ID = $_GET["FF_ID"];

	$query = "
		SELECT FF.ff_date
			,FF.ff_time
			,FF.fuel_meter_value - FF.ff_cnt last_fuel_meter_value
			,FF.fuel_meter_value
			,FF.ff_cnt
			,FF.FD_ID
			,FF.hour_meter_value
			,IFNULL(FT.FT_ID, 0) last
		FROM fuel__Filling FF
		LEFT JOIN fuel__Tank FT ON FT.FT_ID = FF.FT_ID AND FT.last_fuel_meter_value = FF.fuel_meter_value
		WHERE FF.FF_ID = {$FF_ID}
	";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$row = mysqli_fetch_array($res);
	$PR_data = array( "ff_date"=>$row["ff_date"], "ff_time"=>$row["ff_time"], "last_fuel_meter_value"=>$row["last_fuel_meter_value"], "fuel_meter_value"=>$row["fuel_meter_value"], "ff_cnt"=>$row["ff_cnt"], "FD_ID"=>$row["FD_ID"], "hour_meter_value"=>$row["hour_meter_value"], "last"=>$row["last"] );

	echo json_encode($PR_data);
}
elseif( isset($_GET["FA_ID"]) ) {
	$FA_ID = $_GET["FA_ID"];

	$query = "
		SELECT FA.fa_date
			,FA.fa_time
			,FA.fa_cnt
		FROM fuel__Arrival FA
		WHERE FA.FA_ID = {$FA_ID}
	";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$row = mysqli_fetch_array($res);
	$FA_data = array( "fa_date"=>$row["fa_date"], "fa_time"=>$row["fa_time"], "fa_cnt"=>$row["fa_cnt"] );

	echo json_encode($FA_data);
}
elseif( isset($_GET["FT_ID"]) ) {
	$FT_ID = $_GET["FT_ID"];
	$query = "
		SELECT FT.last_fuel_meter_value
		FROM fuel__Tank FT
		WHERE FT.FT_ID = {$FT_ID}
	";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$row = mysqli_fetch_array($res);
	$last_fuel_meter_value = $row["last_fuel_meter_value"];
	$FT_data = array( "last_fuel_meter_value"=>$row["last_fuel_meter_value"]);

	echo json_encode($FT_data);
}

?>
