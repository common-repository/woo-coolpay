( function( $ ) {
	"use strict";

	// DOM ready
	$(function() {
		var cpform = $('#coolpay-payment-form');
		if (cpform.length) {
			setTimeout(function () {
				cpform.submit();
			}, 5000);
		}
	});

})(jQuery);