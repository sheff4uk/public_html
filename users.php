<?
include "config.php";
include "checkrights.php";

//Добавление/редактирование пользователя
if( isset($_POST["USR_ID"]) ) {
	session_start();

	$chars = array("+", " ", "(", ")"); // Символы, которые требуется удалить из строки с телефоном
	$phone = $_POST["phone"] ? '\''.str_replace($chars, "", $_POST["phone"]).'\'' : 'NULL';
	$act = $_POST["act"] ? 1 : 0;
	$cardcode = $_POST["cardcode"];
	$outsourcer = $_POST["outsourcer"] ? 1 : 0;
	$official = $_POST["official"] ? 1 : 0;

	// Обработка строк
	$Surname = convert_str($_POST["Surname"]);
	$Surname = mysqli_real_escape_string($mysqli, $Surname);
	$Name = convert_str($_POST["Name"]);
	$Name = mysqli_real_escape_string($mysqli, $Name);

	// Проверка карты на повтор
	if( $cardcode != '' and $act == 1 ) {
		$query = "
			SELECT USR_Name(USR.USR_ID) name
			FROM Users USR
			WHERE USR.cardcode LIKE '{$cardcode}'
				AND USR.act = 1
		";
	}
	$res = mysqli_query( $mysqli, $query );
	$row = mysqli_fetch_array($res);
	$name = $row["name"];
	if( $name ) {
		$_SESSION["error"][] = "Карту с таким номером уже использует {$name}.";
		$cardcode = '';
	}

	// Добавление / обновление
	if( $_POST["USR_ID"] == "add" ) {
		$query = "
			INSERT INTO Users
			SET Surname = '{$Surname}'
				,Name = '{$Name}'
				,act = {$act}
				,phone = {$phone}
				,F_ID = {$_POST["F_ID"]}
				,RL_ID = {$_POST["RL_ID"]}
				,cardcode = '{$cardcode}'
				,outsourcer = {$outsourcer}
				,official = {$official}
		";
		if( !mysqli_query( $mysqli, $query ) ) {
			$_SESSION["error"][] = "Invalid query: ".mysqli_error( $mysqli );
		}
		else {
			$add = 1;
			$USR_ID = mysqli_insert_id( $mysqli );
		}
	}
	else {
		$query = "
			UPDATE Users
			SET Surname = '{$Surname}'
				,Name = '{$Name}'
				,act = {$act}
				,phone = {$phone}
				,F_ID = {$_POST["F_ID"]}
				,RL_ID = {$_POST["RL_ID"]}
				,cardcode = '{$cardcode}'
				,outsourcer = {$outsourcer}
				,official = {$official}
			WHERE USR_ID = {$_POST["USR_ID"]}
		";
		if( !mysqli_query( $mysqli, $query ) ) {
			$_SESSION["error"][] = "Invalid query: ".mysqli_error( $mysqli );
		}
		$USR_ID = $_POST["USR_ID"];
	}

	// Если указан номер карты, добавляем работника в табель
	if( $cardcode != '' and $act == 1 ) {
		$query = "
			INSERT INTO TariffMonth
			SET year = YEAR(CURDATE())
				,month = MONTH(CURDATE())
				,USR_ID = {$USR_ID}
				,F_ID = {$_POST["F_ID"]}
			ON DUPLICATE KEY UPDATE
				F_ID = F_ID
		";
		if( !mysqli_query( $mysqli, $query ) ) {
			$_SESSION["error"][] = "Invalid query: ".mysqli_error( $mysqli );
		}
	}
	// Убираем возможные пустые записи из табеля
	$query = "
		DELETE TM
		FROM TariffMonth AS TM
		WHERE TM.year = YEAR(CURDATE())
			AND TM.month = MONTH(CURDATE())
			AND TM.USR_ID = {$USR_ID}
			".(($cardcode != '' and $act == 1) ? "AND TM.F_ID NOT IN({$_POST["F_ID"]})" : "")."
			AND (
				SELECT IFNULL(SUM(1), 0)
				FROM Timesheet TS
				WHERE TS.TM_ID = TM.TM_ID
			) = 0
	";
	if( !mysqli_query( $mysqli, $query ) ) {
		$_SESSION["error"][] = "Invalid query: ".mysqli_error( $mysqli );
	}

	if( !isset($_SESSION["error"]) ) {
		$_SESSION["success"][] = $add ? "Новая запись успешно добавлена." : "Запись успешно отредактирована.";
	}

	// Перенаправление
	exit ('<meta http-equiv="refresh" content="0; url=#'.$USR_ID.'">');
}

$title = 'Пользователи';
include "header.php";

// Проверка прав на доступ к экрану
if( !in_array('users', $Rights) ) {
	header($_SERVER['SERVER_PROTOCOL'].' 403 Forbidden');
	die('Недостаточно прав для совершения операции');
}
?>

<style>
	.not_act td {
		background: rgb(150,0,0, .3);
	}
</style>

<!--Таблица с пользавателями-->
<table class="main_table">
	<thead>
		<tr>
			<th></th>
			<th>Фамилия</th>
			<th>Имя</th>
			<th>Телефон</th>
			<th>Участок</th>
			<th>Роль</th>
			<th>Номер карты</th>
			<th>Аутсорсер</th>
			<th>Официально</th>
			<th></th>
		</tr>
	</thead>
	<tbody style="text-align: center;">
		<?
		$query = "
			SELECT USR.USR_ID
				,USR_Icon(USR.USR_ID) icon
				,USR.Surname
				,USR.Name
				,USR.head
				,USR.phone
				,USR.RL_ID
				,USR.cardcode
				,USR.F_ID
				,RL.Role
				,F.f_name
				,USR.act
				,USR.outsourcer
				,USR.official
			FROM Users USR
			JOIN Roles RL ON RL.RL_ID = USR.RL_ID
			JOIN factory F ON F.F_ID = USR.F_ID
			ORDER BY USR.F_ID, USR.RL_ID, USR.Surname, USR.Name
		";
		$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		while( $row = mysqli_fetch_array($res) ) {
			//формируем массив для JSON данных
			$users_data[$row["USR_ID"]] = $row;

			$rowstyle = $row["act"] ? "" : "background: rgb(150,0,0, .3);";
			echo "
				<tr id='{$row["USR_ID"]}' class='".($row["act"] ? "" : "not_act")."'>
					<td>{$row["icon"]}</td>
					<td>{$row["Surname"]}</td>
					<td>{$row["Name"]}</td>
					<td>{$row["phone"]}</td>
					<td>{$row["f_name"]}</td>
					<td>{$row["Role"]}</td>
					<td>{$row["cardcode"]}</td>
					<td>".($row["outsourcer"] ? "<i class='fas fa-check'></i>" : "")."</td>
					<td>".($row["official"] ? "<i class='fas fa-check'></i>" : "")."</td>
					<td><a href='#' class='add_user' usr='{$row["USR_ID"]}' title='Изменить данные пользователя'><i class='fa fa-pencil-alt fa-lg'></i></td>
				</tr>
			";
		}
		?>
	</tbody>
</table>

<div id='add_btn' class="add_user" title='Добавить пользователя'></div>

<div id='user_form' class='addproduct' title='Данные пользователя' style='display:none;'>
	<form method='post' onsubmit="JavaScript:this.subbut.disabled=true;
this.subbut.value='Подождите, пожалуйста!';">
		<fieldset>
			<div id="USR_ID" style="width: auto; float: right; transform: scale(3);"></div>
			<input type="hidden" name="USR_ID">
			<div id="act">
				<label>Активен:</label>
				<div>
					<input type='checkbox' name='act' value="1">
				</div>
			</div>
			<div>
				<label>Фамилия:</label>
				<div>
					<input type='text' name='Surname' autocomplete='off' required>
				</div>
			</div>
			<div>
				<label>Имя:</label>
				<div>
					<input type='text' name='Name' autocomplete='off' required>
				</div>
			</div>
			<div>
				<label>Телефон:</label>
				<div>
					<input type="text" name="phone" id="mtel" style="width: 150px;" autocomplete="off" autofocus>
					<br>
					<span style='color: #911; font-size: .8em;'>Необходим для доступа к личному кабинету</span>
				</div>
			</div>
			<div>
				<label>Участок:</label>
				<div>
					<select name="F_ID" required>
						<option value=""></option>
						<?
						$query = "SELECT F_ID, f_name FROM factory";
						$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
						while( $row = mysqli_fetch_array($res) ) {
							echo "<option value='{$row["F_ID"]}'>{$row["f_name"]}</option>";
						}
						?>
					</select>
				</div>
			</div>
			<div>
				<label>Роль:</label>
				<div>
					<select name="RL_ID" required>
						<option value=""></option>
						<?
						$query = "SELECT RL_ID, Role FROM Roles";
						$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
						while( $row = mysqli_fetch_array($res) ) {
							echo "<option value='{$row["RL_ID"]}'>{$row["Role"]}</option>";
						}
						?>
					</select>
				</div>
			</div>
			<div>
				<label>Номер карты:</label>
				<div>
					<input id="cardcode" type='text' name='cardcode' autocomplete='off'>
					<br>
					<span style='color: #911; font-size: .8em;'>Необходим для ведения табеля</span>
				</div>
			</div>
			<div id="tariff">
				<label>Аутсорсер:</label>
				<div>
					<input type="checkbox" name="outsourcer" value="1">
				</div>
			</div>
			<div id="tariff">
				<label>Официально:</label>
				<div>
					<input type="checkbox" name="official" value="1">
				</div>
			</div>
		</fieldset>
		<div>
			<hr>
			<input type='submit' name="subbut" value='Сохранить' style='float: right;'>
		</div>
	</form>
</div>

<script>
	users_data = <?= json_encode($users_data); ?>;
	shops = <?= json_encode($shops); ?>;

	$(function() {
		$("#cardcode").mask("9999999999");

		// Кнопка добавления набора
		$('.add_user').click( function() {
			// Проверяем сессию
			$.ajax({ url: "check_session.php?script=1", dataType: "script", async: false });

			usr = $(this).attr('usr');

			// Очистка формы
			//$('#user_form fieldset select[name="head"] option').attr('disabled', false);
			$('#user_form fieldset input:not([type="checkbox"])').val('');
			$('#user_form fieldset select').val('').trigger("change");
			$('#user_form input[name="act"]').prop('checked', true );
			$('#user_form input[name="USR_ID"]').val('add');
			//$('#user_form input[name="act"]').attr('is_head', '0');

			if (usr) {
				$('#user_form #USR_ID').html(users_data[usr]['icon']);
				$('#user_form input[name="USR_ID"]').val(usr);
				$('#user_form input[name="act"]').prop('checked', users_data[usr]['act'] == 1 );
				//$('#user_form input[name="act"]').attr('is_head', users_data[usr]['is_head']);
				$('#user_form input[name="Surname"]').val(users_data[usr]['Surname']);
				$('#user_form input[name="Name"]').val(users_data[usr]['Name']);
				$('#user_form input[name="phone"]').val(users_data[usr]['phone']);
				$('#user_form select[name="F_ID"]').val(users_data[usr]['F_ID']);
				$('#user_form select[name="RL_ID"]').val(users_data[usr]['RL_ID']).trigger("change");
				$('#user_form input[name="cardcode"]').val(users_data[usr]['cardcode']);
				$('#user_form input[name="outsourcer"]').prop('checked', users_data[usr]['outsourcer'] == 1 );
				$('#user_form input[name="official"]').prop('checked', users_data[usr]['official'] == 1 );
			}

			$('#user_form').dialog({
				resizable: false,
				width: 500,
				modal: true,
				closeText: 'Закрыть'
			});

			return false;
		});

//		// При смене роли меняем содержимое формы
//		$('#user_form select[name="RL_ID"]').on("change",function(){
//			var RL_ID = $(this).val();
//
//			//Если оптовик, предлагаем выбрать контрагента, иначе выбор руководителя
//			if( RL_ID == 6 ) {
//				$('#kontragent').show('fast');
//				$('#kontragent select[name="KA_ID"]').attr("required", true);
//
//				$('#head').hide('fast');
//				$('#head select[name="head"]').val('');
//			}
//			else {
//				$('#kontragent').hide('fast');
//				$('#kontragent select[name="KA_ID"]').attr("required", false);
//				$('#kontragent select[name="KA_ID"]').val('');
//
//				$('#head').show('fast');
//			}
//
//			// Если почасовик, показываем тариф
//			if( RL_ID == 11 ) {
//				$('#tariff').show('fast');
//				$('#tariff input[name="tariff"]').attr("required", true);
//			}
//			else {
//				$('#tariff').hide('fast');
//				$('#tariff input[name="tariff"]').attr("required", false);
//				$('#tariff input[name="tariff"]').val('');
//			}
//
//			// Если продавец, эмулируем смену региона для запуска отображения салонов
//			$('#user_form select[name="CT_ID"]').trigger("change");
//		});
//
//		// При смене региона выводим список салонов этого региона если роль продавца
//		$('#user_form select[name="CT_ID"]').on("change",function(){
//			// Узнаем выбранный регион и роль
//			var CT_ID = $(this).val(),
//				RL_ID = $('#user_form select[name="RL_ID"]').val();
//			// Если продавец, выводим список салонов для региона
//			if( RL_ID == 5 ) {
//				$('#shops').show('fast');
//				$('#shops input').prop('checked', false);
//				$('#shops .shop_label').hide('fast');
//				if( typeof shops[CT_ID] !== 'undefined' ) {
//					$.each(shops[CT_ID], function(k, v) {
//						$('#user_form input[name="SH_ID['+v+']"]').parent('.shop_label').show('fast');
//					});
//				}
//			}
//			else {
//				$('#shops input').prop('checked', false);
//				$('#shops').hide('fast');
//			}
//		});
//
//		// При выборе руководителя, проверяем нет ли его среди подчиненных
//		$('#user_form select[name="head"]').on("change",function(){
//			var head = $(this).val(),
//				usr = $('#user_form input[name="USR_ID"]').val();
//			tree(usr, head, users_data);
//		});
//
//		//При попытке сделать неактивным проверяем есть ли подчиненные
//		$('#user_form input[name="act"]').click(function() {
//			var is_head = $(this).attr('is_head');
//			if( is_head > 0 ) {
//				noty({timeout: 3000, text: 'Нельзя выключить пользователя, у которого есть подчиненные!', type: 'error'});
//				return false;
//			}
//		});
//
//		function tree(usr, head, data) {
//			if( head > 0 ) {
//				if( usr != head ) {
//					var head = data[head]["head"];
//					tree(usr, head, data);
//				}
//				else {
//					noty({timeout: 3000, text: 'Нельза выбрать руководителя из числа подчиненных или самого себя!', type: 'error'});
//					$('#user_form select[name="head"] option:selected').attr('disabled', true);
//					$('#user_form select[name="head"]').val('');
//				}
//			}
//		}
	});

</script>
<?
include "footer.php";
?>
