<?
include_once "../checkrights.php";

$LO_ID = $_GET["LO_ID"];

$html .= "
	<table style='table-layout: fixed; width: 100%; border-collapse: collapse; border-spacing: 0px; text-align: center;'>
		<thead style='word-wrap: break-word;'>
			<tr>
				<th>Вес</th>
				<th>Время</th>
				<th>Дефект</th>
				<th>Партия</th>
				<th>Пост</th>
			</tr>
		</thead>
		<tbody>
";

$query = "
	SELECT LW.weight
		,LW.nextID
		,DATE_FORMAT(LW.weighing_time, '%H:%i:%s') weighing_time_format
		,LW.goodsID
		,LW.RN
		,WT.post
	FROM list__Weight LW
	LEFT JOIN WeighingTerminal WT ON WT.WT_ID = LW.WT_ID
	WHERE LW.LO_ID = {$LO_ID}
	ORDER BY weighing_time
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	switch ($row["goodsID"]) {
		case 2:
			$defect = "непролив";
			break;
		case 3:
			$defect = "мех. трещина";
			break;
		case 4:
			$defect = "усад. трещина";
			break;
		case 5:
			$defect = "скол";
			break;
		case 6:
			$defect = "дефект формы";
			break;
		case 7:
			$defect = "дефект сборки";
			break;
		default:
			$defect = "";
			break;
	}

	$html .= "
		<tr>
			<td>".($row["weight"]/1000)."</td>
			<td>{$row["weighing_time_format"]}</td>
			<td>{$defect}</td>
			<td>{$row["RN"]}</td>
			<td>{$row["post"]}</td>
		</tr>
	";
}

$html .= "
		</tbody>
	</table>
";

$html = str_replace("\n", "", addslashes($html));
echo "$('#weight_form fieldset').html('{$html}');";
?>
