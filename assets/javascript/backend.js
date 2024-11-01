( function( $ ) {
	"use strict";

	CoolPay.prototype.init = function() {
		// Add event handlers
		this.actionBox.on( 'click', '[data-action]', $.proxy( this.callAction, this ) );
	};

	CoolPay.prototype.callAction = function( e ) {
		e.preventDefault();
		var target = $( e.target );
		var action = target.attr( 'data-action' );

		if( typeof this[action] !== 'undefined' ) {
			var message = target.attr('data-confirm') || 'Are you sure you want to continue?';
			if( confirm( message ) ) {
				this[action]();	
			}
		}	
	};

	CoolPay.prototype.capture = function() {
		var request = this.request( {
			coolpay_action : 'capture'
		} );
	};

	CoolPay.prototype.captureAmount = function () {
		var request = this.request({
			coolpay_action: 'capture',
			coolpay_amount: $('#cp-balance__amount-field').val()
		} );
	};

	CoolPay.prototype.cancel = function() {
		var request = this.request( {
			coolpay_action : 'cancel'
		} );
	};

	CoolPay.prototype.refund = function() {
		var request = this.request( {
			coolpay_action : 'refund'
		} );
	};

	CoolPay.prototype.split_capture = function() {
		var request = this.request( {
			coolpay_action : 'splitcapture',
			amount : parseFloat( $('#coolpay_split_amount').val() ),
			finalize : 0
		} );
	};

	CoolPay.prototype.split_finalize = function() {
		var request = this.request( {
			coolpay_action : 'splitcapture',
			amount : parseFloat( $('#coolpay_split_amount').val() ),
			finalize : 1
		} );
	};

	CoolPay.prototype.request = function( dataObject ) {
		var that = this;
		var request = $.ajax( {
			type : 'POST',
			url : ajaxurl,
			dataType: 'json',
			data : $.extend( {}, { action : 'coolpay_manual_transaction_actions', post : this.postID.val() }, dataObject ),
			beforeSend : $.proxy( this.showLoader, this, true ),
			success : function() {
				$.get( window.location.href, function( data ) {
					var newData = $(data).find( '#' + that.actionBox.attr( 'id' ) + ' .inside' ).html();
					that.actionBox.find( '.inside' ).html( newData );
					that.showLoader( false );
				} );
			}
		} );

		return request;
	};

	CoolPay.prototype.showLoader = function( e, show ) {
		if( show ) {
			this.actionBox.append( this.loaderBox );
		} else {
			this.actionBox.find( this.loaderBox ).remove();
		}
	};

    


    CoolPayCheckAPIStatus.prototype.init = function () {
    	if (this.apiSettingsField.length) {
			$(window).on('load', $.proxy(this.pingAPI, this));
			this.apiSettingsField.on('blur', $.proxy(this.pingAPI, this));
			this.insertIndicator();
		}
	};

	CoolPayCheckAPIStatus.prototype.insertIndicator = function () {
		this.indicator.insertAfter(this.apiSettingsField);
	};

	CoolPayCheckAPIStatus.prototype.pingAPI = function () {
		$.post(ajaxurl, { action: 'coolpay_ping_api', apiKey: this.apiSettingsField.val() }, $.proxy(function (response) {
			if (response.status === 'success') {
				this.indicator.addClass('ok').removeClass('error');
			} else {
				this.indicator.addClass('error').removeClass('ok');
			}
		}, this), "json");
	};
    
	// DOM ready
	$(function() {
		new CoolPay().init();
		new CoolPayCheckAPIStatus().init();

        var emptyLogsButton = $('#wccp_logs_clear');
        emptyLogsButton.on('click', function(e) {
        	e.preventDefault();
        	$.getJSON(ajaxurl, { action: 'coolpay_empty_logs' }, function (response) {
        		if (response.hasOwnProperty('status') && response.status == 'success') {
        			var message = $('<div id="message" class="updated"><p>' + response.message + '</p></div>');
        			message.hide();
        			message.insertBefore($('#wccp_wiki'));
        			message.fadeIn('fast', function () {
        				setTimeout(function () {
        					message.fadeOut('fast', function () {
        						message.remove();
        					});
        				},5000);
        			});
        		} 
        	});
        });
	});

	function CoolPay() {
		this.actionBox 	= $( '#coolpay-payment-actions' );
		this.postID		= $( '#post_ID' );
		this.loaderBox 	= $( '<div class="loader"></div>');
	}

    function CoolPayCheckAPIStatus() {
    	this.apiSettingsField = $('#woocommerce_coolpay_coolpay_apikey');
		this.indicator = $('<span class="wccp_api_indicator"></span>');
	}

})(jQuery);