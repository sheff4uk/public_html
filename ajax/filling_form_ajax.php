<?
include_once "../checkrights.php";

$max_batches = 40; // Максимально возможное число замесов
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
		,CONCAT(ROUND(CW.min_density/1000, 2), '&ndash;', ROUND(CW.max_density/1000, 2)) spec
		,MIN(LB.batch_date) batch_date
		,PB.sf_density
		,PB.lf_density
		,PB.io_density
		,PB.sl_density
		,PB.sn_density
		,PB.cs_density
		,PB.calcium
	FROM plan__Batch PB
	JOIN CounterWeight CW ON CW.CW_ID = PB.CW_ID
	JOIN MixFormula MF ON MF.CW_ID = CW.CW_ID AND MF.F_ID = PB.F_ID
	LEFT JOIN list__Batch LB ON LB.PB_ID = PB.PB_ID
	WHERE PB.PB_ID = {$PB_ID}
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
$row = mysqli_fetch_array($res);

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
$batch_date = $row["batch_date"];
$sf_density = $row["sf_density"];
$lf_density = $row["lf_density"];
$io_density = $row["io_density"];
$sl_density = $row["sl_density"];
$sn_density = $row["sn_density"];
$cs_density = $row["cs_density"];
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

// Формируем список вероятных номеров кассет
$query = "
	SELECT LF.cassette
	FROM list__Batch LB
	JOIN list__Filling LF ON LF.LB_ID = LB.LB_ID
	WHERE LB.PB_ID = (
		SELECT PB_ID
		FROM plan__Batch
		WHERE CW_ID = {$CW_ID}
			AND F_ID = {$F_ID}
			AND fact_batches > 0
			AND PB_ID < {$PB_ID}
		ORDER BY PB_ID DESC
		LIMIT 1
	)
	ORDER BY LF.cassette
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	$cassettes .= "<b class='cassette' id='c_{$row["cassette"]}'>{$row["cassette"]}</b>";
}
$html .= "<div style='width: 100%; border: 1px solid; padding: 10px;'><span>Вероятные номера кассет:</span> {$cassettes}</div>";

// Данные рецепта
$query = "
	SELECT IFNULL(MF.s_fraction, 0) s_fraction
		,IFNULL(MF.l_fraction, 0) l_fraction
		,IFNULL(MF.iron_oxide, 0) iron_oxide
		,IFNULL(MF.slag, 0) slag
		,IFNULL(MF.sand, 0) sand
		,IFNULL(MF.crushed_stone, 0) crushed_stone
		,IFNULL(MF.cement, 0) cement
		,IFNULL(MF.plasticizer, 0) plasticizer
		,IFNULL(MF.water, 0) water
		,COUNT(MF.s_fraction) sf_cnt
		,COUNT(MF.l_fraction) lf_cnt
		,COUNT(MF.iron_oxide) io_cnt
		,COUNT(MF.slag) sl_cnt
		,COUNT(MF.sand) sn_cnt
		,COUNT(MF.crushed_stone) cs_cnt
		,COUNT(MF.cement) cm_cnt
		,COUNT(MF.plasticizer) pl_cnt
		,COUNT(MF.water) wt_cnt
	FROM MixFormula MF
	WHERE MF.CW_ID = {$CW_ID}
		AND MF.F_ID = {$F_ID}
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
$row = mysqli_fetch_array($res);

$html .= "
	<table style='font-size: .85em; table-layout: fixed; width: 100%; border-collapse: collapse; border-spacing: 0px; text-align: center;'>
		<thead style='word-wrap: break-word;'>
			<tr>
				<th rowspan='3' width='30'>№<br>п/п</th>
				<th rowspan='3' width='180'>Дата и время замеса</th>
				<th rowspan='2'>Масса куба раствора, кг</th>
				<th rowspan='3' width='50'>t, ℃ 22±8</th>
				".($row["sf_cnt"] ? "<th>Мелкая дробь, кг</th>" : "")."
				".($row["lf_cnt"] ? "<th>Крупная дробь, кг</th>" : "")."
				".($row["io_cnt"] ? "<th>Окалина, кг</th>" : "")."
				".($row["sl_cnt"] ? "<th>Шлак, кг</th>" : "")."
				".($row["sn_cnt"] ? "<th>КМП, кг</th>" : "")."
				".($row["cs_cnt"] ? "<th>Отсев, кг</th>" : "")."
				".($row["cm_cnt"] ? "<th rowspan='2'>Цемент, кг</th>" : "")."
				".($row["pl_cnt"] ? "<th rowspan='2'>Пластификатор, кг</th>" : "")."
				".($row["wt_cnt"] ? "<th>Вода, кг</th>" : "")."
				<th rowspan='3' colspan='{$fillings}' width='".($fillings * 50)."'>№ кассеты</th>
				<th rowspan='3' width='50'>Недолив</th>
				<th rowspan='3' width='30'><i class='fas fa-cube' title='Испытание куба'></i></th>
			</tr>
			<tr>
				".($row["sf_cnt"] ? "<th><input type='number' min='3' max='6' step='0.01' value='".($sf_density/1000)."' name='sf_density' style='width: 100%; background-color: #7952eb88;' ></th>" : "")."
				".($row["lf_cnt"] ? "<th><input type='number' min='3' max='6' step='0.01' value='".($lf_density/1000)."' name='lf_density' style='width: 100%; background-color: #51d5d788;' ></th>" : "")."
				".($row["io_cnt"] ? "<th><input type='number' min='2' max='3' step='0.01' value='".($io_density/1000)."' name='io_density' style='width: 100%; background-color: #a52a2a80;' ></th>" : "")."
				".($row["sl_cnt"] ? "<th><input type='number' min='1' max='3' step='0.01' value='".($sl_density/1000)."' name='sl_density' style='width: 100%; background-color: #33333380;' ></th>" : "")."
				".($row["sn_cnt"] ? "<th><input type='number' min='1' max='2' step='0.01' value='".($sn_density/1000)."' name='sn_density' style='width: 100%; background-color: #f4a46082;' ></th>" : "")."
				".($row["cs_cnt"] ? "<th><input type='number' min='1' max='2' step='0.01' value='".($cs_density/1000)."' name='cs_density' style='width: 100%; background-color: #8b45137a;' ></th>" : "")."
				<th><input type='number' min='0' max='100' value='".($calcium)."' name='calcium' style='width: 100%; background-color: #1e90ff85;' required></th>
			</tr>
			<tr>
				<th class='nowrap'>{$spec}</th>
				".($row["sf_cnt"] ? "<th class='nowrap'>{$row["s_fraction"]}</th>" : "")."
				".($row["lf_cnt"] ? "<th class='nowrap'>{$row["l_fraction"]}</th>" : "")."
				".($row["io_cnt"] ? "<th class='nowrap'>{$row["iron_oxide"]}</th>" : "")."
				".($row["sl_cnt"] ? "<th class='nowrap'>{$row["slag"]}</th>" : "")."
				".($row["sn_cnt"] ? "<th class='nowrap'>{$row["sand"]}</th>" : "")."
				".($row["cs_cnt"] ? "<th class='nowrap'>{$row["crushed_stone"]}</th>" : "")."
				".($row["cm_cnt"] ? "<th class='nowrap'>{$row["cement"]}</th>" : "")."
				".($row["pl_cnt"] ? "<th class='nowrap'>{$row["plasticizer"]}</th>" : "")."
				".($row["wt_cnt"] ? "<th class='nowrap'>{$row["water"]}</th>" : "")."
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
			,IFNULL(LB.s_fraction, 0) s_fraction
			,IFNULL(LB.l_fraction, 0) l_fraction
			,IFNULL(LB.iron_oxide, 0) iron_oxide
			,IFNULL(LB.slag, 0) slag
			,IFNULL(LB.sand, 0) sand
			,IFNULL(LB.crushed_stone, 0) crushed_stone
			,IFNULL(LB.cement, 0) cement
			,IFNULL(LB.plasticizer, 0) plasticizer
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
		$fillings_cell .= "<td rowspan='{$per_batch}'><input type='number' min='0' max='{$in_cassette}' name='underfilling[{$subrow["LB_ID"]}]' value='{$subrow["underfilling"]}' style='width: 100%;' required></td>";

		$html .= "
			<tr class='batch_row' num='{$i}'>
				<td style='text-align: center; font-size: 1.2em;'>{$i}</td>
				<td><input type='datetime-local' name='batch_time[{$subrow["LB_ID"]}]' style='width: 100%;' value='{$subrow["batch_date"]}T{$subrow["batch_time_format"]}' required></td>
				<td><input type='number' min='2' max='5.5' step='0.01' name='mix_density[{$subrow["LB_ID"]}]' value='".($subrow["mix_density"]/1000)."' style='width: 100%;' required></td>
				<td><input type='number' min='5' max='45' name='temp[{$subrow["LB_ID"]}]' value='{$subrow["temp"]}' style='width: 100%;' required></td>
				".($row["sf_cnt"] ? "<td style='background: #7952eb88;'><input type='number' min='0' name='s_fraction[{$subrow["LB_ID"]}]' value='{$subrow["s_fraction"]}' style='width: 100%;' required></td>" : "")."
				".($row["lf_cnt"] ? "<td style='background: #51d5d788;'><input type='number' min='0' name='l_fraction[{$subrow["LB_ID"]}]' value='{$subrow["l_fraction"]}' style='width: 100%;' required></td>" : "")."
				".($row["io_cnt"] ? "<td style='background: #a52a2a80;'><input type='number' min='0' name='iron_oxide[{$subrow["LB_ID"]}]' value='{$subrow["iron_oxide"]}' style='width: 100%;' required></td>" : "")."
				".($row["sl_cnt"] ? "<td style='background: #33333380;'><input type='number' min='0' name='slag[{$subrow["LB_ID"]}]' value='{$subrow["slag"]}' style='width: 100%;' required></td>" : "")."
				".($row["sn_cnt"] ? "<td style='background: #f4a46082;'><input type='number' min='0' name='sand[{$subrow["LB_ID"]}]' value='{$subrow["sand"]}' style='width: 100%;' required></td>" : "")."
				".($row["cs_cnt"] ? "<td style='background: #8b45137a;'><input type='number' min='0' name='crushed_stone[{$subrow["LB_ID"]}]' value='{$subrow["crushed_stone"]}' style='width: 100%;' required></td>" : "")."
				".($row["cm_cnt"] ? "<td style='background: #7080906b;'><input type='number' min='0' name='cement[{$subrow["LB_ID"]}]' value='{$subrow["cement"]}' style='width: 100%;' required></td>" : "")."
				".($row["pl_cnt"] ? "<td style='background: #80800080;'><input type='number' min='0' step='0.01' name='plasticizer[{$subrow["LB_ID"]}]' value='{$subrow["plasticizer"]}' style='width: 100%;' required></td>" : "")."
				".($row["wt_cnt"] ? "<td style='background: #1e90ff85;'><input type='number' min='0' name='water[{$subrow["LB_ID"]}]' value='{$subrow["water"]}' style='width: 100%;' required></td>" : "")."
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
	$fillings_cell .= "<td rowspan='{$per_batch}'><input type='number' min='0' max='{$in_cassette}' name='underfilling[n_{$i}]' style='width: 100%;' required></td>";

	$html .= "
		<tr class='batch_row' num='{$i}'>
			<td style='text-align: center; font-size: 1.2em;'>{$i}</td>
			<td><input type='datetime-local' name='batch_time[n_{$i}]' style='width: 100%;' required></td>
			<td><input type='number' min='2' max='5.5' step='0.01' name='mix_density[n_{$i}]' style='width: 100%;' required></td>
			<td><input type='number' min='5' max='45' name='temp[n_{$i}]' style='width: 100%;' required></td>
			".($row["sf_cnt"] ? "<td style='background: #7952eb88;'><input type='number' min='0' name='s_fraction[n_{$i}]' style='width: 100%;' required></td>" : "")."
			".($row["lf_cnt"] ? "<td style='background: #51d5d788;'><input type='number' min='0' name='l_fraction[n_{$i}]' style='width: 100%;' required></td>" : "")."
			".($row["io_cnt"] ? "<td style='background: #a52a2a80;'><input type='number' min='0' name='iron_oxide[n_{$i}]' style='width: 100%;' required></td>" : "")."
			".($row["sl_cnt"] ? "<td style='background: #33333380;'><input type='number' min='0' name='slag[n_{$i}]' style='width: 100%;' required></td>" : "")."
			".($row["sn_cnt"] ? "<td style='background: #f4a46082;'><input type='number' min='0' name='sand[n_{$i}]' style='width: 100%;' required></td>" : "")."
			".($row["cs_cnt"] ? "<td style='background: #8b45137a;'><input type='number' min='0' name='crushed_stone[n_{$i}]' style='width: 100%;' required></td>" : "")."
			".($row["cm_cnt"] ? "<td style='background: #7080906b;'><input type='number' min='0' name='cement[n_{$i}]' style='width: 100%;' required></td>" : "")."
			".($row["pl_cnt"] ? "<td style='background: #80800080;'><input type='number' min='0' step='0.01' name='plasticizer[n_{$i}]' style='width: 100%;' required></td>" : "")."
			".($row["wt_cnt"] ? "<td style='background: #1e90ff85;'><input type='number' min='0' name='water[n_{$i}]' style='width: 100%;' required></td>" : "")."
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
		SELECT cassette FROM Cassettes WHERE F_ID = {$F_ID}
	";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $row = mysqli_fetch_array($res) ) {
		$html .= "<option value='{$row["cassette"]}'>";
	}

$html .= "</datalist>";

$html = str_replace("\n", "", addslashes($html));
echo "$('#filling_form fieldset').html('{$html}');";
?>
