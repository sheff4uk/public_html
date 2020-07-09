<?
include_once "../checkrights.php";

$LO_ID = $_GET["LO_ID"];

$query = "
	SELECT LO.LF_ID
		,LO.o_post
		,LO.o_date
		,LO.o_time
		,LO.o_not_spill
		,LO.o_crack
		,LO.o_chipped
		,LO.o_def_form
		,LO.weight1/1000 weight1
		,LO.weight2/1000 weight2
		,LO.weight3/1000 weight3
	FROM list__Opening LO
	WHERE LO.LO_ID = {$LO_ID}
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
$row = mysqli_fetch_array($res);
$weight1 = (float)$row["weight1"];
$weight2 = (float)$row["weight2"];
$weight3 = (float)$row["weight3"];
$LO_data = array( "LF_ID"=>$row["LF_ID"], "o_post"=>$row["o_post"], "o_date"=>$row["o_date"], "o_time"=>$row["o_time"], "o_not_spill"=>$row["o_not_spill"], "o_crack"=>$row["o_crack"], "o_chipped"=>$row["o_chipped"], "o_def_form"=>$row["o_def_form"], "weight1"=>$weight1, "weight2"=>$weight2, "weight3"=>$weight3 );

echo json_encode($LO_data);
?>
