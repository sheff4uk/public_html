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
			font: 0pt "Arial";
		}
		* {
			box-sizing: border-box;
			-moz-box-sizing: border-box;
		}
		.box {
			position: relative;
			overflow:hidden;
			width:80mm;
			height:50mm;
			border: 1px solid;
		}
		.box img {
			position: absolute;
			top:50%;
			left:50%;
			transform:translate(-50%,-50%);
			width:80mm;
			height:50mm;
			object-fit:cover;
			font-size: 8mm;
			word-wrap: break-word;
		}
		.box span {
			filter: drop-shadow(0px 0px 2px #000) drop-shadow(0px 0px 2px #000) drop-shadow(0px 0px 2px #000);
			color: #fff;
			font-size: 8mm;
			margin: 2mm;
			position: absolute;
			bottom: 0mm;
			word-wrap: break-word;
			line-height: 1em;
		}
	</style>
</head>
<body>
<?
	$query = "
		SELECT USR_Name(USR_ID) name
			,photo
		FROM Users
		WHERE USR_ID = {$_GET["USR_ID"]}
	";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$row = mysqli_fetch_array($res);
?>
	<div class="box">
		<img src="/time_tracking/upload/<?=$row["photo"]?>" alt="<?=$row["name"]?>">
		<span><?=$row["name"]?></span>
	</div>
</body>
</html>
