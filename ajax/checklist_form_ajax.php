<?
include_once "../checkrights.php";

$PB_ID = $_GET["PB_ID"];

$query = "
	SELECT DATE_FORMAT(PB.pb_date, '%d.%m.%Y') pb_date_format
		,PB.CW_ID
		,PB.batches
		,PB.fakt
		,CW.item
		,CW.fillings
		,CW.in_cassette
		,CONCAT(ROUND(CW.min_density/1000, 2), '&ndash;', ROUND(CW.max_density/1000, 2)) spec
	FROM plan__Batch PB
	JOIN CounterWeight CW ON CW.CW_ID = PB.CW_ID
	WHERE PB_ID = {$PB_ID}
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
$row = mysqli_fetch_array($res);

$batches = $row["batches"];
$fakt = $row["fakt"];
$item = $row["item"];
$pb_date = $row["pb_date_format"];
$fillings = $row["fillings"];
$in_cassette = $row["in_cassette"];
$CW_ID = $row["CW_ID"];
$spec = $row["spec"];

$html = "
	<input type='hidden' name='PB_ID' value='{$PB_ID}'>
	<input type='hidden' name='batches' value='{$batches}'>
	<input type='hidden' name='fakt' value='{$fakt}'>
	<table style='table-layout: fixed; width: 100%; border-collapse: collapse; border-spacing: 0px; text-align: center;'>
		<tr>
			<td style='border: 1px solid black; line-height: 1em;'><img src='/img/logo.png' alt='KONSTANTA' style='width: 200px; margin: 5px;'></td>
			<td style='font-size: 2em; border: 1px solid black; line-height: 1em;'>{$item}</td>
			<td style='font-size: 2em; border: 1px solid black; line-height: 1em;'>{$pb_date}</td>
		</tr>
	</table>
";

// Данные рецепта
$query = "
	SELECT GROUP_CONCAT(CONCAT('<span style=\'font-size: 1.5em;\'>', letter, '</span>') ORDER BY letter SEPARATOR '<br>') ltr
		,GROUP_CONCAT(CONCAT(ROUND(io_min/1000, 2), '&ndash;', ROUND(io_max/1000, 2)) ORDER BY letter SEPARATOR '<br>') io
		,GROUP_CONCAT(CONCAT(ROUND(sn_min/1000, 2), '&ndash;', ROUND(sn_max/1000, 2)) ORDER BY letter SEPARATOR '<br>') sn
		,GROUP_CONCAT(CONCAT(ROUND(cs_min/1000, 2), '&ndash;', ROUND(cs_max/1000, 2)) ORDER BY letter SEPARATOR '<br>') cs
		,GROUP_CONCAT(distinct CONCAT(iron_oxide, ' ±5') ORDER BY letter SEPARATOR '<br>') iron_oxide
		,GROUP_CONCAT(distinct CONCAT(sand, ' ±5') ORDER BY letter SEPARATOR '<br>') sand
		,GROUP_CONCAT(distinct CONCAT(crushed_stone, ' ±5') ORDER BY letter SEPARATOR '<br>') crushed_stone
		,GROUP_CONCAT(distinct CONCAT(cement, ' ±2') ORDER BY letter SEPARATOR '<br>') cement
		,GROUP_CONCAT(distinct CONCAT('min ', water) ORDER BY letter SEPARATOR '<br>') water
	FROM MixFormula
	WHERE CW_ID = {$CW_ID}
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
$row = mysqli_fetch_array($res);

$html .= "
	<table style='table-layout: fixed; width: 100%; border-collapse: collapse; border-spacing: 0px; text-align: center;'>
		<thead>
			<tr>
				<th rowspan='3' width='30'>№<br>п/п</th>
				<th rowspan='3'>Время замеса</th>
				<th rowspan='2' width='30' style='word-wrap: break-word;'>Рецепт</th>
				<th colspan='".(1 + ($row["io"] ? 1 : 0) + ($row["sn"] ? 1 : 0) + ($row["cs"] ? 1 : 0))."'>Масса куба, кг</th>
				".($row["iron_oxide"] ? "<th rowspan='2'>Окалина, кг</th>" : "")."
				".($row["sand"] ? "<th rowspan='2'>КМП, кг</th>" : "")."
				".($row["crushed_stone"] ? "<th rowspan='2'>Отсев, кг</th>" : "")."
				".($row["cement"] ? "<th rowspan='2'>Цемент, кг</th>" : "")."
				".($row["water"] ? "<th rowspan='2'>Вода, кг</th>" : "")."
				<th rowspan='3' colspan='{$fillings}' width='".($fillings * 60)."'>№ кассеты<h2>Замес на {$fillings} кассеты</h2></th>
				<th rowspan='3'>Недолив</th>
				<th rowspan='3' width='30'><i class='fas fa-cube' title='Испытания кубов'></i></th>
				<th rowspan='3'>Оператор</th>
			</tr>
			<tr>
				".(($row["io"] ? "<th>Окалины</th>" : ""))."
				".(($row["sn"] ? "<th>КМП</th>" : ""))."
				".(($row["cs"] ? "<th>Отсева</th>" : ""))."
				<th>Раствора</th>
			</tr>
			<tr>
				<th>{$row["ltr"]}</th>
				".(($row["io"] ? "<th class='nowrap'>{$row["io"]}</th>" : ""))."
				".(($row["sn"] ? "<th class='nowrap'>{$row["sn"]}</th>" : ""))."
				".(($row["cs"] ? "<th class='nowrap'>{$row["cs"]}</th>" : ""))."
				<th class='nowrap'>{$spec}</th>
				".($row["iron_oxide"] ? "<th class='nowrap'>{$row["iron_oxide"]}</th>" : "")."
				".($row["sand"] ? "<th class='nowrap'>{$row["sand"]}</th>" : "")."
				".($row["crushed_stone"] ? "<th class='nowrap'>{$row["crushed_stone"]}</th>" : "")."
				".($row["cement"] ? "<th class='nowrap'>{$row["cement"]}</th>" : "")."
				".($row["water"] ? "<th class='nowrap'>{$row["water"]}</th>" : "")."
			</tr>
		</thead>
		<tbody>
";

// Редактирование или добавление
if( $fakt ) {
	$query = "
		SELECT LB.LB_ID
			,LB.OP_ID
			,DATE_FORMAT(LB.batch_time, '%H:%i') batch_time_format
			,LB.io_density
			,LB.sn_density
			,LB.cs_density
			,LB.mix_density
			,LB.iron_oxide
			,LB.sand
			,LB.crushed_stone
			,LB.cement
			,LB.water
			,LB.underfilling
			,LB.test
		FROM list__Batch LB
		JOIN Operator OP ON OP.OP_ID = LB.OP_ID
		WHERE LB.PB_ID = {$PB_ID}
		ORDER BY LB.batch_time ASC
	";
	$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$i = 0;
	while( $subrow = mysqli_fetch_array($subres) ) {
		$i++;
		// Номера кассет
		$query = "
			SELECT LF.LF_ID, LF.cassette
			FROM list__Filling LF
			WHERE LF.LB_ID = {$subrow["LB_ID"]}
			ORDER BY LF.LF_ID
		";
		$subsubres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		$fillings_cell = "";
		while( $subsubrow = mysqli_fetch_array($subsubres) ) {
			$fillings_cell .= "<td><input type='number' min='1' max='{$cassetts}' name='cassette[{$subrow["LB_ID"]}][{$subsubrow["LF_ID"]}]' value='{$subsubrow["cassette"]}' style='width: 60px;' required></td>";
		}

		// Дропдаун операторов
		$operators = "
			<select name='OP_ID[{$subrow["LB_ID"]}]' style='width: 70px;' required>
				<option value=''></option>
		";
		$query = "
			SELECT OP.OP_ID, OP.name
			FROM Operator OP
		";
		$subsubres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		while( $subsubrow = mysqli_fetch_array($subsubres) ) {
			$selected = ($subsubrow["OP_ID"] == $subrow["OP_ID"]) ? "selected" : "";
			$operators .= "<option value='{$subsubrow["OP_ID"]}' {$selected}>{$subsubrow["name"]}</option>";
		}
		$operators .= "
			</select>
		";

		$html .= "
			<tr>
				<td style='text-align: center;'>{$i}</td>
				<td><input type='time' name='batch_time[{$subrow["LB_ID"]}]' value='{$subrow["batch_time_format"]}' style='width: 70px;' required></td>
				<td></td>
				".($row["io"] ? "<td><input type='number' min='2' max='3' step='0.01' name='io_density[{$subrow["LB_ID"]}]' value='".($subrow["io_density"]/1000)."' style='width: 70px;' required></td>" : "")."
				".($row["sn"] ? "<td><input type='number' min='1' max='2' step='0.01' name='sn_density[{$subrow["LB_ID"]}]' value='".($subrow["sn_density"]/1000)."' style='width: 70px;' required></td>" : "")."
				".($row["cs"] ? "<td><input type='number' min='1' max='2' step='0.01' name='cs_density[{$subrow["LB_ID"]}]' value='".($subrow["cs_density"]/1000)."' style='width: 70px;' required></td>" : "")."
				<td><input type='number' min='2' max='4' step='0.01' name='mix_density[{$subrow["LB_ID"]}]' value='".($subrow["mix_density"]/1000)."' style='width: 70px;' required></td>
				".($row["iron_oxide"] ? "<td><input type='number' min='0' name='iron_oxide[{$subrow["LB_ID"]}]' value='{$subrow["iron_oxide"]}' style='width: 70px;' required></td>" : "")."
				".($row["sand"] ? "<td><input type='number' min='0' name='sand[{$subrow["LB_ID"]}]' value='{$subrow["sand"]}' style='width: 70px;' required></td>" : "")."
				".($row["crushed_stone"] ? "<td><input type='number' min='0' name='crushed_stone[{$subrow["LB_ID"]}]' value='{$subrow["crushed_stone"]}' style='width: 70px;' required></td>" : "")."
				".($row["cement"] ? "<td><input type='number' min='0' name='cement[{$subrow["LB_ID"]}]' value='{$subrow["cement"]}' style='width: 70px;' required></td>" : "")."
				".($row["water"] ? "<td><input type='number' min='0' name='water[{$subrow["LB_ID"]}]' value='{$subrow["water"]}' style='width: 70px;' required></td>" : "")."
				{$fillings_cell}
				<td><input type='number' min='0' max='{$in_cassette}' name='underfilling[{$subrow["LB_ID"]}]' value='{$subrow["underfilling"]}' style='width: 60px;'></td>
				<td><input type='checkbox' name='test[{$subrow["LB_ID"]}]' ".($subrow["test"] ? "checked" : "")." value='1'></td>
				<td>{$operators}</td>
			</tr>
		";
	}
}
else {
	for ($i = 1; $i <= $batches; $i++) {
		// Номера кассет
		$fillings_cell = '';
		for ($j = 1; $j <= $fillings; $j++) {
			$fillings_cell .= "<td><input type='number' min='1' max='{$cassetts}' name='cassette[{$i}][{$j}]' style='width: 60px;' required></td>";
		}

		// Дропдаун операторов
		$operators = "
			<select name='OP_ID[{$i}]' style='width: 70px;' required>
				<option value=''></option>
		";
		$query = "
			SELECT OP.OP_ID, OP.name
			FROM Operator OP
		";
		$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		while( $subrow = mysqli_fetch_array($subres) ) {
			$operators .= "<option value='{$subrow["OP_ID"]}'>{$subrow["name"]}</option>";
		}
		$operators .= "
			</select>
		";

		$html .= "
			<tr>
				<td style='text-align: center;'>{$i}</td>
				<td><input type='time' name='batch_time[{$i}]' style='width: 70px;' required></td>
				<td></td>
				".($row["io"] ? "<td><input type='number' min='2' max='3' step='0.01' name='io_density[{$i}]' style='width: 70px;' required></td>" : "")."
				".($row["sn"] ? "<td><input type='number' min='1' max='2' step='0.01' name='sn_density[{$i}]' style='width: 70px;' required></td>" : "")."
				".($row["cs"] ? "<td><input type='number' min='1' max='2' step='0.01' name='cs_density[{$i}]' style='width: 70px;' required></td>" : "")."
				<td><input type='number' min='2' max='4' step='0.01' name='mix_density[{$i}]' style='width: 70px;' required></td>
				".($row["iron_oxide"] ? "<td><input type='number' min='0' name='iron_oxide[{$i}]' style='width: 70px;' required></td>" : "")."
				".($row["sand"] ? "<td><input type='number' min='0' name='sand[{$i}]' style='width: 70px;' required></td>" : "")."
				".($row["crushed_stone"] ? "<td><input type='number' min='0' name='crushed_stone[{$i}]' style='width: 70px;' required></td>" : "")."
				".($row["cement"] ? "<td><input type='number' min='0' name='cement[{$i}]' style='width: 70px;' required></td>" : "")."
				".($row["water"] ? "<td><input type='number' min='0' name='water[{$i}]' style='width: 70px;' required></td>" : "")."
				{$fillings_cell}
				<td><input type='number' min='0' max='{$in_cassette}' name='underfilling[{$i}]' style='width: 60px;'></td>
				<td><input type='checkbox' name='test[{$i}]' value='1'></td>
				<td>{$operators}</td>
			</tr>
		";
	}
}

$html .= "
		</tbody>
	</table>
";

$html = str_replace("\n", "", addslashes($html));
echo "$('#checklist_form fieldset').html('{$html}');";
?>
