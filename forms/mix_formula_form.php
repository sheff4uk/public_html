<?
include_once "../config.php";

// Сохранение/редактирование расформовки
if( isset($_POST["CW_ID"]) ) {
	session_start();
	$CW_ID = $_POST["CW_ID"];
	$letter = $_POST["letter"];
	$io_min = $_POST["io_min"] ? $_POST["io_min"] * 1000 : "NULL";
	$io_max = $_POST["io_max"] ? $_POST["io_max"] * 1000 : "NULL";
	$sn_min = $_POST["sn_min"] ? $_POST["sn_min"] * 1000 : "NULL";
	$sn_max = $_POST["sn_max"] ? $_POST["sn_max"] * 1000 : "NULL";
	$iron_oxide = ($_POST["iron_oxide"] != '') ? $_POST["iron_oxide"] : "NULL";
	$sand = ($_POST["sand"] != '') ? $_POST["sand"] : "NULL";
	$crushed_stone = ($_POST["crushed_stone"] != '') ? $_POST["crushed_stone"] : "NULL";
	$cement = ($_POST["cement"] != '') ? $_POST["cement"] : "NULL";
	$water = ($_POST["water"] != '') ? $_POST["water"] : "NULL";

	if( $_POST["MF_ID"] ) { // Редактируем
		$query = "
			UPDATE MixFormula
			SET CW_ID = {$CW_ID}
				,letter = '{$letter}'
				,io_min = {$io_min}
				,io_max = {$io_max}
				,sn_min = {$sn_min}
				,sn_max = {$sn_max}
				,iron_oxide = {$iron_oxide}
				,sand = {$sand}
				,crushed_stone = {$crushed_stone}
				,cement = {$cement}
				,water = {$water}
			WHERE MF_ID = {$_POST["MF_ID"]}
		";
		if( !mysqli_query( $mysqli, $query ) ) {
			$_SESSION["error"][] = "Invalid query: ".mysqli_error( $mysqli );
		}
		$MF_ID = $_POST["MF_ID"];
	}
	else { // Добавляем
		$query = "
			INSERT INTO MixFormula
			SET CW_ID = {$CW_ID}
				,letter = '{$letter}'
				,io_min = {$io_min}
				,io_max = {$io_max}
				,sn_min = {$sn_min}
				,sn_max = {$sn_max}
				,iron_oxide = {$iron_oxide}
				,sand = {$sand}
				,crushed_stone = {$crushed_stone}
				,cement = {$cement}
				,water = {$water}
		";
		if( !mysqli_query( $mysqli, $query ) ) {
			$_SESSION["error"][] = "Invalid query: ".mysqli_error( $mysqli );
		}
		else {
			$add = 1;
			$MF_ID = mysqli_insert_id( $mysqli );
		}
	}

	if( count($_SESSION["error"]) == 0) {
		$_SESSION["success"][] = $add ? "Новыя запись успешно добавлена." : "Запись успешно отредактирована.";
	}

	// Перенаправление в журнал
	exit ('<meta http-equiv="refresh" content="0; url=/mix_formula.php#'.$MF_ID.'">');
}
?>

<style>
	#formula_form table input,
	#formula_form table select {
		font-size: 1.2em;
	}
</style>

<div id='formula_form' title='Параметры рецепта' style='display:none;'>
	<form method='post' action="/forms/mix_formula_form.php" onsubmit="JavaScript:this.subbut.disabled=true;
this.subbut.value='Подождите, пожалуйста!';">
		<fieldset>
			<input type="hidden" name="MF_ID">

			<table style="width: 100%; table-layout: fixed;">
				<thead>
					<tr>
						<th rowspan="2">Противовес</th>
						<th rowspan="2">Литера</th>
						<th colspan="2">Условия применения</th>
						<th colspan="5">Рецепт</th>
					</tr>
					<tr>
						<th>Окалина между</th>
						<th>КМП между</th>
						<th>Окалина, кг</th>
						<th>КМП, кг</th>
						<th>Отсев, кг</th>
						<th>Цемент, кг</th>
						<th>Вода, кг</th>
					</tr>
				</thead>
				<tbody style="text-align: center;">
					<tr>
						<td>
							<select name="CW_ID" required>
								<option value=""></option>
								<?
								$query = "
									SELECT CW.CW_ID
										,CW.item
										,CW.fillings
										#,CW.type
										,SUM(MF.io_min) io
										,SUM(MF.sn_min) sn
										,SUM(MF.cs_min) cs
									FROM CounterWeight CW
									LEFT JOIN MixFormula MF ON MF.CW_ID = CW.CW_ID
									GROUP BY CW.CW_ID
									ORDER BY CW.CW_ID
								";
								$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
								while( $row = mysqli_fetch_array($res) ) {
									echo "<option value='{$row["CW_ID"]}' fillings='{$row["fillings"]}' type='{$row["type"]}' io='{$row["io"]}' sn='{$row["sn"]}' cs='{$row["cs"]}'>{$row["item"]}</option>";
								}
								?>
							</select>
						</td>
						<td>
							<select name="letter" style="width: 50px;" required>
								<option value=""></option>
								<option value="A">A</option>
								<option value="B">B</option>
								<option value="C">C</option>
							</select>
						</td>
						<td class="bg-gray">
							от<input type="number" min="2" max="3" step="0.01" name="io_min" style="width: 70px; display: inline-block;">
							до<input type="number" min="2" max="3" step="0.01" name="io_max" style="width: 70px; display: inline-block;">
						</td>
						<td class="bg-gray">
							от<input type="number" min="1" max="2" step="0.01" name="sn_min" style="width: 70px; display: inline-block;">
							до<input type="number" min="1" max="2" step="0.01" name="sn_max" style="width: 70px; display: inline-block;">
						</td>
						<td style="background: #a52a2a80;"><input type="number" name="iron_oxide" min="0" style="width: 80px;"></td>
						<td style="background: #f4a46082;"><input type="number" name="sand" min="0" style="width: 80px;"></td>
						<td style="background: #8b45137a;"><input type="number" name="crushed_stone" min="0" style="width: 80px;"></td>
						<td style="background: #7080906b;"><input type="number" name="cement" min="0" style="width: 80px;" required></td>
						<td style="background: #1e90ff85;"><input type="number" name="water" min="0" style="width: 80px;" required></td>
					</tr>
				</tbody>
			</table>
		</fieldset>
		<div>
			<hr>
			<input type='submit' name="subbut" value='Записать' style='float: right;'>
		</div>
	</form>
</div>

<script>
	$(function() {
		// Кнопка добавления
		$('.add_formula').click( function() {
			// Проверяем сессию
			$.ajax({ url: "check_session.php?script=1", dataType: "script", async: false });

			var MF_ID = $(this).attr("MF_ID");

			// В случае редактирования заполняем форму
			if( MF_ID ) {
				// Данные аяксом
				$.ajax({
					url: "/ajax/mix_formula_json.php?MF_ID=" + MF_ID,
					success: function(msg) { mf_data = msg; },
					dataType: "json",
					async: false
				});

				$('#formula_form input[name="MF_ID"]').val(MF_ID);
				$('#formula_form select[name="CW_ID"]').val(mf_data['CW_ID']);
				$('#formula_form select[name="letter"]').val(mf_data['letter']);
				$('#formula_form input[name="io_min"]').val(mf_data['io_min']).change();
				$('#formula_form input[name="io_max"]').val(mf_data['io_max']).change();
				$('#formula_form input[name="sn_min"]').val(mf_data['sn_min']).change();
				$('#formula_form input[name="sn_max"]').val(mf_data['sn_max']).change();
				$('#formula_form input[name="cs_min"]').val(mf_data['cs_min']).change();
				$('#formula_form input[name="cs_max"]').val(mf_data['cs_max']).change();
				$('#formula_form input[name="iron_oxide"]').val(mf_data['iron_oxide']);
				$('#formula_form input[name="sand"]').val(mf_data['sand']);
				$('#formula_form input[name="crushed_stone"]').val(mf_data['crushed_stone']);
				$('#formula_form input[name="cement"]').val(mf_data['cement']);
				$('#formula_form input[name="water"]').val(mf_data['water']);
			}
			// Иначе очищаем форму
			else {
				$('#formula_form input[name="MF_ID"]').val();
				$('#formula_form table input').val('').change();
				$('#formula_form table select').val('');
			}

			$('#formula_form').dialog({
				resizable: false,
				width: 1000,
				modal: true,
				closeText: 'Закрыть'
			});

			return false;
		});

		$('#formula_form input[name="io_min"]').change(function() {
			var val = $(this).val();
			$('#formula_form input[name="iron_oxide"]').attr('required', true);
			if( val ) {
				$('#formula_form input[name="io_max"]').attr('min', val);
			}
			else {
				$('#formula_form input[name="io_max"]').attr('min', 2);
			}
			if( !val && !$('#formula_form input[name="io_max"]').val() ) {
				$('#formula_form input[name="iron_oxide"]').attr('required', false);
			}
		});

		$('#formula_form input[name="io_max"]').change(function() {
			var val = $(this).val();
			$('#formula_form input[name="iron_oxide"]').attr('required', true);
			if( val ) {
				$('#formula_form input[name="io_min"]').attr('max', val);
			}
			else {
				$('#formula_form input[name="io_min"]').attr('max', 3);
			}
			if( !val && !$('#formula_form input[name="io_min"]').val() ) {
				$('#formula_form input[name="iron_oxide"]').attr('required', false);
			}
		});

		$('#formula_form input[name="sn_min"]').change(function() {
			var val = $(this).val();
			$('#formula_form input[name="sand"]').attr('required', true);
			if( val ) {
				$('#formula_form input[name="sn_max"]').attr('min', val);
			}
			else {
				$('#formula_form input[name="sn_max"]').attr('min', 1);
			}
			if( !val && !$('#formula_form input[name="sn_max"]').val() ) {
				$('#formula_form input[name="sand"]').attr('required', false);
			}
		});

		$('#formula_form input[name="sn_max"]').change(function() {
			var val = $(this).val();
			$('#formula_form input[name="sand"]').attr('required', true);
			if( val ) {
				$('#formula_form input[name="sn_min"]').attr('max', val);
			}
			else {
				$('#formula_form input[name="sn_min"]').attr('max', 2);
			}
			if( !val && !$('#formula_form input[name="sn_min"]').val() ) {
				$('#formula_form input[name="sand"]').attr('required', false);
			}
		});

		$('#formula_form input[name="cs_min"]').change(function() {
			var val = $(this).val();
			$('#formula_form input[name="crushed_stone"]').attr('required', true);
			if( val ) {
				$('#formula_form input[name="cs_max"]').attr('min', val);
			}
			else {
				$('#formula_form input[name="cs_max"]').attr('min', 1);
			}
			if( !val && !$('#formula_form input[name="cs_max"]').val() ) {
				$('#formula_form input[name="crushed_stone"]').attr('required', false);
			}
		});

		$('#formula_form input[name="cs_max"]').change(function() {
			var val = $(this).val();
			$('#formula_form input[name="crushed_stone"]').attr('required', true);
			if( val ) {
				$('#formula_form input[name="cs_min"]').attr('max', val);
			}
			else {
				$('#formula_form input[name="cs_min"]').attr('max', 2);
			}
			if( !val && !$('#formula_form input[name="cs_min"]').val() ) {
				$('#formula_form input[name="crushed_stone"]').attr('required', false);
			}
		});

	});
</script>
