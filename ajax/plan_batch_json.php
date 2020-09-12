<?
include_once "../checkrights.php";

$PB_ID = $_GET["PB_ID"];

$query = "
	SELECT PB.pb_date
		,PB.CW_ID
		,PB.batches
		,PB.batches * CW.fillings fillings
		,PB.batches * CW.fillings * CW.in_cassette amount
	FROM plan__Batch PB
	JOIN CounterWeight CW ON CW.CW_ID = PB.CW_ID
	WHERE PB.PB_ID = {$PB_ID}
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
$row = mysqli_fetch_array($res);
$PB_data = array( "pb_date"=>$row["pb_date"], "CW_ID"=>$row["CW_ID"], "batches"=>$row["batches"], "fillings"=>$row["fillings"], "amount"=>$row["amount"] );

echo json_encode($PB_data);
?>
