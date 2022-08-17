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
		<title>Pallets report</title>
		<link rel="stylesheet" type='text/css' href="js/ui/jquery-ui.css?v=2">
		<link rel='stylesheet' type='text/css' href='css/style.css?v=23'>
		<script src="https://kit.fontawesome.com/020f21ae61.js" crossorigin="anonymous"></script>
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

		<div id="body_wraper" style="display: none;" class="page">

<?
// Узнаем актуальную стоимость поддона
$query = "
	SELECT PA.pallet_cost
	FROM pallet__Arrival PA
	JOIN pallet__Supplier PS ON PS.PS_ID = PA.PS_ID AND PS.PN_ID = 1
	WHERE PA.pallet_cost > 0
	ORDER BY PA.pa_date DESC, PA.PA_ID DESC
	LIMIT 1
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
$row = mysqli_fetch_array($res);
$actual_pallet_cost = $row["pallet_cost"];

// Узнаем долг в поддонах
$query = "
	SELECT PCB.pallet_balance
	FROM pallet__ClientBalance PCB
	WHERE PCB.CB_ID = 2
		AND PCB.PN_ID = 1
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
$row = mysqli_fetch_array($res);
$pallet_balance = $row["pallet_balance"];
?>

		<fieldset>
			<legend>Filter:</legend>
			<form method="get" style="position: relative;">
				<a href="/online_pallet_report.php" style="position: absolute; top: 10px; right: 10px;" class="button">Reset filter</a>

				<div class="nowrap">
					<span style="display: inline-block;">Please select a date range:</span>
					<input name="date_from" type="date" min="2020-11-01" value="<?=$_GET["date_from"]?>" class="<?=$_GET["date_from"] ? "filtered" : ""?>">
					<input name="date_to" type="date" value="<?=$_GET["date_to"]?>" class="<?=$_GET["date_to"] ? "filtered" : ""?>">
					<i class="fas fa-question-circle" title="By default last 7 days."></i>
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
			<th colspan="2"><h1>Pallets report</h1></th>
			<th colspan="1"><b>Debt in pallets (Vesta): <span style="font-size: 2em; color: red;"><?=$pallet_balance?></span></b></th>
			<th colspan="1"><b>Debt in rubles: <span style="font-size: 2em; color: red;">&#8381;<?=number_format(( $pallet_balance * $actual_pallet_cost ), 0, '', ' ')?></span></b></th>
		</tr>
		<tr>
			<th>Date</th>
			<th>Number of pallets shipped</th>
			<th>Number of pallets returned</th>
			<th>Number of broken pallets</th>
		</tr>
	</thead>
	<tbody style="text-align: center;">

<?
$query = "
	SELECT SUB.date_format
		,SUM(SUB.pallets_shipment) pallets_shipment
		,SUM(SUB.pr_cnt) pr_cnt
		,SUM(SUB.pr_reject) pr_reject
	FROM (
		SELECT DATE_FORMAT(PR.pr_date, '%d/%m/%Y') date_format
			,NULL pallets_shipment
			,PR.pr_cnt
			,NULL pr_reject
			,PR.pr_date date
		FROM pallet__Return PR
		WHERE PR.CB_ID = 2
			".($_GET["date_from"] ? "AND PR.pr_date >= '{$_GET["date_from"]}'" : "")."
			".($_GET["date_to"] ? "AND PR.pr_date <= '{$_GET["date_to"]}'" : "")."

		UNION

		SELECT DATE_FORMAT(LS.ls_date, '%d/%m/%Y')
			,SUM(pallets) pallets_shipment
			,NULL
			,NULL
			,LS.ls_date
		FROM list__Shipment LS
		JOIN CounterWeight CW ON CW.CW_ID = LS.CW_ID AND CW.CB_ID = 2
		WHERE 1
			".($_GET["date_from"] ? "AND LS.ls_date >= '{$_GET["date_from"]}'" : "")."
			".($_GET["date_to"] ? "AND LS.ls_date <= '{$_GET["date_to"]}'" : "")."
		GROUP BY LS.ls_date

		UNION

		SELECT DATE_FORMAT(PD.pd_date, '%d/%m/%Y')
			,NULL
			,NULL
			,PD.pd_cnt
			,PD.pd_date
		FROM pallet__Disposal PD
		WHERE PD.CB_ID = 2
			".($_GET["date_from"] ? "AND PD.pd_date >= '{$_GET["date_from"]}'" : "")."
			".($_GET["date_to"] ? "AND PD.pd_date <= '{$_GET["date_to"]}'" : "")."
		GROUP BY PD.pd_date
	) SUB
	GROUP BY SUB.date
	ORDER BY SUB.date
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	$pallets_shipment += $row["pallets_shipment"];
	$pr_cnt += $row["pr_cnt"];
	$pr_reject += $row["pr_reject"];
	?>
	<tr>
		<td><?=$row["date_format"]?></td>
		<td><?=$row["pallets_shipment"]?></td>
		<td><?=$row["pr_cnt"]?></td>
		<td><n style="color: red;"><?=$row["pr_reject"]?></n></td>
	</tr>
	<?
}
?>
		<tr class="total">
			<td>Total:</td>
			<td><b><?=$pallets_shipment?></b></td>
			<td><b><?=$pr_cnt?></b></td>
			<td><b><?=$pr_reject?></b></td>
		</tr>
	</tbody>
</table>

<?
include "footer.php";
?>
