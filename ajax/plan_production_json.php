<?
include_once "../checkrights.php";

$PP_ID = $_GET["PP_ID"];

$query = "
	SELECT PP.pp_date
		,PP.shift
		,PP.CW_ID
		,PP.batches
		,PP.batches * CW.fillings fillings
		,PP.batches * CW.fillings * CW.in_cassette amount
	FROM plan__Production PP
	JOIN CounterWeight CW ON CW.CW_ID = PP.CW_ID
	WHERE PP.PP_ID = {$PP_ID}
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
$row = mysqli_fetch_array($res);
$PP_data = array( "pp_date"=>$row["pp_date"], "shift"=>$row["shift"], "CW_ID"=>$row["CW_ID"], "batches"=>$row["batches"], "fillings"=>$row["fillings"], "amount"=>$row["amount"] );

echo json_encode($PP_data);
?>
