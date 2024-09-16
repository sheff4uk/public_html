<?php
include "config.php";
$title = 'Приемка сырья';
include "header.php";

// Проверка прав на доступ к экрану
if( !in_array('material_accounting', $Rights) ) {
	header($_SERVER['SERVER_PROTOCOL'].' 403 Forbidden');
	die('Недостаточно прав для совершения операции');
}

$location = $_SERVER['REQUEST_URI'];
include "./forms/material_accounting_form.php";

// Если в фильтре не установлен период, показываем последние 7 дней
if( !$_GET["date_from"] ) {
	$date = date_create('-6 days');
	$_GET["date_from"] = date_format($date, 'Y-m-d');
}
if( !$_GET["date_to"] ) {
	$date = date_create('-0 days');
	$_GET["date_to"] = date_format($date, 'Y-m-d');
}
?>

<!--Фильтр-->
<div id="filter">
	<h3>Фильтр</h3>
	<form method="get" style="position: relative;">
		<a href="/material_accounting.php" style="position: absolute; top: 10px; right: 10px;" class="button">Сброс</a>

		<div class="nowrap" style="display: inline-block; margin-bottom: 10px; margin-right: 30px;">
			<span style="display: inline-block; width: 200px;">Дата приемки между:</span>
			<input name="date_from" type="date" value="<?=$_GET["date_from"]?>" class="<?=$_GET["date_from"] ? "filtered" : ""?>">
			<input name="date_to" type="date" value="<?=$_GET["date_to"]?>" class="<?=$_GET["date_to"] ? "filtered" : ""?>">
			<i class="fas fa-question-circle" title="По умолчанию устанавливаются последние 7 дней."></i>
		</div>

		<div class="nowrap" style="display: inline-block; margin-bottom: 10px; margin-right: 30px;">
			<span>Участок:</span>
			<select name="F_ID" class="<?=$_GET["F_ID"] ? "filtered" : ""?>">
				<option value=""></option>
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

		<div class="nowrap" style="display: inline-block; margin-bottom: 10px; margin-right: 30px;">
			<span>Наименование продукции:</span>
			<select name="MN" class="<?=$_GET["MN"] ? "filtered" : ""?>">
				<option value=""></option>
				<?php
				$query = "
					SELECT MN.MN_ID, MN.material_name
					FROM material__Name MN
					ORDER BY MN.material_name
				";
				$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
				while( $row = mysqli_fetch_array($res) ) {
					$selected = ($row["MN_ID"] == $_GET["MN"]) ? "selected" : "";
					echo "<option value='{$row["MN_ID"]}' {$selected}>{$row["material_name"]}</option>";
				}
				?>
			</select>
		</div>

		<div class="nowrap" style="display: inline-block; margin-bottom: 10px; margin-right: 30px;">
			<span><a href="#" id="add_supplier_list">Поставщик:</a></span>
			<select name="MS" class="<?=$_GET["MS"] ? "filtered" : ""?>">
				<option value=""></option>
				<?php
				$query = "
					SELECT MS.MS_ID, MS.supplier
					FROM material__Supplier MS
					ORDER BY MS.supplier
				";
				$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
				while( $row = mysqli_fetch_array($res) ) {
					$selected = ($row["MS_ID"] == $_GET["MS"]) ? "selected" : "";
					echo "<option value='{$row["MS_ID"]}' {$selected}>{$row["supplier"]}</option>";
				}
				?>
			</select>
		</div>

		<div class="nowrap" style="display: inline-block; margin-bottom: 10px; margin-right: 30px;">
			<span><a href="#" id="add_carrier_list">Перевозчик:</a></span>
			<select name="MC" class="<?=$_GET["MC"] ? "filtered" : ""?>">
				<option value=""></option>
				<?php
				$query = "
					SELECT MC.MC_ID, MC.carrier
					FROM material__Carrier MC
					ORDER BY MC.carrier
				";
				$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
				while( $row = mysqli_fetch_array($res) ) {
					$selected = ($row["MC_ID"] == $_GET["MC"]) ? "selected" : "";
					echo "<option value='{$row["MC_ID"]}' {$selected}>{$row["carrier"]}</option>";
				}
				?>
			</select>
		</div>

		<div class="nowrap" style="display: inline-block; margin-bottom: 10px; margin-right: 30px;">
			<span>№ттн, №тн:</span>
			<input type="text" name="IN" value="<?=$_GET["IN"]?>" class="<?=$_GET["IN"] ? "filtered" : ""?>">
		</div>

		<div class="nowrap" style="display: inline-block; margin-bottom: 10px; margin-right: 30px;">
			<span>№ автомобиля:</span>
			<input type="text" name="CN" value="<?=$_GET["CN"]?>" class="<?=$_GET["CN"] ? "filtered" : ""?>">
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
		
		$( "#summary" ).accordion({
			active: false,
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

<div id="summary">
	<h3>Остатки сырья</h3>
	<div>
		<?php
		$query = "
			SELECT F_ID
				,f_name
			FROM factory
		";
		$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		while( $row = mysqli_fetch_array($res) ) {
			?>
			<div style="display: inline-block; padding: 0 20px;">
			<h2><?=$row["f_name"]?>:</h2>
			<table style="text-align: center; font-weight: bold;">
				<thead>
					<tr>
						<th>Наименование</th>
						<th>Количество</th>
						<th></th>
					</tr>
				</thead>
				<tbody>
			<?php
			$query = "
				SELECT M.company
					,M.M_ID
					,SUM(1) cnt
				FROM material__Balance MB
				JOIN material__Name MN ON MN.MN_ID = MB.MN_ID
				JOIN Manufacturer M ON M.M_ID = MN.M_ID
				WHERE MB.F_ID = {$row["F_ID"]}
				GROUP BY MN.M_ID
				ORDER BY MN.M_ID
			";
			$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			while( $subrow = mysqli_fetch_array($subres) ) {
				if( $subrow["cnt"] > 0 ) {
					echo "
					<tr>\n
						<td colspan='3' style='background-color: rgba(0, 0, 0, 0.2);'>{$subrow["company"]}</td>\n
					</tr>\n
					";

					$query = "
						SELECT MN.material_name
							,ROUND(MB.mb_balance, 2) balance
							,MB.MN_ID
						FROM material__Balance MB
						JOIN material__Name MN ON MN.MN_ID = MB.MN_ID
						WHERE MB.F_ID = {$row["F_ID"]}
							AND MN.M_ID = {$subrow["M_ID"]}
						ORDER BY MN.material_name
					";
					$subsubres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
					while( $subsubrow = mysqli_fetch_array($subsubres) ) {
						echo "
							<tr>\n
								<td>{$subsubrow["material_name"]}</td>\n
								<td>{$subsubrow["balance"]}</td>\n
								<td>\n
									<a href='#' class='edit_material_balance' F_ID='{$row["F_ID"]}' MN_ID='{$subsubrow["MN_ID"]}' f_name='{$row["f_name"]}' material_name='{$subsubrow["material_name"]}' balance='{$subsubrow["balance"]}' title='Коррекция остатка'><i class='fas fa-pencil-alt fa-lg'></i></a>\n
									<a href='#' class='material_movement' F_ID='{$row["F_ID"]}' MN_ID='{$subsubrow["MN_ID"]}' f_name='{$row["f_name"]}' material_name='{$subsubrow["material_name"]}' balance='{$subsubrow["balance"]}' title='Перемещение между участками'><i class='fas fa-right-left fa-lg'></i></a>\n
								</td>\n
							</tr>\n
						";
					}	
				}
			}
			?>
				</tbody>
			</table>
			</div>
			<?php
		}
		?>
	</div>
</div>

<table class="main_table">
	<thead>
		<tr>
			<th>Дата приемки</th>
			<th>Участок</th>
			<th>Наименование продукции</th>
			<th>Поставщик</th>
			<th>Перевозчик</th>
			<th>№ттн, №тн</th>
			<th>№ автомобиля</th>
			<th>Кол-во, т</th>
			<th>Стоимость</th>
			<th>Автор</th>
			<th></th>
		</tr>
	</thead>
	<tbody style="text-align: center;">
		<?php
		$query = "
			SELECT MA.MA_ID
				,DATE_FORMAT(MA.ma_date,'%d.%m.%Y') ma_date_format
				,F.f_name
				,MN.material_name
				,MS.supplier
				,MC.carrier
				,MA.invoice_number
				,MA.car_number
				,MA.ma_cnt
				,MA.ma_cost
				,IF(MA.ma_date < CURDATE() - INTERVAL 1 MONTH , 0, 1) editable
				,IF(MA.USR_ID, USR_Icon(MA.USR_ID), '') USR_Icon
				,DATE_FORMAT(MA.last_edit, '%d.%m.%Y в %H:%i:%s') last_edit		
			FROM material__Arrival MA
			JOIN factory F ON F.F_ID = MA.F_ID
			JOIN material__Name MN ON MN.MN_ID = MA.MN_ID
			LEFT JOIN material__Supplier MS ON MS.MS_ID = MA.MS_ID
			LEFT JOIN material__Carrier MC ON MC.MC_ID = MA.MC_ID
			WHERE 1
				".($_GET["F_ID"] ? "AND MA.F_ID = '{$_GET["F_ID"]}'" : "")."
				".($_GET["date_from"] ? "AND MA.ma_date >= '{$_GET["date_from"]}'" : "")."
				".($_GET["date_to"] ? "AND MA.ma_date <= '{$_GET["date_to"]}'" : "")."
				".($_GET["MN"] ? "AND MA.MN_ID = {$_GET["MN"]}" : "")."
				".($_GET["MS"] ? "AND MA.MS_ID = {$_GET["MS"]}" : "")."
				".($_GET["MC"] ? "AND MA.MC_ID = {$_GET["MC"]}" : "")."
				".($_GET["IN"] ? "AND MA.invoice_number LIKE '%{$_GET["IN"]}%'" : "")."
				".($_GET["CN"] ? "AND MA.car_number LIKE '%{$_GET["CN"]}%'" : "")."
			ORDER BY MA.ma_date, MA.MA_ID
		";
		$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		while( $row = mysqli_fetch_array($res) ) {
			$ma_cnt += $row["ma_cnt"];
			$ma_cost += $row["ma_cost"];
			?>
			<tr id="<?=$row["MA_ID"]?>">
				<td><?=$row["ma_date_format"]?></td>
				<td><?=$row["f_name"]?></td>
				<td><span class="nowrap"><?=$row["material_name"]?></span></td>
				<td><span class="nowrap"><?=$row["supplier"]?></span></td>
				<td><span class="nowrap"><?=$row["carrier"]?></span></td>
				<td><?=$row["invoice_number"]?></td>
				<td><?=$row["car_number"]?></td>
				<td><?=round($row["ma_cnt"], 2)?></td>
				<td><?=$row["ma_cost"]?></td>
				<td><?=$row["USR_Icon"]?><?=($row["last_edit"] ? "<i class='fas fa-clock' title='Сохранено ".$row["last_edit"]."'.></i>" : "")?></td>
				<td>
				<?php
					if( $row["supplier"] == null ) {
						echo "Корректировка";
					}
					else if( $row["editable"] ) {
						echo "<a href='#' class='add_material_arrival' MA_ID='{$row["MA_ID"]}' title='Редактировать'><i class='fa fa-pencil-alt fa-lg'></i></a>\n";
					}
			?>
				</td>
			</tr>
			<?php
		}
		?>

		<tr class="total">
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td>Итог:</td>
			<td><?=round($ma_cnt, 2)?></td>
			<td><?=$ma_cost?></td>
			<td></td>
			<td></td>
		</tr>
	</tbody>
</table>

<div id="add_btn" class="add_material_arrival" title="Внести данные приемки сырья"></div>

<?php
include "footer.php";
?>
