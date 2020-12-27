<?
include_once "../config.php";

// Сохранение/редактирование плана заливки
if( isset($_POST["pb_date"]) ) {
	session_start();
	$pb_date = $_POST["pb_date"];

	foreach ($_POST["CW_ID"] as $key => $value) {
		// Редактируем
		if( $_POST["PB_ID"][$key] ) {
			$query = "
				UPDATE plan__Batch
				SET pb_date = '{$pb_date}'
					,CW_ID = {$value}
					,batches = {$_POST["batches"][$key]}
					,author = {$_SESSION['id']}
				WHERE PB_ID = {$_POST["PB_ID"][$key]}
			";
			if( !mysqli_query( $mysqli, $query ) ) $_SESSION["error"][] = "Invalid query: ".mysqli_error( $mysqli );
		}
		elseif( $_POST["batches"][$key] > 0 ) {
			$query = "
				INSERT INTO plan__Batch
				SET pb_date = '{$pb_date}'
					,CW_ID = {$value}
					,batches = {$_POST["batches"][$key]}
					,author = {$_SESSION['id']}
			";
			if( !mysqli_query( $mysqli, $query ) ) $_SESSION["error"][] = "Invalid query: ".mysqli_error( $mysqli );
		}
	}

	if( count($_SESSION["error"]) == 0) {
		$_SESSION["success"][] = "Данные успешно сохранены.";
	}

	// Получаем неделю и цикл
	$query = "
		SELECT YEARWEEK(pb_date, 1) week
			,WEEKDAY(pb_date) + 1 cycle
		FROM plan__Batch
		WHERE pb_date = '{$pb_date}'
		";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$row = mysqli_fetch_array($res);
	$week = $row["week"];
	$cycle = $row["cycle"];

	// Перенаправление в план
	exit ('<meta http-equiv="refresh" content="0; url=/plan_batch.php?week='.$week.'&#C'.$week.$cycle.'">');
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
			<input type="hidden" name="pb_date">

			<h3>Неделя/цикл: <span style="font-size: 2em;" id="week_cycle"></span></h3>
<!--			<input type="date" name="pb_date" min="<?=date('Y-m-d')?>" max="<?=date('Y-m-d', strtotime("+7 day"))?>" required>-->
			<table style="width: 100%; table-layout: fixed;">
				<thead>
					<tr>
						<th>Противовес</th>
						<th>Замесов</th>
						<th>Заливок</th>
						<th>Деталей</th>
					</tr>
				</thead>
				<tbody style="text-align: center;">
					<?
					$query = "
						SELECT CW.CW_ID, CW.item, CW.fillings, CW.in_cassette
						FROM CounterWeight CW
						ORDER BY CW.CW_ID
					";
					$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
					while( $row = mysqli_fetch_array($res) ) {
						?>
						<tr class="data_row">
							<td><b style="font-size: 1.5em;"><?=$row["item"]?></b><input type="hidden" name="CW_ID[<?=$row["CW_ID"]?>]" value="<?=$row["CW_ID"]?>"><input type="hidden" name="PB_ID[<?=$row["CW_ID"]?>]"></td>
							<td><input type="number" name="batches[<?=$row["CW_ID"]?>]" class="batches" min="0" max="30" fillings="<?=$row["fillings"]?>" in_cassette="<?=$row["in_cassette"]?>" tabindex="<?=(++$index)?>" style="width: 70px;"><i class="fas fa-question-circle" title="Не редактируется. Заливки уже состоялись."></i></td>
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

			var pb_date = $(this).attr("pb_date"),
				week_cycle = $(this).attr("cycle");

			// Если все циклы задействованы, выводим предупреждение
			if( !pb_date ) {
				noty({text: '7 - максимальное количество циклов за неделю.', type: 'error'});
				return false;
			}

			$('#plan_batch_form input[name="pb_date"]').val(pb_date);
			$('#week_cycle').html(week_cycle);

			// Данные аяксом
			$.ajax({
				url: "/ajax/plan_batch_json.php?pb_date=" + pb_date,
				success: function(msg) { pb_data = msg; },
				dataType: "json",
				async: false
			});

			// Очищаем форму
			$('#plan_batch_form .data_row input[type="number"]').val('');
			$('#plan_batch_form .total span').html('');

			for (let sub_pb_data of pb_data) {
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

				if( sub_pb_data['fakt'] > 0 ) {
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
				in_cassette = $(this).attr('in_cassette'),
				batches = $(this).val(),
				total_batches = 0,
				total_fillings = 0,
				total_details = 0;

			$(this).parents('tr').children().children('input.fillings').val(batches * fillings);
			$(this).parents('tr').children().children('input.details').val(batches * fillings * in_cassette);

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
