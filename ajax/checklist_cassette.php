<?
include_once "../checkrights.php";

$LB_ID = $_GET["LB_ID"];

$query = "
	SELECT LP.LP_ID
		,LP.LB_ID
		,cassette
	FROM list__Pourings LP
	WHERE LP.LB_ID = {$LB_ID}
	ORDER BY LP.LP_ID
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) )
{
	$cassette .= "<input type=\'number\' min=\'1\' max=\'200\' name=\'cassette[{$row["LP_ID"]}]\' value=\'{$row["cassette"]}\' required>";
}

echo "$('#fillings').html('{$cassette}');";
?>
