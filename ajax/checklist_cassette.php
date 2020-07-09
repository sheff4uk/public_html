<?
include_once "../checkrights.php";

$LB_ID = $_GET["LB_ID"];

$query = "
	SELECT
		LF.LF_ID,
		LF.cassette
	FROM list__Filling LF
	WHERE LF.LB_ID = {$LB_ID}
	ORDER BY LF.LF_ID
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) )
{
	$cassette .= "<input type=\'number\' min=\'1\' max=\'200\' name=\'cassette[{$row["LF_ID"]}]\' value=\'{$row["cassette"]}\' required>";
}

echo "$('#fillings').html('{$cassette}');";
?>
