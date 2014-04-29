(function ($) {

    $(document).ready(function () {

        $('body').delegate('.bepado_collapsible h2', 'click', function() {
        	var $this = $(this);

        	$this.children('b').toggleClass('collapsed');
        	$this.nextAll('div.content').slideToggle(500);
        });

    });

})(jQuery);
