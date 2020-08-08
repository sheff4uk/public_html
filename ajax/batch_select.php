<?
include_once "../checkrights.php";

$LB_ID = $_GET["LB_ID"];

$batch_select = "<option value=\"\"></option>";

// 24 часа
$query = "
	SELECT LB.LB_ID
		,DATE_FORMAT(LB.batch_date, '%d.%m.%y') batch_date
		,DATE_FORMAT(LB.batch_time, '%H:%i') batch_time
		,CW.item
	FROM list__Batch LB
	JOIN CounterWeight CW ON CW.CW_ID = LB.CW_ID
	LEFT JOIN list__CubeTest LCT ON LCT.LB_ID = LB.LB_ID AND LCT.type = 1
	WHERE LB.test = 1
		AND (
			LCT.LCT_ID IS NULL
			".($LB_ID ? "OR LB.LB_ID = {$LB_ID}" : "")."
		)
	ORDER BY LB.batch_date DESC, LB.batch_time DESC
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

$batch_select .= "<optgroup label=\"24 часа\">";
while( $row = mysqli_fetch_array($res) ) {
	$batch_select .= "<option value=\"{$row["LB_ID"]}\" type=\"1\">{$row["batch_date"]} {$row["batch_time"]} {$row["item"]}</option>";
}
$batch_select .= "</optgroup>";

// 72 часа
$query = "
	SELECT LB.LB_ID
		,DATE_FORMAT(LB.batch_date, '%d.%m.%y') batch_date
		,DATE_FORMAT(LB.batch_time, '%H:%i') batch_time
		,CW.item
	FROM list__Batch LB
	JOIN CounterWeight CW ON CW.CW_ID = LB.CW_ID
	LEFT JOIN list__CubeTest LCT ON LCT.LB_ID = LB.LB_ID AND LCT.type = 2
	WHERE LB.test = 1
		AND (
			LCT.LCT_ID IS NULL
			".($LB_ID ? "OR LB.LB_ID = {$LB_ID}" : "")."
		)
	ORDER BY LB.batch_date DESC, LB.batch_time DESC
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

$batch_select .= "<optgroup label=\"72 часа\">";
while( $row = mysqli_fetch_array($res) ) {
	$batch_select .= "<option value=\"{$row["LB_ID"]}\" type=\"2\">{$row["batch_date"]} {$row["batch_time"]} {$row["item"]}</option>";
}
$batch_select .= "</optgroup>";

echo "$('#batch_select').html('{$batch_select}');";
?>
