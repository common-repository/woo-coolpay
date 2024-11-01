<?php

class WC_CoolPay_Instance extends WC_CoolPay {
    
    public $main_settings = NULL;
    
    public function __construct() {
        parent::__construct();
        
        $this->supports = array( 
            'products', 
            'refunds'
        );
    }
    
    public function setup() {
        $this->hooks_and_filters();

        // Keep a reference
        $this->main_settings = $this->settings;
        
        // Load the form fields and settings
        $this->init_form_fields();
        $this->init_settings();   
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
        $this->form_fields = array();
    }

    /**
    * admin_options function.
    *
    * Prints the admin settings form
    *
    * @access public
    * @return string
    */
    public function admin_options()
    {
        $main_settings_args = array(
            'page' => 'wc-settings',
            'tab' => 'checkout',
            'section' => 'wc_coolpay'
        );
        echo "<h3>CoolPay - {$this->id}, v" . WCCP_VERSION . "</h3>";
        echo "<p>" . sprintf(__('Allows you to receive payments via CoolPay %s.', 'woo-coolpay'), $this->id) . "</p>";
        echo "<p>" . sprintf(__('This module has it\'s main configuration inside the \'CoolPay\' tab.', 'woo-coolpay'), 's') . "</p>";
        echo "<p>" . sprintf(__('Click <a href="%s">here</a> to access the main configuration.', 'woo-coolpay'), add_query_arg( $main_settings_args, admin_url('admin.php'))) . "</p>";
        echo "<table class=\"form-table\">";
                    $this->generate_settings_html();
        echo "</table";
    }
  
    /**
    * s function.
    *
    * Returns a setting if set. Introduced to prevent undefined key when introducing new settings.
    * In an instance class, this method first check if a local setting is set in the current instance. If not, it will check for the same setting inside
    * the core library.
    *
    * @access public
    * @return string
    */
    public function s( $key, $default = NULL )
    {
        if( isset( $this->settings[$key] ) ) {
            return $this->settings[$key];
        }
        
        if( isset( $this->main_settings[$key] ) ) {
            return $this->main_settings[$key];
        }

        return ! is_null( $default ) ? $default : '';
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

        if($id == $this->id) {
            $icons_maxheight = $this->gateway_icon_size();
            $icon .= $this->gateway_icon_create(strtolower($this->id), $icons_maxheight);
        }

        return $icon;
    }
}
