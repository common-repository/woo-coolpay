<?php

class WC_CoolPay_MobilePay extends WC_CoolPay_Instance {
    
    public $main_settings = NULL;
    
    public function __construct() {
        parent::__construct();
        
        // Get gateway variables
        $this->id = 'mobilepay';
        
        $this->method_title = 'CoolPay - MobilePay';
        
        $this->setup();
        
        $this->title = $this->s('title');
        $this->description = $this->s('description');
        
        add_filter( 'woocommerce_coolpay_cardtypelock_mobilepay', array( $this, 'filter_cardtypelock' ) );
    }

    
    /**
    * init_form_fields function.
    *
    * Initiates the plugin settings form fields
    *
    * @access public
    * @return array
    */
    public function init_form_fields()
    {
        $this->form_fields = array(
            'enabled' => array(
                'title' => __( 'Enable', 'woo-coolpay' ),
                'type' => 'checkbox', 
                'label' => __( 'Enable MobilePay payment', 'woo-coolpay' ), 
                'default' => 'no'
            ), 
            '_Shop_setup' => array(
                'type' => 'title',
                'title' => __( 'Shop setup', 'woo-coolpay' ),
            ),
                'title' => array(
                    'title' => __( 'Title', 'woo-coolpay' ), 
                    'type' => 'text', 
                    'description' => __( 'This controls the title which the user sees during checkout.', 'woo-coolpay' ), 
                    'default' => __('MobilePay', 'woo-coolpay')
                ),
                'description' => array(
                    'title' => __( 'Customer Message', 'woo-coolpay' ), 
                    'type' => 'textarea', 
                    'description' => __( 'This controls the description which the user sees during checkout.', 'woo-coolpay' ), 
                    'default' => __('Pay with your mobile phone', 'woo-coolpay')
                ),
        );
    }
   
    
    /**
    * filter_cardtypelock function.
    *
    * Sets the cardtypelock
    *
    * @access public
    * @return string
    */
    public function filter_cardtypelock( )
    {
        return 'mobilepay';
    }
}
