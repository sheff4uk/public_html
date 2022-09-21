<?
include "config.php";
include "checkrights.php";

//Добавление/редактирование пользователя
if( isset($_POST["USR_ID"]) ) {
	session_start();

	$chars = array("+", " ", "(", ")"); // Символы, которые требуется удалить из строки с телефоном
	$phone = $_POST["phone"] ? '\''.str_replace($chars, "", $_POST["phone"]).'\'' : 'NULL';
	$photo = $_POST["photo"] ? '\''.$_POST["photo"].'\'' : 'NULL';
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
				".($_POST["USR_ID"] != "add" ? "AND USR.USR_ID <> {$_POST["USR_ID"]}" : "")."
		";
		$res = mysqli_query( $mysqli, $query );
		$row = mysqli_fetch_array($res);
		$name = $row["name"];
		if( $name ) {
			$_SESSION["error"][] = "Карту с таким номером уже использует {$name}.";
			$cardcode = '';
		}
		else {
			$query = "
				UPDATE Users
				SET cardcode = ''
				WHERE cardcode LIKE '{$cardcode}'
					AND act = 0
			";
			mysqli_query( $mysqli, $query );
		}
	}

	// Добавление / обновление
	if( $_POST["USR_ID"] == "add" ) {
		$query = "
			INSERT INTO Users
			SET Surname = '{$Surname}'
				,Name = '{$Name}'
				,act = 1
				,phone = {$phone}
				,photo = {$photo}
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
				,photo = {$photo}
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

	//////////////////////////////
	// Сохранение фото паспорта //
	//////////////////////////////
	if( $_FILES['uploadfile']['name'] ) {
		$filename = date('U').'_'.$_FILES['uploadfile']['name'];
		$uploaddir = './uploads/';
		$uploadfile = $uploaddir.basename($filename);
		// Копируем файл из каталога для временного хранения файлов:
		if (copy($_FILES['uploadfile']['tmp_name'], $uploadfile))
		{
			// Записываем в БД информацию о файле
			$query = "
				UPDATE Users
				SET passport = '{$filename}'
				WHERE USR_ID = {$USR_ID}
			";
			mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

			$_SESSION["success"][] = "Файл ".$_FILES['uploadfile']['name']." успешно загружен на сервер.";
		}
		else {
			$_SESSION["alert"][] = "Ошибка! Не удалось загрузить файл на сервер!";
		}
	}
	///////////////////////////////////

	// Если указан номер карты, пытаемся добавить работника в табель
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
		elseif( mysqli_insert_id( $mysqli ) ) {
			// Если добавление произошло, заполняем прочерками ранние даты
			$i = 1;
			$cur_year = date('Y');
			$cur_month = date('m');
			$cur_day = date('d');
			while ($i < $cur_day) {
				$query = "
					INSERT INTO Timesheet
					SET ts_date = '{$cur_year}-{$cur_month}-{$i}'
						,USR_ID = {$USR_ID}
						,F_ID = {$_POST["F_ID"]}
						,status = 0
				";
				mysqli_query( $mysqli, $query );
				$i++;
			}
		}
		else {
			// Убираем метку УВ в случае восстановления
			$i = date('d');
			$cur_year = date('Y');
			$cur_month = date('m');
			$las_tday = date('t');
			while ($i <= $las_tday) {
				$query = "
					UPDATE Timesheet
					SET status = IF(status = 2, NULL, status)
					WHERE ts_date = '{$cur_year}-{$cur_month}-{$i}'
						AND USR_ID = {$USR_ID}
						AND F_ID = {$_POST["F_ID"]}
				";
				mysqli_query( $mysqli, $query );
				$i++;
			}
		}
	}

	// Усли уволен, проставляем метки УВ
	if( $cardcode != '' and $act == 0 ) {
		$i = date('d');
		$curyear = date('Y');
		$curmonth = date('m');
		$lastday = date('t');
		while ($i <= $lastday) {
			$query = "
				INSERT INTO Timesheet
				SET ts_date = '{$curyear}-{$curmonth}-{$i}'
					,USR_ID = {$USR_ID}
					,F_ID = {$_POST["F_ID"]}
					,status = 2
				ON DUPLICATE KEY UPDATE
					status = IFNULL(status, 2)
			";
			mysqli_query( $mysqli, $query );
			$i++;
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
	exit ('<meta http-equiv="refresh" content="0; url=/users.php#'.$USR_ID.'">');
}

$title = 'Пользователи';
include "header.php";

// Проверка прав на доступ к экрану
if( !in_array('users', $Rights) ) {
	header($_SERVER['SERVER_PROTOCOL'].' 403 Forbidden');
	die('Недостаточно прав для совершения операции');
}

// Если не выбран участок, берем из сессии
//if( !$_GET["F_ID"] ) {
//	$_GET["F_ID"] = $_SESSION['F_ID'];
//}
?>

<!--Фильтр-->
<div id="filter">
	<h3>Фильтр</h3>
	<form method="get" style="position: relative;">
		<a href="/users.php" style="position: absolute; top: 10px; right: 10px;" class="button">Сброс</a>

		<div class="nowrap" style="margin-bottom: 10px;">
			<span>Участок:</span>
			<select name="F_ID" class="<?=$_GET["F_ID"] ? "filtered" : ""?>" onchange="this.form.submit()">
				<option value=""></option>
				<?
				$query = "
					SELECT F_ID
						,f_name
					FROM factory
					ORDER BY F_ID
				";
				$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
				while( $row = mysqli_fetch_array($res) ) {
					$selected = ($row["F_ID"] == $_GET["F_ID"]) ? "selected" : "";
					echo "<option value='{$row["F_ID"]}' {$selected}>{$row["f_name"]}</option>";
				}
				?>
			</select>
		</div>

		<div class="nowrap" style="margin-bottom: 10px;">
			<span>Аутсорсер:</span>
			<select name="outsrc" class="<?=($_GET["outsrc"] != '') ? "filtered" : ""?>" onchange="this.form.submit()">
				<option value=""></option>
				<option <?=(($_GET["outsrc"] == '1') ? "selected" : "")?> value="1">Да</option>
				<option <?=(($_GET["outsrc"] == '0') ? "selected" : "")?> value="0">Нет</option>
			</select>
		</div>

		<div class="nowrap" style="margin-bottom: 10px;">
			<span>Активен:</span>
			<select name="act" class="<?=($_GET["act"] != '') ? "filtered" : ""?>" onchange="this.form.submit()">
				<option value=""></option>
				<option <?=(($_GET["act"] == '1') ? "selected" : "")?> value="1">Да</option>
				<option <?=(($_GET["act"] == '0') ? "selected" : "")?> value="0">Нет</option>
			</select>
		</div>

		<button style="float: right;">Фильтр</button>
	</form>
</div>

<?
// Узнаем есть ли фильтр
$filter = 0;
foreach ($_GET as &$value) {
	if( $value ) $filter = 1;
}
?>
<script>
	$(document).ready(function() {
		$( "#filter" ).accordion({
			active: <?=($filter ? "0" : "false")?>,
			collapsible: true,
			heightStyle: "content"
		});

		// При скроле сворачивается фильтр
		$(window).scroll(function(){
			$( "#filter" ).accordion({
				active: "false"
			});
		});
	});
</script>

<style>
	.not_act td {
		background: rgb(150,0,0, .3);
	}
	.wr_photo {
		transition: all 0.3s ease-in-out;
		display: flex;
		width: 160px;
		height: 120px;
		background-color: #fdce46;
		background-size: cover;
		border-radius: 5px;
		margin: 5px auto;
		overflow: hidden;
		position: relative;
		box-shadow: 5px 5px 8px rgb(0 0 0 / 60%);
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
		$usr_photo = array();

		$query = "
			SELECT USR.USR_ID
				,USR_Icon(USR.USR_ID) icon
				,USR.Surname
				,USR.Name
				,USR.head
				,USR.phone
				,USR.photo
				#,USR.passport
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
			WHERE 1
				".(($_GET["F_ID"] != '') ? "AND USR.F_ID = {$_GET["F_ID"]}" : "")."
				".(($_GET["outsrc"] != '') ? "AND IFNULL(USR.outsourcer, 0) = {$_GET["outsrc"]}" : "")."
				".(($_GET["act"] != '') ? "AND IFNULL(USR.act, 0) = {$_GET["act"]}" : "")."
			ORDER BY USR.F_ID, USR.RL_ID, USR.Surname, USR.Name
		";
		$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		while( $row = mysqli_fetch_array($res) ) {
			//формируем массив для JSON данных
			$users_data[$row["USR_ID"]] = $row;

			// Формируем массив фотографий
			if( $row["photo"] != '' ) {
				$usr_photo[$row["USR_ID"]][] = "{$row["photo"]}";
			}
			$query = "
				SELECT TR.tr_photo
				FROM Timesheet TS
				JOIN TimeReg TR ON TR.TS_ID = TS.TS_ID AND TR.tr_photo IS NOT NULL
				WHERE TS.USR_ID = {$row["USR_ID"]}
				ORDER BY TR.TR_ID DESC
				LIMIT 20
			";
			$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			while( $subrow = mysqli_fetch_array($subres) ) {
				$usr_photo[$row["USR_ID"]][] = "{$subrow["tr_photo"]}";
			}

			$rowstyle = $row["act"] ? "" : "background: rgb(150,0,0, .3);";
			echo "
				<tr id='{$row["USR_ID"]}' class='".($row["act"] ? "" : "not_act")."'>
					<td style='position: relative;'>
						".($row["photo"] ? "<img src='/time_tracking/upload/{$row["photo"]}' style='width: 100%; border-radius: 5px;'>" : "<div style='height: 32px;'></div>")."
						<div style='position: absolute; top: 10px; left: 5px;'>{$row["icon"]}</div>
						".(($row["passport"] ? "<a style='position: absolute; bottom: 10px; right: 5px;' href='/uploads/{$row["passport"]}' target='_blank' title='Паспорт'><i class='fa-solid fa-passport fa-2x' style='filter: drop-shadow(0px 0px 2px #000);'></i></a>" : ""))."
					</td>
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
	<form enctype='multipart/form-data' method='post' onsubmit="JavaScript:this.subbut.disabled=true;
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
				<label>Фото:</label>
				<div>
					<select name="photo" style="width: 200px;" data-placeholder="Выберите фото">
						<!--Формируется скриптом-->
					</select>
				</div>
			</div>
<!--
			<div>
				<label>Паспорт:</label>
				<div>
					<input type="hidden" name="MAX_FILE_SIZE" value="30000" />
					<input type='file' name='uploadfile'>
				</div>
			</div>
-->
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
	usr_photo = <?= json_encode($usr_photo); ?>;

	$(function() {
		// Select2
		function format (state) {
			var originalOption = state.element;
			if (!state.id || !$(originalOption).data('foo')) return state.text; // optgroup
			return "<div style='display: flex;'><img style='width: 160px; height: 120px;' src='/time_tracking/upload/" + $(originalOption).data('foo') + "'/><div>";
		};
		$('select[name="photo"]').select2({
			language: "ru",
			templateResult: format,
			escapeMarkup: function(m) { return m; }
		});

//		// Костыль для Select2 чтобы работал поиск
//		$.ui.dialog.prototype._allowInteraction = function (e) {
//			return true;
//		};

		$("#cardcode").mask("9999999999");

		// Кнопка добавления набора
		$('.add_user').click( function() {
			// Проверяем сессию
			$.ajax({ url: "check_session.php?script=1", dataType: "script", async: false });

			var usr = $(this).attr('usr');

			// Очистка формы
			$('#user_form #act').hide();
			$('#user_form #USR_ID').html('');
			//$('#user_form fieldset select[name="head"] option').attr('disabled', false);
			$('#user_form fieldset input:not([type="checkbox"])').val('');
			$('#user_form fieldset select').val('').trigger("change");
			$('#user_form input[name="act"]').prop('checked', true );
			$('#user_form input[name="USR_ID"]').val('add');
			//$('#user_form input[name="act"]').attr('is_head', '0');
			$('#user_form select[name="photo"]').html('');

			if (usr) {
				var arr_photo = usr_photo[usr],
					html = '';
				if( arr_photo ) {
					$.each(arr_photo, function(key, val){
						html = html + "<option value='"+val+"' data-foo='"+val+"'>"+val+"</option>";
					});
					$('#user_form select[name="photo"]').html(html);
				}

				$('#user_form #act').show();
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

		<?=($_GET["USR_ID"] ? "$('tr#{$_GET["USR_ID"]} .add_user').click();" : "")?>
	});

</script>
<?
include "footer.php";
?>
