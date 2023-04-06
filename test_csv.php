<!DOCTYPE html>
<html lang="ru">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<title><?=$title?></title>
		<link rel="stylesheet" type='text/css' href="js/ui/jquery-ui.css?3">
		<link rel='stylesheet' type='text/css' href='css/style.css?34'>
		<link rel='stylesheet' type='text/css' href='css/buttons.css'>
		<link rel='stylesheet' type='text/css' href='css/animate.css'>
		<link rel='stylesheet' type='text/css' href='plugins/jReject-master/css/jquery.reject.css'>
		<link rel='stylesheet' type='text/css' href='css/loading.css'>
		<script src="https://kit.fontawesome.com/020f21ae61.js" crossorigin="anonymous"></script>
		<script src="js/jquery-1.11.3.min.js"></script>
		<script src="js/ui/jquery-ui.js"></script>
		<script src="js/script.js?v=6" type="text/javascript"></script>
		<script src="js/jquery.printPage.js" type="text/javascript"></script>
		<script src="js/jquery.columnhover.js" type="text/javascript"></script>
		<script src="js/noty/packaged/jquery.noty.packaged.min.js" type="text/javascript"></script>
		<script src="js/Chart.min.js?1" type="text/javascript"></script>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/locale/ru.min.js"></script>
		<script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-moment@0.1.1"></script>
		<script src="plugins/jReject-master/js/jquery.reject.js" type="text/javascript"></script>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/1.4.0/clipboard.min.js"></script>
		<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.8/css/select2.min.css" rel="stylesheet" />
		<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.8/js/select2.min.js"></script>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.8/js/i18n/ru.js" type="text/javascript"></script>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.js"></script>
		<script src="/js/jquery.ui.totop.js"></script>
	</head>
	<body>
		<form enctype='multipart/form-data' method='post' style="display: inline-block;">
			<input type="file" name="uploadfile">
			<input type='submit' name="subbut" value='Загрузить' style='float: right;'>
		</form>

<?
$row = 1;
if (($handle = fopen($_FILES['uploadfile']['tmp_name'], "r")) !== FALSE) {
	echo "<table class='main_table'>\n";
	while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
		echo "<tr>\n";
		$num = count($data);
		//echo "<p> $num полей в строке $row: <br /></p>\n";
		$row++;
		for ($c=0; $c < $num; $c++) {
			echo "<td>" . $data[$c] . "</td>\n";
		}
		echo "</tr>\n";
	}
	fclose($handle);
	echo "</table>\n";
}
?>
	</body>
</html>
