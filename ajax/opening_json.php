<?
include_once "../checkrights.php";

$LO_ID = $_GET["LO_ID"];

$query = "
	SELECT LO.cassette
		,LO.o_post
		,LO.o_date
		,LO.o_time
		,LO.o_not_spill
		,LO.o_crack
		,LO.o_chipped
		,LO.o_def_form
		,LO.weight1
		,LO.weight2
		,LO.weight3
	FROM list__Opening LO
	WHERE LO.LO_ID = {$LO_ID}
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
$row = mysqli_fetch_array($res);
$LO_data = array( "cassette"=>$row["cassette"], "o_post"=>$row["o_post"], "o_date"=>$row["o_date"], "o_time"=>$row["o_time"], "o_not_spill"=>$row["o_not_spill"], "o_crack"=>$row["o_crack"], "o_chipped"=>$row["o_chipped"], "o_def_form"=>$row["o_def_form"], "weight1"=>$row["weight1"]/1000, "weight2"=>$row["weight2"]/1000, "weight3"=>$row["weight3"]/1000 );

echo json_encode($LO_data);
?>
