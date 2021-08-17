<?
include_once "../checkrights.php";

$day = $_GET["day"];

$query = "
	SELECT SL.master
		,SL.operator
	FROM ShiftLog SL
	WHERE DATE_FORMAT(SL.working_day, '%d.%m.%Y') LIKE '{$day}'
		AND SL.shift = 1
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
$row1 = mysqli_fetch_array($res);

$query = "
	SELECT SL.master
		,SL.operator
	FROM ShiftLog SL
	WHERE DATE_FORMAT(SL.working_day, '%d.%m.%Y') LIKE '{$day}'
		AND SL.shift = 2
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
$row2 = mysqli_fetch_array($res);

$SL_data = array(
	"1" => array( "master"=>$row1["master"], "operator"=>$row1["operator"] ),
	"2" => array( "master"=>$row2["master"], "operator"=>$row2["operator"] )
);

echo json_encode($SL_data);
?>
