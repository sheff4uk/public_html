<?
include_once "../checkrights.php";

$LB_ID = $_GET["LB_ID"];

$query = "
	SELECT LF.LF_ID
		,LF.cassette
		,IF(LO.LO_ID OR LP.LP_ID, 1, 0) is_link
	FROM list__Filling LF
	LEFT JOIN list__Opening LO ON LO.LF_ID = LF.LF_ID
	LEFT JOIN list__Packing LP ON LP.LF_ID = LF.LF_ID
	WHERE LF.LB_ID = {$LB_ID}
	ORDER BY LF.LF_ID
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) )
{
	$cassette .= "<input type=\'number\' min=\'1\' max=\'{$cassetts}\' name=\'cassette[{$row["LF_ID"]}]\' value=\'{$row["cassette"]}\' required ".($row["is_link"] ? "readonly" : "").">".($row["is_link"] ? '<i id="date_notice" class="fas fa-question-circle" style="position: absolute; right: 10px; line-height: 24px;" title="Номер кассеты не редактируется так как есть связанные этапы расформовки или упаковки."></i>' : '');
}

echo "$('#fillings').html('{$cassette}');";
?>
