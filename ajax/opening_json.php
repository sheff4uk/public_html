<?
include_once "../checkrights.php";

$LO_ID = $_GET["LO_ID"];

$query = "
	SELECT LO.cassette
		,DATE_FORMAT(LO.opening_time, '%d.%m.%Y') o_date
		,DATE_FORMAT(LO.opening_time, '%H:%i') o_time
		,LOD.not_spill
		,LOD.crack
		,LOD.crack_drying
		,LOD.chipped
		,LOD.def_form
		,LOD.def_assembly
	FROM list__Opening LO
	LEFT JOIN list__Opening_def LOD ON LOD.LO_ID = LO.LO_ID
	WHERE LO.LO_ID = {$LO_ID}
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
$row = mysqli_fetch_array($res);
$LO_data = array( "cassette"=>$row["cassette"], "o_date"=>$row["o_date"], "o_time"=>$row["o_time"], "not_spill"=>$row["not_spill"], "crack"=>$row["crack"], "crack_drying"=>$row["crack_drying"], "chipped"=>$row["chipped"], "def_form"=>$row["def_form"], "def_assembly"=>$row["def_assembly"] );

echo json_encode($LO_data);
?>
