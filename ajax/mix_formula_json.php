<?php
include_once "../checkrights.php";

$MF_ID = $_GET["MF_ID"];

if( $_GET["type"] == "1" ) {
	$query = "
		SELECT MF.water
			,MF.min_density
			,MF.max_density
		FROM MixFormula MF
		WHERE MF.MF_ID = {$MF_ID}
	";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$row = mysqli_fetch_array($res);
	$MF_data = array(
		"water"=>$row["water"],
		"min_density"=>$row["min_density"]/1000,
		"max_density"=>$row["max_density"]/1000
	);

	echo json_encode($MF_data);
}
elseif( $_GET["type"] == "2" ) {
	$query = "
		SELECT MN.MN_ID
			,MFM.quantity
		FROM material__Name MN
		LEFT JOIN MixFormulaMaterial MFM ON MFM.MN_ID = MN.MN_ID
			AND MFM.MF_ID = {$MF_ID}
		WHERE MN.step IS NOT NULL
	";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$MFM_data = array();
	while( $row = mysqli_fetch_array($res) ) {
		$MFM_data[$row["MN_ID"]] = $row["quantity"];
	}

	echo json_encode($MFM_data);
}
?>
