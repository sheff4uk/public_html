<?
include_once "../checkrights.php";

$LO_ID = $_GET["LO_ID"];

$html .= "
	<table style='table-layout: fixed; width: 100%; border-collapse: collapse; border-spacing: 0px; text-align: center;'>
		<thead style='word-wrap: break-word;'>
			<tr>
				<th>№ п/п</th>
				<th>Вес, г</th>
				<th>Время</th>
				<th>Дефект</th>
				<th>Партия</th>
				<th>Пост</th>
				<th>Штрих-код</th>
			</tr>
		</thead>
		<tbody>
";

$query = "
	SELECT LW.weight
		,IF(LW.weight BETWEEN ROUND(CW.min_weight/100*101) AND ROUND(CW.max_weight/100*101), 0, IF(LW.weight > ROUND(CW.max_weight/100*101), LW.weight - ROUND(CW.max_weight/100*101), LW.weight - ROUND(CW.min_weight/100*101))) weight_diff
		,LW.nextID
		,DATE_FORMAT(LW.weighing_time, '%H:%i:%s') weighing_time_format
		,LW.goodsID
		,LW.RN
		,WT.post
		,CONCAT(LPAD(LW.WT_ID, 8, '0'), LPAD(LW.nextID, 8, '0')) barcode
	FROM list__Weight LW
	LEFT JOIN WeighingTerminal WT ON WT.WT_ID = LW.WT_ID
	LEFT JOIN list__Opening LO ON LO.LO_ID = LW.LO_ID
	LEFT JOIN list__Filling LF ON LF.LF_ID = LO.LF_ID
	LEFT JOIN list__Batch LB ON LB.LB_ID = LF.LB_ID
	LEFT JOIN plan__Batch PB ON PB.PB_ID = LB.PB_ID
	LEFT JOIN CounterWeight CW ON CW.CW_ID = PB.CW_ID
	WHERE LW.LO_ID = {$LO_ID}
	ORDER BY weighing_time
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
$i = 0;
while( $row = mysqli_fetch_array($res) ) {
	$i++; //Порядковый номер
	switch ($row["goodsID"]) {
		case 0:
			$defect = "брак";
			break;
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
			<td>{$i}</td>
			<td>".number_format($row["weight"], 0, ',', '&nbsp;').($row["weight_diff"] ? "<font class='diff_alert'>".($row["weight_diff"] > 0 ? " +" : " ").number_format($row["weight_diff"], 0, ',', '&nbsp;')."</font>" : "")."</td>
			<td>{$row["weighing_time_format"]}</td>
			<td>{$defect}</td>
			<td>{$row["RN"]}</td>
			<td>{$row["post"]}</td>
			<td>{$row["barcode"]}</td>
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
