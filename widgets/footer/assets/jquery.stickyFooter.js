/*!
 * jQuery Sticky Footer 1.0
 * Corey Snyder
 * http://tangerineindustries.com
 *
 * Released under the MIT license
 *
 * Copyright 2013 Corey Snyder.
 *
 * Date: Thu Jan 22 2013 13:34:00 GMT-0630 (Eastern Daylight Time)
 */

$(window).resize(function () {
	stickyFooter();
});

function stickyFooter() {
	$(".sticky-footer").removeAttr('style');
	if (window.innerHeight != document.body.offsetHeight) {
		var offset = window.innerHeight - document.body.offsetHeight;
		var current = parseInt($(".sticky-footer").css("margin-top"));

		if (current+offset > parseInt($(".sticky-footer").css("margin-top"))) {
			$(".sticky-footer").css({"margin-top":(current+offset)+"px"});
		}
	}
}