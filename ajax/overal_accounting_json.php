<?
include_once "../checkrights.php";

$OA_ID = $_GET["OA_ID"];

$query = "
	SELECT OA.OI_ID
		,ABS(OA.oa_cnt) oa_cnt
		,OA.oa_date
	FROM overal__Accounting OA
	WHERE OA.OA_ID = {$OA_ID}
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
$row = mysqli_fetch_array($res);
$OA_data = array( "OI_ID"=>$row["OI_ID"], "oa_cnt"=>$row["oa_cnt"], "oa_date"=>$row["oa_date"] );

echo json_encode($OA_data);

?>
