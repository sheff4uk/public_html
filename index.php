<?
include "config.php";
include "header.php";

// Сохранение маршрутного листа
if( isset($_POST["CW_ID"]) ) {
	$CW_ID = $_POST["CW_ID"];

	$filling_date = "{$_POST["filling_date"]} {$_POST["filling_time"]}";
	$batch = $_POST["batch"];
	$cassette = $_POST["cassette"];
	$amount = $_POST["amount"];
	$OP_ID = $_POST["OP_ID"];

	$decoupling_date = "{$_POST["decoupling_date"]} {$_POST["decoupling_time"]}";
	$d_not_spill = $_POST["d_not_spill"];
	$d_crack = $_POST["d_crack"];
	$d_chipped = $_POST["d_chipped"];
	$d_def_form = $_POST["d_def_form"];
	$d_post = $_POST["d_post"];

	$boxing_date = "{$_POST["boxing_date"]} {$_POST["boxing_time"]}";
	$weight1 = $_POST["weight1"];
	$weight2 = $_POST["weight2"];
	$weight3 = $_POST["weight3"];
	$b_not_spill = $_POST["b_not_spill"];
	$b_crack = $_POST["b_crack"];
	$b_chipped = $_POST["b_chipped"];
	$b_def_form = $_POST["b_def_form"];
	$b_post = $_POST["b_post"];

	$query = "
		INSERT INTO RouteSheet
		SET CW_ID = {$CW_ID}

			,filling_date = '{$filling_date}'
			,batch = {$batch}
			,cassette = {$cassette}
			,amount = {$amount}
			,OP_ID = {$OP_ID}

			,decoupling_date = '{$decoupling_date}'
			".($d_not_spill ? ",d_not_spill = {$d_not_spill}" : "")."
			".($d_crack ? ",d_crack = {$d_crack}" : "")."
			".($d_chipped ? ",d_chipped = {$d_chipped}" : "")."
			".($d_def_form ? ",d_def_form = {$d_def_form}" : "")."
			".($d_post ? ",d_post = {$d_post}" : "")."

			,boxing_date = '{$boxing_date}'
			".($weight1 ? ",weight1 = {$weight1}" : "")."
			".($weight2 ? ",weight2 = {$weight2}" : "")."
			".($weight3 ? ",weight3 = {$weight3}" : "")."
			".($b_not_spill ? ",b_not_spill = {$b_not_spill}" : "")."
			".($b_crack ? ",b_crack = {$b_crack}" : "")."
			".($b_chipped ? ",b_chipped = {$b_chipped}" : "")."
			".($b_def_form ? ",b_def_form = {$b_def_form}" : "")."
			".($b_post ? ",b_post = {$b_post}" : "")."
	";
	mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$RS_ID = mysqli_insert_id( $mysqli );

	// Перенаправление на экран деталей набора
	exit ('<meta http-equiv="refresh" content="0; url=/">');
}
///////////////////////////////////////////////////////

// Вывод маршрутных листов
?>
<table>
	<thead>
		<tr>
			<th>Противовес</th>
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

<?
$query = "
	SELECT RS.RS_ID
		,CW.item
		,CW.min_weight
		,CW.max_weight

		,DATE_FORMAT(RS.filling_date, '%d.%m.%y') filling_date
		,DATE_FORMAT(RS.filling_date, '%H:%i') filling_time
		,RS.batch
		,RS.cassette
		,RS.amount
		,OP.name

		,DATE_FORMAT(RS.decoupling_date, '%d.%m.%y') decoupling_date
		,DATE_FORMAT(RS.decoupling_date, '%H:%i') decoupling_time
		,RS.d_amount
		,RS.d_not_spill
		,RS.d_crack
		,RS.d_chipped
		,RS.d_def_form
		,RS.d_post

		,DATE_FORMAT(RS.boxing_date, '%d.%m.%y') boxing_date
		,DATE_FORMAT(RS.boxing_date, '%H:%i') boxing_time
		,RS.weight1
		,RS.weight2
		,RS.weight3
		,RS.b_amount
		,RS.b_not_spill
		,RS.b_crack
		,RS.b_chipped
		,RS.b_def_form
		,RS.b_post
	FROM RouteSheet RS
	JOIN CounterWeight CW ON CW.CW_ID = RS.CW_ID
	JOIN Operator OP ON OP.OP_ID = RS.OP_ID
	ORDER BY RS.RS_ID DESC
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	?>
		<tr style="border-top: 2px solid #333;">
			<td rowspan="3" style="font-size: 1.5em; font-weight: bold;"><?=$row["item"]?></td>
			<td>Заливка</td>
			<td><?=$row["filling_date"]?></td>
			<td><?=$row["filling_time"]?></td>
			<td><?=$row["batch"]?></td>
			<td><?=$row["cassette"]?></td>
			<td><?=$row["amount"]?></td>
			<td colspan="4" style="background-color: #333333;"></td>
			<td><?=$row["name"]?></td>
		</tr>
		<tr>
			<td>Расформовка</td>
			<td><?=$row["decoupling_date"]?></td>
			<td><?=$row["decoupling_time"]?></td>
			<td colspan="2" id="weight" style="border-top: 2px solid #333; border-left: 2px solid #333; border-right: 2px solid #333;">Вес противовеса <span class="nowrap"><?=$row["min_weight"]?> - <?=$row["max_weight"]?></span> г</td>
			<td><?=$row["d_amount"]?></td>
			<td><?=$row["d_not_spill"]?></td>
			<td><?=$row["d_crack"]?></td>
			<td><?=$row["d_chipped"]?></td>
			<td><?=$row["d_def_form"]?></td>
			<td><?=$row["d_post"]?></td>
		</tr>
		<tr>
			<td>Упаковка</td>
			<td><?=$row["boxing_date"]?></td>
			<td><?=$row["boxing_time"]?></td>
			<td colspan="2" class="nowrap" style="border-left: 2px solid #333; border-right: 2px solid #333;"><?=$row["weight1"]?> | <?=$row["weight2"]?> | <?=$row["weight3"]?></td>
			<td><?=$row["b_amount"]?></td>
			<td><?=$row["b_not_spill"]?></td>
			<td><?=$row["b_crack"]?></td>
			<td><?=$row["b_chipped"]?></td>
			<td><?=$row["b_def_form"]?></td>
			<td><?=$row["b_post"]?></td>
		</tr>
	<?
}
?>
	</tbody>
</table>

<div id="add_btn" title="Внести маршрутный лист"></div>

<!-- Форма маршрутного листа -->
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
					<td><input type="date" name="filling_date" required></td>
					<td><input type="time" name="filling_time" required></td>
					<td><input type="number" name="batch" min="1" max="30" style="width: 70px;" required></td>
					<td><input type="number" name="cassette" min="1" max="200" style="width: 70px;" required></td>
					<td><input type="number" name="amount" id="amount" min="0" style="width: 70px;" required></td>
					<td colspan="4" style="background-color: #333333;"></td>
					<td>
						<select name="OP_ID" style="width: 80px;" required>
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
					<td><input type="date" name="decoupling_date" required></td>
					<td><input type="time" name="decoupling_time" required></td>
					<td colspan="2" id="weight">Вес противовеса <b class="nowrap"></b> г</td>
					<td id="d_amount" style="font-size: 1.2em"></td>
					<td><input type="number" name="d_not_spill" class="d_defect" min="0" style="width: 50px;"></td>
					<td><input type="number" name="d_crack" class="d_defect" min="0" style="width: 50px;"></td>
					<td><input type="number" name="d_chipped" class="d_defect" min="0" style="width: 50px;"></td>
					<td><input type="number" name="d_def_form" class="d_defect" min="0" style="width: 50px;"></td>
					<td><input type="number" name="d_post" min="1" style="width: 50px;"></td>
				</tr>
				<tr>
					<td>Упаковка</td>
					<td><input type="date" name="boxing_date" required></td>
					<td><input type="time" name="boxing_time" required></td>
					<td colspan="2"><input type="number" name="weight1" min="1000" max="20000" style="width: 70px; font-size: 1em;"><input type="number" name="weight2" min="1000" max="20000" style="width: 70px; font-size: 1em;"><input type="number" name="weight3" min="1000" max="20000" style="width: 70px; font-size: 1em;"></td>
					<td id="b_amount" style="font-size: 1.2em"></td>
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
