<?
include_once "../config.php";

// Сохранение/редактирование плана заливки
if( isset($_POST["cycle"]) ) {
	session_start();
	$year = $_POST["year"];
	$cycle = $_POST["cycle"];
	$F_ID = $_POST["F_ID"];

	foreach ($_POST["CW_ID"] as $key => $value) {
		// Редактируем
		if( $_POST["PB_ID"][$key] ) {
			$query = "
				UPDATE plan__Batch
				SET batches = {$_POST["batches"][$key]}
					,author = {$_SESSION['id']}
				WHERE PB_ID = {$_POST["PB_ID"][$key]}
			";
			if( !mysqli_query( $mysqli, $query ) ) $_SESSION["error"][] = "Invalid query: ".mysqli_error( $mysqli );
		}
		elseif( $_POST["batches"][$key] > 0 ) {
			$query = "
				INSERT INTO plan__Batch
				SET year = {$year}
					,cycle = {$cycle}
					,F_ID = {$F_ID}
					,CW_ID = {$value}
					,batches = {$_POST["batches"][$key]}
					,author = {$_SESSION['id']}
			";
			if( !mysqli_query( $mysqli, $query ) ) $_SESSION["error"][] = "Invalid query: ".mysqli_error( $mysqli );
		}
	}

	if( !isset($_SESSION["error"]) ) {
		$_SESSION["success"][] = "Данные успешно сохранены.";
	}

	// Перенаправление в план
	exit ('<meta http-equiv="refresh" content="0; url=/plan_batch.php?year='.$year.'&F_ID='.$F_ID.'&#C'.$year.$cycle.'">');
}
?>

<style>
	#plan_batch_form table input,
	#plan_batch_form table select {
		font-size: 1.2em;
	}
</style>

<div id='plan_batch_form' title='Данные плана заливки' style='display:none;'>
	<form method='post' action="/forms/plan_batch_form.php" onsubmit="JavaScript:this.subbut.disabled=true;
this.subbut.value='Подождите, пожалуйста!';">
		<fieldset>
			<input type="hidden" name="year">
			<input type="hidden" name="cycle">
			<input type="hidden" name="F_ID">

			<h3>Цикл: <span style="font-size: 2em;" id="year_cycle"></span></h3>
			<table style="width: 100%; table-layout: fixed;">
				<thead>
					<tr>
						<th>Противовес</th>
						<th>Замесов</th>
						<th>Кассет</th>
						<th>Деталей</th>
					</tr>
				</thead>
				<tbody style="text-align: center;">
					<?
					$query = "
						SELECT CW.CW_ID, CW.item
						FROM CounterWeight CW
						JOIN MixFormula MF ON MF.CW_ID = CW.CW_ID AND MF.F_ID = {$_GET["F_ID"]}
						ORDER BY CW.CW_ID
					";
					$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
					while( $row = mysqli_fetch_array($res) ) {
						?>
						<tr class="data_row">
							<td><b style="font-size: 1.5em;"><?=$row["item"]?></b><input type="hidden" name="CW_ID[<?=$row["CW_ID"]?>]" value="<?=$row["CW_ID"]?>"><input type="hidden" name="PB_ID[<?=$row["CW_ID"]?>]"></td>
							<td><input type="number" name="batches[<?=$row["CW_ID"]?>]" class="batches" min="0" max="80" fillings="" per_batch="" in_cassette="" tabindex="<?=(++$index)?>" style="width: 70px;"><i class="fas fa-question-circle" title="Не редактируется. Заливки уже состоялись."></i></td>
							<td><input type="number" name="fillings" class="fillings" style="width: 70px;" readonly></td>
							<td><input type="number" name="details" class="details" style="width: 70px;" readonly></td>
						</tr>
						<?
					}
					?>
					<tr class="total">
						<td>Всего:</td>
						<td id="total_batches"><span></span></td>
						<td id="total_fillings"><span></span></td>
						<td id="total_details"><span></span></td>
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
		$('.add_pb').click( function() {
			// Проверяем сессию
			$.ajax({ url: "check_session.php?script=1", dataType: "script", async: false });

			var cycle = $(this).attr("cycle"),
				year = $(this).attr("year"),
				F_ID = $(this).attr("f_id");

			$('#plan_batch_form input[name="year"]').val(year);
			$('#plan_batch_form input[name="cycle"]').val(cycle);
			$('#plan_batch_form input[name="F_ID"]').val(F_ID);
			$('#year_cycle').html(year + "-" + cycle);

			// Данные аяксом
			$.ajax({
				url: "/ajax/plan_batch_json.php?year=" + year + "&cycle=" + cycle + "&F_ID=" + F_ID,
				success: function(msg) { pb_data = msg; },
				dataType: "json",
				async: false
			});

			// Очищаем форму
			$('#plan_batch_form .data_row input[type="number"]').val('');
			$('#plan_batch_form .total span').html('');

			for (let sub_pb_data of pb_data) {
				$('#plan_batch_form input[name="batches[' + sub_pb_data['CW_ID'] + ']"]').attr('step', sub_pb_data['per_batch']);
				$('#plan_batch_form input[name="batches[' + sub_pb_data['CW_ID'] + ']"]').attr('fillings', sub_pb_data['fillings']);
				$('#plan_batch_form input[name="batches[' + sub_pb_data['CW_ID'] + ']"]').attr('per_batch', sub_pb_data['per_batch']);
				$('#plan_batch_form input[name="batches[' + sub_pb_data['CW_ID'] + ']"]').attr('in_cassette', sub_pb_data['in_cassette']);
				$('#plan_batch_form input[name="batches[' + sub_pb_data['CW_ID'] + ']"]').attr('placeholder', sub_pb_data['placeholder']);

				if( sub_pb_data['PB_ID'] ) {
					$('#plan_batch_form input[name="batches[' + sub_pb_data['CW_ID'] + ']"]').val(sub_pb_data['batches']).change();
					$('#plan_batch_form input[name="batches[' + sub_pb_data['CW_ID'] + ']"]').attr('required', true);
					$('#plan_batch_form input[name="PB_ID[' + sub_pb_data['CW_ID'] + ']"]').val(sub_pb_data['PB_ID']);
				}
				else {
					$('#plan_batch_form input[name="batches[' + sub_pb_data['CW_ID'] + ']"]').attr('required', false);
					$('#plan_batch_form input[name="PB_ID[' + sub_pb_data['CW_ID'] + ']"]').val('');
				}

				if( sub_pb_data['fact_batches'] > 0 ) {
					$('#plan_batch_form input[name="batches[' + sub_pb_data['CW_ID'] + ']"]').attr('readonly', true);
					$('#plan_batch_form input[name="batches[' + sub_pb_data['CW_ID'] + ']"]').parent().children('i').show('fast');
				}
				else {
					$('#plan_batch_form input[name="batches[' + sub_pb_data['CW_ID'] + ']"]').attr('readonly', false);
					$('#plan_batch_form input[name="batches[' + sub_pb_data['CW_ID'] + ']"]').parent().children('i').hide('fast');
				}
			}

			$('#plan_batch_form').dialog({
				resizable: false,
				width: 1000,
				modal: true,
				closeText: 'Закрыть'
			});

			return false;
		});

		// При изменении кол-ва замесов пересчитываем заливки и детали
		$('#plan_batch_form input.batches').change(function() {
			var fillings = $(this).attr('fillings'),
				per_batch = $(this).attr('per_batch'),
				in_cassette = $(this).attr('in_cassette'),
				batches = $(this).val(),
				total_batches = 0,
				total_fillings = 0,
				total_details = 0;

			$(this).parents('tr').children().children('input.fillings').val(batches * fillings / per_batch);
			$(this).parents('tr').children().children('input.details').val(batches * fillings / per_batch * in_cassette);

			// Вычисляем сумму
			$('.data_row').each(function(){
				total_batches += Number($(this).children().children('input.batches').val());
				total_fillings += Number($(this).children().children('input.fillings').val());
				total_details += Number($(this).children().children('input.details').val());
			});

			$('#total_batches span').html(total_batches);
			$('#total_fillings span').html(total_fillings);
			$('#total_details span').html(total_details);
		});
	});
</script>
