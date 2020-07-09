<?
include_once "../checkrights.php";

$LP_ID = $_GET["LP_ID"];

$query = "
	SELECT LP.LF_ID
		,LP.p_post
		,LP.p_date
		,LP.p_time
		,LP.p_not_spill
		,LP.p_crack
		,LP.p_chipped
		,LP.p_def_form
	FROM list__Packing LP
	WHERE LP.LP_ID = {$LP_ID}
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
$row = mysqli_fetch_array($res);
$LP_data = array( "LF_ID"=>$row["LF_ID"], "p_post"=>$row["p_post"], "p_date"=>$row["p_date"], "p_time"=>$row["p_time"], "p_not_spill"=>$row["p_not_spill"], "p_crack"=>$row["p_crack"], "p_chipped"=>$row["p_chipped"], "p_def_form"=>$row["p_def_form"] );

echo json_encode($LP_data);
?>
