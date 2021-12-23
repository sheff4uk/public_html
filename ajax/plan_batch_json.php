<?
include_once "../checkrights.php";

$year = $_GET["year"];
$cycle = $_GET["cycle"];
$F_ID = $_GET["F_ID"];

$query = "
	SELECT CW.CW_ID
		,PB.batches
		,PB.fact_batches
		,IFNULL(PB.fillings, MF.fillings) fillings
		,IFNULL(PB.per_batch, MF.per_batch) per_batch
		,IFNULL(PB.in_cassette, MF.in_cassette) in_cassette
		,PB.PB_ID
		,IFNULL(PB.batches, (SELECT batches FROM plan__Batch WHERE CW_ID = CW.CW_ID AND year = {$year} AND cycle < {$cycle} ORDER BY cycle DESC LIMIT 1)) `placeholder`
	FROM MixFormula MF
	JOIN CounterWeight CW ON CW.CW_ID = MF.CW_ID
	LEFT JOIN plan__Batch PB ON PB.CW_ID = CW.CW_ID AND PB.year = {$year} AND PB.cycle = {$cycle} AND PB.F_ID = MF.F_ID
	WHERE MF.F_ID = {$F_ID}
	ORDER BY CW.CW_ID
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
$PB_data = array();
while( $row = mysqli_fetch_array($res) ) {
	$PB_data[] = array( "CW_ID"=>$row["CW_ID"], "batches"=>$row["batches"], "fact_batches"=>$row["fact_batches"], "fillings"=>$row["fillings"], "per_batch"=>$row["per_batch"], "in_cassette"=>$row["in_cassette"], "PB_ID"=>$row["PB_ID"], "placeholder"=>$row["placeholder"] );
}

echo json_encode($PB_data);
?>
