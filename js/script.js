$.fx.speeds._default = 300;

// Форматирование числа в денежный формат
Number.prototype.format = function(n, x) {
	var re = '\\d(?=(\\d{' + (x || 3) + '})+' + (n > 0 ? '\\.' : '$') + ')';
	return this.toFixed(Math.max(0, ~~n)).replace(new RegExp(re, 'g'), '$& ');
};

////////////////////////////////////////////////////////////////////////////////
$(function(){
	$().UItoTop({ easingType: 'easeOutQuart' });

	$( '.btnset' ).buttonset();
		// Fix for http://bugs.jqueryui.com/ticket/7856
		$('[type=radio]').change(function() {
			$(this).parent().buttonset("destroy");
			$(this).parent().buttonset();
		});

	// Всплывающая подсказка
	$( document ).tooltip({
		track: true,
//		items: "img, [html], [title]",
		items: "[html]",
		content: function() {
			var element = $( this );
			if ( element.is( "[html]" ) ) {
				return element.attr( "html" );
			}
			if ( element.is( "[title]" ) ) {
				return element.attr( "title" );
			}
			if ( element.is( "img" ) ) {
				return element.attr( "alt" );
			}
		}
	});

	$( ".accordion" ).accordion({
		collapsible: true,
		heightStyle: 'content'
	});

	// Шапка таблицы фиксируется на месте
	$(window).scroll(function(){
		var scrollTop = $(window).scrollTop();
		if(scrollTop != 0) {
			$(".main_table thead").css({'position':'fixed', 'width':'980px', 'display':'inherit', 'table-layout':'fixed', 'top':'50px', 'z-index':'3'});
		}
		else {
			$(".main_table thead").css({'display':'contents'});
		}
	});
});
