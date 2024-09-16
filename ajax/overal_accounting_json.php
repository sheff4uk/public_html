<?php
include_once "../checkrights.php";

$OA_ID = $_GET["OA_ID"];

$query = "
	SELECT OA.OI_ID
		,ABS(OA.oa_cnt) oa_cnt
		,DATE(OA.oa_date) oa_date
		,IFNULL(OA.correction, 0) correction
	FROM overal__Accounting OA
	WHERE OA.OA_ID = {$OA_ID}
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
$row = mysqli_fetch_array($res);
$OA_data = array( "OI_ID"=>$row["OI_ID"], "oa_cnt"=>$row["oa_cnt"], "oa_date"=>$row["oa_date"], "correction"=>$row["correction"] );

echo json_encode($OA_data);

?>
