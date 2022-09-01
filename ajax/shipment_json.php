<?
include_once "../checkrights.php";

$LS_ID = $_GET["LS_ID"];

$query = "
	SELECT LS.ls_date
		,LS.CWP_ID
		,LS.PN_ID
		,LS.pallets
		,LS.in_pallet
		,LS.pallets * LS.in_pallet amount
	FROM list__Shipment LS
	WHERE LS.LS_ID = {$LS_ID}
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
$row = mysqli_fetch_array($res);
$PS_data = array( "ls_date"=>$row["ls_date"], "CWP_ID"=>$row["CWP_ID"], "PN_ID"=>$row["PN_ID"], "pallets"=>$row["pallets"], "in_pallet"=>$row["in_pallet"], "amount"=>$row["amount"] );

echo json_encode($PS_data);
?>
