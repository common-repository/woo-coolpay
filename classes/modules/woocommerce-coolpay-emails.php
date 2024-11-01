<?php

/**
 * Class WC_CoolPay_Emails
 */
class WC_CoolPay_Emails extends WC_CoolPay_Module {

	/** @var null|static */
	protected static $_instance;

	/**
	 * Perform actions and filters
	 *
	 * @return mixed
	 */
	public function hooks() {
		add_filter( 'woocommerce_email_classes', array( $this, 'emails' ), 10, 1 );
		add_action( 'woocommerce_coolpay_order_action_payment_link_created', array( $this, 'send_customer_payment_link' ), 1, 2 );
	}

	/**
	 * Add support for custom emails
	 *
	 * @param $emails
	 *
	 * @return mixed
	 */
	public function emails( $emails ) {
		require_once WCCP_PATH . 'classes/emails/woocommerce-coolpay-payment-link-email.php';

		$emails['WC_CoolPay_Payment_Link_Email'] = new WC_CoolPay_Payment_Link_Email();

		return $emails;
	}

	/**
	 * Make sure the mailer is loaded in order to load e-mails.
	 *
	 * @param $payment_link
	 * @param $order
	 */
	public function send_customer_payment_link( $payment_link, $order ) {
		/** @var WC_CoolPay_Payment_Link_Email $mail */
		$mail = wc()->mailer()->emails['WC_CoolPay_Payment_Link_Email'];

		if ( $mail ) {
			$mail->trigger( $payment_link, $order );
		}
	}
}