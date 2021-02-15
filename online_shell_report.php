<?
include "config.php";
//include "header.php";

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

<!DOCTYPE html>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<title>Shell report</title>
		<link rel="stylesheet" type='text/css' href="js/ui/jquery-ui.css?v=2">
		<link rel='stylesheet' type='text/css' href='css/style.css?v=23'>
		<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.3.1/css/all.css" integrity="sha384-mzrmE5qonljUremFsqc01SB46JvROS7bZs3IO2EmfFsd15uHvIt+Y8vEf7N7fWAU" crossorigin="anonymous">
		<link rel='stylesheet' type='text/css' href='plugins/jReject-master/css/jquery.reject.css'>
		<script src="js/jquery-1.11.3.min.js"></script>
		<script src="js/ui/jquery-ui.js"></script>
		<script src="js/script.js?v=1"></script>
		<script src="/js/jquery.ui.totop.js"></script>
		<script src="plugins/jReject-master/js/jquery.reject.js" type="text/javascript"></script>
		<script>
			$(function() {
				$('#body_wraper').fadeIn('slow');
				$( 'input[type=submit], input[type=button], .button, button' ).button();

				//Check the browser
				$.reject({
					reject: {
						safari: true, // Apple Safari
						//chrome: true, // Google Chrome
						firefox: true, // Mozilla Firefox
						msie: true, // Microsoft Internet Explorer
						//opera: true, // Opera
						konqueror: true, // Konqueror (Linux)
						unknown: true // Everything else
					},
					close: false,
					display: ['chrome','opera'],
					header: 'Your browser is out of date',
					paragraph1: 'You are using an outdated browser that does not support modern web standards and poses a security risk to your data.',
					paragraph2: 'Please install a modern browser:',
					closeMessage: ''
				});
			});
		</script>
	</head>
	<body>
		<nav class="navbar">
			<div class="page">
			<div class="navbar-header" id="main">
				<n class="navbar-brand" style="position: relative;"><?=$company_name?> <sub>β</sub></n>
			</div>
			</div>
		</nav>

		<fieldset>
			<legend>Filter:</legend>
			<form method="get" style="position: relative;">
				<a href="/online_shell_report.php" style="position: absolute; top: 10px; right: 10px;" class="button">Reset filter</a>

				<div class="nowrap">
					<span style="display: inline-block;">Please select a date range:</span>
					<input name="date_from" type="date" value="<?=$_GET["date_from"]?>" class="<?=$_GET["date_from"] ? "filtered" : ""?>">
					<input name="date_to" type="date" value="<?=$_GET["date_to"]?>" class="<?=$_GET["date_to"] ? "filtered" : ""?>">
					<i class="fas fa-question-circle" title="By default last 7 days."></i>
					&nbsp;&nbsp;&nbsp;&nbsp;
					<span>Part-number:</span>
					<select name="CW_ID" class="<?=$_GET["CW_ID"] ? "filtered" : ""?>">
						<option value=""></option>
						<?
						$query = "
							SELECT CW.CW_ID, CW.item
							FROM CounterWeight CW
							WHERE CW.CB_ID = 2
							ORDER BY CW.CW_ID
						";
						$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
						while( $row = mysqli_fetch_array($res) ) {
							$selected = ($row["CW_ID"] == $_GET["CW_ID"]) ? "selected" : "";
							echo "<option value='{$row["CW_ID"]}' {$selected}>{$row["item"]}</option>";
						}
						?>
					</select>
					&nbsp;&nbsp;&nbsp;&nbsp;
					<button>Filter</button>
				</div>

			</form>
		</fieldset>

		<?
		// Узнаем есть ли фильтр
		$filter = 0;
		foreach ($_GET as &$value) {
			if( $value ) $filter = 1;
		}
		?>

<br>
<table class="main_table">
	<thead>
		<tr>
			<th colspan="3"><h1>Shell report</h1></th>
			<th colspan="3"></th>
			<th colspan="3"></th>
		</tr>
		<tr>
			<th>Date</th>
			<th>Part-number</th>
			<th>Number of shell received</th>
			<th>Actual volume, dm<sup>3</sup></th>
			<th>Volume as per drawing, dm<sup>3</sup></th>
			<th>Number of broken shells</th>
			<th>Exfolation</th>
			<th>Crack</th>
			<th>Chipped</th>
		</tr>
	</thead>
	<tbody style="text-align: center;">

<?
$query = "
	SELECT 'A' type
		,DATE_FORMAT(SA.sa_date, '%d/%m/%Y') date_format
		,CW.item
		,SA.sa_cnt
		,SA.actual_volume
		,CW.drawing_volume
		,NULL sr_cnt
		,NULL exfolation
		,NULL crack
		,NULL chipped
		,SA.sa_date date
		,SA.CW_ID
	FROM shell__Arrival SA
	JOIN CounterWeight CW ON CW.CW_ID = SA.CW_ID AND CW.CB_ID = 2
	WHERE 1
		".($_GET["date_from"] ? "AND SA.sa_date >= '{$_GET["date_from"]}'" : "")."
		".($_GET["date_to"] ? "AND SA.sa_date <= '{$_GET["date_to"]}'" : "")."
		".($_GET["CW_ID"] ? "AND SA.CW_ID={$_GET["CW_ID"]}" : "")."

	UNION

	SELECT 'R' type
		,DATE_FORMAT(SR.sr_date, '%d/%m/%Y') date_format
		,CW.item
		,NULL
		,NULL
		,NULL
		,SUM(SR.sr_cnt) sr_cnt
		,SUM(SR.exfolation) exfolation
		,SUM(SR.crack) crack
		,SUM(SR.chipped) chipped
		,SR.sr_date date
		,SR.CW_ID
	FROM shell__Reject SR
	JOIN CounterWeight CW ON CW.CW_ID = SR.CW_ID AND CW.CB_ID = 2
	WHERE 1
		".($_GET["date_from"] ? "AND SR.sr_date >= '{$_GET["date_from"]}'" : "")."
		".($_GET["date_to"] ? "AND SR.sr_date <= '{$_GET["date_to"]}'" : "")."
		".($_GET["CW_ID"] ? "AND SR.CW_ID={$_GET["CW_ID"]}" : "")."
	GROUP BY date, CW_ID

	ORDER BY date, type, CW_ID
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	$sa_cnt += $row["sa_cnt"];
	$sr_cnt += $row["sr_cnt"];
	$exfolation += $row["exfolation"];
	$crack += $row["crack"];
	$chipped += $row["chipped"];
	?>
	<tr>
		<td><?=$row["date_format"]?></td>
		<td><?=$row["item"]?></td>
		<td><b style="color: green;"><?=$row["sa_cnt"]?></b></td>
		<td><?=($row["actual_volume"] ? $row["actual_volume"]/1000 : "")?></td>
		<td><?=($row["drawing_volume"] ? $row["drawing_volume"]/1000 : "")?></td>
		<td><b style="color: red;"><?=$row["sr_cnt"]?></b></td>
		<td><?=$row["exfolation"]?></td>
		<td><?=$row["crack"]?></td>
		<td><?=$row["chipped"]?></td>
	</tr>
	<?
}
?>
		<tr class="total">
			<td></td>
			<td>Total:</td>
			<td><b><?=$sa_cnt?></b></td>
			<td></td>
			<td></td>
			<td><b><?=$sr_cnt?></b></td>
			<td><?=$exfolation?></td>
			<td><?=$crack?></td>
			<td><?=$chipped?></td>
		</tr>
	</tbody>
</table>

<?
include "footer.php";
?>
