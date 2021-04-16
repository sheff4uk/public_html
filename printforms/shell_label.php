<?
include "../config.php";
?>

<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.3.1/css/all.css" integrity="sha384-mzrmE5qonljUremFsqc01SB46JvROS7bZs3IO2EmfFsd15uHvIt+Y8vEf7N7fWAU" crossorigin="anonymous">

	<style>
		body {
			margin: 0;
			padding: 0;
			background-color: #FAFAFA;
			font: 0pt "Arial";
		}
		* {
			box-sizing: border-box;
			-moz-box-sizing: border-box;
		}
		.page {
			width: 21cm;
			min-height: 29.7cm;
			padding: 2.09cm .77cm;
			margin: 1cm auto;
			border: 1px #D3D3D3 solid;
			border-radius: 5px;
			background: white;
			box-shadow: 0 0 5px rgba(0, 0, 0, 0.1);
		}
		@media print {
			.page {
				margin: 0;
				border: initial;
				border-radius: initial;
				width: initial;
				min-height: initial;
				box-shadow: initial;
				background: initial;
				page-break-after: always;
			}
		}
		@page {
			size: portrait;
			size: A4;
			margin: 0;
		}

		.label {
			width: 48.5mm;
			height: 25.4mm;
/*			border: 1px dotted #999;*/
			padding: 5px;
			box-sizing: border-box;
			font-size: 16px;
			display: inline-block;
		}
	</style>
</head>
<body>
	<div class="page">
<?
	$count = 0;
	$query = "
		SELECT LPAD(SI_ID, 7, '0') SI_ID
		FROM shell__Item
		WHERE SA_ID = {$_GET['SA_ID']}
	";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $row = mysqli_fetch_array($res) ) {
		$count++;
		$code = '2'.$row["SI_ID"];
		?>
		<span class="label" style="position: relative;">
			<img src="../barcode.php?code=<?=$code?>&w=170&h=90" alt="barcode">
			<div style="position: absolute; top: 80px; width: 100%; text-align: center;"><span style="background-color: white;"><?=$code?></span></div>
		</span>
		<?
		if( $count == 40 ) {
			$count = 0;
			echo "</div><div class='page'>";
		}
	}
?>
	</div>
</body>
</html>
