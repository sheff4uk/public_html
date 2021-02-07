<?
include "../config.php";
?>

<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.3.1/css/all.css" integrity="sha384-mzrmE5qonljUremFsqc01SB46JvROS7bZs3IO2EmfFsd15uHvIt+Y8vEf7N7fWAU" crossorigin="anonymous">

	<style>
		@media print {
			@page {
				size: portrait;
				padding: 0;
				margin: 0;
			}
		}

		.label {
			width: 7cm;
			height: 3.95cm;
			border: .5px dotted;
			padding: 10px;
			box-sizing: border-box;
			font-size: 20px;
		}
	</style>
</head>
<body>
	<div style="display: flex; flex-wrap: wrap;">

<?
	$code = 10000000;
	for($i=0; $i <= 20; $i++) {
		$code++;
		?>
		<div class="label">
			<span style="float: right;">45319434</span>
			<span>01.02.2021</span>
			<img src="../barcode.php?code=<?=$code?>" alt="barcode" style="width: 100%;">
			<span style="display: block; text-align: center;"><?=$code?></span>
		</div>
		<?
	}
?>

	</div>
</body>
</html>
