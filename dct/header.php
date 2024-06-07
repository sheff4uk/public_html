<!DOCTYPE html>
<html lang="ru">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<title><?=$title?></title>
		<script src="../js/jquery-1.11.3.min.js"></script>
		<!-- <script src="https://kit.fontawesome.com/020f21ae61.js" crossorigin="anonymous"></script> -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
		<script src="../js/jquery.printPage.js" type="text/javascript"></script>
	</head>
    <style>
        body {
            padding-top: 50px;
            color: #333;
            font-family: Arial, sans-serif;
            font-size: 11pt;
        }
        .navbar {
            background-color: #222;
            border-color: #000;
            position: fixed;
            top: 0;
            width: 100%;
            /* z-index: 10; */
            margin-left: -8px;
        }
        ul.navbar-nav {
            /* float: right; */
            padding-left: 0;
            margin: 0;
            list-style: none;
        }
        .navbar-nav > li {
            float: left;
            /* display: flex; */
            position: relative;
        }
        .navbar a {
            text-decoration: none;
        }
        .navbar-nav li > a {
            color: #9d9d9d;
            line-height: 20px;
            position: relative;
            display: block;
            padding: 15px 15px;
            font-size: 14px;
            background: #222;
            white-space: nowrap;
        }
        .navbar-nav .active > a {
            color: #fff;
            background-color: #000;
        }
    </style>
	<body>
        
	<!-- NAVBAR -->
	<nav class="navbar">
		<div>
<?
	$menu["Отгрузка"] = "shipment.php";
    $menu["Брак"] = "cwreject.php";

	// Формируем элементы меню
	$nav_buttons = "";
	foreach ($menu as $title=>$url) {
        $pieces = explode("?", $url);
        $class = (strpos($_SERVER["REQUEST_URI"], "dct/".$pieces[0]) == 1) ? "active" : "";
        $nav_buttons .= "<li class='{$class}'><a href='{$url}'>{$title}</a></li>";
	}
?>
	        <ul class='navbar-nav'>
	            <?=$nav_buttons?>
	        </ul>
	    </div>
	</nav>
	<!-- END NAVBAR -->
