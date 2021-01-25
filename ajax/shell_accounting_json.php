<?
include_once "../checkrights.php";

if( isset($_GET["SR_ID"]) ) {
	$SR_ID = $_GET["SR_ID"];

	$query = "
		SELECT SR.sr_date
			,SR.CW_ID
			,SR.sr_cnt
			,SR.exfolation
			,SR.crack
			,SR.chipped
			,SR.batch_number
		FROM shell__Reject SR
		WHERE SR.SR_ID = {$SR_ID}
	";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$row = mysqli_fetch_array($res);
	$SR_data = array( "sr_date"=>$row["sr_date"], "CW_ID"=>$row["CW_ID"], "sr_cnt"=>$row["sr_cnt"], "exfolation"=>$row["exfolation"], "crack"=>$row["crack"], "chipped"=>$row["chipped"], "batch_number"=>$row["batch_number"] );

	echo json_encode($SR_data);
}
elseif( isset($_GET["SA_ID"]) ) {
	$SA_ID = $_GET["SA_ID"];

	$query = "
		SELECT SA.sa_date
			,SA.CW_ID
			,SA.sa_cnt
			,SA.actual_volume
			,SA.batch_number
		FROM shell__Arrival SA
		WHERE SA.SA_ID = {$SA_ID}
	";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$row = mysqli_fetch_array($res);
	$SA_data = array( "sa_date"=>$row["sa_date"], "CW_ID"=>$row["CW_ID"], "sa_cnt"=>$row["sa_cnt"], "actual_volume"=>$row["actual_volume"]/1000, "batch_number"=>$row["batch_number"] );

	echo json_encode($SA_data);
}

?>
