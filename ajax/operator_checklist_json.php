<?
include_once "../checkrights.php";

$OC_ID = $_GET["OC_ID"];

$query = "
	SELECT OC.CW_ID
		,DATE(OC.batch_date) batch_date
		,OC.batch_num
		,OC.batch_num
		,OC.iron_oxide_weight
		,OC.iron_oxide
		,OC.sand
		,OC.cement
		,OC.water
		,OC.mix_weight
		,OC.OP_ID
		,OC.sOP_ID
	FROM OperatorChecklist OC
	WHERE OC.OC_ID = {$OC_ID}
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) )
{
	$OC_data = array( "CW_ID"=>$row["CW_ID"], "batch_date"=>$row["batch_date"], "batch_num"=>$row["batch_num"], "iron_oxide_weight"=>$row["iron_oxide_weight"], "iron_oxide"=>$row["iron_oxide"], "sand"=>$row["sand"], "cement"=>$row["cement"], "water"=>$row["water"], "mix_weight"=>$row["mix_weight"], "OP_ID"=>$row["OP_ID"], "sOP_ID"=>$row["sOP_ID"] );
}

echo json_encode($OC_data);

?>
