<?
include_once "../checkrights.php";

$LO_ID = $_GET["LO_ID"];

$query = "
	SELECT LO.cassette
		,DATE_FORMAT(LO.opening_time, '%d.%m.%Y') o_date
		,DATE_FORMAT(LO.opening_time, '%H:%i') o_time
		,LO.not_spill
		,LO.crack
		,LO.chipped
		,LO.def_form
		,LO.weight1
		,LO.weight2
		,LO.weight3
	FROM list__Opening LO
	WHERE LO.LO_ID = {$LO_ID}
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
$row = mysqli_fetch_array($res);
$LO_data = array( "cassette"=>$row["cassette"], "o_date"=>$row["o_date"], "o_time"=>$row["o_time"], "not_spill"=>$row["not_spill"], "crack"=>$row["crack"], "chipped"=>$row["chipped"], "def_form"=>$row["def_form"], "weight1"=>($row["weight1"] ? $row["weight1"]/1000 : ""), "weight2"=>($row["weight2"] ? $row["weight2"]/1000 : ""), "weight3"=>($row["weight3"] ? $row["weight3"]/1000 : "") );

echo json_encode($LO_data);
?>
