<?
include "config.php";
include "header.php";

// Сохранение/редактирование маршрутного листа
if( isset($_POST["CW_ID"]) ) {
	$CW_ID = $_POST["CW_ID"];

	$filling_date = "{$_POST["filling_date"]} {$_POST["filling_time"]}";
	$batch = $_POST["batch"];
	$cassette = $_POST["cassette"];
	$amount = $_POST["amount"];
	$OP_ID = $_POST["OP_ID"];
	$sOP_ID = $_POST["sOP_ID"] ? $_POST["sOP_ID"] : "NULL";

	$decoupling_date = "{$_POST["decoupling_date"]} {$_POST["decoupling_time"]}";
	$d_not_spill = $_POST["d_not_spill"] ? $_POST["d_not_spill"] : "NULL";
	$d_crack = $_POST["d_crack"] ? $_POST["d_crack"] : "NULL";
	$d_chipped = $_POST["d_chipped"] ? $_POST["d_chipped"] : "NULL";
	$d_def_form = $_POST["d_def_form"] ? $_POST["d_def_form"] : "NULL";
	$d_post = $_POST["d_post"] ? $_POST["d_post"] : "NULL";

	$boxing_date = "{$_POST["boxing_date"]} {$_POST["boxing_time"]}";
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
				,batch = {$batch}
				,cassette = {$cassette}
				,amount = {$amount}
				,OP_ID = {$OP_ID}
				,sOP_ID = {$sOP_ID}

				,decoupling_date = '{$decoupling_date}'
				,d_not_spill = {$d_not_spill}
				,d_crack = {$d_crack}
				,d_chipped = {$d_chipped}
				,d_def_form = {$d_def_form}
				,d_post = {$d_post}

				,boxing_date = '{$boxing_date}'
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
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		$RS_ID = $_POST["RS_ID"];
	}
	// Сохраняем новый маршрутный лист
	else {
		$query = "
			INSERT INTO RouteSheet
			SET CW_ID = {$CW_ID}

				,filling_date = '{$filling_date}'
				,batch = {$batch}
				,cassette = {$cassette}
				,amount = {$amount}
				,OP_ID = {$OP_ID}
				,sOP_ID = {$sOP_ID}

				,decoupling_date = '{$decoupling_date}'
				,d_not_spill = {$d_not_spill}
				,d_crack = {$d_crack}
				,d_chipped = {$d_chipped}
				,d_def_form = {$d_def_form}
				,d_post = {$d_post}

				,boxing_date = '{$boxing_date}'
				,weight1 = {$weight1}
				,weight2 = {$weight2}
				,weight3 = {$weight3}
				,b_not_spill = {$b_not_spill}
				,b_crack = {$b_crack}
				,b_chipped = {$b_chipped}
				,b_def_form = {$b_def_form}
				,b_post = {$b_post}
		";
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		$RS_ID = mysqli_insert_id( $mysqli );
	}

	// Перенаправление на экран деталей набора
	exit ('<meta http-equiv="refresh" content="0; url=/#'.$RS_ID.'">');
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
			<th><i class="far fa-lg fa-hourglass" title="Интервал в часах с моента заливки."></i></th>
			<th>№ замеса</th>
			<th>№ кассеты</th>
			<th>Кол-во годных деталей</th>
			<th>Непролив</th>
			<th>Трещина</th>
			<th>Скол</th>
			<th>Дефект форм</th>
			<th>Оператор/<br>пост</th>
			<th></th>
		</tr>
	</thead>
	<tbody style="text-align: center;">

<?
$query = "
	SELECT RS.RS_ID
		,CW.item
		,CW.in_cassette
		,CW.min_weight
		,CW.max_weight

		,DATE_FORMAT(RS.filling_date, '%d.%m.%y') filling_date
		,DATE_FORMAT(RS.filling_date, '%H:%i') filling_time
		,RS.batch
		,RS.cassette
		,RS.amount
		,OP.name OPname
		,sOP.name sOPname

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

		,RS.interval1
		,RS.interval2
	FROM RouteSheet RS
	JOIN CounterWeight CW ON CW.CW_ID = RS.CW_ID
	JOIN Operator OP ON OP.OP_ID = RS.OP_ID
	LEFT JOIN Operator sOP ON sOP.OP_ID = RS.sOP_ID
	ORDER BY RS.RS_ID DESC
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	?>
		<tr style="border-top: 2px solid #333;">
			<td rowspan="3"><span style="font-size: 1.5em; font-weight: bold;"><?=$row["item"]?></span><p class="nowrap" id="<?=$row["RS_ID"]?>">id: <b><?=$row["RS_ID"]?></b></p></td>
			<td>Заливка</td>
			<td><?=$row["filling_date"]?></td>
			<td><?=$row["filling_time"]?></td>
			<td></td>
			<td><?=$row["batch"]?></td>
			<td><?=$row["cassette"]?></td>
			<td style="position: relative;"><b><?=$row["amount"]?></b><div style="background-color: chartreuse; left: 0; bottom: 0; width: <?=(100*$row["amount"]/$row["in_cassette"])?>%; position: absolute; height: 100%; opacity: .3;"></div></td>
			<td colspan="4" style="background-color: #333;"></td>
			<td><?=$row["OPname"]?><br><span style="font-size: .9em;"><?=$row["sOPname"]?></span></td>
			<td rowspan="3"><a href="#" class="add_route_sheet" RS_ID="<?=$row["RS_ID"]?>" title="Изменить маршрутный лист"><i class="fa fa-pencil-alt fa-lg"></i></a></td>
		</tr>
		<tr>
			<td>Расформовка</td>
			<td><?=$row["decoupling_date"]?></td>
			<td><?=$row["decoupling_time"]?></td>
			<td <?=($row["interval1"] < 24 ? "class='error'" : "")?>><?=$row["interval1"]?></td>
			<td colspan="2" id="weight" style="border-top: 2px solid #333; border-left: 2px solid #333; border-right: 2px solid #333;">Вес противовеса <span class="nowrap"><?=$row["min_weight"]?> - <?=$row["max_weight"]?></span> г</td>
			<td style="position: relative;" <?=($row["d_amount"] < 0 ? "class='error'" : "")?>><b><?=$row["d_amount"]?></b><div style="background-color: chartreuse; left: 0; bottom: 0; width: <?=(100*$row["d_amount"]/$row["in_cassette"])?>%; position: absolute; height: 100%; opacity: .3;"></div></td>
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
			<td <?=($row["interval2"] < 120 ? "class='error'" : "")?>><?=$row["interval2"]?></td>
			<td colspan="2" class="nowrap" style="border-left: 2px solid #333; border-right: 2px solid #333;">
				<span class="<?=(($row["weight1"] < $row["min_weight"] or $row["weight1"] > $row["max_weight"]) ? "bg-red" : "")?>"><?=$row["weight1"]?></span>
				<span class="<?=(($row["weight2"] < $row["min_weight"] or $row["weight2"] > $row["max_weight"]) ? "bg-red" : "")?>"><?=$row["weight2"]?></span>
				<span class="<?=(($row["weight3"] < $row["min_weight"] or $row["weight3"] > $row["max_weight"]) ? "bg-red" : "")?>"><?=$row["weight3"]?></span>
			</td>
			<td  style="position: relative;" <?=($row["b_amount"] < 0 ? "class='error'" : "")?>><b><?=$row["b_amount"]?></b><div style="background-color: chartreuse; left: 0; bottom: 0; width: <?=(100*$row["b_amount"]/$row["in_cassette"])?>%; position: absolute; height: 100%; opacity: .3;"></div></td>
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

<div id="add_btn" class="add_route_sheet" title="Внести маршрутный лист"></div>

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
		<input type="hidden" name="RS_ID">
		<label for="CW">Код противовеса:</label>
		<select name="CW_ID" style="font-size: 2em;" required>
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
					<td><input type="number" name="batch" min="1" max="50" style="width: 70px;" required></td>
					<td><input type="number" name="cassette" min="1" max="200" style="width: 70px;" required></td>
					<td><input type="number" name="amount" min="0" style="width: 70px;" required></td>
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
						<br>
						<select name="sOP_ID" style="width: 80px;">
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
					<td colspan="2" class="weight">Вес противовеса <b class="nowrap"></b> г</td>
					<td class="d_amount" style="font-size: 1.2em"></td>
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
			$('#route_sheet_form input[name="decoupling_date"]').attr("min", $(this).val());
		});
		$('#route_sheet_form input[name="decoupling_date"]').change(function() {
			$('#route_sheet_form input[name="boxing_date"]').attr("min", $(this).val());
		});

		// Пересчет числа годных детелей при изменениях данных по браку
		$('#route_sheet_form input[name="amount"]').change(function() {
			var d_amount = $('#route_sheet_form input[name="amount"]').val();
			$('#route_sheet_form .d_defect').each(function() {
				d_amount = d_amount - $(this).val();
			});

			var b_amount = d_amount;
			$('#route_sheet_form .b_defect').each(function() {
				b_amount = b_amount - $(this).val();
			});
			$('#route_sheet_form .d_amount').text(d_amount).effect( 'highlight', {color: 'red'}, 200 );
			$('#route_sheet_form .b_amount').text(b_amount).effect( 'highlight', {color: 'red'}, 200 );
		});
		$('#route_sheet_form .d_defect').change(function() {
			var d_amount = $('#route_sheet_form input[name="amount"]').val();
			$('#route_sheet_form .d_defect').each(function() {
				d_amount = d_amount - $(this).val();
			});

			var b_amount = d_amount;
			$('#route_sheet_form .b_defect').each(function() {
				b_amount = b_amount - $(this).val();
			});
			$('#route_sheet_form .d_amount').text(d_amount).effect( 'highlight', {color: 'red'}, 200 );
			$('#route_sheet_form .b_amount').text(b_amount).effect( 'highlight', {color: 'red'}, 200 );
		});
		$('#route_sheet_form .b_defect').change(function() {
			var b_amount = $('#route_sheet_form .d_amount').text();
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
			var RS_ID = $(this).attr("RS_ID");

			// В случае редактирования заполняем форму
			if( RS_ID ) {
				// Разблокируем форму
				$('#route_sheet_form table input').attr("disabled", false);
				$('#route_sheet_form table select').attr("disabled", false);

				// Данные маршрутного листа аяксом
				$.ajax({ url: "/ajax/route_sheet_json.php?RS_ID=" + RS_ID, success:function(msg){ route_sheet_data = msg; }, dataType: "json", async: false });

				// Идентификатор формы
				$('#route_sheet_form input[name="RS_ID"]').val(RS_ID);
				// Противовес
				$('#route_sheet_form select[name="CW_ID"]').val(route_sheet_data['CW_ID']);

				// Дата/время заливки
				$('#route_sheet_form input[name="filling_date"]').val(route_sheet_data['filling_date']).change();
				$('#route_sheet_form input[name="filling_time"]').val(route_sheet_data['filling_time']);
				// № замеса
				$('#route_sheet_form input[name="batch"]').val(route_sheet_data['batch']);
				// № Кассеты
				$('#route_sheet_form input[name="cassette"]').val(route_sheet_data['cassette']);
				// Кол-во годных деталей и максимальный предел
				$('#route_sheet_form input[name="amount"]').val(route_sheet_data['amount']);
				$('#route_sheet_form input[name="amount"]').attr("max", route_sheet_data['in_cassette']);
				// Оператор + помошник
				$('#route_sheet_form select[name="OP_ID"]').val(route_sheet_data['OP_ID']);
				$('#route_sheet_form select[name="sOP_ID"]').val(route_sheet_data['sOP_ID']);

				// Дата/время расформовки
				$('#route_sheet_form input[name="decoupling_date"]').val(route_sheet_data['decoupling_date']).change();
				$('#route_sheet_form input[name="decoupling_time"]').val(route_sheet_data['decoupling_time']);
				// Допустимые границы веса
				$('#route_sheet_form .weight b').text(route_sheet_data['min_weight'] + ' - ' + route_sheet_data['max_weight']);
				// Кол-во годных деталей
				$('#route_sheet_form .d_amount').text(route_sheet_data['d_amount']);
				// Дефекты расформовки
				$('#route_sheet_form input[name="d_not_spill"]').val(route_sheet_data['d_not_spill']);
				$('#route_sheet_form input[name="d_crack"]').val(route_sheet_data['d_crack']);
				$('#route_sheet_form input[name="d_chipped"]').val(route_sheet_data['d_chipped']);
				$('#route_sheet_form input[name="d_def_form"]').val(route_sheet_data['d_def_form']);
				// № поста
				$('#route_sheet_form input[name="d_post"]').val(route_sheet_data['d_post']);

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
				$('#route_sheet_form .d_amount').text('');
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

<?
include "footer.php";
?>
