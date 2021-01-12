<?
include_once "../checkrights.php";

if( isset($_GET["PR_ID"]) ) {
	$PR_ID = $_GET["PR_ID"];

	$query = "
		SELECT PR.pr_date
			,PR.CB_ID
			,PR.pr_cnt
			,PR.pr_reject
		FROM pallet__Return PR
		WHERE PR.PR_ID = {$PR_ID}
	";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$row = mysqli_fetch_array($res);
	$PR_data = array( "pr_date"=>$row["pr_date"], "CB_ID"=>$row["CB_ID"], "pr_cnt"=>$row["pr_cnt"], "pr_reject"=>$row["pr_reject"] );

	echo json_encode($PR_data);
}
elseif( isset($_GET["PA_ID"]) ) {
	$PA_ID = $_GET["PA_ID"];

	$query = "
		SELECT PA.pa_date
			,PA.pa_cnt
			,PA.pallet_cost
		FROM pallet__Arrival PA
		WHERE PA.PA_ID = {$PA_ID}
	";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$row = mysqli_fetch_array($res);
	$PA_data = array( "pa_date"=>$row["pa_date"], "pa_cnt"=>$row["pa_cnt"], "pallet_cost"=>$row["pallet_cost"] );

	echo json_encode($PA_data);
}

?>
