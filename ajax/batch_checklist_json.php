<?
include_once "../checkrights.php";

$BC_ID = $_GET["BC_ID"];

$query = "
	SELECT BC.CW_ID
		,DATE(BC.batch_date) batch_date
		,BC.batch_num
		,batch_num
		,iron_oxide_weight
		,iron_oxide
		,sand
		,cement
		,water
		,mix_weight
	FROM BatchChecklist BC
	WHERE BC.BC_ID = {$BC_ID}
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) )
{
	$BC_data = array( "CW_ID"=>$row["CW_ID"], "batch_date"=>$row["batch_date"], "batch_num"=>$row["batch_num"], "iron_oxide_weight"=>$row["iron_oxide_weight"], "iron_oxide"=>$row["iron_oxide"], "sand"=>$row["sand"], "cement"=>$row["cement"], "water"=>$row["water"], "mix_weight"=>$row["mix_weight"] );
}

echo json_encode($BC_data);

?>
