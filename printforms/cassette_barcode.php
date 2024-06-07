<?
include "../config.php";
?>

<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<!-- <script src="https://kit.fontawesome.com/020f21ae61.js" crossorigin="anonymous"></script> -->
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

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
			width: 48mm;
			height: 40mm;
			border: 1px dotted #999;
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
	for ($i = 1; $i <= 371; $i++) {
		if( $count == 0 ) {
			echo "<div class='page'>";
		}
		$count++;
		$code = '11'.str_pad($i, 6, "0", STR_PAD_LEFT);
		?>
		<span class="label" style="position: relative;">
			<img src="../barcode.php?code=<?=$code?>&w=170&h=124" alt="barcode" style="position: absolute; top: 12px;">
			<span style="position: absolute; left: calc(50% - 30px); bottom: 5px; width: 60px; text-align: center; font-weight: bold; background: #fff; font-size: 20px;"><?=$i?></span>
		</span>
		<?
		if( $count == 24 ) {
			$count = 0;
			echo "</div>";
		}
	}
?>
</body>
</html>
