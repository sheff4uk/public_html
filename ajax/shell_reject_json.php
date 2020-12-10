<?
include_once "../checkrights.php";

$SR_ID = $_GET["SR_ID"];

$query = "
	SELECT SR.sr_date
		,SR.CW_ID
		,SR.sr_cnt
		,SR.exfolation
		,SR.crack
	FROM ShellReject SR
	WHERE SR.SR_ID = {$SR_ID}
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
$row = mysqli_fetch_array($res);
$SR_data = array( "sr_date"=>$row["sr_date"], "CW_ID"=>$row["CW_ID"], "sr_cnt"=>$row["sr_cnt"], "exfolation"=>$row["exfolation"], "crack"=>$row["crack"] );

echo json_encode($SR_data);
?>
