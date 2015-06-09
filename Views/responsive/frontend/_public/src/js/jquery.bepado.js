;(function ($, undefined) {

	$.plugin('bepado', {

		/** Plugin constructor */
		init: function () {
			var me = this;

			/** Register event listener */
			me._on('.collapsible-dispatch--headline', 'click', $.proxy(me.onSlideDispatch, me));
		},

		/** Event listener method */
        onSlideDispatch: function (event) {
            var me = this,
                $el = $(event.currentTarget),
                dispatchCt = $el.parents('.bepado--collapsible-dispatch'),
                dispatchBody = dispatchCt.find('.collapsible-dispatch--body'),
                raquo = $el.find('.bepado--raquo');
			event.preventDefault();

            if (raquo.hasClass('collapsible-dispatch--collapsed')) {
                raquo.removeClass('collapsible-dispatch--collapsed');
                dispatchBody.slideDown('slow');
            } else {
                raquo.addClass('collapsible-dispatch--collapsed');
                dispatchBody.slideUp('slow');
            }
		},

		/** Destroys the plugin */
		destroy: function () {
			this._destroy();
		}
	});

	$('.bepado--collapsible-dispatch').bepado();
})(jQuery);