<?
include "config.php";
include "header.php";

echo "<div id='add_btn' title='Внести маршрутный лист'></div>";
?>

<!-- Форма добавления набора -->
<style>
	#route_sheet_form table input,
	#route_sheet_form table select {
		font-size: 1.2em;
	}
</style>

<div id='route_sheet_form' title='Маршрутный лист' style='display:none;'>
	<form method='post' onsubmit="JavaScript:this.subbut.disabled=true;
this.subbut.value='Подождите, пожалуйста!';">
		<fieldset>
		<label for="CW">Код противовеса:</label>
		<select name="CW_ID" id="CW" style="font-size: 2em;" required>
			<option value=""></option>
			<?
			$query = "
				SELECT CW.CW_ID, CW.item, CW.min_weight, CW.max_weight, CW.in_cassette
				FROM CounterWeight CW
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
					<th>Оператор/<br>пост</th>
				</tr>
			</thead>
			<tbody style="text-align: center;">
				<tr>
					<td>Заливка</td>
					<td><input type="date" required></td>
					<td><input type="time" required></td>
					<td><input type="number" min="1" max="30" style="width: 70px;" required></td>
					<td><input type="number" min="1" max="200" style="width: 70px;" required></td>
					<td><input type="number" id="amount" min="0" style="width: 70px;" required></td>
					<td colspan="4" style="background-color: #333333;"></td>
					<td>
						<select name="OP_ID" required>
							<option value=""></option>
							<?
							$query = "
								SELECT OP.OP_ID, OP.name
								FROM Operator OP
							";
							$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
							while( $row = mysqli_fetch_array($res) ) {
								echo "<option value='{$row["OP_ID"]}'>{$row["name"]}</option>";
							}
							?>
						</select>
					</td>
				</tr>
				<tr>
					<td>Расформовка</td>
					<td><input type="date" required></td>
					<td><input type="time" required></td>
					<td colspan="2" id="weight">Вес противовеса <b class="nowrap"></b> г</td>
					<td id="d_amount" style="font-size: 1.2em"></td>
					<td><input type="number" class="d_defect" min="0" style="width: 50px;"></td>
					<td><input type="number" class="d_defect" min="0" style="width: 50px;"></td>
					<td><input type="number" class="d_defect" min="0" style="width: 50px;"></td>
					<td><input type="number" class="d_defect" min="0" style="width: 50px;"></td>
					<td><input type="number" min="1" style="width: 50px;"></td>
				</tr>
				<tr>
					<td>Упаковка</td>
					<td><input type="date" required></td>
					<td><input type="time" required></td>
					<td colspan="2"><input type="number" min="1000" max="20000" style="width: 70px; font-size: 1em;"><input type="number" min="1000" max="20000" style="width: 70px; font-size: 1em;"><input type="number" min="1000" max="20000" style="width: 70px; font-size: 1em;"></td>
					<td id="b_amount" style="font-size: 1.2em"></td>
					<td><input type="number" class="b_defect" min="0" style="width: 50px;"></td>
					<td><input type="number" class="b_defect" min="0" style="width: 50px;"></td>
					<td><input type="number" class="b_defect" min="0" style="width: 50px;"></td>
					<td><input type="number" class="b_defect" min="0" style="width: 50px;"></td>
					<td><input type="number" min="1" style="width: 50px;"></td>
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
		$('#route_sheet_form table input').attr("disabled", true);
		$('#route_sheet_form table select').attr("disabled", true);

		// Пересчет числа годных детелей при изменениях данных по браку
		$('#route_sheet_form #amount').change(function() {
			var d_amount = $('#route_sheet_form #amount').val();
			$('#route_sheet_form .d_defect').each(function() {
				d_amount = d_amount - $(this).val();
			});

			var b_amount = d_amount;
			$('#route_sheet_form .b_defect').each(function() {
				b_amount = b_amount - $(this).val();
			});
			$('#route_sheet_form #d_amount').text(d_amount).effect( 'highlight', {color: 'red'}, 500 );
			$('#route_sheet_form #b_amount').text(b_amount).effect( 'highlight', {color: 'red'}, 500 );
		});
		$('#route_sheet_form .d_defect').change(function() {
			var d_amount = $('#route_sheet_form #amount').val();
			$('#route_sheet_form .d_defect').each(function() {
				d_amount = d_amount - $(this).val();
			});

			var b_amount = d_amount;
			$('#route_sheet_form .b_defect').each(function() {
				b_amount = b_amount - $(this).val();
			});
			$('#route_sheet_form #d_amount').text(d_amount).effect( 'highlight', {color: 'red'}, 500 );
			$('#route_sheet_form #b_amount').text(b_amount).effect( 'highlight', {color: 'red'}, 500 );
		});
		$('#route_sheet_form .b_defect').change(function() {
			var b_amount = $('#route_sheet_form #d_amount').text();
			$('#route_sheet_form .b_defect').each(function() {
				b_amount = b_amount - $(this).val();
			});
			$('#route_sheet_form #b_amount').text(b_amount).effect( 'highlight', {color: 'red'}, 500 );
		});

		// При выборе кода противовеса разблокируется форма
		$('#route_sheet_form #CW').change(function() {
			var val = $(this).val();
			if( val ) {
				$('#route_sheet_form table input').attr("disabled", false);
				$('#route_sheet_form table select').attr("disabled", false);

				var in_cassette = $('#route_sheet_form #CW option:selected').attr('in_cassette');
				$('#route_sheet_form #amount').val(in_cassette).change();
				$('#route_sheet_form #amount').attr('max', in_cassette);

				var min_weight = $('#route_sheet_form #CW option:selected').attr('min_weight');
				var max_weight = $('#route_sheet_form #CW option:selected').attr('max_weight');
				$('#route_sheet_form #weight b').text(min_weight+' - '+max_weight);

				$('#route_sheet_form #amount').effect( 'highlight', {color: 'red'}, 500 );
				$('#route_sheet_form #weight').effect( 'highlight', {color: 'red'}, 500 );
			}
			else {
				$('#route_sheet_form table input').attr("disabled", true);
				$('#route_sheet_form table select').attr("disabled", true);
			}
		});

		// Кнопка добавления маршрутного листа
		$('#add_btn').click( function() {

			$('#route_sheet_form').dialog({
				resizable: false,
				draggable: false,
				width: 1000,
				modal: true,
				closeText: 'Закрыть'
			});

			return false;
		});
	});
</script>

<?
include "footer.php";
?>
