<?
include_once "../config.php";

// Сохранение/редактирование рецепта
if( isset($_POST["CW_ID"]) ) {
	session_start();
	$s_fraction = ($_POST["s_fraction"] != '') ? $_POST["s_fraction"] : "NULL";
	$l_fraction = ($_POST["l_fraction"] != '') ? $_POST["l_fraction"] : "NULL";
	$iron_oxide = ($_POST["iron_oxide"] != '') ? $_POST["iron_oxide"] : "NULL";
	$sand = ($_POST["sand"] != '') ? $_POST["sand"] : "NULL";
	$crushed_stone = ($_POST["crushed_stone"] != '') ? $_POST["crushed_stone"] : "NULL";
	$cement = ($_POST["cement"] != '') ? $_POST["cement"] : "NULL";
	$plasticizer = ($_POST["plasticizer"] != '') ? $_POST["plasticizer"] : "NULL";
	$water = ($_POST["water"] != '') ? $_POST["water"] : "NULL";

	if( $_POST["MF_ID"] ) { // Редактируем
		$query = "
			UPDATE MixFormula
			SET s_fraction = {$s_fraction}
				,l_fraction = {$l_fraction}
				,iron_oxide = {$iron_oxide}
				,sand = {$sand}
				,crushed_stone = {$crushed_stone}
				,cement = {$cement}
				,plasticizer = {$plasticizer}
				,water = {$water}
			WHERE MF_ID = {$_POST["MF_ID"]}
		";
		if( !mysqli_query( $mysqli, $query ) ) {
			$_SESSION["error"][] = "Invalid query: ".mysqli_error( $mysqli );
		}
		$MF_ID = $_POST["MF_ID"];
		$F_ID = $_POST["F_ID"];
	}

	if( count($_SESSION["error"]) == 0) {
		$_SESSION["success"][] = $add ? "Новыя запись успешно добавлена." : "Запись успешно отредактирована.";
	}

	// Перенаправление в журнал
	exit ('<meta http-equiv="refresh" content="0; url=/mix_formula.php?F_ID='.$F_ID.'#'.$MF_ID.'">');
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
			<input type="hidden" name="F_ID">

			<table style="width: 100%; table-layout: fixed;">
				<thead>
					<tr>
						<th rowspan="2">Противовес</th>
						<th colspan="8">Ингредиенты</th>
					</tr>
					<tr>
						<th>Мелкая дробь, кг</th>
						<th>Крупная дробь, кг</th>
						<th>Окалина, кг</th>
						<th>КМП, кг</th>
						<th>Отсев, кг</th>
						<th>Цемент, кг</th>
						<th>Пластификатор, кг</th>
						<th>Вода, кг</th>
					</tr>
				</thead>
				<tbody style="text-align: center;">
					<tr>
						<td><input type="hidden" name="CW_ID"><b id="item"></b></td>
						<td style="background: "><input type="number" name="s_fraction" min="0" style="width: 80px;"></td>
						<td style="background: "><input type="number" name="l_fraction" min="0" style="width: 80px;"></td>
						<td style="background: #a52a2a80;"><input type="number" name="iron_oxide" min="0" style="width: 80px;"></td>
						<td style="background: #f4a46082;"><input type="number" name="sand" min="0" style="width: 80px;"></td>
						<td style="background: #8b45137a;"><input type="number" name="crushed_stone" min="0" style="width: 80px;"></td>
						<td style="background: #7080906b;"><input type="number" name="cement" min="0" style="width: 80px;" required></td>
						<td style="background: #80800080;"><input type="number" name="plasticizer" min="0" step="0.01" style="width: 80px;"></td>
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
			$('#formula_form input[name="sand"]').val(mf_data['sand']);
			$('#formula_form input[name="crushed_stone"]').val(mf_data['crushed_stone']);
			$('#formula_form input[name="cement"]').val(mf_data['cement']);
			$('#formula_form input[name="plasticizer"]').val(mf_data['plasticizer']);
			$('#formula_form input[name="water"]').val(mf_data['water']);

			$('#formula_form').dialog({
				resizable: false,
				width: 1000,
				modal: true,
				closeText: 'Закрыть'
			});

			return false;
		});
	});
</script>
