<?
include_once "../checkrights.php";

$LCT_ID = $_GET["LCT_ID"];

$query = "
	SELECT LCT.LB_ID
		,LCT.test_date
		,LCT.test_time
		,cube_weight
		,pressure
	FROM list__CubeTest LCT
	WHERE LCT.LCT_ID = {$LCT_ID}
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
$row = mysqli_fetch_array($res);
$LCT_data = array( "LB_ID"=>$row["LB_ID"], "test_date"=>$row["test_date"], "test_time"=>$row["test_time"], "cube_weight"=>$row["cube_weight"]/1000, "pressure"=>$row["pressure"] );

echo json_encode($LCT_data);
?>
