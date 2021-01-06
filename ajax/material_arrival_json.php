<?
include_once "../checkrights.php";

$MA_ID = $_GET["MA_ID"];

$query = "
	SELECT MA.ma_date
		,MA.material_name
		,MA.supplier
		,MA.invoice_number
		,MA.car_number
		,MA.batch_number
		,MA.certificate_number
		,MA.ma_cnt
	FROM material__Arrival MA
	WHERE MA.MA_ID = {$MA_ID}
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
$row = mysqli_fetch_array($res);
$ma_data = array( "ma_date"=>$row["ma_date"], "material_name"=>$row["material_name"], "supplier"=>$row["supplier"], "invoice_number"=>$row["invoice_number"], "car_number"=>$row["car_number"], "batch_number"=>$row["batch_number"], "certificate_number"=>$row["certificate_number"], "ma_cnt"=>$row["ma_cnt"] );

echo json_encode($ma_data);
?>
