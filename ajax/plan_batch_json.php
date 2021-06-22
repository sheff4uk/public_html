<?
include_once "../checkrights.php";

$year = $_GET["year"];
$cycle = $_GET["cycle"];

$query = "
	SELECT CW.CW_ID
		,PB.batches
		,PB.fact_batches
		,IFNULL(PB.fillings_per_batch, CW.fillings) fillings
		,IFNULL(PB.in_cassette, CW.in_cassette) in_cassette
		,PB.PB_ID
		,IFNULL(PB.batches, (SELECT batches FROM plan__Batch WHERE CW_ID = CW.CW_ID AND year = {$year} AND cycle < {$cycle} ORDER BY cycle DESC LIMIT 1)) `placeholder`
	FROM CounterWeight CW
	LEFT JOIN plan__Batch PB ON PB.CW_ID = CW.CW_ID AND PB.year = {$year} AND PB.cycle = {$cycle}
	ORDER BY CW.CW_ID
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
$PB_data = array();
while( $row = mysqli_fetch_array($res) ) {
	$PB_data[] = array( "CW_ID"=>$row["CW_ID"], "batches"=>$row["batches"], "fact_batches"=>$row["fact_batches"], "fillings"=>$row["fillings"], "in_cassette"=>$row["in_cassette"], "PB_ID"=>$row["PB_ID"], "placeholder"=>$row["placeholder"] );
}

echo json_encode($PB_data);
?>
