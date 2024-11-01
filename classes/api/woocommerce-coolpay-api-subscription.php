<?php
/**
 * WC_CoolPay_API_Subscription class
 *
 * @class 		WC_CoolPay_API_Subscription
 * @since		4.0.0
 * @package		Woocommerce_CoolPay/Classes
 * @category	Class
 * @author 		PerfectSolution
 * @docs        https://coolpay.com/docs/apidocs/
 */

class WC_CoolPay_API_Subscription extends WC_CoolPay_API_Transaction
{
  	/**
	* __construct function.
	*
	* @access public
	* @return void
	*/
    public function __construct( $resource_data = NULL )
    {
    	// Run the parent construct
    	parent::__construct();

    	// Set the resource data to an object passed in on object instantiation.
    	// Usually done when we want to perform actions on an object returned from
    	// the API sent to the plugin callback handler.
  		if( is_object( $resource_data ) )
  		{
  			$this->resource_data = $resource_data;
  		}

    	// Append the main API url
        $this->api_url .= 'subscriptions/';
    }


   	/**
	* create function.
	*
	* Creates a new subscription via the API
	*
	* @access public
	* @param  WC_CoolPay_Order $order
	* @return object
	* @throws CoolPay_API_Exception
	*/
    public function create( WC_CoolPay_Order $order )
    {
        return parent::create( $order );
    }


   	/**
	* recurring function.
	*
	* Sends a 'recurring' request to the CoolPay API
	*
	* @access public
	* @param  int $transaction_id
	* @param  int $amount
	* @return $request
	* @throws CoolPay_API_Exception
	*/
    public function recurring( $subscription_id, $order, $amount = NULL)
    {
        // Check if a custom amount ha been set
        if( $amount === NULL )
        {
            // No custom amount set. Default to the order total
            $amount = WC_Subscriptions_Order::get_recurring_total( $order );
        }

        if( ! $order instanceof WC_CoolPay_Order ) {
			$order_id = $order->get_id();
            $order = new WC_CoolPay_Order( $order_id );
        }

        $order_number = $order->get_order_number_for_api( $is_recurring = TRUE );

    	$request = $this->post( sprintf( '%d/%s?synchronized', $subscription_id, "recurring" ), array(
            'amount' => WC_CoolPay_Helper::price_multiply( $amount ),
            'order_id' => sprintf('%s', $order_number ),
            'auto_capture' => $order->get_autocapture_setting(),
            'autofee' => WC_CoolPay_Helper::option_is_enabled( WC_CP()->s( 'coolpay_autofee' ) ),
            'text_on_statement' => WC_CP()->s('coolpay_text_on_statement'),
            'order_post_id' => $order->get_id(),
        ), TRUE );

        return $request;
    }


  	/**
	* cancel function.
	*
	* Sends a 'cancel' request to the CoolPay API
	*
	* @access public
	* @param  int $subscription_id
	* @return void
	* @throws CoolPay_API_Exception
	*/
    public function cancel( $subscription_id )
    {
    	$this->post( sprintf( '%d/%s', $subscription_id, "cancel" ) );
    }


	/**
	 * is_action_allowed function.
	 *
	 * Check if the action we are about to perform is allowed according to the current transaction state.
	 *
	 * @access public
	 *
	 * @param $action
	 *
	 * @return boolean
	 * @throws CoolPay_API_Exception
	 */
    public function is_action_allowed( $action )
    {
        $state = $this->get_current_type();

        $allowed_states = array(
            'cancel' => array( 'authorize' ),
            'standard_actions' => array( 'authorize' )
        );

        return array_key_exists( $action, $allowed_states ) AND in_array( $state, $allowed_states[$action] );
    }
}