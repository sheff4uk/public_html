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
			border: 1px dotted #999;
			padding: 4px;
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
		SELECT SI.SI_ID
			,DATE_FORMAT(SA.sa_date, '%d.%m.%y') sa_date_format
			,CW.Item
		FROM shell__Item SI
		JOIN shell__Arrival SA ON SA.SA_ID = SI.SA_ID
		JOIN CounterWeight CW ON CW.CW_ID = SA.CW_ID
		WHERE SI.SA_ID = {$_GET['SA_ID']}
	";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $row = mysqli_fetch_array($res) ) {
		$count++;
		?>
		<span class="label" style="position: relative; font-size: 12px;">
			<img src="../barcode.php?code=<?=$code?>&w=170&h=80" alt="barcode" style="position: absolute; top: 12px;">
			<span style="position: absolute; left: 30px; width: 97px; text-align: center; letter-spacing: 5px; font-weight:bold;"><?=$row["SI_ID"]?></span>
			<span style="position: absolute; left: 10px; text-align: center;"><?=substr($row["Item"], -3)?></span>
			<span style="position: absolute; right: 10px; text-align: center;"><?=$row["sa_date_format"]?></span>
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
