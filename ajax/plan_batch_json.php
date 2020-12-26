<?
include_once "../checkrights.php";

$pb_date = $_GET["pb_date"];

$query = "
	SELECT CW.CW_ID
		,PB.batches
		,PB.fakt
		,PB.PB_ID
	FROM CounterWeight CW
	LEFT JOIN plan__Batch PB ON PB.CW_ID = CW.CW_ID AND PB.pb_date = '{$pb_date}'
	ORDER BY CW.CW_ID
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
$PB_data = array();
while( $row = mysqli_fetch_array($res) ) {
	$PB_data[] = array( "CW_ID"=>$row["CW_ID"], "batches"=>$row["batches"], "fakt"=>$row["fakt"], "PB_ID"=>$row["PB_ID"] );
}

echo json_encode($PB_data);
?>
