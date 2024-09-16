<?php
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
	$min_density = ($_POST["min_density"] != '') ? $_POST["min_density"]*1000 : "0";
	$max_density = ($_POST["max_density"] != '') ? $_POST["max_density"]*1000 : "0";

	if( $_POST["MF_ID"] ) { // Редактируем
		$MF_ID = $_POST["MF_ID"];
		$F_ID = $_POST["F_ID"];

		$query = "
			UPDATE MixFormula
			SET water = {$water}
				,min_density = {$min_density}
				,max_density = {$max_density}
			WHERE MF_ID = {$MF_ID}
		";
		if( !mysqli_query( $mysqli, $query ) ) {
			$_SESSION["error"][] = "Invalid query: ".mysqli_error( $mysqli );
		}

		// Очищаем рецепт чтобы записать новый
		$query = "
			DELETE FROM MixFormulaMaterial
			WHERE MF_ID = {$MF_ID}
		";
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

		foreach ($_POST["material"] as $key => $value) {
			if( $value != '' ) {
				$query = "
					INSERT INTO MixFormulaMaterial
					SET MF_ID = {$MF_ID}
						,MN_ID = {$key}
						,quantity = {$value}
				";
				mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			}
		}

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
			<?php
			$query = "
				SELECT MN.MN_ID
					,MN.material_name
					,MN.color
					,MN.step
					,MN.admission
				FROM material__Name MN
				WHERE MN.step IS NOT NULL
				ORDER BY MN.material_name
			";
			$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			while( $row = mysqli_fetch_array($res) ) {
				echo "
					<div>\n
						<label style='width: 250px;'>{$row["material_name"]}, кг</label>\n
						<div>\n
							<input type='number' name='material[{$row["MN_ID"]}]' min='0' step='{$row["step"]}' style='width: 100px; background-color: #{$row["color"]};' autocomplete='off'>\n
							<span>±{$row["admission"]}</span>\n
						</div>\n
					</div>\n
				";
			}
			?>
			<div>
				<label style="width: 250px;">Вода, л</label>
				<div>
					<input type="number" name="water" min="0" style="width: 100px; background-color: #1e90ff85;" autocomplete="off" required>
					<span>min</span>
				</div>
			</div>
			<hr>
			<div>
				<label style="width: 250px;">Плотность раствора кг/л:</label>
				<div>
					<input type="number" name="min_density" min="2" max="5.5" step="0.01" style="width: 100px;" autocomplete="off" required>
					-
					<input type="number" name="max_density" min="2" max="5.5" step="0.01" style="width: 100px;" autocomplete="off" required>
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
				url: "/ajax/mix_formula_json.php?type=1&MF_ID=" + MF_ID,
				success: function(msg) { mf_data = msg; },
				dataType: "json",
				async: false
			});

			$.ajax({
				url: "/ajax/mix_formula_json.php?type=2&MF_ID=" + MF_ID,
				success: function(msg) { mfm_data = msg; },
				dataType: "json",
				async: false
			});

			$('#formula_form input[name="MF_ID"]').val(MF_ID);
			$('#formula_form input[name="F_ID"]').val(F_ID);

			$.each(mfm_data, function( key, value ) {
				$('#formula_form input[name="material['+key+']"]').val(value);
			});

			$('#formula_form input[name="water"]').val(mf_data['water']);
			$('#formula_form input[name="min_density"]').val(mf_data['min_density']);
			$('#formula_form input[name="max_density"]').val(mf_data['max_density']);

			$('#formula_form').dialog({
				resizable: false,
				width: 600,
				modal: true,
				closeText: 'Закрыть',
				title: 'Рецепт '+item
			});

			return false;
		});
	});
</script>
