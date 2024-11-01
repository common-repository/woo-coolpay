<?php

/**
 * Plugin Name: WooCommerce CoolPay
 * Plugin URI: https://wordpress.org/plugins/woo-coolpay/
 * Description: Integrates your CoolPay payment gateway into your WooCommerce installation.
 * Version: 4.10.0
 * Author: Perfect Solution
 * Text Domain: woo-coolpay
 * Author URI: http://perfect-solution.dk
 * WC requires at least: 3.0
 * WC tested up to: 3.5.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WCCP_VERSION', '4.10.0' );
define( 'WCCP_URL', plugins_url( __FILE__ ) );
define( 'WCCP_PATH', plugin_dir_path( __FILE__ ) );

add_action( 'plugins_loaded', 'init_coolpay_gateway', 0 );

/**
 * Adds notice in case of WooCommerce being inactive
 */
function wc_coolpay_woocommerce_inactive_notice() {
	$class    = 'notice notice-error';
	$headline = __( 'WooCommerce CoolPay requires WooCommerce to be active.', 'woo-coolpay' );
	$message  = __( 'Go to the plugins page to activate WooCommerce', 'woo-coolpay' );
	printf( '<div class="%1$s"><h2>%2$s</h2><p>%3$s</p></div>', $class, $headline, $message );
}

function init_coolpay_gateway() {
	/**
	 * Required functions
	 */
	if ( ! function_exists( 'is_woocommerce_active' ) ) {
		require_once WCCP_PATH . 'woo-includes/woo-functions.php';
	}

	/**
	 * Check if WooCommerce is active, and if it isn't, disable Subscriptions.
	 *
	 * @since 1.0
	 */
	if ( ! is_woocommerce_active() ) {
		add_action( 'admin_notices', 'wc_coolpay_woocommerce_inactive_notice' );

		return;
	}

	// Import helper classes
	require_once WCCP_PATH . 'helpers/notices.php';
	require_once WCCP_PATH . 'classes/woocommerce-coolpay-install.php';
	require_once WCCP_PATH . 'classes/api/woocommerce-coolpay-api.php';
	require_once WCCP_PATH . 'classes/api/woocommerce-coolpay-api-transaction.php';
	require_once WCCP_PATH . 'classes/api/woocommerce-coolpay-api-payment.php';
	require_once WCCP_PATH . 'classes/api/woocommerce-coolpay-api-subscription.php';
	require_once WCCP_PATH . 'classes/modules/woocommerce-coolpay-module.php';
	require_once WCCP_PATH . 'classes/modules/woocommerce-coolpay-emails.php';
	require_once WCCP_PATH . 'classes/modules/woocommerce-coolpay-admin-orders.php';
	require_once WCCP_PATH . 'classes/woocommerce-coolpay-exceptions.php';
	require_once WCCP_PATH . 'classes/woocommerce-coolpay-log.php';
	require_once WCCP_PATH . 'classes/woocommerce-coolpay-helper.php';
	require_once WCCP_PATH . 'classes/woocommerce-coolpay-address.php';
	require_once WCCP_PATH . 'classes/woocommerce-coolpay-settings.php';
	require_once WCCP_PATH . 'classes/woocommerce-coolpay-order.php';
	require_once WCCP_PATH . 'classes/woocommerce-coolpay-subscription.php';
	require_once WCCP_PATH . 'classes/woocommerce-coolpay-countries.php';
	require_once WCCP_PATH . 'classes/woocommerce-coolpay-views.php';


	// Main class
	class WC_CoolPay extends WC_Payment_Gateway {

		/**
		 * $_instance
		 * @var mixed
		 * @access public
		 * @static
		 */
		public static $_instance = null;

		/**
		 * @var WC_CoolPay_Log
		 */
		public $log;

		/**
		 * get_instance
		 *
		 * Returns a new instance of self, if it does not already exist.
		 *
		 * @access public
		 * @static
		 * @return WC_CoolPay
		 */
		public static function get_instance() {
			if ( null === self::$_instance ) {
				self::$_instance = new self();
			}

			return self::$_instance;
		}


		/**
		 * __construct function.
		 *
		 * The class construct
		 *
		 * @access public
		 * @return void
		 */
		public function __construct() {
			$this->id           = 'coolpay';
			$this->method_title = 'CoolPay';
			$this->icon         = '';
			$this->has_fields   = false;

			$this->supports = array(
				'subscriptions',
				'products',
				'subscription_cancellation',
				'subscription_reactivation',
				'subscription_suspension',
				'subscription_amount_changes',
				'subscription_date_changes',
				'subscription_payment_method_change_admin',
				'subscription_payment_method_change_customer',
				'refunds',
				'multiple_subscriptions',
				'pre-orders',
			);

			$this->log = new WC_CoolPay_Log();

			// Load the form fields and settings
			$this->init_form_fields();
			$this->init_settings();

			// Get gateway variables
			$this->title             = $this->s( 'title' );
			$this->description       = $this->s( 'description' );
			$this->instructions      = $this->s( 'instructions' );
			$this->order_button_text = $this->s( 'checkout_button_text' );

			do_action( 'woocommerce_coolpay_loaded' );
		}


		/**
		 * filter_load_instances function.
		 *
		 * Loads in extra instances of as separate gateways
		 *
		 * @access public static
		 * @return void
		 */
		public static function filter_load_instances( $methods ) {
			require_once WCCP_PATH . 'classes/instances/instance.php';
			require_once WCCP_PATH . 'classes/instances/mobilepay.php';
			require_once WCCP_PATH . 'classes/instances/viabill.php';
			require_once WCCP_PATH . 'classes/instances/klarna.php';
			require_once WCCP_PATH . 'classes/instances/sofort.php';

			$methods[] = 'WC_CoolPay_MobilePay';
			$methods[] = 'WC_CoolPay_ViaBill';
			$methods[] = 'WC_CoolPay_Klarna';
			$methods[] = 'WC_CoolPay_Sofort';

			return $methods;
		}


		/**
		 * hooks_and_filters function.
		 *
		 * Applies plugin hooks and filters
		 *
		 * @access public
		 * @return string
		 */
		public function hooks_and_filters() {
			WC_CoolPay_Admin_Orders::get_instance();
			WC_CoolPay_Emails::get_instance();

			add_action( 'woocommerce_api_wc_' . $this->id, array( $this, 'callback_handler' ) );
			add_action( 'woocommerce_receipt_' . $this->id, array( $this, 'receipt_page' ) );
			add_action( 'woocommerce_order_status_completed', array( $this, 'woocommerce_order_status_completed' ) );
			add_action( 'in_plugin_update_message-woocommerce-coolpay/woocommerce-coolpay.php', array( __CLASS__, 'in_plugin_update_message' ) );

			// WooCommerce Subscriptions hooks/filters
			add_action( 'woocommerce_scheduled_subscription_payment_' . $this->id, array( $this, 'scheduled_subscription_payment' ), 10, 2 );
			add_action( 'woocommerce_subscription_cancelled_' . $this->id, array( $this, 'subscription_cancellation' ) );
			add_action( 'woocommerce_subscription_payment_method_updated_to_' . $this->id, array(
				$this,
				'on_subscription_payment_method_updated_to_coolpay',
			), 10, 2 );
			add_filter( 'wcs_renewal_order_meta_query', array( $this, 'remove_failed_coolpay_attempts_meta_query' ), 10 );
			add_filter( 'wcs_renewal_order_meta_query', array( $this, 'remove_legacy_transaction_id_meta_query' ), 10 );
			add_filter( 'woocommerce_subscription_payment_meta', array( $this, 'woocommerce_subscription_payment_meta' ), 10, 2 );
			add_action( 'woocommerce_subscription_validate_payment_meta_' . $this->id, array(
				$this,
				'woocommerce_subscription_validate_payment_meta',
			), 10, 2 );

			// Custom bulk actions
			add_action( 'admin_footer-edit.php', array( $this, 'register_bulk_actions' ) );
			add_action( 'load-edit.php', array( $this, 'handle_bulk_actions' ) );

			// WooCommerce Pre-Orders
			add_action( 'wc_pre_orders_process_pre_order_completion_payment_' . $this->id, array( $this, 'process_pre_order_payments' ) );

			if ( is_admin() ) {
				add_action( 'admin_menu', 'WC_CoolPay_Helper::enqueue_stylesheet' );
				add_action( 'admin_menu', 'WC_CoolPay_Helper::enqueue_javascript_backend' );
				add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
				add_action( 'wp_ajax_coolpay_manual_transaction_actions', array( $this, 'ajax_coolpay_manual_transaction_actions' ) );
				add_action( 'wp_ajax_coolpay_empty_logs', array( $this, 'ajax_empty_logs' ) );
				add_action( 'wp_ajax_coolpay_ping_api', array( $this, 'ajax_ping_api' ) );
				add_action( 'wp_ajax_coolpay_run_data_upgrader', 'WC_CoolPay_Install::ajax_run_upgrader' );
				add_action( 'in_plugin_update_message-woocommerce-coolpay/woocommerce-coolpay.php', array(
					__CLASS__,
					'in_plugin_update_message',
				) );
			}

			// Make sure not to add these actions multiple times
			if ( ! has_action( 'init', 'WC_CoolPay_Helper::load_i18n' ) ) {
				add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 2 );
				add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );

				if ( WC_CoolPay_Helper::option_is_enabled( $this->s( 'coolpay_orders_transaction_info', 'yes' ) ) ) {
					add_filter( 'manage_edit-shop_order_columns', array( $this, 'filter_shop_order_posts_columns' ), 10, 1 );
					add_filter( 'manage_shop_order_posts_custom_column', array( $this, 'apply_custom_order_data' ) );
					add_filter( 'manage_shop_subscription_posts_custom_column', array( $this, 'apply_custom_order_data' ) );
					add_action( 'woocommerce_coolpay_accepted_callback', array( $this, 'callback_update_transaction_cache' ), 10, 2 );
				}

				add_action( 'admin_notices', array( $this, 'admin_notices' ) );
			}

			add_action( 'init', 'WC_CoolPay_Helper::load_i18n' );
			add_filter( 'woocommerce_gateway_icon', array( $this, 'apply_gateway_icons' ), 2, 3 );

			// Third party plugins
			add_filter( 'qtranslate_language_detect_redirect', 'WC_CoolPay_Helper::qtranslate_prevent_redirect', 10, 3 );
			add_filter( 'wpss_misc_form_spam_check_bypass', 'WC_CoolPay_Helper::spamshield_bypass_security_check', - 10, 1 );
		}

		/**
		 * s function.
		 *
		 * Returns a setting if set. Introduced to prevent undefined key when introducing new settings.
		 *
		 * @access public
		 *
		 * @param      $key
		 * @param null $default
		 *
		 * @return mixed
		 */
		public function s( $key, $default = null ) {
			if ( isset( $this->settings[ $key ] ) ) {
				return $this->settings[ $key ];
			}

			return ! is_null( $default ) ? $default : '';
		}

		/**
		 * Hook used to display admin notices
		 */
		public function admin_notices() {
			WC_CoolPay_Settings::show_admin_setup_notices();
			WC_CoolPay_Install::show_update_warning();
		}


		/**
		 * add_action_links function.
		 *
		 * Adds action links inside the plugin overview
		 *
		 * @access public static
		 * @return array
		 */
		public static function add_action_links( $links ) {
			$links = array_merge( array(
				'<a href="' . WC_CoolPay_Settings::get_settings_page_url() . '">' . __( 'Settings', 'woo-coolpay' ) . '</a>',
			), $links );

			return $links;
		}


		/**
		 * ajax_coolpay_manual_transaction_actions function.
		 *
		 * Ajax method taking manual transaction requests from wp-admin.
		 *
		 * @access public
		 * @return void
		 */
		public function ajax_coolpay_manual_transaction_actions() {
			if ( isset( $_REQUEST['coolpay_action'] ) AND isset( $_REQUEST['post'] ) ) {
				$param_action = $_REQUEST['coolpay_action'];
				$param_post   = $_REQUEST['post'];

				$order = new WC_CoolPay_Order( (int) $param_post );

				try {
					$transaction_id = $order->get_transaction_id();

					// Subscription
					if ( $order->contains_subscription() ) {
						$payment = new WC_CoolPay_API_Subscription();
						$payment->get( $transaction_id );
					} // Payment
					else {
						$payment = new WC_CoolPay_API_Payment();
						$payment->get( $transaction_id );
					}

					$payment->get( $transaction_id );

					// Based on the current transaction state, we check if
					// the requested action is allowed
					if ( $payment->is_action_allowed( $param_action ) ) {
						// Check if the action method is available in the payment class
						if ( method_exists( $payment, $param_action ) ) {
							// Fetch amount if sent.
							$amount = isset( $_REQUEST['coolpay_amount'] ) ? WC_CoolPay_Helper::price_custom_to_multiplied( $_REQUEST['coolpay_amount'] ) : $payment->get_remaining_balance();

							// Call the action method and parse the transaction id and order object
							call_user_func_array( array( $payment, $param_action ), array(
								$transaction_id,
								$order,
								WC_CoolPay_Helper::price_multiplied_to_float( $amount ),
							) );
						} else {
							throw new CoolPay_API_Exception( sprintf( "Unsupported action: %s.", $param_action ) );
						}
					} // The action was not allowed. Throw an exception
					else {
						throw new CoolPay_API_Exception( sprintf( "Action: \"%s\", is not allowed for order #%d, with type state \"%s\"", $param_action, $order->get_clean_order_number(), $payment->get_current_type() ) );
					}
				} catch ( CoolPay_Exception $e ) {
					$e->write_to_logs();
				} catch ( CoolPay_API_Exception $e ) {
					$e->write_to_logs();
				}

			}
		}

		/**
		 * ajax_empty_logs function.
		 *
		 * Ajax method to empty the debug logs
		 *
		 * @access public
		 * @return json
		 */
		public function ajax_empty_logs() {
			$this->log->clear();
			echo json_encode( array( 'status' => 'success', 'message' => 'Logs successfully emptied' ) );
			exit;
		}

		/**
		 * Checks if an API key is able to connect to the API
		 */
		public function ajax_ping_api() {
			$status = 'error';
			if ( ! empty( $_POST['apiKey'] ) ) {
				try {
					$api = new WC_CoolPay_API( sanitize_text_field( $_POST['apiKey'] ) );
					$api->get( '/payments?page_size=1' );
					$status = 'success';
				} catch ( CoolPay_API_Exception $e ) {
					//
				}
			}
			echo json_encode( array( 'status' => $status ) );
			exit;
		}

		/**
		 * woocommerce_order_status_completed function.
		 *
		 * Captures one or several transactions when order state changes to complete.
		 *
		 * @access public
		 * @return void
		 */
		public function woocommerce_order_status_completed( $post_id ) {
			// Instantiate new order object
			$order = new WC_CoolPay_Order( $post_id );

			// Check the gateway settings.
			if ( $order->has_coolpay_payment() && WC_CoolPay_Helper::option_is_enabled( $this->s( 'coolpay_captureoncomplete' ) ) ) {
				// Capture only orders that are actual payments (regular orders / recurring payments)
				if ( ! WC_CoolPay_Subscription::is_subscription( $order ) ) {
					$transaction_id = $order->get_transaction_id();
					$payment        = new WC_CoolPay_API_Payment();

					// Check if there is a transaction ID
					if ( $transaction_id ) {
						try {
							// Retrieve resource data about the transaction
							$payment->get( $transaction_id );

							// Check if the transaction can be captured
							if ( $payment->is_action_allowed( 'capture' ) ) {

								// In case a payment has been partially captured, we check the balance and subtracts it from the order
								// total to avoid exceptions.
								$amount_multiplied = WC_CoolPay_Helper::price_multiply( $order->get_total() ) - $payment->get_balance();
								$amount            = WC_CoolPay_Helper::price_multiplied_to_float( $amount_multiplied );

								// Capture the payment
								$payment->capture( $transaction_id, $order, $amount );
							}
						} catch ( CoolPay_Exception $e ) {
							$this->log->add( $e->getMessage() );
						}
					}
				}
			}
		}


		/**
		 * payment_fields function.
		 *
		 * Prints out the description of the gateway. Also adds two checkboxes for viaBill/creditcard for customers to choose how to pay.
		 *
		 * @access public
		 * @return void
		 */
		public function payment_fields() {
			if ( $this->description ) {
				echo wpautop( wptexturize( $this->description ) );
			}
		}


		/**
		 * receipt_page function.
		 *
		 * Shows the recipt. This is the very last step before opening the payment window.
		 *
		 * @access public
		 * @return void
		 */
		public function receipt_page( $order ) {
			echo $this->generate_coolpay_form( $order );
		}

		/**
		 * Processing payments on checkout
		 *
		 * @param $order_id
		 *
		 * @return array
		 */
		public function process_payment( $order_id ) {
			try {
				// Instantiate order object
				$order = new WC_CoolPay_Order( $order_id );

				// Does the order need a new CoolPay payment?
				$needs_payment = true;

				// Default redirect to
				$redirect_to = $this->get_return_url( $order );

				// Instantiate a new transaction
				$api_transaction = new WC_CoolPay_API_Payment();

				// If the order is a subscripion or an attempt of updating the payment method
				if ( ! WC_CoolPay_Subscription::cart_contains_switches() && ( $order->contains_subscription() || $order->is_request_to_change_payment() ) ) {
					// Instantiate a subscription transaction instead of a payment transaction
					$api_transaction = new WC_CoolPay_API_Subscription();
					// Clean up any legacy data regarding old payment links before creating a new payment.
					$order->delete_payment_id();
					$order->delete_payment_link();
				}
				// If the order contains a product switch and does not need a payment, we will skip the CoolPay
				// payment window since we do not need to create a new payment nor modify an existing.
				else if ( $order->order_contains_switch() && ! $order->needs_payment() ) {
					$needs_payment = false;
				}

				if ( $needs_payment ) {
					// Create a new object
					$payment = new stdClass();
					// If a payment ID exists, go get it
					$payment->id = $order->get_payment_id();
					// Create a payment link
					$link = new stdClass();
					// If a payment link exists, go get it
					$link->url = $order->get_payment_link();

					// If the order does not already have a payment ID,
					// we will create one an attach it to the order
					// We also check if a payment already exists. If a link exists, we don't
					// need to create a payment.
					if ( empty( $payment->id ) && empty( $link->url ) ) {
						$payment = $api_transaction->create( $order );
						$order->set_payment_id( $payment->id );
					}

					// Create or update the payment link. This is necessary to do EVERY TIME
					// to avoid fraud with changing amounts.
					$link = $api_transaction->patch_link( $payment->id, $order );

					if ( WC_CoolPay_Helper::is_url( $link->url ) ) {
						$order->set_payment_link( $link->url );
					}

					// Overwrite the standard checkout url. Go to the CoolPay payment window.
					if ( WC_CoolPay_Helper::is_url( $link->url ) ) {
						$redirect_to = $link->url;
					}
				}

				// Perform redirect
				return array(
					'result'   => 'success',
					'redirect' => $redirect_to,
				);

			} catch ( CoolPay_Exception $e ) {
				$e->write_to_logs();
				wc_add_notice( $e->getMessage(), 'error' );
			}
		}

		/**
		 * HOOK: Handles pre-order payments
		 */
		public function process_pre_order_payments( $order ) {
			// Set order object
			$order = new WC_CoolPay_Order( $order );

			// Get transaction ID
			$transaction_id = $order->get_transaction_id();

			// Check if there is a transaction ID
			if ( $transaction_id ) {
				try {
					// Set payment object
					$payment = new WC_CoolPay_API_Payment();

					// Retrieve resource data about the transaction
					$payment->get( $transaction_id );

					// Check if the transaction can be captured
					if ( $payment->is_action_allowed( 'capture' ) ) {
						try {
							// Capture the payment
							$payment->capture( $transaction_id, $order );
						} // Payment failed
						catch ( CoolPay_API_Exception $e ) {
							$this->log->add( sprintf( "Could not process pre-order payment for order: #%s with transaction id: %s. Payment failed. Exception: %s", $order->get_clean_order_number(), $transaction_id, $e->getMessage() ) );

							$order->update_status( 'failed' );
						}
					}
				} catch ( CoolPay_API_Exception $e ) {
					$this->log->add( sprintf( "Could not process pre-order payment for order: #%s with transaction id: %s. Transaction not found. Exception: %s", $order->get_clean_order_number(), $transaction_id, $e->getMessage() ) );
				}

			}
		}

		/**
		 * Process refunds
		 * WooCommerce 2.2 or later
		 *
		 * @param  int    $order_id
		 * @param  float  $amount
		 * @param  string $reason
		 *
		 * @return bool|WP_Error
		 */
		public function process_refund( $order_id, $amount = null, $reason = '' ) {
			try {
				$order = new WC_CoolPay_Order( $order_id );

				$transaction_id = $order->get_transaction_id();

				// Check if there is a transaction ID
				if ( ! $transaction_id ) {
					throw new CoolPay_Exception( sprintf( __( "No transaction ID for order: %s", 'woo-coolpay' ), $order_id ) );
				}

				// Create a payment instance and retrieve transaction information
				$payment = new WC_CoolPay_API_Payment();
				$payment->get( $transaction_id );

				// Check if the transaction can be refunded
				if ( ! $payment->is_action_allowed( 'refund' ) ) {
					if ( in_array( $payment->get_current_type(), array( 'authorize', 'recurring' ), true ) ) {
						throw new CoolPay_Exception( __( 'A non-captured payment cannot be refunded.', 'woo-coolpay' ) );
					} else {
						throw new CoolPay_Exception( __( 'Transaction state does not allow refunds.', 'woo-coolpay' ) );
					}
				}

				// Perform a refund API request
				$payment->refund( $transaction_id, $order, $amount );

				return true;
			} catch ( CoolPay_Exception $e ) {
				$e->write_to_logs();

				return new WP_Error( 'coolpay_refund_error', $e->getMessage() );
			}
		}

		/**
		 * Clear cart in case its not already done.
		 *
		 * @return [type] [description]
		 */
		public function thankyou_page() {
			global $woocommerce;
			$woocommerce->cart->empty_cart();
		}


		/**
		 * scheduled_subscription_payment function.
		 *
		 * Runs every time a scheduled renewal of a subscription is required
		 *
		 * @access public
		 * @return The API response
		 */
		public function scheduled_subscription_payment( $amount_to_charge, $renewal_order ) {
			// Create subscription instance
			$transaction = new WC_CoolPay_API_Subscription();

			// Block the callback
			$transaction->block_callback = true;

			/** @var WC_Subscription $subscription */
			// Get the subscription based on the renewal order
			$subscription = WC_CoolPay_Subscription::get_subscriptions_for_renewal_order( $renewal_order, $single = true );

			// Make new instance to properly get the transaction ID with built in fallbacks.
			$subscription_order = new WC_CoolPay_Order( $subscription->get_id() );

			// Get the transaction ID from the subscription
			$transaction_id = $subscription_order->get_transaction_id();

			// Capture a recurring payment with fixed amount
			$response = $this->process_recurring_payment( $transaction, $transaction_id, $amount_to_charge, $renewal_order );

			return $response;
		}


		/**
		 * Wrapper to process a recurring payment on an order/subscription
		 *
		 * @param WC_CoolPay_API_Subscription $transaction
		 * @param                              $subscription_transaction_id
		 * @param                              $amount_to_charge
		 * @param                              $order
		 *
		 * @return mixed
		 */
		public function process_recurring_payment( WC_CoolPay_API_Subscription $transaction, $subscription_transaction_id, $amount_to_charge, $order ) {
			if ( ! $order instanceof WC_CoolPay_Order ) {
				$order = new WC_CoolPay_Order( $order );
			}

			$response = null;
			try {
				// Block the callback
				$transaction->block_callback = true;

				// Capture a recurring payment with fixed amount
				list( $response ) = $transaction->recurring( $subscription_transaction_id, $order, $amount_to_charge );

				if ( ! $response->accepted ) {
					throw new CoolPay_Exception( "Recurring payment not accepted by acquirer." );
				}

				// If there is a fee added to the transaction.
				if ( ! empty( $response->fee ) ) {
					$order->add_transaction_fee( $response->fee );
				}
				// Process the recurring payment on the orders
				WC_CoolPay_Subscription::process_recurring_response( $response, $order );

				// Reset failed attempts.
				$order->reset_failed_coolpay_payment_count();
			} catch ( CoolPay_Exception $e ) {
				$order->increase_failed_coolpay_payment_count();

				// Set the payment as failed
				$order->update_status( 'failed', 'Automatic renewal of ' . $order->get_order_number() . ' failed. Message: ' . $e->getMessage() );

				// Write debug information to the logs
				$e->write_to_logs();
			} catch ( CoolPay_API_Exception $e ) {
				$order->increase_failed_coolpay_payment_count();

				// Set the payment as failed
				$order->update_status( 'failed', 'Automatic renewal of ' . $order->get_order_number() . ' failed. Message: ' . $e->getMessage() );

				// Write debug information to the logs
				$e->write_to_logs();
			}

			return $response;
		}

		/**
		 * Prevents the failed attempts count to be copied to renewal orders
		 *
		 * @param $order_meta_query
		 *
		 * @return string
		 */
		public function remove_failed_coolpay_attempts_meta_query( $order_meta_query ) {
			$order_meta_query .= " AND `meta_key` NOT IN ('" . WC_CoolPay_Order::META_FAILED_PAYMENT_COUNT . "')";
			$order_meta_query .= " AND `meta_key` NOT IN ('_coolpay_transaction_id')";

			return $order_meta_query;
		}

		/**
		 * Prevents the legacy transaction ID from being copied to renewal orders
		 *
		 * @param $order_meta_query
		 *
		 * @return string
		 */
		public function remove_legacy_transaction_id_meta_query( $order_meta_query ) {
			$order_meta_query .= " AND `meta_key` NOT IN ('TRANSACTION_ID')";

			return $order_meta_query;
		}

		/**
		 * Declare gateway's meta data requirements in case of manual payment gateway changes performed by admins.
		 *
		 * @param array            $payment_meta
		 *
		 * @param  WC_Subscription $subscription
		 *
		 * @return array
		 */
		public function woocommerce_subscription_payment_meta( $payment_meta, $subscription ) {
			$order                    = new WC_CoolPay_Order( $subscription->get_id() );
			$payment_meta['coolpay'] = array(
				'post_meta' => array(
					'_coolpay_transaction_id' => array(
						'value' => $order->get_transaction_id(),
						'label' => __( 'CoolPay Transaction ID', 'woo-coolpay' ),
					),
				),
			);

			return $payment_meta;
		}

		/**
		 * Check if the transaction ID actually exists as a subscription transaction in the manager.
		 * If not, an exception will be thrown resulting in a validation error.
		 *
		 * @param array           $payment_meta
		 *
		 * @param WC_Subscription $subscription
		 *
		 * @throws CoolPay_API_Exception
		 */
		public function woocommerce_subscription_validate_payment_meta( $payment_meta, $subscription ) {
			if ( isset( $payment_meta['post_meta']['_coolpay_transaction_id']['value'] ) ) {
				$transaction_id = $payment_meta['post_meta']['_coolpay_transaction_id']['value'];
				$order          = new WC_CoolPay_Order( $subscription->get_id() );

				// Validate only if the transaction ID has changed
				if ( $transaction_id !== $order->get_transaction_id() ) {
					$transaction = new WC_CoolPay_API_Subscription();
					$transaction->get( $transaction_id );

					// If transaction could be found, add a note on the order for history and debugging reasons.
					$subscription->add_order_note( sprintf( __( 'CoolPay Transaction ID updated from #%d to #%d', 'woo-coolpay' ), $order->get_transaction_id(), $transaction_id ), 0, true );
				}
			}
		}

		/**
		 * Triggered when customers are changing payment method to CoolPay.
		 *
		 * @param $new_payment_method
		 * @param $subscription
		 * @param $old_payment_method
		 */
		public function on_subscription_payment_method_updated_to_coolpay( $subscription, $old_payment_method ) {
			$order = new WC_CoolPay_Order( $subscription->get_id() );
			$order->increase_payment_method_change_count();
		}


		/**
		 * subscription_cancellation function.
		 *
		 * Cancels a transaction when the subscription is cancelled
		 *
		 * @access public
		 *
		 * @param WC_Order $order - WC_Order object
		 *
		 * @return void
		 */
		public function subscription_cancellation( $order ) {
			if ( 'cancelled' !== $order->get_status() ) {
				return;
			}

			try {
				if ( WC_CoolPay_Subscription::is_subscription( $order ) ) {
					$order          = new WC_CoolPay_Order( $order );
					$transaction_id = $order->get_transaction_id();

					$subscription = new WC_CoolPay_API_Subscription();
					$subscription->get( $transaction_id );

					if ( $subscription->is_action_allowed( 'cancel' ) ) {
						$subscription->cancel( $transaction_id );
					}
				}
			} catch ( CoolPay_Exception $e ) {
				$e->write_to_logs();
			} catch ( CoolPay_API_Exception $e ) {
				$e->write_to_logs();
			}
		}

		/**
		 * on_order_cancellation function.
		 *
		 * Is called when a customer cancels the payment process from the CoolPay payment window.
		 *
		 * @access public
		 * @return void
		 */
		public function on_order_cancellation( $order_id ) {
			$order = new WC_Order( $order_id );

			// Redirect the customer to account page if the current order is failed
			if ( $order->get_status() === 'failed' ) {
				$payment_failure_text = sprintf( __( '<p><strong>Payment failure</strong> A problem with your payment on order <strong>#%i</strong> occured. Please try again to complete your order.</p>', 'woo-coolpay' ), $order_id );

				wc_add_notice( $payment_failure_text, 'error' );

				wp_redirect( get_permalink( get_option( 'woocommerce_myaccount_page_id' ) ) );
			}

			$order->add_order_note( __( 'CoolPay Payment', 'woo-coolpay' ) . ': ' . __( 'Cancelled during process', 'woo-coolpay' ) );

			wc_add_notice( __( '<p><strong>%s</strong>: %s</p>', __( 'Payment cancelled', 'woo-coolpay' ), __( 'Due to cancellation of your payment, the order process was not completed. Please fulfill the payment to complete your order.', 'woo-coolpay' ) ), 'error' );
		}

		/**
		 * callback_handler function.
		 *
		 * Is called after a payment has been submitted in the CoolPay payment window.
		 *
		 * @access public
		 * @return void
		 */
		public function callback_handler() {
			// Get callback body
			$request_body = file_get_contents( "php://input" );

			if ( empty( $request_body ) ) {
				return;
			}

			// Decode the body into JSON
			$json = json_decode( $request_body );

			// Instantiate payment object
			$payment = new WC_CoolPay_API_Payment( $json );

			// Fetch order number;
			$order_number = WC_CoolPay_Order::get_order_id_from_callback( $json );

			// Fetch subscription post ID if present
			$subscription_id = WC_CoolPay_Order::get_subscription_id_from_callback( $json );

			if ( ! empty( $subscription_id ) ) {
				$subscription = new WC_CoolPay_Order( $subscription_id );
			}

			if ( $payment->is_authorized_callback( $request_body ) ) {
				// Instantiate order object
				$order = new WC_CoolPay_Order( $order_number );

				$order_id = $order->get_id();

				// Get last transaction in operation history
				$transaction = end( $json->operations );

				// Is the transaction accepted and approved by CP / Acquirer?
				if ( $json->accepted ) {

					// Perform action depending on the operation status type
					try {
						switch ( $transaction->type ) {
							//
							// Cancel callbacks are currently not supported by the CoolPay API
							//
							case 'cancel' :
								// Write a note to the order history
								$order->note( __( 'Payment cancelled.', 'woo-coolpay' ) );
								break;

							case 'capture' :
								// Write a note to the order history
								$order->note( __( 'Payment captured.', 'woo-coolpay' ) );
								break;

							case 'refund' :
								$order->note( sprintf( __( 'Refunded %s %s', 'woo-coolpay' ), WC_CoolPay_Helper::price_normalize( $transaction->amount ), $json->currency ) );
								break;

							case 'authorize' :
								// Set the transaction order ID
								$order->set_transaction_order_id( $json->order_id );

								// Remove payment link
								$order->delete_payment_link();

								// Remove payment ID, now we have the transaction ID
								$order->delete_payment_id();

								// Subscription authorization
								if ( ! empty( $subscription_id ) ) {
									// Write log
									$subscription->note( sprintf( __( 'Subscription authorized. Transaction ID: %s', 'woo-coolpay' ), $json->id ) );
									// Activate the subscription

									// Check if there is an initial payment on the subscription.
									// We are saving the total before completing the original payment.
									// This gives us the correct payment for the auto initial payment on subscriptions.
									$subscription_initial_payment = $order->get_total();

									// Mark the payment as complete
									//$subscription->set_transaction_id($json->id);
									// Temporarily save the transaction ID on a custom meta row to avoid empty values in 3.0.
									update_post_meta( $subscription_id, '_coolpay_transaction_id', $json->id );
									//$subscription->payment_complete($json->id);
									$subscription->set_transaction_order_id( $json->order_id );

									// Only make an instant payment if there is an initial payment
									if ( $subscription_initial_payment > 0 ) {
										// Check if this is an order containing a subscription
										if ( ! WC_CoolPay_Subscription::is_subscription( $order_id ) && $order->contains_subscription() ) {
											// Process a recurring payment.
											$this->process_recurring_payment( new WC_CoolPay_API_Subscription(), $json->id, $subscription_initial_payment, $order );
										}
									}
									// If there is no initial payment, we will mark the order as complete.
									// This is usually happening if a subscription has a free trial.
									else {
										// Only complete the order payment if we are not changing payment method.
										// This is to avoid the subscription going into a 'processing' limbo.
										if ( empty( $json->variables->change_payment ) ) {
											$order->payment_complete();
										}
									}

								} // Regular payment authorization
								else {
									// Add order transaction fee if available
									if ( ! empty( $json->fee ) ) {
										$order->add_transaction_fee( $json->fee );
									}

									// Check for pre-order
									if ( WC_CoolPay_Helper::has_preorder_plugin() && WC_Pre_Orders_Order::order_contains_pre_order( $order ) && WC_Pre_Orders_Order::order_requires_payment_tokenization( $order_id ) ) {
										try {
											// Set transaction ID without marking the payment as complete
											$order->set_transaction_id( $json->id );
										} catch ( WC_Data_Exception $e ) {
											$this->log->add( __( 'An error occured while setting transaction id: %d on order %s. %s', $json->id, $order_id, $e->getMessage() ) );
										}
										WC_Pre_Orders_Order::mark_order_as_pre_ordered( $order );
									} // Regular product
									else {
										// Register the payment on the order
										$order->payment_complete( $json->id );
									}

									// Write a note to the order history
									$order->note( sprintf( __( 'Payment authorized. Transaction ID: %s', 'woo-coolpay' ), $json->id ) );
								}
								break;
						}

						do_action( 'woocommerce_coolpay_accepted_callback', $order, $json );
						do_action( 'woocommerce_coolpay_accepted_callback_status_' . $transaction->type, $order, $json );

					} catch ( CoolPay_API_Exception $e ) {
						$e->write_to_logs();
					}
				}

				// The transaction was not accepted.
				// Print debug information to logs
				else {
					// Write debug information
					$this->log->separator();
					$this->log->add( sprintf( __( 'Transaction failed for #%s.', 'woo-coolpay' ), $order_number ) );
					$this->log->add( sprintf( __( 'CoolPay status code: %s.', 'woo-coolpay' ), $transaction->qp_status_code ) );
					$this->log->add( sprintf( __( 'CoolPay status message: %s.', 'woo-coolpay' ), $transaction->cp_status_msg ) );
					$this->log->add( sprintf( __( 'Acquirer status code: %s', 'woo-coolpay' ), $transaction->aq_status_code ) );
					$this->log->add( sprintf( __( 'Acquirer status message: %s', 'woo-coolpay' ), $transaction->aq_status_msg ) );
					$this->log->separator();

					if ( $transaction->type == 'recurring' ) {
						WC_Subscriptions_Manager::process_subscription_payment_failure_on_order( $order );
					}

					if ( 'rejected' != $json->state ) {
						// Update the order statuses
						if ( $transaction->type == 'subscribe' ) {
							WC_Subscriptions_Manager::process_subscription_payment_failure_on_order( $order );
						} else {
							$order->update_status( 'failed' );
						}
					}
				}
			} else {
				$this->log->add( sprintf( __( 'Invalid callback body for order #%s.', 'woo-coolpay' ), $order_number ) );
			}
		}

		/**
		 * @param WC_CoolPay_Order $order
		 * @param                   $json
		 */
		public function callback_update_transaction_cache( $order, $json ) {
			try {
				// Instantiating a payment transaction.
				// The type of transaction is currently not important for caching - hence no logic for handling subscriptions is added.
				$transaction = new WC_CoolPay_API_Payment( $json );
				$transaction->cache_transaction();
			} catch ( CoolPay_Exception $e ) {
				$this->log->add( sprintf( 'Could not cache transaction from callback for order: #%s -> %s', $order->get_id(), $e->getMessage() ) );
			}
		}

		/**
		 * init_form_fields function.
		 *
		 * Initiates the plugin settings form fields
		 *
		 * @access public
		 * @return array
		 */
		public function init_form_fields() {
			$this->form_fields = WC_CoolPay_Settings::get_fields();
		}


		/**
		 * admin_options function.
		 *
		 * Prints the admin settings form
		 *
		 * @access public
		 * @return string
		 */
		public function admin_options() {
			echo "<h3>CoolPay - {$this->id}, v" . WCCP_VERSION . "</h3>";
			echo "<p>" . __( 'Allows you to receive payments via CoolPay.', 'woo-coolpay' ) . "</p>";

			WC_CoolPay_Settings::clear_logs_section();

			do_action( 'woocommerce_coolpay_settings_table_before' );

			echo "<table class=\"form-table\">";
			$this->generate_settings_html();
			echo "</table";

			do_action( 'woocommerce_coolpay_settings_table_after' );
		}


		/**
		 * add_meta_boxes function.
		 *
		 * Adds the action meta box inside the single order view.
		 *
		 * @access public
		 * @return void
		 */
		public function add_meta_boxes() {
			global $post;

			$screen     = get_current_screen();
			$post_types = array( 'shop_order', 'shop_subscription' );

			if ( in_array( $screen->id, $post_types, true ) && in_array( $post->post_type, $post_types, true ) ) {
				$order = new WC_CoolPay_Order( $post->ID );
				if ( $order->has_coolpay_payment() ) {
					add_meta_box( 'coolpay-payment-actions', __( 'CoolPay Payment', 'woo-coolpay' ), array(
						&$this,
						'meta_box_payment',
					), 'shop_order', 'side', 'high' );
					add_meta_box( 'coolpay-payment-actions', __( 'CoolPay Subscription', 'woo-coolpay' ), array(
						&$this,
						'meta_box_subscription',
					), 'shop_subscription', 'side', 'high' );
				}
			}
		}


		/**
		 * meta_box_payment function.
		 *
		 * Inserts the content of the API actions meta box - Payments
		 *
		 * @access public
		 * @return void
		 */
		public function meta_box_payment() {
			global $post;
			$order = new WC_CoolPay_Order( $post->ID );

			$transaction_id = $order->get_transaction_id();
			if ( $transaction_id && $order->has_coolpay_payment() ) {
				$state = null;
				try {
					$transaction = new WC_CoolPay_API_Payment();
					$transaction->get( $transaction_id );
					$transaction->cache_transaction();

					$state = $transaction->get_state();

					try {
						$status = $transaction->get_current_type();
					} catch ( CoolPay_API_Exception $e ) {
						if ( $state !== 'initial' ) {
							throw new CoolPay_API_Exception( $e->getMessage() );
						}
					
					$status = $state;
					}
					echo "<p class=\"woocommerce-coolpay-{$status}\"><strong>" . __( 'Current payment state', 'woo-coolpay' ) . ": " . $status . "</strong></p>";

					if ( $transaction->is_action_allowed( 'standard_actions' ) ) {
						echo "<h4><strong>" . __( 'Actions', 'woo-coolpay' ) . "</strong></h4>";
						echo "<ul class=\"order_action\">";

						if ( $transaction->is_action_allowed( 'capture' ) ) {
							echo "<li class=\"cp-full-width\"><a class=\"button button-primary\" data-action=\"capture\" data-confirm=\"" . __( 'You are about to CAPTURE this payment', 'woo-coolpay' ) . "\">" . sprintf( __( 'Capture Full Amount (%s)', 'woo-coolpay' ), $transaction->get_formatted_remaining_balance() ) . "</a></li>";
						}

						printf( "<li class=\"cp-balance\"><span class=\"cp-balance__label\">%s:</span><span class=\"cp-balance__amount\"><span class='cp-balance__currency'>%s</span>%s</span></li>", __( 'Remaining balance', 'woo-coolpay' ), $transaction->get_currency(), $transaction->get_formatted_remaining_balance() );
						printf( "<li class=\"cp-balance last\"><span class=\"cp-balance__label\">%s:</span><span class=\"cp-balance__amount\"><span class='cp-balance__currency'>%s</span><input id='cp-balance__amount-field' type='text' value='%s' /></span></li>", __( 'Capture amount', 'woo-coolpay' ), $transaction->get_currency(), $transaction->get_formatted_remaining_balance() );

						if ( $transaction->is_action_allowed( 'capture' ) ) {
							echo "<li class=\"cp-full-width\"><a class=\"button\" data-action=\"captureAmount\" data-confirm=\"" . __( 'You are about to CAPTURE this payment', 'woo-coolpay' ) . "\">" . __( 'Capture Specified Amount', 'woo-coolpay' ) . "</a></li>";
						}


						if ( $transaction->is_action_allowed( 'cancel' ) ) {
							echo "<li class=\"cp-full-width\"><a class=\"button\" data-action=\"cancel\" data-confirm=\"" . __( 'You are about to CANCEL this payment', 'woo-coolpay' ) . "\">" . __( 'Cancel', 'woo-coolpay' ) . "</a></li>";
						}

						echo "</ul>";
					}

					printf( '<p><small><strong>%s:</strong> %d <span class="cp-meta-card"><img src="%s" /></span></small>', __( 'Transaction ID', 'woo-coolpay' ), $transaction_id, WC_Coolpay_Helper::get_payment_type_logo( $transaction->get_brand() ) );

					$transaction_order_id = $order->get_transaction_order_id();
					if ( isset( $transaction_order_id ) && ! empty( $transaction_order_id ) ) {
						printf( '<p><small><strong>%s:</strong> %s</small>', __( 'Transaction Order ID', 'woo-coolpay' ), $transaction_order_id );
					}
				} catch ( CoolPay_API_Exception $e ) {
					$e->write_to_logs();
					if ( $state !== 'initial' ) {
						$e->write_standard_warning();
					}
				} catch ( CoolPay_Exception $e ) {
					$e->write_to_logs();
					if ( $state !== 'initial' ) {
						$e->write_standard_warning();
					}
				}
			}

			// Show payment ID and payment link for orders that have not yet
			// been paid. Show this information even if the transaction ID is missing.
			$payment_id = $order->get_payment_id();
			if ( isset( $payment_id ) && ! empty( $payment_id ) ) {
				printf( '<p><small><strong>%s:</strong> %d</small>', __( 'Payment ID', 'woo-coolpay' ), $payment_id );
			}

			$payment_link = $order->get_payment_link();
			if ( isset( $payment_link ) && ! empty( $payment_link ) ) {
				printf( '<p><small><strong>%s:</strong> <br /><input type="text" style="%s"value="%s" readonly /></small></p>', __( 'Payment Link', 'woo-coolpay' ), 'width:100%', $payment_link );
			}
		}


		/**
		 * meta_box_payment function.
		 *
		 * Inserts the content of the API actions meta box - Subscriptions
		 *
		 * @access public
		 * @return void
		 */
		public function meta_box_subscription() {
			global $post;
			$order = new WC_CoolPay_Order( $post->ID );

			$transaction_id = $order->get_transaction_id();
			$state          = null;
			if ( $transaction_id && $order->has_coolpay_payment() ) {
				try {

					$transaction = new WC_CoolPay_API_Subscription();
					$transaction->get( $transaction_id );
					$status = null;
					$state  = $transaction->get_state();
					try {
						$status = $transaction->get_current_type() . ' (' . __( 'subscription', 'woo-coolpay' ) . ')';
					} catch ( CoolPay_API_Exception $e ) {
						if ( 'initial' !== $state ) {
							throw new CoolPay_API_Exception( $e->getMessage() );
						}
						$status = $state;
					}

					echo "<p class=\"woocommerce-coolpay-{$status}\"><strong>" . __( 'Current payment state', 'woo-coolpay' ) . ": " . $status . "</strong></p>";

					printf( '<p><small><strong>%s:</strong> %d <span class="cp-meta-card"><img src="%s" /></span></small>', __( 'Transaction ID', 'woo-coolpay' ), $transaction_id, WC_Coolpay_Helper::get_payment_type_logo( $transaction->get_brand() ) );

					$transaction_order_id = $order->get_transaction_order_id();
					if ( isset( $transaction_order_id ) && ! empty( $transaction_order_id ) ) {
						printf( '<p><small><strong>%s:</strong> %s</small>', __( 'Transaction Order ID', 'woo-coolpay' ), $transaction_order_id );
					}
				} catch ( CoolPay_API_Exception $e ) {
					$e->write_to_logs();
					if ( 'initial' !== $state ) {
						$e->write_standard_warning();
					}
				}
			}
		}


		/**
		 * email_instructions function.
		 *
		 * Adds custom text to the order confirmation email.
		 *
		 * @access public
		 *
		 * @param WC_Order $order
		 * @param boolean  $sent_to_admin
		 *
		 * @return bool /string/void
		 */
		public function email_instructions( $order, $sent_to_admin ) {
			$payment_method = version_compare( WC_VERSION, '3.0', '<' ) ? $order->payment_method : $order->get_payment_method();

			if ( $sent_to_admin || ( $order->get_status() !== 'processing' && $order->get_status() !== 'completed' ) || $payment_method !== 'coolpay' ) {
				return;
			}

			if ( $this->instructions ) {
				echo wpautop( wptexturize( $this->instructions ) );
			}
		}

		/**
		 * Adds a separate column for payment info
		 *
		 * @param array $show_columns
		 *
		 * @return array
		 */
		public function filter_shop_order_posts_columns( $show_columns ) {
			$column_name   = 'coolpay_transaction_info';
			$column_header = __( 'Payment', 'woo-coolpay' );

			return WC_CoolPay_Helper::array_insert_after( 'shipping_address', $show_columns, $column_name, $column_header );
		}

		/**
		 * apply_custom_order_data function.
		 *
		 * Applies transaction ID and state to the order data overview
		 *
		 * @access public
		 * @return void
		 */
		public function apply_custom_order_data( $column ) {
			global $post, $woocommerce;

			$order = new WC_CoolPay_Order( $post->ID );

			// Show transaction ID on the overview
			if ( ( $post->post_type == 'shop_order' && $column == 'coolpay_transaction_info' ) || ( $post->post_type == 'shop_subscription' && $column == 'order_title' ) ) {
				// Insert transaction id and payment status if any
				$transaction_id = $order->get_transaction_id();

				try {
					if ( $transaction_id && $order->has_coolpay_payment() ) {

						if ( WC_CoolPay_Subscription::is_subscription( $post->ID ) ) {
							$transaction = new WC_CoolPay_API_Subscription();
						} else {
							$transaction = new WC_CoolPay_API_Payment();
						}

						// Get transaction data
						$transaction->maybe_load_transaction_from_cache( $transaction_id );

						if ( $order->subscription_is_renewal_failure() ) {
							$status = __( 'Failed renewal', 'woo-coolpay' );
						} else {
							$status = $transaction->get_current_type();
						}

						WC_CoolPay_Views::get_view( 'html-order-table-transaction-data.php', array(
							'transaction_id'             => $transaction_id,
							'transaction_order_id'       => $order->get_transaction_order_id(),
							'transaction_brand'          => $transaction->get_brand(),
							'transaction_brand_logo_url' => WC_CoolPay_Helper::get_payment_type_logo( $transaction->get_brand() ),
							'transaction_status'         => $status,
							'transaction_is_test'        => $transaction->is_test(),
							'is_cached'                  => $transaction->is_loaded_from_cached(),
						) );
					}
				} catch ( CoolPay_API_Exception $e ) {
					$this->log->add( sprintf( 'Order list: #%s - %s', $order->get_id(), $e->getMessage() ) );
				} catch ( CoolPay_Exception $e ) {
					$this->log->add( sprintf( 'Order list: #%s - %s', $order->get_id(), $e->getMessage() ) );
				}

			}
		}

		/**
		 * FILTER: apply_gateway_icons function.
		 *
		 * Sets gateway icons on frontend
		 *
		 * @access public
		 * @return void
		 */
		public function apply_gateway_icons( $icon, $id ) {
			if ( $id == $this->id ) {
				$icon = '';

				$icons = $this->s( 'coolpay_icons' );

				if ( ! empty( $icons ) ) {
					$icons_maxheight = $this->gateway_icon_size();

					foreach ( $icons as $key => $item ) {
						$icon .= $this->gateway_icon_create( $item, $icons_maxheight );
					}
				}
			}

			return $icon;
		}


		/**
		 * gateway_icon_create
		 *
		 * Helper to get the a gateway icon image tag
		 *
		 * @access protected
		 * @return string
		 */
		protected function gateway_icon_create( $icon, $max_height ) {
			if ( file_exists( __DIR__ . '/assets/images/cards/' . $icon . '.svg' ) ) {
				$icon_url = $icon_url = WC_HTTPS::force_https_url( plugin_dir_url( __FILE__ ) . 'assets/images/cards/' . $icon . '.svg' );
			} else {
				$icon_url = WC_HTTPS::force_https_url( plugin_dir_url( __FILE__ ) . 'assets/images/cards/' . $icon . '.png' );
			}

			$icon_url = apply_filters( 'woocommerce_coolpay_checkout_gateway_icon_url', $icon_url, $icon );

			return '<img src="' . $icon_url . '" alt="' . esc_attr( $this->get_title() ) . '" style="max-height:' . $max_height . '"/>';
		}


		/**
		 * gateway_icon_size
		 *
		 * Helper to get the a gateway icon image max height
		 *
		 * @access protected
		 * @return void
		 */
		protected function gateway_icon_size() {
			$settings_icons_maxheight = $this->s( 'coolpay_icons_maxheight' );

			return ! empty( $settings_icons_maxheight ) ? $settings_icons_maxheight . 'px' : '20px';
		}


		/**
		 *
		 * get_gateway_currency
		 *
		 * Returns the gateway currency
		 *
		 * @access public
		 *
		 * @param WC_Order $order
		 *
		 * @return void
		 */
		public function get_gateway_currency( $order ) {
			if ( WC_CoolPay_Helper::option_is_enabled( $this->s( 'coolpay_currency_auto' ) ) ) {
				$currency = version_compare( WC_VERSION, '3.0', '<' ) ? $order->get_order_currency() : $order->get_currency();
			} else {
				$currency = $this->s( 'coolpay_currency' );
			}

			$currency = apply_filters( 'woocommerce_coolpay_currency', $currency, $order );

			return $currency;
		}


		/**
		 *
		 * get_gateway_language
		 *
		 * Returns the gateway language
		 *
		 * @access public
		 * @return string
		 */
		public function get_gateway_language() {
			$language = apply_filters( 'woocommerce_coolpay_language', $this->s( 'coolpay_language' ) );

			return $language;
		}

		/**
		 * Registers custom bulk actions
		 */
		public function register_bulk_actions() {
			global $post_type;

			if ( $post_type === 'shop_order' && WC_CoolPay_Subscription::plugin_is_active() ) {
				WC_CoolPay_Views::get_view( 'bulk-actions.php' );
			}
		}

		/**
		 * Handles custom bulk actions
		 */
		public function handle_bulk_actions() {
			$wp_list_table = _get_list_table( 'WP_Posts_List_Table' );

			$action = $wp_list_table->current_action();

			// Check for posts
			if ( ! empty( $_GET['post'] ) ) {
				$order_ids = $_GET['post'];

				// Make sure the $posts variable is an array
				if ( ! is_array( $order_ids ) ) {
					$order_ids = array( $order_ids );
				}
			}

			if ( current_user_can( 'manage_woocommerce' ) ) {
				switch ( $action ) {
					// 3. Perform the action
					case 'coolpay_capture_recurring':
						// Security check
						$this->bulk_action_coolpay_capture_recurring( $order_ids );

						// Redirect client
						wp_redirect( $_SERVER['HTTP_REFERER'] );
						exit;
						break;

					default:
						return;
				}
			}
		}

		/**
		 * @param array $order_ids
		 */
		public function bulk_action_coolpay_capture_recurring( $order_ids = array() ) {
			if ( ! empty( $order_ids ) ) {
				foreach ( $order_ids as $order_id ) {
					$order          = new WC_CoolPay_Order( $order_id );
					$payment_method = version_compare( WC_VERSION, '3.0', '<' ) ? $order->payment_method : $order->get_payment_method();
					if ( WC_CoolPay_Subscription::is_renewal( $order ) && $order->needs_payment() && $payment_method === $this->id ) {
						$this->scheduled_subscription_payment( $order->get_total(), $order );
					}
				}
			}

		}


		/**
		 *
		 * in_plugin_update_message
		 *
		 * Show plugin changes. Code adapted from W3 Total Cache.
		 *
		 * @access public
		 * @static
		 * @return void
		 */
		public static function in_plugin_update_message( $args ) {
			$transient_name = 'wccp_upgrade_notice_' . $args['Version'];
			if ( false === ( $upgrade_notice = get_transient( $transient_name ) ) ) {
				$response = wp_remote_get( 'https://plugins.svn.wordpress.org/woocommerce-coolpay/trunk/README.txt' );

				if ( ! is_wp_error( $response ) && ! empty( $response['body'] ) ) {
					$upgrade_notice = self::parse_update_notice( $response['body'] );
					set_transient( $transient_name, $upgrade_notice, DAY_IN_SECONDS );
				}
			}

			echo wp_kses_post( $upgrade_notice );
		}

		/**
		 *
		 * parse_update_notice
		 *
		 * Parse update notice from readme file.
		 *
		 * @param  string $content
		 *
		 * @return string
		 */
		private static function parse_update_notice( $content ) {
			// Output Upgrade Notice
			$matches        = null;
			$regexp         = '~==\s*Upgrade Notice\s*==\s*=\s*(.*)\s*=(.*)(=\s*' . preg_quote( WCCP_VERSION, '/' ) . '\s*=|$)~Uis';
			$upgrade_notice = '';

			if ( preg_match( $regexp, $content, $matches ) ) {
				$version = trim( $matches[1] );
				$notices = (array) preg_split( '~[\r\n]+~', trim( $matches[2] ) );

				if ( version_compare( WCCP_VERSION, $version, '<' ) ) {

					$upgrade_notice .= '<div class="wc_plugin_upgrade_notice">';

					foreach ( $notices as $index => $line ) {
						$upgrade_notice .= wp_kses_post( preg_replace( '~\[([^\]]*)\]\(([^\)]*)\)~', '<a href="${2}">${1}</a>', $line ) );
					}

					$upgrade_notice .= '</div> ';
				}
			}

			return wp_kses_post( $upgrade_notice );
		}

		/**
		 * path
		 *
		 * Returns a plugin URL path
		 *
		 * @param $path
		 *
		 * @return mixed
		 */
		public function plugin_url( $path ) {
			return plugins_url( $path, __FILE__ );
		}
	}

	/**
	 * Make the object available for later use
	 *
	 * @return WC_CoolPay
	 */
	function WC_CP() {
		return WC_CoolPay::get_instance();
	}

	// Instantiate
	WC_CP();
	WC_CP()->hooks_and_filters();

	// Add the gateway to WooCommerce
	function add_coolpay_gateway( $methods ) {
		$methods[] = 'WC_CoolPay';

		return apply_filters( 'woocommerce_coolpay_load_instances', $methods );
	}

	add_filter( 'woocommerce_payment_gateways', 'add_coolpay_gateway' );
	add_filter( 'woocommerce_coolpay_load_instances', 'WC_CoolPay::filter_load_instances' );
	add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'WC_CoolPay::add_action_links' );
}

/**
 * Run installer
 *
 * @param string __FILE__ - The current file
 * @param function - Do the installer/update logic.
 */
register_activation_hook( __FILE__, function () {
	require_once WCCP_PATH . 'classes/woocommerce-coolpay-install.php';

	// Run the installer on the first install.
	if ( WC_CoolPay_Install::is_first_install() ) {
		WC_CoolPay_Install::install();
	}
} );