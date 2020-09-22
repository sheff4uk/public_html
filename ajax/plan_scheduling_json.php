<?
include_once "../checkrights.php";

$PS_ID = $_GET["PS_ID"];

$query = "
	SELECT PS.ps_date
		,PS.CW_ID
		,PS.pallets
		,PS.pallets * CW.in_pallet amount
	FROM plan__Scheduling PS
	JOIN CounterWeight CW ON CW.CW_ID = PS.CW_ID
	WHERE PS.PS_ID = {$PS_ID}
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
$row = mysqli_fetch_array($res);
$PS_data = array( "ps_date"=>$row["ps_date"], "CW_ID"=>$row["CW_ID"], "pallets"=>$row["pallets"], "amount"=>$row["amount"] );

echo json_encode($PS_data);
?>
