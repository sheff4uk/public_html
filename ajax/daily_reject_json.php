<?
include_once "../checkrights.php";

$LDR_ID = $_GET["LDR_ID"];

$query = "
	SELECT LDR.reject_date
		,LDR.CW_ID
		,LDR.o_reject_cnt
		,LDR.p_reject_cnt
	FROM list__DailyReject LDR
	WHERE LDR.LDR_ID = {$LDR_ID}
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
$row = mysqli_fetch_array($res);
$LDR_data = array( "reject_date"=>$row["reject_date"], "CW_ID"=>$row["CW_ID"], "o_reject_cnt"=>$row["o_reject_cnt"], "p_reject_cnt"=>$row["p_reject_cnt"] );

echo json_encode($LDR_data);
?>
