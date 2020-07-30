<?
include_once "../checkrights.php";

$LCT_ID = $_GET["LCT_ID"];

$query = "
	SELECT LCT.test_date
		,LCT.CW_ID
		,24_test_time
		,24_cube_weight
		,24_pressure
		,72_test_time
		,72_cube_weight
		,72_pressure
	FROM list__CubeTest LCT
	WHERE LCT.LCT_ID = {$LCT_ID}
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
$row = mysqli_fetch_array($res);
$LCT_data = array( "test_date"=>$row["test_date"], "CW_ID"=>$row["CW_ID"], "24_test_time"=>$row["24_test_time"], "24_cube_weight"=>$row["24_cube_weight"]/1000, "24_pressure"=>$row["24_pressure"], "72_test_time"=>$row["72_test_time"], "72_cube_weight"=>$row["72_cube_weight"]/1000, "72_pressure"=>$row["72_pressure"] );

echo json_encode($LCT_data);
?>
