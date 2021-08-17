<?
include "config.php";
$title = 'Журнал смен';
include "header.php";
include "./forms/shift_log_form.php";
//die("<h1>Ведутся работы</h1>");

// Если в фильтре не установлена неделя, показываем текущую
if( !$_GET["week"] ) {
	$query = "SELECT YEARWEEK(CURDATE(), 1) week";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$row = mysqli_fetch_array($res);
	$_GET["week"] = $row["week"];
}
?>

<!--Фильтр-->
<div id="filter">
	<h3>Фильтр</h3>
	<form method="get" style="position: relative;">
		<a href="/shift_log.php" style="position: absolute; top: 10px; right: 10px;" class="button">Сброс</a>

		<div class="nowrap" style="margin-bottom: 10px;">
			<span>Неделя:</span>
			<select name="week" class="<?=$_GET["week"] ? "filtered" : ""?>" onchange="this.form.submit()">
				<?
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

<table class="main_table">
	<thead>
		<tr>
			<th>День</th>
			<th>Смена<br>1 (07:00-18:59)<br>2 (19:00-06:59)</th>
			<th>Мастер</th>
			<th>Оператор</th>
			<th></th>
		</tr>
	</thead>
	<tbody style="text-align: center;">
	<?
	// Узнаем первый день выбранной недели
	$query = "
		SELECT ADDDATE(CURDATE(), 0-WEEKDAY(CURDATE())) first_day
		UNION
		SELECT ADDDATE(working_day, 0-WEEKDAY(working_day)) first_day
		FROM ShiftLog
		WHERE YEARWEEK(working_day, 1) LIKE '{$_GET["week"]}'
	";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$row = mysqli_fetch_array($res);
	$first_day = $row["first_day"];

	// Получаем список дней недели
	$query = "
		SELECT ADDDATE('{$first_day}', 0) next_day
			,DATE_FORMAT(ADDDATE('{$first_day}', 0), '%d.%m.%Y') next_day_format
		UNION
		SELECT ADDDATE('{$first_day}', 1) next_day
			,DATE_FORMAT(ADDDATE('{$first_day}', 1), '%d.%m.%Y') next_day_format
		UNION
		SELECT ADDDATE('{$first_day}', 2) next_day
			,DATE_FORMAT(ADDDATE('{$first_day}', 2), '%d.%m.%Y') next_day_format
		UNION
		SELECT ADDDATE('{$first_day}', 3) next_day
			,DATE_FORMAT(ADDDATE('{$first_day}', 3), '%d.%m.%Y') next_day_format
		UNION
		SELECT ADDDATE('{$first_day}', 4) next_day
			,DATE_FORMAT(ADDDATE('{$first_day}', 4), '%d.%m.%Y') next_day_format
		UNION
		SELECT ADDDATE('{$first_day}', 5) next_day
			,DATE_FORMAT(ADDDATE('{$first_day}', 5), '%d.%m.%Y') next_day_format
		UNION
		SELECT ADDDATE('{$first_day}', 6) next_day
			,DATE_FORMAT(ADDDATE('{$first_day}', 6), '%d.%m.%Y') next_day_format
	";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $row = mysqli_fetch_array($res) ) {
		// Первая смена
		$query = "
			SELECT USR_Icon(SL.master) master
				,USR_Icon(SL.operator) operator
			FROM ShiftLog SL
			WHERE SL.working_day LIKE '{$row["next_day"]}' AND SL.shift = 1
		";
		$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		$subrow = mysqli_fetch_array($subres);
		?>
		<tr style="border-top: 2px solid;">
			<td rowspan = "2"><?=$row["next_day_format"]?></td>
			<td><b>1</b></td>
			<td><?=$subrow["master"]?></td>
			<td><?=$subrow["operator"]?></td>
			<td rowspan = "2"><a href="#" class="add_shift_log" day="<?=$row["next_day_format"]?>" week="<?=$_GET["week"]?>" title="Редактировать смены"><i class="fa fa-pencil-alt fa-lg"></i></a></td>
		</tr>
		<?
		// Вторая смена
		$query = "
			SELECT CONCAT(MU.Surname, ' ', IFNULL(MU.Name, '')) master
				,CONCAT(OU.Surname, ' ', IFNULL(OU.Name, '')) operator
			FROM ShiftLog SL
			LEFT JOIN Users MU on MU.USR_ID = SL.master
			LEFT JOIN Users OU on OU.USR_ID = SL.operator
			WHERE SL.working_day LIKE '{$row["next_day"]}' AND SL.shift = 2
		";
		$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		$subrow = mysqli_fetch_array($subres);
		?>
		<tr>
			<td><b>2</b></td>
			<td><?=$subrow["master"]?></td>
			<td><?=$subrow["operator"]?></td>
		</tr>
		<?
	}
	?>
	</tbody>
</table>

<?
include "footer.php";
?>
