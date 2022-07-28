<?
include_once "../checkrights.php";

$MF_ID = $_GET["MF_ID"];

$query = "
	SELECT MF.s_fraction
		,MF.l_fraction
		,MF.iron_oxide
		,MF.slag10
		,MF.slag20
		,MF.slag020
		,MF.slag30
		,MF.sand
		,MF.crushed_stone
		,MF.cement
		,MF.plasticizer
		,MF.water
	FROM MixFormula MF
	WHERE MF.MF_ID = {$MF_ID}
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
$row = mysqli_fetch_array($res);
$MF_data = array( "s_fraction"=>$row["s_fraction"], "l_fraction"=>$row["l_fraction"], "iron_oxide"=>$row["iron_oxide"], "slag10"=>$row["slag10"], "slag20"=>$row["slag20"], "slag020"=>$row["slag020"], "slag30"=>$row["slag30"], "sand"=>$row["sand"], "crushed_stone"=>$row["crushed_stone"], "cement"=>$row["cement"], "plasticizer"=>$row["plasticizer"], "water"=>$row["water"] );

echo json_encode($MF_data);
?>
