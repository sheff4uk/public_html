<?
include_once "../config.php";

// Сохранение/редактирование рецепта
if( isset($_POST["MF_ID"]) ) {
	session_start();
	$s_fraction = ($_POST["s_fraction"] != '') ? $_POST["s_fraction"] : "NULL";
	$l_fraction = ($_POST["l_fraction"] != '') ? $_POST["l_fraction"] : "NULL";
	$iron_oxide = ($_POST["iron_oxide"] != '') ? $_POST["iron_oxide"] : "NULL";
	$slag10 = ($_POST["slag10"] != '') ? $_POST["slag10"] : "NULL";
	$slag20 = ($_POST["slag20"] != '') ? $_POST["slag20"] : "NULL";
	$slag020 = ($_POST["slag020"] != '') ? $_POST["slag020"] : "NULL";
	$slag30 = ($_POST["slag30"] != '') ? $_POST["slag30"] : "NULL";
	$sand = ($_POST["sand"] != '') ? $_POST["sand"] : "NULL";
	$crushed_stone = ($_POST["crushed_stone"] != '') ? $_POST["crushed_stone"] : "NULL";
	$crushed_stone515 = ($_POST["crushed_stone515"] != '') ? $_POST["crushed_stone515"] : "NULL";
	$cement = ($_POST["cement"] != '') ? $_POST["cement"] : "0";
	$plasticizer = ($_POST["plasticizer"] != '') ? $_POST["plasticizer"] : "NULL";
	$water = ($_POST["water"] != '') ? $_POST["water"] : "0";
	$min_density = ($_POST["min_density"] != '') ? $_POST["min_density"] : "0";
	$max_density = ($_POST["max_density"] != '') ? $_POST["max_density"] : "0";

	if( $_POST["MF_ID"] ) { // Редактируем
		$query = "
			UPDATE MixFormula
			SET s_fraction = {$s_fraction}
				,l_fraction = {$l_fraction}
				,iron_oxide = {$iron_oxide}
				,slag10 = {$slag10}
				,slag20 = {$slag20}
				,slag020 = {$slag020}
				,slag30 = {$slag30}
				,sand = {$sand}
				,crushed_stone = {$crushed_stone}
				,crushed_stone515 = {$crushed_stone515}
				,cement = {$cement}
				,plasticizer = {$plasticizer}
				,water = {$water}
				,min_density = {$min_density}
				,max_density = {$max_density}
			WHERE MF_ID = {$_POST["MF_ID"]}
		";
		if( !mysqli_query( $mysqli, $query ) ) {
			$_SESSION["error"][] = "Invalid query: ".mysqli_error( $mysqli );
		}
		$MF_ID = $_POST["MF_ID"];
		$F_ID = $_POST["F_ID"];
	}

	if( !isset($_SESSION["error"]) ) {
		$_SESSION["success"][] = $add ? "Новыя запись успешно добавлена." : "Запись успешно отредактирована.";
	}

	// Перенаправление в журнал
	exit ('<meta http-equiv="refresh" content="0; url=/mix_formula.php?F_ID='.$F_ID.'#'.$MF_ID.'">');
}
?>

<style>
	#formula_form table input,
	#formula_form table select {
		font-size: 1em;
	}
</style>

<div id='formula_form' class='addproduct' style='display:none;'>
	<form method='post' action="/forms/mix_formula_form.php" onsubmit="JavaScript:this.subbut.disabled=true;
this.subbut.value='Подождите, пожалуйста!';">
		<fieldset>
			<input type="hidden" name="MF_ID">
			<input type="hidden" name="F_ID">

			<table style="width: 100%; table-layout: fixed;">
				<thead>
					<tr>
						<th>Мелкая дробь, кг</th>
						<th>Крупная дробь, кг</th>
						<th>Окалина, кг</th>
						<th>Шлак 0-10, кг</th>
						<th>Шлак 10-20, кг</th>
						<th>Шлак 0-20, кг</th>
						<th>Шлак 5-30, кг</th>
						<th>КМП, кг</th>
						<th>Отсев, кг</th>
						<th>Отсев 5-15, кг</th>
						<th>Цемент, кг</th>
						<th>Пластификатор, кг</th>
						<th>Вода, кг</th>
					</tr>
				</thead>
				<tbody style="text-align: center;">
					<tr>
						<td style="background: #7952eb88"><input type="number" name="s_fraction" min="0" style="width: 65px;"></td>
						<td style="background: #51d5d788"><input type="number" name="l_fraction" min="0" style="width: 56px;"></td>
						<td style="background: #a52a2a80;"><input type="number" name="iron_oxide" min="0" style="width: 65px;"></td>
						<td style="background: #33333380;"><input type="number" name="slag10" min="0" style="width: 65px;"></td>
						<td style="background: #33333380;"><input type="number" name="slag20" min="0" style="width: 65px;"></td>
						<td style="background: #33333380;"><input type="number" name="slag020" min="0" style="width: 65px;"></td>
						<td style="background: #33333380;"><input type="number" name="slag30" min="0" style="width: 65px;"></td>
						<td style="background: #f4a46082;"><input type="number" name="sand" min="0" style="width: 65px;"></td>
						<td style="background: #8b45137a;"><input type="number" name="crushed_stone" min="0" style="width: 65px;"></td>
						<td style="background: #8b45137a;"><input type="number" name="crushed_stone515" min="0" style="width: 65px;"></td>
						<td style="background: #7080906b;"><input type="number" name="cement" min="0" style="width: 65px;" required></td>
						<td style="background: #80800080;"><input type="number" name="plasticizer" min="0" step="0.01" style="width: 65px;"></td>
						<td style="background: #1e90ff85;"><input type="number" name="water" min="0" style="width: 65px;" required></td>
					</tr>
				</tbody>
			</table>

			<div>
				<label>Мин. плотность раствора г/л:</label>
				<div>
					<input type="number" name="min_density" min="0" max="6000" style="width: 100px;" autocomplete="off" required>
				</div>
			</div>
			<div>
				<label>Макс. плотность раствора г/л:</label>
				<div>
					<input type="number" name="max_density" min="0" max="6000" style="width: 100px;" autocomplete="off" required>
				</div>
			</div>
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

			var MF_ID = $(this).attr("MF_ID"),
				item = $(this).attr("item"),
				F_ID = $(this).attr("F_ID");

			// Заполняем форму
			// Данные аяксом
			$.ajax({
				url: "/ajax/mix_formula_json.php?MF_ID=" + MF_ID,
				success: function(msg) { mf_data = msg; },
				dataType: "json",
				async: false
			});

			$('#formula_form input[name="MF_ID"]').val(MF_ID);
			$('#formula_form input[name="F_ID"]').val(F_ID);
			$('#formula_form #item').text(item);
			$('#formula_form input[name="s_fraction"]').val(mf_data['s_fraction']);
			$('#formula_form input[name="l_fraction"]').val(mf_data['l_fraction']);
			$('#formula_form input[name="iron_oxide"]').val(mf_data['iron_oxide']);
			$('#formula_form input[name="slag10"]').val(mf_data['slag10']);
			$('#formula_form input[name="slag20"]').val(mf_data['slag20']);
			$('#formula_form input[name="slag020"]').val(mf_data['slag020']);
			$('#formula_form input[name="slag30"]').val(mf_data['slag30']);
			$('#formula_form input[name="sand"]').val(mf_data['sand']);
			$('#formula_form input[name="crushed_stone"]').val(mf_data['crushed_stone']);
			$('#formula_form input[name="crushed_stone515"]').val(mf_data['crushed_stone515']);
			$('#formula_form input[name="cement"]').val(mf_data['cement']);
			$('#formula_form input[name="plasticizer"]').val(mf_data['plasticizer']);
			$('#formula_form input[name="water"]').val(mf_data['water']);
			$('#formula_form input[name="min_density"]').val(mf_data['min_density']);
			$('#formula_form input[name="max_density"]').val(mf_data['max_density']);

			$('#formula_form').dialog({
				resizable: false,
				width: 1000,
				modal: true,
				closeText: 'Закрыть',
				title: 'Рецепт '+item
			});

			return false;
		});
	});
</script>
