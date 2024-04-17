<?
include "config.php";
include "checkrights.php";

//Редактирование времени выдержки
if( isset($_POST["USR_ID"]) ) {
    session_start();

	// Обработка строк
	$citizenship = convert_str($_POST["citizenship"]);
	$citizenship = mysqli_real_escape_string($mysqli, $citizenship);
	$post = convert_str($_POST["post"]);
	$post = mysqli_real_escape_string($mysqli, $post);
	$comment = convert_str($_POST["comment"]);
	$comment = mysqli_real_escape_string($mysqli, $comment);

	$citizenship = $citizenship ? '\''.$citizenship.'\'' : 'NULL';
	$post = $post ? '\''.$post.'\'' : 'NULL';
    $comment = $comment ? '\''.$comment.'\'' : 'NULL';

	$passport_valid = $_POST["passport_valid"] ? '\''.$_POST["passport_valid"].'\'' : 'NULL';
    $mig_card_from = $_POST["mig_card_from"] ? '\''.$_POST["mig_card_from"].'\'' : 'NULL';
    $mig_card_valid = $_POST["mig_card_valid"] ? '\''.$_POST["mig_card_valid"].'\'' : 'NULL';
    $mig_reg_valid = $_POST["mig_reg_valid"] ? '\''.$_POST["mig_reg_valid"].'\'' : 'NULL';
    $patent_from = $_POST["patent_from"] ? '\''.$_POST["patent_from"].'\'' : 'NULL';
    $patent_valid = $_POST["patent_valid"] ? '\''.$_POST["patent_valid"].'\'' : 'NULL';
    $contract_from = $_POST["contract_from"] ? '\''.$_POST["contract_from"].'\'' : 'NULL';
    $contract_valid = $_POST["contract_valid"] ? '\''.$_POST["contract_valid"].'\'' : 'NULL';
    $DMS = $_POST["DMS"] ? '\''.$_POST["DMS"].'\'' : 'NULL';
    $certificate = $_POST["certificate"] ? '\''.$_POST["certificate"].'\'' : 'NULL';

	$query = "
		INSERT INTO Migrants
		SET USR_ID = {$_POST["USR_ID"]}
            ,passport_valid = {$passport_valid}
            ,citizenship = {$citizenship}
            ,mig_card_from = {$mig_card_from}
            ,mig_card_valid = {$mig_card_valid}
            ,mig_reg_valid = {$mig_reg_valid}
            ,patent_from = {$patent_from}
            ,patent_valid = {$patent_valid}
            ,contract_from = {$contract_from}
            ,contract_valid = {$contract_valid}
            ,post = {$post}
            ,DMS = {$DMS}
            ,certificate = {$certificate}
            ,comment = {$comment}
        ON DUPLICATE KEY UPDATE
            passport_valid = {$passport_valid}
            ,citizenship = {$citizenship}
            ,mig_card_from = {$mig_card_from}
            ,mig_card_valid = {$mig_card_valid}
            ,mig_reg_valid = {$mig_reg_valid}
            ,patent_from = {$patent_from}
            ,patent_valid = {$patent_valid}
            ,contract_from = {$contract_from}
            ,contract_valid = {$contract_valid}
            ,post = {$post}
            ,DMS = {$DMS}
            ,certificate = {$certificate}
            ,comment = {$comment}
    ";
	if( !mysqli_query( $mysqli, $query ) ) {
		$_SESSION["error"][] = "Invalid query: ".mysqli_error( $mysqli );
	}
	if( !isset($_SESSION["error"]) ) {
		$_SESSION["success"][] = "Запись успешно отредактирована.";
	}

	// Перенаправление
	exit ('<meta http-equiv="refresh" content="0; url=#'.$_POST["USR_ID"].'">');
}

$title = 'Мигранты';
include "header.php";

// Проверка прав на доступ к экрану
if( !in_array('migrants', $Rights) ) {
	header($_SERVER['SERVER_PROTOCOL'].' 403 Forbidden');
	die('Недостаточно прав для совершения операции');
}
?>
<style>
	.not_act td {
		background: rgb(150,0,0, .3);
	}
</style>

<?
// Если не выбран участок, берем из сессии
if( !$_GET["F_ID"] ) {
	$_GET["F_ID"] = $_SESSION['F_ID'];
}
?>

<!--Фильтр-->
<div id="filter">
	<h3>Фильтр</h3>
	<form method="get" style="position: relative;">
		<a href="/migrants.php" style="position: absolute; top: 10px; right: 10px;" class="button">Сброс</a>

		<div class="nowrap" style="margin-bottom: 10px;">
			<span>Участок:</span>
			<select name="F_ID" class="<?=$_GET["F_ID"] ? "filtered" : ""?>" onchange="this.form.submit()">
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

<!--Таблица с мигрантами-->
<table class="main_table">
	<thead>
		<tr>
            <th rowspan="2"></th>
			<th rowspan="2">Работник</th>
			<th>Паспорт</th>
            <th rowspan="2">Гражданство</th>
            <th colspan="2">Миг. карта</th>
            <th>Миг. учёт</th>
            <th colspan="2">Патент</th>
            <th colspan="2">Трудовой договор</th>
            <th rowspan="2">Должность</th>
            <th>ДМС</th>
            <th>Сертификат</th>
            <th rowspan="2" colspan="2">Примечание</th>
            <th rowspan="2"></th>
		</tr>
        <tr>
            <th>Срок действия</th>
            <th>Дата въезда</th>
            <th>Срок действия</th>
            <th>Срок действия</th>
            <th>Дата выдачи</th>
            <th>Срок действия по чеку</th>
            <th>Дата заключения</th>
            <th>Срок действия</th>
            <th>Срок действия</th>
            <th>Срок действия</th>
        </tr>
	</thead>
	<tbody style="text-align: center;">

<?
$query = "
	SELECT USR.USR_ID
        ,USR_Icon(USR.USR_ID) icon
        ,USR.Surname
        ,USR.Name
        ,USR.photo
        ,USR.act
        ,MG.passport_valid
        ,DATE_FORMAT(MG.passport_valid, '%d.%m.%y') passport_valid_format
        ,MG.citizenship
        ,MG.mig_card_from
        ,DATE_FORMAT(MG.mig_card_from, '%d.%m.%y') mig_card_from_format
        ,MG.mig_card_valid
        ,DATE_FORMAT(MG.mig_card_valid, '%d.%m.%y') mig_card_valid_format
        ,MG.mig_reg_valid
        ,DATE_FORMAT(MG.mig_reg_valid, '%d.%m.%y') mig_reg_valid_format
        ,MG.patent_from
        ,DATE_FORMAT(MG.patent_from, '%d.%m.%y') patent_from_format
        ,MG.patent_valid
        ,DATE_FORMAT(MG.patent_valid, '%d.%m.%y') patent_valid_format
        ,MG.contract_from
        ,DATE_FORMAT(MG.contract_from, '%d.%m.%y') contract_from_format
        ,MG.contract_valid
        ,DATE_FORMAT(MG.contract_valid, '%d.%m.%y') contract_valid_format
        ,MG.post
        ,MG.DMS
        ,DATE_FORMAT(MG.DMS, '%d.%m.%y') DMS_format
        ,MG.certificate
        ,DATE_FORMAT(MG.certificate, '%d.%m.%y') certificate_format
        ,MG.comment
	FROM Users USR
    LEFT JOIN Migrants MG ON MG.USR_ID = USR.USR_ID
    WHERE USR.F_ID = {$_GET["F_ID"]}
        AND USR.RL_ID = 4
	ORDER BY USR.act DESC, USR.Surname, USR.Name
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	//формируем массив для JSON данных
	$migrants_data[$row["USR_ID"]] = $row;
	?>
    <tr id="<?=$row["USR_ID"]?>" class="<?=($row["act"] ? "" : "not_act")?>">
        <td style='position: relative;'>
            <?=($row["photo"] ? "<img src='/time_tracking/upload/{$row["photo"]}' style='width: 100%; border-radius: 5px;'>" : "<div style='height: 32px;'></div>")?>
            <div style="position: absolute; top: 10px; left: 5px;"><?=$row["icon"]?></div>
            <?=(($row["passport"] ? "<a style='position: absolute; bottom: 10px; right: 5px;' href='/uploads/{$row["passport"]}' target='_blank' title='Паспорт'><i class='fa-solid fa-passport fa-2x' style='filter: drop-shadow(0px 0px 2px #000);'></i></a>" : ""))?>
        </td>
        <td><span><a href="users.php?USR_ID=<?=$row["USR_ID"]?>" target="_blank"><?=$row["Surname"]?> <?=$row["Name"]?></a></span></td>
        <td><span><?=$row["passport_valid_format"]?></span></td>
        <td><span><?=$row["citizenship"]?></span></td>
        <td><span><?=$row["mig_card_from_format"]?></span></td>
        <td><span><?=$row["mig_card_valid_format"]?></span></td>
        <td><span><?=$row["mig_reg_valid_format"]?></span></td>
        <td><span><?=$row["patent_from_format"]?></span></td>
        <td><span><?=$row["patent_valid_format"]?></span></td>
        <td><span><?=$row["contract_from_format"]?></span></td>
        <td><span><?=$row["contract_valid_format"]?></span></td>
        <td><span><?=$row["post"]?></span></td>
        <td><span><?=$row["DMS_format"]?></span></td>
        <td><span><?=$row["certificate_format"]?></span></td>
        <td colspan="2"><span><?=$row["comment"]?></span></td>
		<td><a href="#" class="edit_migrant" usr="<?=$row["USR_ID"]?>" title='Изменить данные'><i class="fa fa-pencil-alt fa-lg"></i></a></td>
	</tr>
	<?
}
?>
	</tbody>
</table>
<!--Конец таблицы с мигрантами-->

<!--Форма редактирования-->
<div id='migrant_form' class='addproduct' title='Данные рабочего' style='display:none;'>
	<form enctype='multipart/form-data' method='post' onsubmit="this.subbut.disabled=true;this.subbut.value='Подождите, пожалуйста!';">
		<fieldset>
			<div id="USR_ID" style="width: auto; float: right; transform: scale(3);"></div>
			<input type="hidden" name="USR_ID">
			<div>
				<label>Паспорт, срок действия:</label>
				<div>
					<input type="date" name="passport_valid">
				</div>
			</div>
			<div>
				<label>Гражданство:</label>
				<div>
					<input type='text' name='citizenship' autocomplete='off'>
				</div>
			</div>
			<div>
				<label>Миг. карта, дата въезда:</label>
				<div>
					<input type="date" name="mig_card_from">
				</div>
			</div>
			<div>
				<label>Миг. карта, срок действия:</label>
				<div>
					<input type="date" name="mig_card_valid">
				</div>
			</div>
			<div>
				<label>Миг. учёт, срок действия:</label>
				<div>
					<input type="date" name="mig_reg_valid">
				</div>
			</div>
			<div>
				<label>Патент, дата выдачи:</label>
				<div>
					<input type="date" name="patent_from">
				</div>
			</div>
			<div>
				<label>Патент, срок действия по чеку:</label>
				<div>
					<input type="date" name="patent_valid">
				</div>
			</div>
			<div>
				<label>Трудовой договор, дата заключения:</label>
				<div>
					<input type="date" name="contract_from">
				</div>
			</div>
			<div>
				<label>Трудовой договор, срок действия:</label>
				<div>
					<input type="date" name="contract_valid">
				</div>
			</div>
			<div>
				<label>Должность:</label>
				<div>
					<input type='text' name='post' autocomplete='off'>
				</div>
			</div>
			<div>
				<label>ДМС, срок действия:</label>
				<div>
					<input type="date" name="DMS">
				</div>
			</div>
			<div>
				<label>Сертификат, срок действия:</label>
				<div>
					<input type="date" name="certificate">
				</div>
			</div>
			<div>
				<label>Примечание:</label>
				<div>
                    <textarea name="comment" rows="3" cols="35"></textarea>
				</div>
			</div>
        </fieldset>
        <div>
            <hr>
            <input type='submit' name="subbut" value='Сохранить' style='float: right;'>
        </div>
	</form>
</div>
<!--Конец формы-->
<script>
	migrants_data = <?= json_encode($migrants_data); ?>;

	$(function() {
		// Автокомплит гражданства
		<?
			$query = "
				SELECT MG.citizenship
				FROM Migrants MG
				WHERE MG.citizenship IS NOT NULL
				GROUP BY MG.citizenship
			";
			$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			while( $row = mysqli_fetch_array($res) ) {
				//формируем массив для JSON данных
				$citizenships[] = $row["citizenship"];
			}
		?>
		var citizenships = <?= json_encode($citizenships); ?>;
		$('input[name="citizenship"]').autocomplete({
			appendTo: "#migrant_form",
			source: citizenships,
			minLength: 0,
			autoFocus: true
		});
		$('input[name="citizenship"]').on("focus", function(){
			$( "input[name='citizenship']" ).autocomplete( "search", "" );
		});

		// Автокомплит должностей
		<?
			$query = "
				SELECT MG.post
				FROM Migrants MG
				WHERE MG.post IS NOT NULL
				GROUP BY MG.post
			";
			$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			while( $row = mysqli_fetch_array($res) ) {
				//формируем массив для JSON данных
				$posts[] = $row["post"];
			}
		?>
		var posts = <?= json_encode($posts); ?>;
		$('input[name="post"]').autocomplete({
			appendTo: "#migrant_form",
			source: posts,
			minLength: 0
		});
		$('input[name="post"]').on("focus", function(){
		 	$( "input[name='post']" ).autocomplete( "search", "" );
		});

		// Кнопка редактирования
		$('.edit_migrant').click( function() {
			// Проверяем сессию
			$.ajax({ url: "check_session.php?script=1", dataType: "script", async: false });

			var usr = $(this).attr('usr');

            $('#migrant_form #USR_ID').html(migrants_data[usr]['icon']);
            $('#migrant_form input[name="USR_ID"]').val(usr);
            $('#migrant_form input[name="passport_valid"]').val(migrants_data[usr]['passport_valid']);
            $('#migrant_form input[name="citizenship"]').val(migrants_data[usr]['citizenship']);
            $('#migrant_form input[name="mig_card_from"]').val(migrants_data[usr]['mig_card_from']);
            $('#migrant_form input[name="mig_card_valid"]').val(migrants_data[usr]['mig_card_valid']);
            $('#migrant_form input[name="mig_reg_valid"]').val(migrants_data[usr]['mig_reg_valid']);
            $('#migrant_form input[name="patent_from"]').val(migrants_data[usr]['patent_from']);
            $('#migrant_form input[name="patent_valid"]').val(migrants_data[usr]['patent_valid']);
            $('#migrant_form input[name="contract_from"]').val(migrants_data[usr]['contract_from']);
            $('#migrant_form input[name="contract_valid"]').val(migrants_data[usr]['contract_valid']);
            $('#migrant_form input[name="post"]').val(migrants_data[usr]['post']);
            $('#migrant_form input[name="DMS"]').val(migrants_data[usr]['DMS']);
            $('#migrant_form input[name="certificate"]').val(migrants_data[usr]['certificate']);
            $('#migrant_form textarea[name="comment"]').val(migrants_data[usr]['comment']);

			$('#migrant_form').dialog({
				resizable: false,
				width: 500,
				modal: true,
				closeText: 'Закрыть'
			});

			// Автокомплит поверх диалога
			//$('input[name="citizenship"]').autocomplete( "option", "appendTo", "#migrant_form" );
			//$('input[name="post"]').autocomplete( "option", "appendTo", "#migrant_form" );

			return false;
		});
	});

</script>

<?
include "footer.php";
?>
