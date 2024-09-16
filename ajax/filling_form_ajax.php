<?php
include_once "../checkrights.php";

$max_batches = 80; // Максимально возможное число замесов
$PB_ID = $_GET["PB_ID"];
$query = "
	SELECT PB.F_ID
		,PB.year
		,PB.cycle
		,PB.CW_ID
		,PB.batches
		,PB.fact_batches
		,CW.item
		,IFNULL(PB.fillings, MF.fillings) fillings
		,IFNULL(PB.per_batch, MF.per_batch) per_batch
		,IFNULL(PB.in_cassette, MF.in_cassette) in_cassette
		,CONCAT(ROUND(MF.min_density/1000, 2), '&ndash;', ROUND(MF.max_density/1000, 2)) spec
		,MF.water
		,PB.calcium
		,IF( IFNULL(PB.print_time, PB.change_time) < NOW() - INTERVAL 10 DAY, 0, 1 ) editable
		#,1 editable
	FROM plan__Batch PB
	JOIN CounterWeight CW ON CW.CW_ID = PB.CW_ID
	JOIN MixFormula MF ON MF.CW_ID = CW.CW_ID AND MF.F_ID = PB.F_ID
	WHERE PB.PB_ID = {$PB_ID}
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
$row = mysqli_fetch_array($res);

if( $row["editable"] ) {

	$F_ID = $row["F_ID"];
	$year = $row["year"];
	$cycle = $row["cycle"];
	$batches = $row["batches"];
	$fact_batches = $row["fact_batches"];
	$item = $row["item"];
	$fillings = $row["fillings"];
	$per_batch = $row["per_batch"];
	$in_cassette = $row["in_cassette"];
	$CW_ID = $row["CW_ID"];
	$spec = $row["spec"];
	$water = $row["water"];
	$calcium = $row["calcium"];

	$html = "
		<style>
			.cassette::-webkit-inner-spin-button,
			.cassette::-webkit-outer-spin-button {
				-webkit-appearance: none;
				margin: 0;
			}
		</style>

		<p style='display: none; text-align: center; font-size: 2em;'>Число замесов: <input type='number' name='fact_batches' id='rows' min='".($fact_batches ? $fact_batches : "1")."' max='{$max_batches}' value='".($fact_batches ? $fact_batches : $batches)."'></p>
		<input type='hidden' name='F_ID' value='{$F_ID}'>
		<input type='hidden' name='PB_ID' value='{$PB_ID}'>
		<input type='hidden' name='fillings' value='{$fillings}'>
		<input type='hidden' name='per_batch' value='{$per_batch}'>
		<input type='hidden' name='in_cassette' value='{$in_cassette}'>
		<table style='table-layout: fixed; width: 100%; border-collapse: collapse; border-spacing: 0px; text-align: center;'>
			<tr>
				<td style='border: 1px solid black;'>
					<img src='/img/logo.png' alt='KONSTANTA' style='width: 200px; margin: 5px;'>
				</td>
				<td style='border: 1px solid black; line-height: 1em; position: relative;'>
					<span style='position: absolute; top: 0px; left: 5px;' class='nowrap'>деталь</span>
					<n style='font-size: 2em;'>{$item}</n>
				</td>
				<td style='border: 1px solid black; line-height: 1em; position: relative;' width='170'>
					<span style='position: absolute; top: 0px; left: 5px;' class='nowrap'>цикл</span>
					<n style='font-size: 2em;'>{$year}-{$cycle}</n>
				</td>
			</tr>
		</table>
	";

	// Формируем список зарезервированных кассет
	$query = "
		SELECT cassette
		FROM plan__BatchCassette
		WHERE PB_ID = {$PB_ID}
		ORDER BY cassette
	";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $row = mysqli_fetch_array($res) ) {
		$cassettes .= "<b class='cassette' id='c_{$row["cassette"]}'>{$row["cassette"]}</b>";
	}
	$html .= "<div style='width: 100%; border: 1px solid; padding: 10px;'><span>Зарезервированные кассеты:</span> {$cassettes}</div>";

	// Данные рецепта
	$query = "
		SELECT MN.material_name
			,MFM.quantity
			,2 - MN.checkbox_density rowspan
			,PBD.density
			,MN.checkbox_density
			,MN.MN_ID
			,MN.color
			,MN.admission
		FROM plan__Batch PB
		JOIN MixFormula MF ON MF.CW_ID = PB.CW_ID AND MF.F_ID = PB.F_ID
		JOIN MixFormulaMaterial MFM ON MFM.MF_ID = MF.MF_ID
		JOIN material__Name MN ON MN.MN_ID = MFM.MN_ID
		LEFT JOIN plan__BatchDensity PBD ON PBD.PB_ID = PB.PB_ID
			AND PBD.MN_ID = MFM.MN_ID
		WHERE PB.PB_ID = {$PB_ID}
		ORDER BY MN.material_name
	";

	$html .= "
		<table style='font-size: .85em; table-layout: fixed; width: 100%; border-collapse: collapse; border-spacing: 0px; text-align: center;'>
			<thead style='word-wrap: break-word;'>
				<tr>
					<th rowspan='3' width='30'>№<br>п/п</th>
					<th rowspan='3' width='180'>Дата и время замеса</th>
					<th rowspan='2'>Масса куба раствора, кг</th>
					<th rowspan='3' width='50'>t, ℃ 22±8</th>
	";

	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $row = mysqli_fetch_array($res) ) {
		$html .= "<th rowspan='{$row["rowspan"]}'>{$row["material_name"]}, кг</th>";
	}

	$html .= "
					<th>Вода, л</th>
					<th rowspan='3' colspan='{$fillings}' width='".($fillings * 50)."'>№ кассеты</th>
					<th rowspan='3' width='50'>Недолив</th>
					<th rowspan='3' width='30'><i class='fas fa-cube' title='Испытание куба'></i></th>
				</tr>
				<tr>
	";

	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $row = mysqli_fetch_array($res) ) {
		if ($row["checkbox_density"]) {
			$html .= "<th><input type='number' min='1' max='6' step='0.01' value='".($row["density"]/1000)."' name='density[{$row["MN_ID"]}]' style='width: 100%; background-color: #{$row["color"]};' ></th>";
		}
	}

	$html .= "
					<th><input type='number' min='0' max='100' value='".($calcium)."' name='calcium' style='width: 100%; background-color: #1e90ff85;' required></th>
				</tr>
				<tr>
					<th class='nowrap'>{$spec}</th>
	";

	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $row = mysqli_fetch_array($res) ) {
		$html .= "<th class='nowrap'>{$row["quantity"]}±{$row["admission"]}</th>";
	}

	$html .= "
					<th class='nowrap'>min {$water}</th>
				</tr>
			</thead>
			<tbody>
	";

	$k = 0; //Счетчик строк если замес на несколько кассет
	// Выводим сохраненные замесы в случае редактирования
	if( $fact_batches ) {
		$query = "
			SELECT LB.LB_ID
				,LB.batch_date
				,DATE_FORMAT(LB.batch_time, '%H:%i') batch_time_format
				,LB.mix_density
				,LB.temp
				,IFNULL(LB.water, 0) water
				,SUM(LF.underfilling) underfilling
				,LB.test
				,IF((SELECT SUM(1) FROM list__CubeTest WHERE LB_ID = LB.LB_ID), 1, 0) is_test
			FROM list__Batch LB
			LEFT JOIN list__Filling LF ON LF.LB_ID = LB.LB_ID
			WHERE LB.PB_ID = {$PB_ID}
			GROUP BY LB.LB_ID
			ORDER BY LB.batch_date, LB.batch_time
		";
		$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		$i = 0;
		while( $subrow = mysqli_fetch_array($subres) ) {
			$i++;
			// Номера кассет
			$query = "
				SELECT LF.LF_ID
					,LF.cassette
				FROM list__Filling LF
				WHERE LF.LB_ID = {$subrow["LB_ID"]}
				ORDER BY LF.LF_ID
			";
			$subsubres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			$fillings_cell = "";
			while( $subsubrow = mysqli_fetch_array($subsubres) ) {
				$fillings_cell .= "
					<td rowspan='{$per_batch}'>
						<input type='number' class='cassette' min='1' max='{$cassetts}' name='cassette[{$subrow["LB_ID"]}][{$subsubrow["LF_ID"]}]' value='{$subsubrow["cassette"]}' style='width: 50px;' required ".($subsubrow["is_link"] ? "readonly" : "")." list='defaultNumbers'>
					</td>
				";
			}
			$fillings_cell .= "<td rowspan='{$per_batch}'><input type='number' min='0' max='".($in_cassette * $fillings)."' name='underfilling[{$subrow["LB_ID"]}]' value='{$subrow["underfilling"]}' style='width: 100%;' required></td>";

			$html .= "
				<tr class='batch_row' num='{$i}'>
					<td style='text-align: center; font-size: 1.2em;'>{$i}</td>
					<td><input type='datetime-local' name='batch_time[{$subrow["LB_ID"]}]' style='width: 100%;' value='{$subrow["batch_date"]}T{$subrow["batch_time_format"]}' required></td>
					<td><input type='number' min='2' max='5.5' step='0.01' name='mix_density[{$subrow["LB_ID"]}]' value='".($subrow["mix_density"]/1000)."' style='width: 100%;' required></td>
					<td><input type='number' min='5' max='45' name='temp[{$subrow["LB_ID"]}]' value='{$subrow["temp"]}' style='width: 100%;' required></td>
			";

			$query = "
				SELECT LBM.quantity
					,MN.color
					,MN.MN_ID
					,MN.step
				FROM MixFormula MF
				JOIN MixFormulaMaterial MFM ON MFM.MF_ID = MF.MF_ID
				JOIN material__Name MN ON MN.MN_ID = MFM.MN_ID
				LEFT JOIN list__BatchMaterial LBM ON LBM.MN_ID = MN.MN_ID
					AND LBM.LB_ID = {$subrow["LB_ID"]}
				WHERE MF.F_ID = {$F_ID}
					AND MF.CW_ID = {$CW_ID}
				ORDER BY MN.material_name
			";
			$subsubres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			while( $subsubrow = mysqli_fetch_array($subsubres) ) {
				$html .= "<td style='background: #{$subsubrow["color"]};'><input type='number' min='0' step='{$subsubrow["step"]}' name='material[{$subrow["LB_ID"]}][{$subsubrow["MN_ID"]}]' value='{$subsubrow["quantity"]}' style='width: 100%;' required></td>";
			}

			$html .= "
					<td style='background: #1e90ff85;'><input type='number' min='0' name='water[{$subrow["LB_ID"]}]' value='{$subrow["water"]}' style='width: 100%;' required></td>
					".($k == 0 ? $fillings_cell : "")."
					<td class='nowrap'><input type='checkbox' name='test[{$subrow["LB_ID"]}]' ".($subrow["test"] ? "checked" : "")." ".($subrow["is_test"] ? "onclick='return false;'" : "")." value='1'>".($subrow["is_test"] ? "<i id='test_notice' class='fas fa-question-circle' title='Не редактируется так как есть связанные испытания куба.'></i>" : "")."</td>
				</tr>
			";
			$k++;
			$k = ($k == $per_batch ? 0 : $k);
		}
	}
	// Выводим пустые строки
	for ($i = $fact_batches + 1; $i <= $max_batches; $i++) {
		// Номера кассет
		$fillings_cell = '';
		for ($j = 1; $j <= $fillings; $j++) {
			$fillings_cell .= "
				<td rowspan='{$per_batch}'>
					<input type='number' class='cassette' min='1' max='{$cassetts}' name='cassette[n_{$i}][{$j}]' style='width: 50px;' required list='defaultNumbers'>
				</td>
			";
		}
		$fillings_cell .= "<td rowspan='{$per_batch}'><input type='number' min='0' max='".($in_cassette * $fillings)."' name='underfilling[n_{$i}]' style='width: 100%;' required></td>";

		$html .= "
			<tr class='batch_row' num='{$i}'>
				<td style='text-align: center; font-size: 1.2em;'>{$i}</td>
				<td><input type='datetime-local' name='batch_time[n_{$i}]' style='width: 100%;' required></td>
				<td><input type='number' min='2' max='5.5' step='0.01' name='mix_density[n_{$i}]' style='width: 100%;' required></td>
				<td><input type='number' min='5' max='45' name='temp[n_{$i}]' style='width: 100%;' required></td>
		";

		$query = "
			SELECT MN.color
				,MN.MN_ID
				,MN.step
			FROM MixFormula MF
			JOIN MixFormulaMaterial MFM ON MFM.MF_ID = MF.MF_ID
			JOIN material__Name MN ON MN.MN_ID = MFM.MN_ID
			WHERE MF.F_ID = {$F_ID}
				AND MF.CW_ID = {$CW_ID}
			ORDER BY MN.material_name
		";
		$subsubres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		while( $subsubrow = mysqli_fetch_array($subsubres) ) {
			$html .= "<td style='background: #{$subsubrow["color"]};'><input type='number' min='0' step='{$subsubrow["step"]}' name='material[n_{$i}][{$subsubrow["MN_ID"]}]' style='width: 100%;' required></td>";
		}

		$html .= "
				<td style='background: #1e90ff85;'><input type='number' min='0' name='water[n_{$i}]' style='width: 100%;' required></td>
				".($k == 0 ? $fillings_cell : "")."
				<td><input type='checkbox' name='test[n_{$i}]' value='1'></td>
			</tr>
		";
		$k++;
		$k = ($k == $per_batch ? 0 : $k);
	}

	$html .= "
			</tbody>
		</table>
		<datalist id='defaultNumbers'>
	";

	$query = "
		SELECT cassette
		FROM plan__BatchCassette
		WHERE PB_ID = {$PB_ID}
		ORDER BY cassette
	";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $row = mysqli_fetch_array($res) ) {
		$html .= "<option value='{$row["cassette"]}'>";
	}

	$html .= "</datalist>";
	echo "$('input[name=subbut]').attr('disabled', false);";
	echo "$('input[name=subbut]').show('fast');";
}
else {
	$html = "<p>Записи старше 10 дней не редактируются!</p>";
	echo "$('input[name=subbut]').attr('disabled', true);";
	echo "$('input[name=subbut]').hide('fast');";
}

$html = str_replace("\n", "", addslashes($html));
echo "$('#filling_form fieldset').html('{$html}');";
?>
