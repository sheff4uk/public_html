<?
//ini_set('display_errors', 1);
//error_reporting(E_ALL);

include_once "checkrights.php";

// Функция делает ссылки кликабельными
function src_url($src) {
	$src = preg_replace('/((?:\w+:\/\/|www\.)[\w.\/%\d&?#+=-]+)/i', '<a href="\1" target="_blank" class="button">\1</a>', $src);
	return $src;
}

?>
<!DOCTYPE html>
<html>
	<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?=$title?></title>
<!--	<link rel="stylesheet" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/themes/ui-lightness/jquery-ui.css">-->
	<link rel="stylesheet" type='text/css' href="js/ui/jquery-ui.css?v=2">
	<link rel='stylesheet' type='text/css' href='css/style.css?v=14'>
	<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.3.1/css/all.css" integrity="sha384-mzrmE5qonljUremFsqc01SB46JvROS7bZs3IO2EmfFsd15uHvIt+Y8vEf7N7fWAU" crossorigin="anonymous">
<!--	<link rel='stylesheet' type='text/css' href='css/font-awesome.min.css'>-->
	<link rel='stylesheet' type='text/css' href='css/buttons.css'>
	<link rel='stylesheet' type='text/css' href='css/animate.css'>
	<link rel='stylesheet' type='text/css' href='plugins/jReject-master/css/jquery.reject.css'>
	<link rel='stylesheet' type='text/css' href='css/loading.css'>
<!--	<link rel='stylesheet' type='text/css' href='js/timepicker/jquery-ui-timepicker-addon.css'>-->
<!--	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>-->
<!--	<script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>-->
	<script src="js/jquery-1.11.3.min.js"></script>
	<script src="js/ui/jquery-ui.js"></script>
<!--	<script src="js/jquery.ui.datepicker-ru.js"></script>-->
	<script src="js/script.js?v=3" type="text/javascript"></script>
	<script src="js/jquery.printPage.js" type="text/javascript"></script>
	<script src="js/jquery.columnhover.js" type="text/javascript"></script>
	<script src="js/noty/packaged/jquery.noty.packaged.min.js" type="text/javascript"></script>
	<script src="js/Chart.min.js" type="text/javascript"></script>
	<script src="plugins/jReject-master/js/jquery.reject.js" type="text/javascript"></script>
<!--	<script src="js/timepicker/jquery-ui-timepicker-addon.js" type="text/javascript"></script>-->
<!--	<script src="js/timepicker/jquery-ui-timepicker-ru.js" type="text/javascript"></script>-->

	<script src="https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/1.4.0/clipboard.min.js"></script>
	<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.8/css/select2.min.css" rel="stylesheet" />
	<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.8/js/select2.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.8/js/i18n/ru.js" type="text/javascript"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.maskedinput/1.4.1/jquery.maskedinput.js"></script>
	<script src="/js/jquery.ui.totop.js"></script>

	<script>
		$(document).ready(function(){
			$('#body_wraper').fadeIn('slow');
			$('#loading').hide();

			//Проверка браузера
			$.reject({
				reject: {
					safari: true, // Apple Safari
					//chrome: true, // Google Chrome
					//firefox: true, // Mozilla Firefox
					msie: true, // Microsoft Internet Explorer
					//opera: true, // Opera
					konqueror: true, // Konqueror (Linux)
					unknown: true // Everything else
				},
				close: false,
				display: ['chrome','firefox','opera'],
				header: 'Ваш браузер устарел',
				paragraph1: 'Вы пользуетесь устаревшим браузером, который не поддерживает современные веб-стандарты и представляет угрозу безопасности Ваших данных.',
				paragraph2: 'Пожалуйста, установите современный браузер:',
				closeMessage: ''
			});

//			// Принудительное перемещение к якорю после перезагрузки страницы
//			var loc = window.location.hash.replace("#","");
//			if (loc != "") {
//				location.replace(document.URL);
//			}

			$( 'input[type=submit], input[type=button], .button, button' ).button();

			// Календарь
			$( "input.date" ).datepicker({
				dateFormat: 'dd.mm.yy',
				onClose: function( selectedDate ) {
					if( $(this).hasClass( "from" ) ) {
						$(this).parents( "form" ).find( ".to" ).datepicker( "option", "minDate", selectedDate );
					}
					if( $(this).hasClass( "to" ) ) {
						$(this).parents( "form" ).find( ".from" ).datepicker( "option", "maxDate", selectedDate );
					}
				}
			});

			// Плавная прокрутка к якорю при загрузке страницы
			var loc = window.location.hash.replace("#","");
			if (loc == "") {loc = "main"}

			var nav = $("#"+loc);
			if (nav.length) {
				var destination = nav.offset().top - 200;
				$("body:not(:animated)").animate({ scrollTop: destination }, 200);
				$("html").animate({ scrollTop: destination }, 200);
			}

			// Плавная прокрутка к якорю при клике на ссылку якоря
			$('a[href*=#]').click(function() {
				if (location.pathname.replace(/^\//,'') == this.pathname.replace(/^\//,'') && location.hostname == this.hostname) {
					var $target = $(this.hash);
					//$target = $target.length && $target || $('[name=' + this.hash.slice(1) +']');
					if ($target.length) {
						var targetOffset = $target.offset().top - ($(".navbar").outerHeight(true)+ 120);
						$('html,body').animate({scrollTop: targetOffset}, 500);
						//return false;
					}
				}
			});
		});

		// Диалог подтверждения действия
		function confirm(text, href) {
			var self = this;
			self.dfd = $.Deferred();
			var n = noty({
				text		: text,
				dismissQueue: false,
				modal		: true,
				buttons		: [
					{addClass: 'btn btn-primary', text: 'Ok', onClick: function ($noty) {
						$noty.close();
						//noty({timeout: 3000, text: 'Вы нажали кнопку "Ok"', type: 'success'});
						if(href !== undefined) {window.location.href = href}
						self.dfd.resolve(true);
					}
					},
					{addClass: 'btn btn-danger', text: 'Отмена', onClick: function ($noty) {
						$noty.close();
						noty({timeout: 3000, text: 'Вы нажали кнопку "Отмена"', type: 'error'});
						self.dfd.resolve(false);
					}
					}
				],
				closable: false,
				timeout: false
			});
			return self.dfd.promise();
		}

		// Функция замены в строке спец символов
		var entityMap = {
			'&': '&amp;',
			'<': '&lt;',
			'>': '&gt;',
			'"': '&quot;',
			"'": '&#39;',
			'/': '&#x2F;',
			'`': '&#x60;',
			'=': '&#x3D;'
		};

		function escapeHtml(string) {
			return String(string).replace(/[&<>"'`=\/]/g, function (s) {
				return entityMap[s];
			});
		}
	</script>

<?
	// Выводим собранные в сесии сообщения через noty
	include "noty.php";
?>

</head>
<body>

	<div id="loading" class='uil-default-css' style='transform:scale(1); position: absolute; left: calc(50% - 100px); top: calc(50% - 100px); align-items: center; display: flex;'><div style='top:80px;left:93px;width:14px;height:40px;background:#fdce46;-webkit-transform:rotate(0deg) translate(0,-60px);transform:rotate(0deg) translate(0,-60px);border-radius:10px;position:absolute;'></div><div style='top:80px;left:93px;width:14px;height:40px;background:#fdce46;-webkit-transform:rotate(30deg) translate(0,-60px);transform:rotate(30deg) translate(0,-60px);border-radius:10px;position:absolute;'></div><div style='top:80px;left:93px;width:14px;height:40px;background:#fdce46;-webkit-transform:rotate(60deg) translate(0,-60px);transform:rotate(60deg) translate(0,-60px);border-radius:10px;position:absolute;'></div><div style='top:80px;left:93px;width:14px;height:40px;background:#fdce46;-webkit-transform:rotate(90deg) translate(0,-60px);transform:rotate(90deg) translate(0,-60px);border-radius:10px;position:absolute;'></div><div style='top:80px;left:93px;width:14px;height:40px;background:#fdce46;-webkit-transform:rotate(120deg) translate(0,-60px);transform:rotate(120deg) translate(0,-60px);border-radius:10px;position:absolute;'></div><div style='top:80px;left:93px;width:14px;height:40px;background:#fdce46;-webkit-transform:rotate(150deg) translate(0,-60px);transform:rotate(150deg) translate(0,-60px);border-radius:10px;position:absolute;'></div><div style='top:80px;left:93px;width:14px;height:40px;background:#fdce46;-webkit-transform:rotate(180deg) translate(0,-60px);transform:rotate(180deg) translate(0,-60px);border-radius:10px;position:absolute;'></div><div style='top:80px;left:93px;width:14px;height:40px;background:#fdce46;-webkit-transform:rotate(210deg) translate(0,-60px);transform:rotate(210deg) translate(0,-60px);border-radius:10px;position:absolute;'></div><div style='top:80px;left:93px;width:14px;height:40px;background:#fdce46;-webkit-transform:rotate(240deg) translate(0,-60px);transform:rotate(240deg) translate(0,-60px);border-radius:10px;position:absolute;'></div><div style='top:80px;left:93px;width:14px;height:40px;background:#fdce46;-webkit-transform:rotate(270deg) translate(0,-60px);transform:rotate(270deg) translate(0,-60px);border-radius:10px;position:absolute;'></div><div style='top:80px;left:93px;width:14px;height:40px;background:#fdce46;-webkit-transform:rotate(300deg) translate(0,-60px);transform:rotate(300deg) translate(0,-60px);border-radius:10px;position:absolute;'></div><div style='top:80px;left:93px;width:14px;height:40px;background:#fdce46;-webkit-transform:rotate(330deg) translate(0,-60px);transform:rotate(330deg) translate(0,-60px);border-radius:10px;position:absolute;'></div></div>

	<!-- NAVBAR -->
	<nav class="navbar">
		<div class="page">
		<div class="navbar-header" id="main">
			<a class="navbar-brand" href="/" title="На главную" style="position: relative;"><?=$company_name?></a>
		</div>
<?
	if( !empty($_SESSION['id']) ) {
		$menu["Операции"]["Замес+Заливка"] = "checklist.php";
		$menu["Операции"]["Расформовка"] = "opening.php";
		$menu["Операции"]["Упаковка"] = "packing.php";
		$menu["Доп. данные"]["Суточный&nbsp;брак"] = "daily_reject.php";
		$menu["Доп. данные"]["Испытания&nbsp;кубов"] = "cubetest.php";
		$menu["Маршрутный лист"] = "route_sheet.php";
		$menu["Статистика"] = "statistic.php";
		$menu["Выход {$USR_Icon}"] = "exit.php";
	}

	// Формируем элементы меню
	$nav_buttons = "";
	foreach ($menu as $title=>$url) {
		// Если содержится подменю
		if (is_array($url)) {
			$sub_buttons = "";
			$class = "";
			foreach ($url as $sub_title=>$sub_url) {
				$pieces = explode("?", $sub_url);
				if (strpos($_SERVER["REQUEST_URI"], $pieces[0])) {
					$sub_class = "active";
					$class = "active";
				}
				else {
					$sub_class = "";
				}
				$sub_buttons .= "<li class='{$sub_class}'><a href='{$sub_url}'>{$sub_title}</a></li>";
			}
			$nav_buttons .= "<li class='parent {$class}'><a href='#'>{$title} <i class='fas fa-angle-down'></i></a><ul>{$sub_buttons}</ul></li>";
		}
		else {
			$pieces = explode("?", $url);
			$class = strpos($_SERVER["REQUEST_URI"], $pieces[0]) ? "active" : "";
			$nav_buttons .= "<li class='{$class}'><a href='{$url}'>{$title}</a></li>";
		}
	}

	echo "<ul class='navbar-nav'>";
	echo $nav_buttons;
	echo "</ul>";
	echo "</div>";
	echo "</nav>";
	// END NAVBAR

	$MONTHS = array(1=>'Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь');
	$MONTHS_DATE = array(1=>'янв.', 'февр.', 'мар.', 'апр.', 'мая', 'июня', 'июля', 'авг.', 'сент.', 'окт.', 'нояб.', 'дек.');
?>
	<div id="body_wraper" style="display: none;" class="page">

<script>
	$(function() {
		$("#mtel").mask("+7 (999) 999 99 99");
	});
</script>
