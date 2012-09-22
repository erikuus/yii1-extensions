/**
 * Loading script.
 *
 * @author Vitaliy Stepanenko <mail@vitaliy.in>
 * @copyright Copyright &copy; 2011 Vitaliy Stepanenko
 * @license BSD
 *
 * @link http://www.yiiframework.com/extension/loading/
 *
 * @package widgets.loading
 * @version $Id:$ (1.0)
 *
 * Usage:
 * Loading.show();
 * Loading.hide();
 */
$(function() {

	var defaults = {
		maxOpacity:0.8,
		animationDuration:250
	}

	function LoadingConstructor(config) {
		if (window.Loading) return window.Loading;
		if (!config) config = {};
		this.$el = $('<div id="loading"/>').appendTo('body').hide();
		$.extend(this, defaults, config);
	}

	LoadingConstructor.prototype = {
		show:function() {
			this.$el.show().css('opacity', 0).animate({opacity:this.maxOpacity}, this.animationDuration);
		},
		hide:function() {
			this.$el.animate(
				{opacity:0},
				this.animationDuration,
				function() {
					$(this).hide();
				}
			);
		}
	}

	window.Loading = new LoadingConstructor();
});