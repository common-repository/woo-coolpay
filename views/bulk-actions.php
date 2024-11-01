<script type="text/javascript">
	jQuery( document ).ready( function() {
		jQuery( '<option>' ).val( 'coolpay_capture_recurring' ).text( '<?php _e( 'Capture payment and activate subscription', 'woo-coolpay' ); ?>' ).appendTo( "select[name='action']" );

		jQuery("select[name='action']").on('change', function () {
			if (this.value  === 'coolpay_capture_recurring') {
				jQuery(this).closest('form').attr('target', '_blank');
			}
		});
	} );
</script>