<?php

class WC_CoolPay_ViaBill extends WC_CoolPay_Instance {
    
    public $main_settings = NULL;
    
    public function __construct() {
        parent::__construct();
        
        // Get gateway variables
        $this->id = 'viabill';
        
        $this->method_title = 'CoolPay - ViaBill';
        
        $this->setup();
        
        $this->title = $this->s('title');
        $this->description = $this->s('description');
        
        add_filter( 'woocommerce_coolpay_cardtypelock_viabill', array( $this, 'filter_cardtypelock' ) );
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
                'label' => __( 'Enable ViaBill payment', 'woo-coolpay' ), 
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
                    'default' => __('ViaBill', 'woo-coolpay')
                ),
                'description' => array(
                    'title' => __( 'Customer Message', 'woo-coolpay' ), 
                    'type' => 'textarea', 
                    'description' => __( 'This controls the description which the user sees during checkout.', 'woo-coolpay' ), 
                    'default' => __('Pay with ViaBill', 'woo-coolpay')
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
        return 'viabill';
    }
}
