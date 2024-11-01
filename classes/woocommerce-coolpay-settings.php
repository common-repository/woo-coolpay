<?php
/**
 * WC_CoolPay_Settings class
 *
 * @class 		WC_CoolPay_Settings
 * @version		1.0.0
 * @package		Woocommerce_CoolPay/Classes
 * @category	Class
 * @author 		PerfectSolution
 */
class WC_CoolPay_Settings {

	/**
	* get_fields function.
	*
	* Returns an array of available admin settings fields
	*
	* @access public static
	* @return array
	*/
	public static function get_fields()
	{
		$fields = 
			array(
				'enabled' => array(
                    'title' => __( 'Enable', 'woo-coolpay' ),
                    'type' => 'checkbox', 
                    'label' => __( 'Enable CoolPay Payment', 'woo-coolpay' ), 
                    'default' => 'yes'
                ), 

				'_Account_setup' => array(
					'type' => 'title',
					'title' => __( 'API - Integration', 'woo-coolpay' ),
				),

					'coolpay_privatekey' => array(
						'title' => __('Private key', 'woo-coolpay') . self::get_required_symbol(),
						'type' => 'text',
						'description' => __( 'Your agreement private key. Found in the "Integration" tab inside the CoolPay manager.', 'woo-coolpay' ),
                        'desc_tip' => true,
					),
					'coolpay_apikey' => array(
						'title' => __('Api User key', 'woo-coolpay') . self::get_required_symbol(),
						'type' => 'text',
						'description' => __( 'Your API User\'s key. Create a separate API user in the "Users" tab inside the CoolPay manager.' , 'woo-coolpay' ),
                        'desc_tip' => true,
					),
				'_Autocapture' => array(
					'type' => 'title',
					'title' => __('Autocapture settings', 'woo-coolpay' )
				),
					'coolpay_autocapture' => array(
                        'title' => __( 'Physical products (default)', 'woo-coolpay' ), 
                        'type' => 'checkbox', 
                        'label' => __( 'Enable', 'woo-coolpay' ),
                        'description' => __( 'Automatically capture payments on physical products.', 'woo-coolpay' ), 
                        'default' => 'no',
                        'desc_tip' => false,
					),
					'coolpay_autocapture_virtual' => array(
                        'title' => __( 'Virtual products', 'woo-coolpay' ), 
                        'type' => 'checkbox', 
                        'label' => __( 'Enable', 'woo-coolpay' ),
                        'description' => __( 'Automatically capture payments on virtual products. If the order contains both physical and virtual products, this setting will be overwritten by the default setting above.', 'woo-coolpay' ), 
                        'default' => 'no',
                        'desc_tip' => false,
					),
                '_Currency_settings' => array(
                    'type' => 'title',
                    'title' => __('Currency settings', 'woo-coolpay' )
                ),
                    'coolpay_currency' => array(
                        'title' => __('Fixed Currency', 'woo-coolpay'),
                        'description' => __('Choose a fixed currency. Please make sure to use the same currency as in your WooCommerce currency settings.', 'woo-coolpay' ),
                        'desc_tip' => true,
                        'type' => 'select',
                        'options' => array(
                            'DKK' => 'DKK', 
                            'EUR' => 'EUR',
                            'GBP' => 'GBP',
                            'NOK' => 'NOK',
                            'SEK' => 'SEK',
                            'USD' => 'USD'
                        )
                    ),
                    'coolpay_currency_auto' => array(
                        'title' => __( 'Auto Currency', 'woo-coolpay' ), 
                        'type' => 'checkbox', 
                        'label' => __( 'Enable', 'woo-coolpay' ),
                        'description' => __( 'Automatically checks out with the order currency. This setting overwrites the "Fixed Currency" setting.', 'woo-coolpay' ), 
                        'default' => 'no',
                        'desc_tip' => true,
                    ),
				'_Extra_gateway_settings' => array(
					'type' => 'title',
					'title' => __('Extra gateway settings', 'woo-coolpay' )
				),
					'coolpay_language' => array(
                        'title' => __('Language', 'woo-coolpay'),
                        'description' => __('Payment Window Language', 'woo-coolpay'),
                        'desc_tip' => true,
                        'type' => 'select',
                        'options' => array(
                            'da' => 'Danish',
                            'de' =>'German', 
                            'en' =>'English', 
                            'fr' =>'French', 
                            'it' =>'Italian', 
                            'no' =>'Norwegian', 
                            'nl' =>'Dutch', 
                            'pl' =>'Polish', 
                            'se' =>'Swedish'
                        )
					),
					'coolpay_currency' => array(
                        'title' => __('Currency', 'woo-coolpay'),
                        'description' => __('Choose your currency. Please make sure to use the same currency as in your WooCommerce currency settings.', 'woo-coolpay' ),
                        'desc_tip' => true,
                        'type' => 'select',
                        'options' => array(
                            'DKK' => 'DKK', 
                            'EUR' => 'EUR',
                            'GBP' => 'GBP',
                            'NOK' => 'NOK',
                            'SEK' => 'SEK',
                            'USD' => 'USD'
                        )
					),
					'coolpay_cardtypelock' => array(
                        'title' => __( 'Payment methods', 'woo-coolpay' ), 
                        'type' => 'text', 
                        'description' => __( 'Default: creditcard. Type in the cards you wish to accept (comma separated).<br>For example you want to accept all credit cards but NOT JCB and Visa cards issued in USA:<code>creditcard, !jcb, !visa-us</code>.<br> See the valid payment types here: <b><a target="_blank" href="https://coolpay.com/docs/payment-methods/">https://coolpay.com/docs/payment-methods/</a></b>', 'woo-coolpay' ), 
                        'default' => 'creditcard',
					),
					'coolpay_branding_id' => array(
                        'title' => __( 'Branding ID', 'woo-coolpay' ), 
                        'type' => 'text', 
                        'description' => __( 'Leave empty if you have no custom branding options', 'woo-coolpay' ), 
                        'default' => '',
                        'desc_tip' => true,
					),	

					'coolpay_autofee' => array(
                        'title' => __( 'Enable autofee', 'woo-coolpay' ), 
                        'type' => 'checkbox', 
                        'label' => __( 'Enable', 'woo-coolpay' ),
                        'description' => __( 'If enabled, the fee charged by the acquirer will be calculated and added to the transaction amount.', 'woo-coolpay' ), 
                        'default' => 'no',
                        'desc_tip' => true,
					),        
					'coolpay_captureoncomplete' => array(
                        'title' => __( 'Capture on complete', 'woo-coolpay' ), 
                        'type' => 'checkbox', 
                        'label' => __( 'Enable', 'woo-coolpay' ),
                        'description' => __( 'When enabled coolpay payments will automatically be captured when order state is set to "Complete".', 'woo-coolpay'), 
                        'default' => 'no',
                        'desc_tip' => true,
					),
                    'coolpay_text_on_statement' => array(
                        'title' => __( 'Text on statement', 'woo-coolpay' ), 
                        'type' => 'text', 
                        'description' => __( 'Text that will be placed on cardholder’s bank statement (currently only supported by Clearhaus).', 'woo-coolpay' ), 
                        'default' => '',
                        'desc_tip' => true,
                        'custom_attributes' => array(
                            'maxlength' => 22,
                        ),
                    ),  

        
				'_Shop_setup' => array(
					'type' => 'title',
					'title' => __( 'Shop setup', 'woo-coolpay' ),
				),
					'title' => array(
                        'title' => __( 'Title', 'woo-coolpay' ), 
                        'type' => 'text', 
                        'description' => __( 'This controls the title which the user sees during checkout.', 'woo-coolpay' ), 
                        'default' => __( 'CoolPay', 'woo-coolpay' ),
                        'desc_tip' => true,
                    ),
					'description' => array(
                        'title' => __( 'Customer Message', 'woo-coolpay' ), 
                        'type' => 'textarea', 
                        'description' => __( 'This controls the description which the user sees during checkout.', 'woo-coolpay' ), 
                        'default' => __( 'Pay via CoolPay. Allows you to pay with your credit card via CoolPay.', 'woo-coolpay' ),
                        'desc_tip' => true,
                    ),
					'checkout_button_text' => array(
                        'title' => __( 'Order button text', 'woo-coolpay' ), 
                        'type' => 'text', 
                        'description' => __( 'Text shown on the submit button when choosing payment method.', 'woo-coolpay' ), 
                        'default' => __( 'Go to payment', 'woo-coolpay' ),
                        'desc_tip' => true,
                    ),
					'instructions' => array(
                        'title'       => __( 'Email instructions', 'woo-coolpay' ),
                        'type'        => 'textarea',
                        'description' => __( 'Instructions that will be added to emails.', 'woo-coolpay' ),
                        'default'     => '',
                        'desc_tip' => true,
					 ),
					'coolpay_icons' => array(
                        'title' => __( 'Credit card icons', 'woo-coolpay' ),
                        'type' => 'multiselect',
                        'description' => __( 'Choose the card icons you wish to show next to the CoolPay payment option in your shop.', 'woo-coolpay' ),
                        'desc_tip' => true,
                        'class'             => 'wc-enhanced-select',
                        'css'               => 'width: 450px;',
                        'custom_attributes' => array(
                            'data-placeholder' => __( 'Select icons', 'woo-coolpay' )
                        ),
                        'default' => '',
                        'options' => array(
                        	'apple-pay' => 'Apple Pay',
                            'dankort' => 'Dankort',
                            'edankort' => 'eDankort',
                            'visa'	=> 'Visa',
                            'visaelectron' => 'Visa Electron',
                            'visa-verified' => 'Verified by Visa',
                            'mastercard' => 'Mastercard',
                            'mastercard-securecode' => 'Mastercard SecureCode',
                            'maestro' => 'Maestro',
                            'jcb' => 'JCB',
                            'americanexpress' => 'American Express',
                            'diners' => 'Diner\'s Club',
                            'discovercard' => 'Discover Card',
                            'viabill' => 'ViaBill',
                            'paypal' => 'Paypal',
                            'danskebank' => 'Danske Bank',
                            'nordea' => 'Nordea',
                            'mobilepay' => 'MobilePay',
                            'forbrugsforeningen' => 'Forbrugsforeningen',
                            'ideal' => 'iDEAL',
                            'unionpay' => 'UnionPay',
                            'sofort' => 'Sofort',
                            'cirrus' => 'Cirrus',
                            'klarna' => 'Klarna',
                            'bankaxess' => 'BankAxess'
                        ),
					),
					'coolpay_icons_maxheight' => array(
						'title' => __( 'Credit card icons maximum height', 'woo-coolpay' ),
						'type'  => 'number',
						'description' => __( 'Set the maximum pixel height of the credit card icons shown on the frontend.', 'woo-coolpay' ),
						'default' => 20,
                        'desc_tip' => true,
					),      
                'Google Analytics' => array(
					'type' => 'title',
					'title' => __( 'Google Analytics', 'woo-coolpay' ),
				),
					'coolpay_google_analytics_tracking_id' => array(
                        'title' => __( 'Tracking ID', 'woo-coolpay' ), 
                        'type' => 'text', 
                        'description' => __( 'Your Google Analytics tracking ID. Digits only.', 'woo-coolpay' ), 
                        'default' => '',
                        'desc_tip' => true,
                    ),
				'ShopAdminSetup' => array(
					'type' => 'title',
					'title' => __( 'Shop Admin Setup', 'woo-coolpay' ),
				),

					'coolpay_orders_transaction_info' => array(
						'title' => __( 'Fetch Transaction Info', 'woo-coolpay' ),
						'type' => 'checkbox',
						'label' => __( 'Enable', 'woo-coolpay' ),
						'description' => __( 'Show transaction information in the order overview.', 'woo-coolpay' ),
						'default' => 'yes',
						'desc_tip' => false,
					),
            
                'CustomVariables' => array(
					'type' => 'title',
					'title' => __( 'Custom Variables', 'woo-coolpay' ),
				),
                    'coolpay_custom_variables' => array(
                        'title'             => __( 'Select Information', 'woo-coolpay' ),
                        'type'              => 'multiselect',
                        'class'             => 'wc-enhanced-select',
                        'css'               => 'width: 450px;',
                        'default'           => '',
                        'description'       => __( 'Selected options will store the specific data on your transaction inside your CoolPay Manager.', 'woo-coolpay' ),
                        'options'           => self::custom_variable_options(),
                        'desc_tip'          => true,
                        'custom_attributes' => array(
                            'data-placeholder' => __( 'Select order data', 'woo-coolpay' )
                        )
                    ),
				);

				if( WC_CoolPay_Subscription::plugin_is_active() )
				{
					$fields['woocommerce-subscriptions'] = array(
						'type' => 'title',
						'title' => 'Subscriptions'
					);

					$fields['subscription_autocomplete_renewal_orders'] = array(
						'title' => __( 'Complete renewal orders', 'woo-coolpay' ),
						'type' => 'checkbox',
						'label' => __( 'Enable', 'woo-coolpay' ),
						'description' => __( 'Automatically mark a renewal order as complete on successful recurring payments.', 'woo-coolpay' ),
						'default' => 'no',
						'desc_tip' => true,
					);
				}

		return $fields;
	}
    
    
	/**
	* custom_variable_options function.
	*
	* Provides a list of custom variable options used in the settings
	*
	* @access private
	* @return array
	*/    
    private static function custom_variable_options()
    {
        $options = array(
            'billing_all_data'      => __( 'Billing: Complete Customer Details', 'woo-coolpay' ), 
            'browser_useragent'     => __( 'Browser: User Agent', 'woo-coolpay' ),
            'customer_email'        => __( 'Customer: Email Address', 'woo-coolpay' ),
            'customer_phone'        => __( 'Customer: Phone Number', 'woo-coolpay' ),
            'shipping_all_data'     => __( 'Shipping: Complete Customer Details', 'woo-coolpay' ),
            'shipping_method'       => __( 'Shipping: Shipping Method', 'woo-coolpay' ),
        );
        
        asort($options);
        
        return $options;
    }

    /**
     * Clears the log file.
     *
     * @return void
     */
    public static function clear_logs_section() {
        printf( '<h3 class="wc-settings-sub-title">%s</h3>', __( 'Debug', 'woo-coolpay' ) );
        printf( '<a id="wccp_wiki" class="button button-primary" href="%s" target="_blank">%s</a>', self::get_wiki_link(), __( 'Got problems? Check out the Wiki.', 'woo-coolpay' ) );
        printf( '<a id="wccp_logs" class="button" href="%s">%s</a>', WC_CP()->log->get_admin_link(), __( 'View debug logs', 'woo-coolpay' ) );
        printf( '<button id="wccp_logs_clear" class="button">%s</button>', __( 'Empty debug logs', 'woo-coolpay' ) );
        printf( '<br/>');
        printf( '<h3 class="wc-settings-sub-title">%s</h3>', __( 'Enable', 'woo-coolpay' ) );
    }

    /**
     * Returns the link to the gateway settings page.
     *
     * @return mixed
     */
    public static function get_settings_page_url() {
        return admin_url( 'admin.php?page=wc-settings&tab=checkout&section=wc_coolpay' );
    }

    /**
     * Shows an admin notice if the setup is not complete.
     *
     * @return void
     */
    public static function show_admin_setup_notices() {
        $error_fields = array();

        $mandatory_fields = array(
            'coolpay_privatekey' => __('Private key', 'woo-coolpay'),
            'coolpay_apikey' => __('Api User key', 'woo-coolpay')
        );

        foreach($mandatory_fields as $mandatory_field_setting => $mandatory_field_label) {
            if (self::has_empty_mandatory_post_fields($mandatory_field_setting)) {
                $error_fields[] = $mandatory_field_label;
            }
        }

        if (!empty($error_fields)) {
            $message = sprintf('<h2>%s</h2>', __( "WooCommerce CoolPay", 'woo-coolpay' ) );
            $message .= sprintf('<p>%s</p>', sprintf(__('You have missing or incorrect settings. Go to the <a href="%s">settings page</a>.', 'woo-coolpay'), self::get_settings_page_url()) );
            $message .= '<ul>';
            foreach($error_fields as $error_field) {
                $message .= "<li>" . sprintf(__('<strong>%s</strong> is mandatory.', 'woo-coolpay'), $error_field) . "</li>";
            }
            $message .= '</ul>';

            printf('<div class="%s">%s</div>', 'notice notice-error', $message);
        }

    }

    /**
     * @return string
     */
    public static function get_wiki_link() {
        return 'https://coolpay.com/docs';
    }

    /**
     * Logic wrapper to check if some of the mandatory fields are empty on post request.
     *
     * @return bool
     */
    private static function has_empty_mandatory_post_fields($settings_field) {
        $post_key = 'woocommerce_coolpay_' . $settings_field;
        $setting_key = WC_CP()->s($settings_field);
        return empty($_POST[$post_key]) && empty($setting_key);

    }

    /**
     * @return string
     */
    private static function get_required_symbol() {
        return '<span style="color: red;">*</span>';
    }
}


?>