<?
include "../config.php";

$RS_ID = $_GET["RS_ID"];

$query = "
	SELECT RS.CW_ID
		,CW.in_cassette
		,CW.min_weight
		,CW.max_weight

		,DATE(RS.filling_date) filling_date
		,TIME(RS.filling_date) filling_time
		,RS.batch
		,RS.cassette
		,RS.amount
		,RS.OP_ID
		,RS.sOP_ID

		,DATE(RS.decoupling_date) decoupling_date
		,TIME(RS.decoupling_date) decoupling_time
		,RS.d_amount
		,RS.d_not_spill
		,RS.d_crack
		,RS.d_chipped
		,RS.d_def_form
		,RS.d_post

		,DATE(RS.boxing_date) boxing_date
		,TIME(RS.boxing_date) boxing_time
		,RS.weight1
		,RS.weight2
		,RS.weight3
		,RS.b_amount
		,RS.b_not_spill
		,RS.b_crack
		,RS.b_chipped
		,RS.b_def_form
		,RS.b_post
	FROM RouteSheet RS
	JOIN CounterWeight CW ON CW.CW_ID = RS.CW_ID
	WHERE RS.RS_ID = {$RS_ID}
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) )
{
	$RS_data = array( "CW_ID"=>$row["CW_ID"], "in_cassette"=>$row["in_cassette"], "min_weight"=>$row["min_weight"], "max_weight"=>$row["max_weight"], "filling_date"=>$row["filling_date"], "filling_time"=>$row["filling_time"], "batch"=>$row["batch"], "cassette"=>$row["cassette"], "amount"=>$row["amount"], "OP_ID"=>$row["OP_ID"], "sOP_ID"=>$row["sOP_ID"], "decoupling_date"=>$row["decoupling_date"], "decoupling_time"=>$row["decoupling_time"], "d_amount"=>$row["d_amount"], "d_not_spill"=>$row["d_not_spill"], "d_crack"=>$row["d_crack"], "d_chipped"=>$row["d_chipped"], "d_def_form"=>$row["d_def_form"], "d_post"=>$row["d_post"], "boxing_date"=>$row["boxing_date"], "boxing_time"=>$row["boxing_time"], "weight1"=>$row["weight1"], "weight2"=>$row["weight2"], "weight3"=>$row["weight3"], "b_amount"=>$row["b_amount"], "b_not_spill"=>$row["b_not_spill"], "b_crack"=>$row["b_crack"], "b_chipped"=>$row["b_chipped"], "b_def_form"=>$row["b_def_form"], "b_post"=>$row["b_post"] );
}

echo json_encode($RS_data);

?>
