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
	$overalls_issued = $_POST["overalls_issued"] ? '\''.$_POST["overalls_issued"].'\'' : 'NULL';

	// Обработка строк
	$Surname = convert_str($_POST["Surname"]);
	$Surname = mysqli_real_escape_string($mysqli, $Surname);
	$Name = convert_str($_POST["Name"]);
	$Name = mysqli_real_escape_string($mysqli, $Name);
	$post = convert_str($_POST["post"]);
	$post = mysqli_real_escape_string($mysqli, $post);
	$user_type = convert_str($_POST["user_type"]);
	$user_type = mysqli_real_escape_string($mysqli, $user_type);

	$post = $post ? '\''.$post.'\'' : 'NULL';
	$user_type = $user_type ? '\''.$user_type.'\'' : 'NULL';

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
				,post = {$post}
				,user_type = {$user_type}
				,overalls_issued = {$overalls_issued}
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
		// Узнаем участок до обновления
		$query = "
			SELECT F_ID
				,act
			FROM Users
			WHERE USR_ID = {$_POST["USR_ID"]}
		";
		$res = mysqli_query( $mysqli, $query );
		$row = mysqli_fetch_array($res);
		$before_F_ID = $row["F_ID"];
		$before_act = $row["act"];

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
				,post = {$post}
				,user_type = {$user_type}
				,overalls_issued = {$overalls_issued}
			WHERE USR_ID = {$_POST["USR_ID"]}
		";
		if( !mysqli_query( $mysqli, $query ) ) {
			$_SESSION["error"][] = "Invalid query: ".mysqli_error( $mysqli );
		}
		$USR_ID = $_POST["USR_ID"];
	}

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

		// Если новый работник или восстановленный или смена участка, напоминаем проверить тариф
		if( $add ) {
			$_SESSION["alert"][] = "<h1>Не забудьте установить тариф в табеле.</h1>";
		}
		elseif( $before_act != $act ) {
			$_SESSION["alert"][] = "<h1>Не забудьте проверить тариф в табеле.</h1>";
		}
		elseif( $before_F_ID != $_POST["F_ID"] ) {
			$_SESSION["alert"][] = "<h1>Не забудьте проверить тариф в табеле на новом участке.</h1>";
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
				WHERE YEAR(TS.ts_date) = TM.year
					AND MONTH(TS.ts_date) = TM.month
					AND TS.USR_ID = TM.USR_ID
					AND TS.F_ID = TM.F_ID
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
			<span>Тип:</span>
			<select name="user_type" class="<?=($_GET["user_type"] != '') ? "filtered" : ""?>" onchange="this.form.submit()">
				<option value=""></option>
			<?
				$query = "
					SELECT USR.user_type
					FROM Users USR
					WHERE USR.user_type IS NOT NULL
					GROUP BY USR.user_type
				";
				$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
				while( $row = mysqli_fetch_array($res) ) {
					$selected = ($_GET["user_type"] == $row["user_type"]) ? "selected" : "";
					echo "<option {$selected} value='{$row["user_type"]}'>{$row["user_type"]}</option>\n";
				}
			?>
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
			<th>Пост</th>
			<th>Номер карты</th>
			<th>Тип</th>
			<th>Дата выдачи спецодежды</th>
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
				,USR.RL_ID
				,USR.cardcode
				,USR.F_ID
				,RL.Role
				,USR.post
				,USR.user_type
				,F.f_name
				,USR.act
				,USR.overalls_issued
				,DATE_FORMAT(USR.overalls_issued, '%d.%m.%Y') overalls_issued_format
			FROM Users USR
			JOIN Roles RL ON RL.RL_ID = USR.RL_ID
			JOIN factory F ON F.F_ID = USR.F_ID
			WHERE 1
				".(($_GET["F_ID"] != '') ? "AND USR.F_ID = {$_GET["F_ID"]}" : "")."
				".(($_GET["user_type"] != '') ? "AND USR.user_type LIKE '{$_GET["user_type"]}'" : "")."
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
				JOIN TimesheetShift TSS ON TSS.TS_ID = TS.TS_ID
				JOIN TimeReg TR ON TR.TSS_ID = TSS.TSS_ID AND TR.tr_photo IS NOT NULL
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
					<td>{$row["post"]}</td>
					<td>{$row["cardcode"]}</td>
					<td>{$row["user_type"]}</td>
					<td>{$row["overalls_issued_format"]}</td>
					<td>
						<a href='#' class='add_user' usr='{$row["USR_ID"]}' title='Изменить данные пользователя'><i class='fa fa-pencil-alt fa-lg'></i></a>
						<a href='/printforms/card_label.php?USR_ID={$row["USR_ID"]}' class='print' title='Печать этикетки на карту'><i class='fa-solid fa-address-card fa-lg'></i></a>
					</td>
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
				<label>Пост:</label>
				<div>
					<input type='text' name='post' autocomplete='off'>
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
			<div>
				<label>Тип:</label>
				<div>
					<input type='text' name='user_type' autocomplete='off'>
				</div>
			</div>
			<div>
				<label>Дата выдачи спецодежды:</label>
				<div>
					<input type="date" name="overalls_issued" max="<?=date('Y-m-d')?>">
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
		// Автокомплит типов в форме
		<?
			$query = "
				SELECT USR.user_type
				FROM Users USR
				WHERE USR.user_type IS NOT NULL
				GROUP BY USR.user_type
			";
			$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			while( $row = mysqli_fetch_array($res) ) {
				//формируем массив для JSON данных
				$user_types[] = $row["user_type"];
			}
		?>
		var user_types = <?= json_encode($user_types); ?>;
		$('input[name="user_type"]').autocomplete({
			appendTo: "#user_form",
			source: user_types,
			minLength: 0,
			autoFocus: true
		});
		$('input[name="user_type"]').on("focus", function(){
			$( "input[name='user_type']" ).autocomplete( "search", "" );
		});

		// Автокомплит постов в форме
		<?
			$query = "
				SELECT USR.post
				FROM Users USR
				WHERE USR.post IS NOT NULL
				GROUP BY USR.post
			";
			$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			while( $row = mysqli_fetch_array($res) ) {
				//формируем массив для JSON данных
				$posts[] = $row["post"];
			}
		?>
		var posts = <?= json_encode($posts); ?>;
		$('input[name="post"]').autocomplete({
			appendTo: "#user_form",
			source: posts,
			minLength: 0
		});
		$('input[name="post"]').on("focus", function(){
			$( "input[name='post']" ).autocomplete( "search", "" );
		});

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

		// Кнопка добавления пользователя
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
				$('#user_form input[name="post"]').val(users_data[usr]['post']);
				$('#user_form input[name="cardcode"]').val(users_data[usr]['cardcode']);
				$('#user_form input[name="user_type"]').val(users_data[usr]['user_type']);
				$('#user_form input[name="overalls_issued"]').val(users_data[usr]['overalls_issued']);
			}

			$('#user_form').dialog({
				resizable: false,
				width: 500,
				modal: true,
				closeText: 'Закрыть'
			});

			// Автокомплит поверх диалога
			$('input[name="post"]').autocomplete( "option", "appendTo", "#user_form" );

			return false;
		});

		<?=($_GET["USR_ID"] ? "$('tr#{$_GET["USR_ID"]} .add_user').click();" : "")?>

		$(".print").printPage();
	});

</script>
<?
include "footer.php";
?>
