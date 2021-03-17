<?
include_once "../checkrights.php";

$MF_ID = $_GET["MF_ID"];

$query = "
	SELECT MF.CW_ID
		,MF.letter
		,MF.io_min
		,MF.io_max
		,MF.sn_min
		,MF.sn_max
		,MF.cs_min
		,MF.cs_max
		,MF.iron_oxide
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
$MF_data = array( "CW_ID"=>$row["CW_ID"], "letter"=>$row["letter"], "io_min"=>($row["io_min"] ? $row["io_min"]/1000 : ''), "io_max"=>($row["io_max"] ? $row["io_max"]/1000 : ''), "sn_min"=>($row["sn_min"] ? $row["sn_min"]/1000 : ''), "sn_max"=>($row["sn_max"] ? $row["sn_max"]/1000 : ''), "cs_min"=>($row["cs_min"] ? $row["cs_min"]/1000 : ''), "cs_max"=>($row["cs_max"] ? $row["cs_max"]/1000 : ''), "iron_oxide"=>$row["iron_oxide"], "sand"=>$row["sand"], "crushed_stone"=>$row["crushed_stone"], "cement"=>$row["cement"], "plasticizer"=>$row["plasticizer"], "water"=>$row["water"] );

echo json_encode($MF_data);
?>
