<?
include_once "../checkrights.php";

$LB_ID = $_GET["LB_ID"];

$query = "
	SELECT LB.CW_ID
		,LB.OP_ID
		,LB.batch_date
		,LB.batch_time
		,LB.comp_density
		,LB.mix_density
		,LB.iron_oxide
		,LB.sand
		,LB.crushed_stone
		,LB.cement
		,LB.water
		,LB.underfilling
		,LB.test
		,IF(MAX(LO.LO_ID) OR MAX(LP.LP_ID), 1, 0) is_link
		,IF(LCT.LCT_ID, 1, 0) is_test
	FROM list__Batch LB
	JOIN list__Filling LF ON LF.LB_ID = LB.LB_ID
	LEFT JOIN list__Opening LO ON LO.LF_ID = LF.LF_ID
	LEFT JOIN list__Packing LP ON LP.LF_ID = LF.LF_ID
	LEFT JOIN list__CubeTest LCT ON LCT.LB_ID = LB.LB_ID
	WHERE LB.LB_ID = {$LB_ID}
	GROUP BY LB.LB_ID
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) )
{
	$LB_data = array( "CW_ID"=>$row["CW_ID"], "OP_ID"=>$row["OP_ID"], "batch_date"=>$row["batch_date"], "batch_time"=>$row["batch_time"], "comp_density"=>$row["comp_density"]/1000, "mix_density"=>$row["mix_density"]/1000, "iron_oxide"=>$row["iron_oxide"], "sand"=>$row["sand"], "crushed_stone"=>$row["crushed_stone"], "cement"=>$row["cement"], "water"=>$row["water"], "underfilling"=>$row["underfilling"], "test"=>$row["test"], "is_link"=>$row["is_link"], "is_test"=>$row["is_test"] );
}

echo json_encode($LB_data);

?>
