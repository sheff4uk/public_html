<?
include_once "../config.php";

// Сохранение/редактирование маршрутного листа
if( isset($_POST["CW_ID"]) ) {
	session_start();

	$CW_ID = $_POST["CW_ID"];

	$filling_date = $_POST["filling_date"];
	$filling_time = $_POST["filling_time"];
	$batch = $_POST["batch"];
	$cassette = $_POST["cassette"];
	$amount = $_POST["amount"];

	$opening_date = $_POST["opening_date"];
	$opening_time = $_POST["opening_time"];
	$o_not_spill = $_POST["o_not_spill"] ? $_POST["o_not_spill"] : "NULL";
	$o_crack = $_POST["o_crack"] ? $_POST["o_crack"] : "NULL";
	$o_chipped = $_POST["o_chipped"] ? $_POST["o_chipped"] : "NULL";
	$o_def_form = $_POST["o_def_form"] ? $_POST["o_def_form"] : "NULL";
	$o_post = $_POST["o_post"] ? $_POST["o_post"] : "NULL";

	$boxing_date = $_POST["boxing_date"];
	$boxing_time = $_POST["boxing_time"];
	$weight1 = $_POST["weight1"];
	$weight2 = $_POST["weight2"];
	$weight3 = $_POST["weight3"];
	$b_not_spill = $_POST["b_not_spill"] ? $_POST["b_not_spill"] : "NULL";
	$b_crack = $_POST["b_crack"] ? $_POST["b_crack"] : "NULL";
	$b_chipped = $_POST["b_chipped"] ? $_POST["b_chipped"] : "NULL";
	$b_def_form = $_POST["b_def_form"] ? $_POST["b_def_form"] : "NULL";
	$b_post = $_POST["b_post"] ? $_POST["b_post"] : "NULL";

	// Редактируем маршрутный лист
	if( $_POST["RS_ID"] ) {
		$query = "
			UPDATE RouteSheet
			SET CW_ID = {$CW_ID}

				,filling_date = '{$filling_date}'
				,filling_time = '{$filling_time}'
				,batch = {$batch}
				,cassette = {$cassette}
				,amount = {$amount}

				,opening_date = '{$opening_date}'
				,opening_time = '{$opening_time}'
				,o_not_spill = {$o_not_spill}
				,o_crack = {$o_crack}
				,o_chipped = {$o_chipped}
				,o_def_form = {$o_def_form}
				,o_post = {$o_post}

				,boxing_date = '{$boxing_date}'
				,boxing_time = '{$boxing_time}'
				,weight1 = {$weight1}
				,weight2 = {$weight2}
				,weight3 = {$weight3}
				,b_not_spill = {$b_not_spill}
				,b_crack = {$b_crack}
				,b_chipped = {$b_chipped}
				,b_def_form = {$b_def_form}
				,b_post = {$b_post}
			WHERE RS_ID = {$_POST["RS_ID"]}
		";
		if( !mysqli_query( $mysqli, $query ) ) {
			$_SESSION["error"][] = "Invalid query: ".mysqli_error( $mysqli );
		}
		$RS_ID = $_POST["RS_ID"];
	}
	// Сохраняем новый маршрутный лист
	else {
		$query = "
			INSERT INTO RouteSheet
			SET CW_ID = {$CW_ID}

				,filling_date = '{$filling_date}'
				,filling_time = '{$filling_time}'
				,batch = {$batch}
				,cassette = {$cassette}
				,amount = {$amount}

				,opening_date = '{$opening_date}'
				,opening_time = '{$opening_time}'
				,o_not_spill = {$o_not_spill}
				,o_crack = {$o_crack}
				,o_chipped = {$o_chipped}
				,o_def_form = {$o_def_form}
				,o_post = {$o_post}

				,boxing_date = '{$boxing_date}'
				,boxing_time = '{$boxing_time}'
				,weight1 = {$weight1}
				,weight2 = {$weight2}
				,weight3 = {$weight3}
				,b_not_spill = {$b_not_spill}
				,b_crack = {$b_crack}
				,b_chipped = {$b_chipped}
				,b_def_form = {$b_def_form}
				,b_post = {$b_post}
		";
		if( !mysqli_query( $mysqli, $query ) ) {
			$_SESSION["error"][] = "Invalid query: ".mysqli_error( $mysqli );
		}
		$RS_ID = mysqli_insert_id( $mysqli );
	}

	// Перенаправление в журнал маршрутных листов
	exit ('<meta http-equiv="refresh" content="0; url=/route_sheet.php?RS_ID='.$RS_ID.'">');
}
///////////////////////////////////////////////////////
?>
<!-- Форма маршрутного листа -->
<style>
	#route_sheet_form table input,
	#route_sheet_form table select {
		font-size: 1.2em;
	}
</style>

<div id='route_sheet_form' title='Маршрутный лист' style='display:none;'>
	<form method='post' action="/forms/route_sheet_form.php" onsubmit="JavaScript:this.subbut.disabled=true;
this.subbut.value='Подождите, пожалуйста!';">
		<fieldset>
		<input type="hidden" name="RS_ID">

		Код противовеса:
		<select name="CW_ID" style="font-size: 2em;" required>
			<option value=""></option>
			<?
			$query = "
				SELECT CW.CW_ID, CW.item, CW.min_weight, CW.max_weight, CW.in_cassette
				FROM CounterWeight CW
				ORDER BY CW.CW_ID
			";
			$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			while( $row = mysqli_fetch_array($res) ) {
				echo "<option value='{$row["CW_ID"]}' in_cassette='{$row["in_cassette"]}' min_weight='{$row["min_weight"]}' max_weight='{$row["max_weight"]}'>{$row["item"]}</option>";
			}
			?>
		</select>

		<table>
			<thead>
				<tr>
					<th>Операция</th>
					<th>Дата</th>
					<th>Время</th>
					<th>№ замеса</th>
					<th>№ кассеты</th>
					<th>Кол-во годных деталей</th>
					<th>Непролив</th>
					<th>Трещина</th>
					<th>Скол</th>
					<th>Дефект форм</th>
					<th>Пост</th>
				</tr>
			</thead>
			<tbody style="text-align: center;">
				<tr>
					<td>Заливка</td>
					<td><input type="date" name="filling_date" required></td>
					<td><input type="time" name="filling_time" required></td>
					<td>
						<select name="batch" style="width: 70px;" required>
							<option value=""></option>
							<?
							for ($i = 1; $i <= 30; $i++) {
								echo "<option value='{$i}'>{$i}</option>";
							}
							?>
						</select>
					</td>
					<td><input type="number" name="cassette" min="1" max="200" style="width: 70px;" required></td>
					<td><input type="number" name="amount" min="0" style="width: 70px;" required></td>
					<td colspan="5" style="background-color: #333333;"></td>
				</tr>
				<tr>
					<td>Расформовка</td>
					<td><input type="date" name="opening_date" required></td>
					<td><input type="time" name="opening_time" required></td>
					<td colspan="2" class="weight">Вес противовеса <b class="nowrap"></b> г</td>
					<td class="o_amount" style="font-size: 1.2em"></td>
					<td><input type="number" name="o_not_spill" class="o_defect" min="0" style="width: 50px;"></td>
					<td><input type="number" name="o_crack" class="o_defect" min="0" style="width: 50px;"></td>
					<td><input type="number" name="o_chipped" class="o_defect" min="0" style="width: 50px;"></td>
					<td><input type="number" name="o_def_form" class="o_defect" min="0" style="width: 50px;"></td>
					<td><input type="number" name="o_post" min="1" style="width: 50px;"></td>
				</tr>
				<tr>
					<td>Упаковка</td>
					<td><input type="date" name="boxing_date" required></td>
					<td><input type="time" name="boxing_time" required></td>
					<td colspan="2"><input type="number" name="weight1" min="5000" max="20000" style="width: 70px; font-size: 1em;" required><input type="number" name="weight2" min="5000" max="20000" style="width: 70px; font-size: 1em;" required><input type="number" name="weight3" min="5000" max="20000" style="width: 70px; font-size: 1em;" required></td>
					<td class="b_amount" style="font-size: 1.2em"></td>
					<td><input type="number" name="b_not_spill" class="b_defect" min="0" style="width: 50px;"></td>
					<td><input type="number" name="b_crack" class="b_defect" min="0" style="width: 50px;"></td>
					<td><input type="number" name="b_chipped" class="b_defect" min="0" style="width: 50px;"></td>
					<td><input type="number" name="b_def_form" class="b_defect" min="0" style="width: 50px;"></td>
					<td><input type="number" name="b_post" min="1" style="width: 50px;"></td>
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
		// Ограничения по выбору отрицательных дат
		$('#route_sheet_form input[name="filling_date"]').change(function() {
			$('#route_sheet_form input[name="opening_date"]').attr("min", $(this).val());
		});
		$('#route_sheet_form input[name="opening_date"]').change(function() {
			$('#route_sheet_form input[name="boxing_date"]').attr("min", $(this).val());
		});

		// Пересчет числа годных детелей при изменениях данных по браку
		$('#route_sheet_form input[name="amount"]').change(function() {
			var o_amount = $('#route_sheet_form input[name="amount"]').val();
			$('#route_sheet_form .o_defect').each(function() {
				o_amount = o_amount - $(this).val();
			});

			var b_amount = o_amount;
			$('#route_sheet_form .b_defect').each(function() {
				b_amount = b_amount - $(this).val();
			});
			$('#route_sheet_form .o_amount').text(o_amount).effect( 'highlight', {color: 'red'}, 200 );
			$('#route_sheet_form .b_amount').text(b_amount).effect( 'highlight', {color: 'red'}, 200 );
		});
		$('#route_sheet_form .o_defect').change(function() {
			var o_amount = $('#route_sheet_form input[name="amount"]').val();
			$('#route_sheet_form .o_defect').each(function() {
				o_amount = o_amount - $(this).val();
			});

			var b_amount = o_amount;
			$('#route_sheet_form .b_defect').each(function() {
				b_amount = b_amount - $(this).val();
			});
			$('#route_sheet_form .o_amount').text(o_amount).effect( 'highlight', {color: 'red'}, 200 );
			$('#route_sheet_form .b_amount').text(b_amount).effect( 'highlight', {color: 'red'}, 200 );
		});
		$('#route_sheet_form .b_defect').change(function() {
			var b_amount = $('#route_sheet_form .o_amount').text();
			$('#route_sheet_form .b_defect').each(function() {
				b_amount = b_amount - $(this).val();
			});
			$('#route_sheet_form .b_amount').text(b_amount).effect( 'highlight', {color: 'red'}, 200 );
		});

		// При выборе кода противовеса разблокируется форма
		$('#route_sheet_form select[name="CW_ID"]').change(function() {
			var val = $(this).val();
			if( val ) {
				$('#route_sheet_form table input').attr("disabled", false);
				$('#route_sheet_form table select').attr("disabled", false);

				var in_cassette = $('#route_sheet_form select[name="CW_ID"] option:selected').attr('in_cassette');
				$('#route_sheet_form input[name="amount"]').val(in_cassette).change();
				$('#route_sheet_form input[name="amount"]').attr('max', in_cassette);

				var min_weight = $('#route_sheet_form select[name="CW_ID"] option:selected').attr('min_weight');
				var max_weight = $('#route_sheet_form select[name="CW_ID"] option:selected').attr('max_weight');
				$('#route_sheet_form .weight b').text(min_weight+' - '+max_weight);

				$('#route_sheet_form input[name="amount"]').effect( 'highlight', {color: 'red'}, 200 );
				$('#route_sheet_form .weight').effect( 'highlight', {color: 'red'}, 200 );
			}
			else {
				$('#route_sheet_form table input').attr("disabled", true);
				$('#route_sheet_form table select').attr("disabled", true);
			}
		});

		// Кнопка добавления маршрутного листа
		$('.add_route_sheet').click( function() {
			// Проверяем сессию
			$.ajax({ url: "check_session.php?script=1", dataType: "script", async: false });

			var RS_ID = $(this).attr("RS_ID");

			// В случае редактирования заполняем форму
			if( RS_ID ) {
				// Разблокируем форму
				$('#route_sheet_form table input').attr("disabled", false);
				$('#route_sheet_form table select').attr("disabled", false);

				// Данные маршрутного листа аяксом
				$.ajax({
					url: "/ajax/route_sheet_json.php?RS_ID=" + RS_ID,
					success: function(msg) { route_sheet_data = msg; },
					dataType: "json",
					async: false
				});

				// Идентификатор формы
				$('#route_sheet_form input[name="RS_ID"]').val(RS_ID);
				// Противовес
				$('#route_sheet_form select[name="CW_ID"]').val(route_sheet_data['CW_ID']);

				// Дата/время заливки
				$('#route_sheet_form input[name="filling_date"]').val(route_sheet_data['filling_date']).change();
				$('#route_sheet_form input[name="filling_time"]').val(route_sheet_data['filling_time']);
				// № замеса
				$('#route_sheet_form select[name="batch"]').val(route_sheet_data['batch']);
				// № Кассеты
				$('#route_sheet_form input[name="cassette"]').val(route_sheet_data['cassette']);
				// Кол-во годных деталей и максимальный предел
				$('#route_sheet_form input[name="amount"]').val(route_sheet_data['amount']);
				$('#route_sheet_form input[name="amount"]').attr("max", route_sheet_data['in_cassette']);

				// Дата/время расформовки
				$('#route_sheet_form input[name="opening_date"]').val(route_sheet_data['opening_date']).change();
				$('#route_sheet_form input[name="opening_time"]').val(route_sheet_data['opening_time']);
				// Допустимые границы веса
				$('#route_sheet_form .weight b').text(route_sheet_data['min_weight'] + ' - ' + route_sheet_data['max_weight']);
				// Кол-во годных деталей
				$('#route_sheet_form .o_amount').text(route_sheet_data['o_amount']);
				// Дефекты расформовки
				$('#route_sheet_form input[name="o_not_spill"]').val(route_sheet_data['o_not_spill']);
				$('#route_sheet_form input[name="o_crack"]').val(route_sheet_data['o_crack']);
				$('#route_sheet_form input[name="o_chipped"]').val(route_sheet_data['o_chipped']);
				$('#route_sheet_form input[name="o_def_form"]').val(route_sheet_data['o_def_form']);
				// № поста
				$('#route_sheet_form input[name="o_post"]').val(route_sheet_data['o_post']);

				// Дата/время упаковки
				$('#route_sheet_form input[name="boxing_date"]').val(route_sheet_data['boxing_date']);
				$('#route_sheet_form input[name="boxing_time"]').val(route_sheet_data['boxing_time']);
				// Контрольные взвешивания
				$('#route_sheet_form input[name="weight1"]').val(route_sheet_data['weight1']);
				$('#route_sheet_form input[name="weight2"]').val(route_sheet_data['weight2']);
				$('#route_sheet_form input[name="weight3"]').val(route_sheet_data['weight3']);
				// Кол-во годных деталей
				$('#route_sheet_form .b_amount').text(route_sheet_data['b_amount']);
				// Дефекты упаковки
				$('#route_sheet_form input[name="b_not_spill"]').val(route_sheet_data['b_not_spill']);
				$('#route_sheet_form input[name="b_crack"]').val(route_sheet_data['b_crack']);
				$('#route_sheet_form input[name="b_chipped"]').val(route_sheet_data['b_chipped']);
				$('#route_sheet_form input[name="b_def_form"]').val(route_sheet_data['b_def_form']);
				// № поста
				$('#route_sheet_form input[name="b_post"]').val(route_sheet_data['b_post']);
			}
			// Иначе очищаем форму и блокируем её
			else {
				$('#route_sheet_form input[name="RS_ID"]').val('');
				$('#route_sheet_form select[name="CW_ID"]').val('');
				$('#route_sheet_form .weight b').text('');
				$('#route_sheet_form .o_amount').text('');
				$('#route_sheet_form .b_amount').text('');
				$('#route_sheet_form table input').val('');
				$('#route_sheet_form table select').val('');
				$('#route_sheet_form table input').attr("disabled", true);
				$('#route_sheet_form table select').attr("disabled", true);
				$('#route_sheet_form input[type="date"]').attr("min", '');
			}

			$('#route_sheet_form').dialog({
				resizable: false,
				width: 1000,
				modal: true,
				closeText: 'Закрыть'
			});

			return false;
		});
	});
</script>
