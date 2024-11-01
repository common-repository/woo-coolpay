<?php
/**
 * Update WC_CoolPay to 4.3
 *
 * @author 		PerfectSolution
 * @version     2.0.9
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

$settings = get_option( 'woocommerce_coolpay_settings' );

if ( ! isset( $settings['coolpay_autocapture_virtual'] ) && isset( $settings['coolpay_autocapture'] ) ) {
    $settings['coolpay_autocapture_virtual'] = $settings['coolpay_autocapture'];
}

update_option( 'woocommerce_coolpay_settings', $settings );