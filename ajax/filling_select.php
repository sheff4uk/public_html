<?
include_once "../checkrights.php";

$LF_ID = $_GET["LF_ID"];
$type = $_GET["type"]; // 1-расформовка, 2-упаковка

$filling_select = "<option value=\"\"></option>";

if( $type == 1 ) {
	$query = "
		SELECT LF.LF_ID
			,DATE_FORMAT(PB.pb_date, '%d.%m.%y') pb_date_format
			,LF.cassette
			,CW.item
		FROM list__Batch LB
		JOIN plan__Batch PB ON PB.PB_ID = LB.PB_ID
		JOIN CounterWeight CW ON CW.CW_ID = PB.CW_ID
		JOIN list__Filling LF ON LF.LB_ID = LB.LB_ID
		LEFT JOIN list__Opening LO ON LO.LF_ID = LF.LF_ID
		WHERE LO.LO_ID IS NULL
		".($LF_ID ? "OR LF.LF_ID = {$LF_ID}" : "")."
		ORDER BY PB.pb_date, LF.cassette
	";
}

if( $type == 2 ) {
	$query = "
		SELECT LF.LF_ID
			,DATE_FORMAT(PB.pb_date, '%d.%m.%y') pb_date_format
			,LF.cassette
			,CW.item
		FROM list__Batch LB
		JOIN plan__Batch PB ON PB.PB_ID = LB.PB_ID
		JOIN CounterWeight CW ON CW.CW_ID = PB.CW_ID
		JOIN list__Filling LF ON LF.LB_ID = LB.LB_ID
		LEFT JOIN list__Packing LP ON LP.LF_ID = LF.LF_ID
		WHERE LP.LP_ID IS NULL
		".($LF_ID ? "OR LF.LF_ID = {$LF_ID}" : "")."
		ORDER BY PB.pb_date DESC, LF.cassette
	";
}

$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	$filling_select .= "<option value=\"{$row["LF_ID"]}\">{$row["pb_date_format"]} [{$row["cassette"]}] {$row["item"]}</option>";
}

echo "$('#filling_select').html('{$filling_select}');";
echo "$('#filling_select').val('{$LF_ID}');";
echo "$('#filling_select').select2({ placeholder: 'Выберите заливку', language: 'ru' });";
// Костыль для Select2 чтобы работал поиск
echo "$.ui.dialog.prototype._allowInteraction = function (e) {return true;};";
?>
