<?
include "../config.php";
?>

<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<script src="https://kit.fontawesome.com/020f21ae61.js" crossorigin="anonymous"></script>

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
			width: 29.7cm;
			min-height: 21cm;
			padding: 1cm 1cm;
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
			@page {
				size: landscape;
/*				size: A4;*/
				margin: 0;
			}
		}

		.label {
			width: 276mm;
			height: 185mm;
/*			border: 1px dotted #999;*/
			padding: 4px;
			box-sizing: border-box;
			font-size: 16px;
			display: inline-block;
		}
	</style>
</head>
<body>
<?
	$count = 0;
	for ($i = 1; $i <= 100; $i++) {
		if( $count == 0 ) {
			echo "<div class='page'>";
		}
		$count++;
		$code = '11'.str_pad($i, 6, "0", STR_PAD_LEFT);
		?>
		<span class="label" style="position: relative;">
			<span style="position: absolute; left: calc(50% - 150px); top: 0px; width: 300px; text-align: center; font-weight: bold; background: #fff; font-size: 60px; letter-spacing: 50px;"><?=$i?></span>
			<img src="../barcode.php?code=<?=$code?>&w=1020&h=650" alt="barcode" style="position: absolute; top: 60px;">
		</span>
		<?
		if( $count == 1 ) {
			$count = 0;
			echo "</div>";
		}
	}
?>
</body>
</html>
