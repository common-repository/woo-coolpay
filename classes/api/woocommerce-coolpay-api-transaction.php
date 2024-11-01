<?php
/**
 * WC_CoolPay_API_Transaction class
 *
 * Used for common methods shared between payments and subscriptions
 *
 * @class          WC_CoolPay_API_Payment
 * @since          4.0.0
 * @package        Woocommerce_CoolPay/Classes
 * @category       Class
 * @author         PerfectSolution
 * @docs        https://coolpay.com/docs/apidocs/
 */

class WC_CoolPay_API_Transaction extends WC_CoolPay_API {

	/**
	 * @var bool
	 */
	protected $loaded_from_cache = false;

	/**
	 * get_current_type function.
	 *
	 * Returns the current payment type
	 *
	 * @access public
	 * @return string
	 * @throws CoolPay_API_Exception
	 */
	public function get_current_type() {
		$last_operation = $this->get_last_operation();

		if ( ! is_object( $last_operation ) ) {
			throw new CoolPay_API_Exception( "Malformed operation response", 0 );
		}

		return $last_operation->type;
	}

	/**
	 * get_last_operation function.
	 *
	 * Returns the last successful transaction operation
	 *
	 * @access public
	 * @return stdClass
	 * @throws CoolPay_API_Exception
	 */
	public function get_last_operation() {
		if ( ! is_object( $this->resource_data ) ) {
			throw new CoolPay_API_Exception( 'No API payment resource data available.', 0 );
		}

		// Loop through all the operations and return only the operations that were successful (based on the qp_status_code and pending mode).
		$successful_operations = array_filter( $this->resource_data->operations, function ( $operation ) {
			return $operation->qp_status_code == 20000 || $operation->pending == true;
		} );

		$last_operation = end( $successful_operations );

		if ( ! is_object( $last_operation ) ) {
			throw new CoolPay_API_Exception( 'Malformed operation object' );
		}

		if ( $last_operation->pending === true ) {
			$last_operation->type = __( 'Pending - check your CoolPay manager', 'woo-coolpay' );
		}

		return $last_operation;
	}

	/**
	 * is_test function.
	 *
	 * Tests if a payment was made in test mode.
	 *
	 * @access public
	 * @return boolean
	 * @throws CoolPay_API_Exception
	 */
	public function is_test() {
		if ( ! is_object( $this->resource_data ) ) {
			throw new CoolPay_API_Exception( 'No API payment resource data available.', 0 );
		}

		return $this->resource_data->test_mode;
	}

	/**
	 * create function.
	 *
	 * Creates a new payment via the API
	 *
	 * @access public
	 *
	 * @param  WC_CoolPay_Order $order
	 *
	 * @return object
	 * @throws CoolPay_API_Exception
	 */
	public function create( WC_CoolPay_Order $order ) {
		$base_params = array(
			'currency'      => WC_CP()->get_gateway_currency( $order ),
			'order_post_id' => $order->get_id(),
		);

		$text_on_statement = WC_CP()->s( 'coolpay_text_on_statement' );
		if ( ! empty( $text_on_statement ) ) {
			$base_params['text_on_statement'] = $text_on_statement;
		}

		$order_params = $order->get_transaction_params();

		$params = array_merge( $base_params, $order_params );

		$payment = $this->post( '/', $params );

		return $payment;
	}

	/**
	 * create_link function.
	 *
	 * Creates or updates a payment link via the API
	 *
	 * @since  4.5.0
	 * @access public
	 *
	 * @param  int               $transaction_id
	 * @param  WC_CoolPay_Order $order
	 *
	 * @return object
	 * @throws CoolPay_API_Exception
	 */
	public function patch_link( $transaction_id, WC_CoolPay_Order $order ) {
		$cardtypelock = WC_CP()->s( 'coolpay_cardtypelock' );

		$payment_method = strtolower( version_compare( WC_VERSION, '3.0', '<' ) ? $order->payment_method : $order->get_payment_method() );

		$base_params = array(
			'language'                     => WC_CP()->get_gateway_language(),
			'currency'                     => WC_CP()->get_gateway_currency( $order ),
			'callbackurl'                  => WC_CoolPay_Helper::get_callback_url(),
			'autocapture'                  => WC_CoolPay_Helper::option_is_enabled( $order->get_autocapture_setting() ),
			'autofee'                      => WC_CoolPay_Helper::option_is_enabled( WC_CP()->s( 'coolpay_autofee' ) ),
			'payment_methods'              => apply_filters( 'woocommerce_coolpay_cardtypelock_' . $payment_method, $cardtypelock, $payment_method ),
			'branding_id'                  => WC_CP()->s( 'coolpay_branding_id' ),
			'google_analytics_tracking_id' => WC_CP()->s( 'coolpay_google_analytics_tracking_id' ),
			'customer_email'               => version_compare( WC_VERSION, '3.0', '<' ) ? $order->billing_email : $order->get_billing_email(),
		);

		$order_params = $order->get_transaction_link_params();

		$merged_params = array_merge( $base_params, $order_params );

		$params = apply_filters( 'woocommerce_coolpay_transaction_link_params', $merged_params, $order, $payment_method );

		$payment_link = $this->put( sprintf( '%d/link', $transaction_id ), $params );

		return $payment_link;
	}

	/**
	 * get_cardtype function
	 *
	 * Returns the payment type / card type used on the transaction
	 *
	 * @since  4.5.0
	 * @return mixed
	 * @throws CoolPay_API_Exception
	 */
	public function get_brand() {
		if ( ! is_object( $this->resource_data ) ) {
			throw new CoolPay_API_Exception( 'No API payment resource data available.', 0 );
		}

		return $this->resource_data->metadata->brand;
	}

	/**
	 * get_formatted_balance function
	 *
	 * Returns a formatted transaction balance
	 *
	 * @since  4.5.0
	 * @return mixed
	 * @throws CoolPay_API_Exception
	 */
	public function get_formatted_balance() {
		return WC_CoolPay_Helper::price_normalize( $this->get_balance() );
	}

	/**
	 * get_balance function
	 *
	 * Returns the transaction balance
	 *
	 * @since  4.5.0
	 * @return mixed
	 * @throws CoolPay_API_Exception
	 */
	public function get_balance() {
		if ( ! is_object( $this->resource_data ) ) {
			throw new CoolPay_API_Exception( 'No API payment resource data available.', 0 );
		}

		return ! empty( $this->resource_data->balance ) ? $this->resource_data->balance : null;
	}

	/**
	 * get_currency function
	 *
	 * Returns a transaction currency
	 *
	 * @since  4.5.0
	 * @return mixed
	 * @throws CoolPay_API_Exception
	 */
	public function get_currency() {
		if ( ! is_object( $this->resource_data ) ) {
			throw new CoolPay_API_Exception( 'No API payment resource data available.', 0 );
		}

		return $this->resource_data->currency;
	}

	/**
	 * get_formatted_remaining_balance function
	 *
	 * Returns a formatted transaction balance
	 *
	 * @since  4.5.0
	 * @return mixed
	 * @throws CoolPay_API_Exception
	 */
	public function get_formatted_remaining_balance() {
		return WC_CoolPay_Helper::price_normalize( $this->get_remaining_balance() );
	}

	/**
	 * get_remaining_balance function
	 *
	 * Returns a remaining balance
	 *
	 * @since  4.5.0
	 * @return mixed
	 * @throws CoolPay_API_Exception
	 */
	public function get_remaining_balance() {
		$balance = $this->get_balance();

		$authorized_operations = array_filter( $this->resource_data->operations, function ( $operation ) {
			return 'authorize' === $operation->type;
		} );

		if ( empty( $authorized_operations ) ) {
			return;
		}

		$operation = reset( $authorized_operations );

		$amount = $operation->amount;

		$remaining = $amount;

		if ( $balance > 0 ) {
			$remaining = $amount - $balance;
		}

		return $remaining;
	}

	/**
	 * Checks if either a specific operation or the last operation was successful.
	 *
	 * @param null $operation
	 *
	 * @return bool
	 * @since 4.5.0
	 * @throws CoolPay_API_Exception
	 */
	public function is_operation_approved( $operation = null ) {
		if ( ! is_object( $this->resource_data ) ) {
			throw new CoolPay_API_Exception( 'No API payment resource data available.', 0 );
		}

		if ( $operation === null ) {
			$operation = $this->get_last_operation();
		}

		return $this->resource_data->accepted && $operation->qp_status_code == 20000 && $operation->aq_status_code == 20000;
	}

	/**
	 * get_metadata function
	 *
	 * Returns the metadata of a transaction
	 *
	 * @since  4.5.0
	 * @return mixed
	 * @throws CoolPay_API_Exception
	 */
	public function get_metadata() {
		if ( ! is_object( $this->resource_data ) ) {
			throw new CoolPay_API_Exception( 'No API payment resource data available.', 0 );
		}

		return $this->resource_data->metadata;
	}

	/**
	 * get_state function
	 *
	 * Returns the current transaction state
	 *
	 * @since  4.5.0
	 * @return mixed
	 * @throws CoolPay_API_Exception
	 */
	public function get_state() {
		if ( ! is_object( $this->resource_data ) ) {
			throw new CoolPay_API_Exception( 'No API payment resource data available.', 0 );
		}

		return $this->resource_data->state;
	}

	/**
	 * Fetches transaction data based on a transaction ID. This method checks if the transaction is cached in a transient before it asks the
	 * CoolPay API. Cached data will always be used if available.
	 *
	 * If no data is cached, we will fetch the transaction from the API and cache it.
	 *
	 * @param        $transaction_id
	 *
	 * @return object|stdClass
	 * @throws CoolPay_API_Exception
	 * @throws CoolPay_Exception
	 */
	public function maybe_load_transaction_from_cache( $transaction_id ) {

		$is_caching_enabled = self::is_transaction_caching_enabled();

		if ( empty( $transaction_id ) ) {
			throw new CoolPay_Exception( __( 'Transaction ID cannot be empty', 'woo-coolpay' ) );
		}

		if ( $is_caching_enabled && false !== ( $transient = get_transient( 'wccp_transaction_' . $transaction_id ) ) ) {
			$this->loaded_from_cache = true;

			return $this->resource_data = (object) json_decode( $transient );
		}

		$this->get( $transaction_id );

		if ( $is_caching_enabled ) {
			$this->cache_transaction();
		}

		return $this->resource_data;
	}

	/**
	 * @return boolean
	 */
	public static function is_transaction_caching_enabled() {
		return apply_filters( 'woocommerce_coolpay_transaction_cache_enabled', true );
	}

	/**
	 * Updates cache data for a transaction
	 *
	 * @return boolean
	 * @throws CoolPay_Exception
	 */
	public function cache_transaction() {
		if ( ! is_object( $this->resource_data ) ) {
			throw new CoolPay_Exception( "Cannot cache empty transaction." );
		}

		if ( ! self::is_transaction_caching_enabled() ) {
			return false;
		}

		// Cache expiration in seconds
		$expiration = apply_filters( 'woocommerce_coolpay_transaction_cache_expiration', 7 * DAY_IN_SECONDS );

		return set_transient( 'wccp_transaction_' . $this->resource_data->id, json_encode( $this->resource_data ), $expiration );
	}

	/**
	 * @return bool
	 */
	public function is_loaded_from_cached() {
		return $this->loaded_from_cache;
	}
}