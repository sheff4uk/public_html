<?
include_once "../checkrights.php";

// Возврат поддонов
if( $_GET["type"] == "R" ) {
	$PR_ID = $_GET["ID"];

	$query = "
		SELECT PR.pr_date
			,PR.CB_ID
			,PR.pr_cnt
			,PR.pr_reject
			,PR.PN_ID
		FROM pallet__Return PR
		WHERE PR.PR_ID = {$PR_ID}
	";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$row = mysqli_fetch_array($res);
	$PR_data = array( "date"=>$row["pr_date"], "source"=>$row["CB_ID"], "cnt"=>$row["pr_cnt"], "broken"=>$row["pr_reject"], "PN_ID"=>$row["PN_ID"] );

	echo json_encode($PR_data);
}
// Приобретение поддонов
elseif( $_GET["type"] == "A" ) {
	$PA_ID = $_GET["ID"];

	$query = "
		SELECT PA.pa_date
			,PA.PS_ID
			,PA.pa_cnt
			,PA.pa_reject
			,PA.pallet_cost
		FROM pallet__Arrival PA
		WHERE PA.PA_ID = {$PA_ID}
	";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$row = mysqli_fetch_array($res);
	$PA_data = array( "date"=>$row["pa_date"], "source"=>$row["PS_ID"], "cnt"=>$row["pa_cnt"], "broken"=>$row["pa_reject"], "cost"=>$row["pallet_cost"] );

	echo json_encode($PA_data);
}
// Ремонт поддонов
elseif( $_GET["type"] == "F" ) {
	$PF_ID = $_GET["ID"];

	$query = "
		SELECT PF.PN_ID
			,PF.pd_date
			,PF.pd_cnt * -1 pd_cnt
		FROM pallet__Disposal PF
		WHERE PF.PD_ID = {$PF_ID}
	";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$row = mysqli_fetch_array($res);
	$PF_data = array( "PN_ID"=>$row["PN_ID"], "date"=>$row["pd_date"], "source"=>"", "cnt"=>$row["pd_cnt"] );

	echo json_encode($PF_data);
}
// Списание поддонов
elseif( $_GET["type"] == "D" ) {
	$PD_ID = $_GET["ID"];

	$query = "
		SELECT PD.PN_ID
			,PD.CB_ID
			,PD.pd_date
			,PD.pd_cnt
		FROM pallet__Disposal PD
		WHERE PD.PD_ID = {$PD_ID}
	";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$row = mysqli_fetch_array($res);
	$PD_data = array( "PN_ID"=>$row["PN_ID"], "CB_ID"=>$row["CB_ID"], "pd_date"=>$row["pd_date"], "pd_cnt"=>$row["pd_cnt"] );

	echo json_encode($PD_data);
}

?>
