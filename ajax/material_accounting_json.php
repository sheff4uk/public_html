<?
include_once "../checkrights.php";

$MA_ID = $_GET["MA_ID"];

$query = "
	SELECT MA.ma_date
		,MA.F_ID
		,MA.MN_ID
		,MA.MS_ID
		,MA.MC_ID
		,MA.invoice_number
		,MA.car_number
		,MA.ma_cnt
		,MA.ma_cost
	FROM material__Arrival MA
	WHERE MA.MA_ID = {$MA_ID}
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
$row = mysqli_fetch_array($res);
$ma_data = array( "ma_date"=>$row["ma_date"], "F_ID"=>$row["F_ID"], "MN_ID"=>$row["MN_ID"], "MS_ID"=>$row["MS_ID"], "MC_ID"=>$row["MC_ID"], "invoice_number"=>$row["invoice_number"], "car_number"=>$row["car_number"], "ma_cnt"=>$row["ma_cnt"], "ma_cost"=>$row["ma_cost"] );

echo json_encode($ma_data);
?>
