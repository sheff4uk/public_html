<?php
include "config.php";
$title = 'Журнал смен';
include "header.php";

// Проверка прав на доступ к экрану
if( !in_array('shift_log', $Rights) ) {
	header($_SERVER['SERVER_PROTOCOL'].' 403 Forbidden');
	die('Недостаточно прав для совершения операции');
}

include "./forms/shift_log_form.php";
//die("<h1>Ведутся работы</h1>");

// Если в фильтре не установлена неделя, показываем текущую
if( !$_GET["week"] ) {
	$query = "SELECT YEARWEEK(CURDATE(), 1) week";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$row = mysqli_fetch_array($res);
	$_GET["week"] = $row["week"];
}

// Если не выбран участок, берем из сессии
if( !$_GET["F_ID"] ) {
	$_GET["F_ID"] = $_SESSION['F_ID'];
}
$F_ID = $_GET["F_ID"];
?>

<!--Фильтр-->
<div id="filter">
	<h3>Фильтр</h3>
	<form method="get" style="position: relative;">
		<a href="/shift_log.php" style="position: absolute; top: 10px; right: 10px;" class="button">Сброс</a>

		<div class="nowrap" style="margin-bottom: 10px;">
			<span>Участок:</span>
			<select name="F_ID" class="<?=$_GET["F_ID"] ? "filtered" : ""?>" onchange="this.form.submit()">
				<?php
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
			<span>Неделя:</span>
			<select name="week" class="<?=$_GET["week"] ? "filtered" : ""?>" onchange="this.form.submit()">
				<?php
				$query = "
					SELECT LEFT(YEARWEEK(CURDATE(), 1), 4) year
					UNION
					SELECT LEFT(YEARWEEK(working_day, 1), 4) year
					FROM ShiftLog
					ORDER BY year DESC
				";
				$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
				while( $row = mysqli_fetch_array($res) ) {
					echo "<optgroup label='{$row["year"]}'>";
					$query = "
						SELECT SUB.week
							,SUB.week_format
							,SUB.WeekStart
							,SUB.WeekEnd
						FROM (
							SELECT LEFT(YEARWEEK(CURDATE(), 1), 4) year
								,YEARWEEK(CURDATE(), 1) week
								,RIGHT(YEARWEEK(CURDATE(), 1), 2) week_format
								,DATE_FORMAT(ADDDATE(CURDATE(), 0-WEEKDAY(CURDATE())), '%e %b') WeekStart
								,DATE_FORMAT(ADDDATE(CURDATE(), 6-WEEKDAY(CURDATE())), '%e %b') WeekEnd
							UNION
							SELECT LEFT(YEARWEEK(working_day, 1), 4) year
								,YEARWEEK(working_day, 1) week
								,RIGHT(YEARWEEK(working_day, 1), 2) week_format
								,DATE_FORMAT(ADDDATE(working_day, 0-WEEKDAY(working_day)), '%e %b') WeekStart
								,DATE_FORMAT(ADDDATE(working_day, 6-WEEKDAY(working_day)), '%e %b') WeekEnd
							FROM ShiftLog
							WHERE LEFT(YEARWEEK(working_day, 1), 4) = {$row["year"]}
							GROUP BY week
						) SUB
						WHERE SUB.year = {$row["year"]}
						ORDER BY SUB.week DESC
					";
					$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
					while( $subrow = mysqli_fetch_array($subres) ) {
						$selected = ($subrow["week"] == $_GET["week"]) ? "selected" : "";
						echo "<option value='{$subrow["week"]}' {$selected}>{$subrow["week_format"]} [{$subrow["WeekStart"]} - {$subrow["WeekEnd"]}]</option>";
					}
					echo "</optgroup>";
				}
				?>
			</select>
			<i class="fas fa-question-circle" title="По умолчанию устанавливается текущая неделя."></i>
		</div>

		<button style="float: right;">Фильтр</button>
	</form>
</div>

<?php
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

<table class="main_table">
	<thead>
		<tr>
			<th>День</th>
			<th>Смена</th>
			<th>Мастер</th>
			<th>Оператор</th>
			<th></th>
		</tr>
	</thead>
	<tbody style="text-align: center;">
	<?php
	// Узнаем первый день выбранной недели
	$query = "
		SELECT ADDDATE(CURDATE(), 0-WEEKDAY(CURDATE())) first_day
		UNION
		SELECT ADDDATE(working_day, 0-WEEKDAY(working_day)) first_day
		FROM ShiftLog
		WHERE F_ID = {$F_ID}
			AND YEARWEEK(working_day, 1) LIKE '{$_GET["week"]}'
		ORDER BY first_day
		LIMIT 1
	";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$row = mysqli_fetch_array($res);
	$first_day = $row["first_day"];

	// Получаем список дней недели
	$query = "
		SELECT ADDDATE('{$first_day}', 0) next_day
			,DATE_FORMAT(ADDDATE('{$first_day}', 0), '%d.%m.%Y') next_day_format
			,DATEDIFF(ADDDATE('{$first_day}', 0), CURDATE()) date_diff
		UNION
		SELECT ADDDATE('{$first_day}', 1) next_day
			,DATE_FORMAT(ADDDATE('{$first_day}', 1), '%d.%m.%Y') next_day_format
			,DATEDIFF(ADDDATE('{$first_day}', 1), CURDATE()) date_diff
		UNION
		SELECT ADDDATE('{$first_day}', 2) next_day
			,DATE_FORMAT(ADDDATE('{$first_day}', 2), '%d.%m.%Y') next_day_format
			,DATEDIFF(ADDDATE('{$first_day}', 2), CURDATE()) date_diff
		UNION
		SELECT ADDDATE('{$first_day}', 3) next_day
			,DATE_FORMAT(ADDDATE('{$first_day}', 3), '%d.%m.%Y') next_day_format
			,DATEDIFF(ADDDATE('{$first_day}', 3), CURDATE()) date_diff
		UNION
		SELECT ADDDATE('{$first_day}', 4) next_day
			,DATE_FORMAT(ADDDATE('{$first_day}', 4), '%d.%m.%Y') next_day_format
			,DATEDIFF(ADDDATE('{$first_day}', 4), CURDATE()) date_diff
		UNION
		SELECT ADDDATE('{$first_day}', 5) next_day
			,DATE_FORMAT(ADDDATE('{$first_day}', 5), '%d.%m.%Y') next_day_format
			,DATEDIFF(ADDDATE('{$first_day}', 5), CURDATE()) date_diff
		UNION
		SELECT ADDDATE('{$first_day}', 6) next_day
			,DATE_FORMAT(ADDDATE('{$first_day}', 6), '%d.%m.%Y') next_day_format
			,DATEDIFF(ADDDATE('{$first_day}', 6), CURDATE()) date_diff
	";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $row = mysqli_fetch_array($res) ) {
		$query = "
			SELECT SL.shift
				,USR_Icon(SL.master) master
				,USR_Icon(SL.operator) operator
			FROM ShiftLog SL
			WHERE SL.F_ID = {$F_ID}
				AND SL.working_day LIKE '{$row["next_day"]}'
		";
		$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		$num_rows = mysqli_num_rows($subres);
		$subrow = mysqli_fetch_array($subres);
		?>
		<tr style="border-top: 2px solid;">
			<td rowspan="<?=($num_rows ? $num_rows : 1)?>"><?=$row["next_day_format"]?></td>
			<td><b><?=$subrow["shift"]?></b></td>
			<td><?=$subrow["master"]?></td>
			<td><?=$subrow["operator"]?></td>
			<td rowspan="<?=($num_rows ? $num_rows : 1)?>">
				<?php
				if ($row["date_diff"] <= 0) {
					echo "<a href='#' class='add_shift_log' f_id='{$F_ID}' day='{$row["next_day_format"]}' week='{$_GET["week"]}' title='Редактировать смены'><i class='fa fa-pencil-alt fa-lg'></i></a>";
				}
				?>
			</td>
		</tr>
		<?php
		while( $subrow = mysqli_fetch_array($subres) ) {
			?>
			<tr>
				<td><b><?=$subrow["shift"]?></b></td>
				<td><?=$subrow["master"]?></td>
				<td><?=$subrow["operator"]?></td>
			</tr>
			<?php	
		}
	}
	?>
	</tbody>
</table>

<?php
include "footer.php";
?>
