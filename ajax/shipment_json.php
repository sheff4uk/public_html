<?
include_once "../checkrights.php";

$LS_ID = $_GET["LS_ID"];

$query = "
	SELECT LS.ls_date
		,LS.CW_ID
		,LS.pallets
		,LS.pallets * CW.in_pallet amount
	FROM list__Shipment LS
	JOIN CounterWeight CW ON CW.CW_ID = LS.CW_ID
	WHERE LS.LS_ID = {$LS_ID}
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
$row = mysqli_fetch_array($res);
$PS_data = array( "ls_date"=>$row["ls_date"], "CW_ID"=>$row["CW_ID"], "pallets"=>$row["pallets"], "amount"=>$row["amount"] );

echo json_encode($PS_data);
?>
