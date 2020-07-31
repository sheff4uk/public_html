<?
include_once "../checkrights.php";

$LB_ID = $_GET["LB_ID"];

$batch_select = "<option value=\"\"></option>";

$query = "
	SELECT LB.LB_ID
		,DATE_FORMAT(LB.batch_date, '%d.%m.%y') batch_date
		,DATE_FORMAT(LB.batch_time, '%H:%i') batch_time
		,CW.item
	FROM list__Batch LB
	JOIN CounterWeight CW ON CW.CW_ID = LB.CW_ID
	LEFT JOIN list__CubeTest LCT ON LCT.LB_ID = LB.LB_ID
	WHERE LCT.LCT_ID IS NULL
	".($LB_ID ? "OR LB.LB_ID = {$LB_ID}" : "")."
	ORDER BY LB.batch_date DESC, LB.batch_time DESC
	LIMIT 200
";

$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	$batch_select .= "<option value=\"{$row["LB_ID"]}\">{$row["batch_date"]} {$row["batch_time"]} {$row["item"]}</option>";
}

echo "$('#batch_select').html('{$batch_select}');";
?>
