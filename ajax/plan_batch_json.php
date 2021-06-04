<?
include_once "../checkrights.php";

$pb_date = $_GET["pb_date"];

$query = "
	SELECT CW.CW_ID
		,PB.batches
		,PB.fact_batches
		,IFNULL(PB.fillings_per_batch, CW.fillings) fillings
		,IFNULL(PB.in_cassette, CW.in_cassette) in_cassette
		,PB.PB_ID
		,IFNULL(PB.batches, PPB.batches) `placeholder`
	FROM CounterWeight CW
	LEFT JOIN plan__Batch PB ON PB.CW_ID = CW.CW_ID AND PB.pb_date = '{$pb_date}'
	LEFT JOIN plan__Batch PPB ON PPB.CW_ID = CW.CW_ID AND PPB.pb_date = ADDDATE('{$pb_date}', -1)
		AND YEARWEEK(PPB.pb_date, 1) = YEARWEEK('{$pb_date}', 1)
	ORDER BY CW.CW_ID
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
$PB_data = array();
while( $row = mysqli_fetch_array($res) ) {
	$PB_data[] = array( "CW_ID"=>$row["CW_ID"], "batches"=>$row["batches"], "fact_batches"=>$row["fact_batches"], "fillings"=>$row["fillings"], "in_cassette"=>$row["in_cassette"], "PB_ID"=>$row["PB_ID"], "placeholder"=>$row["placeholder"] );
}

echo json_encode($PB_data);
?>
