<?php
/**
 * Plugin Name: Hesabe Payment Gateway
 * Plugin URI: https://hesabe.com/
 * Description: Integrate the Hesabe payment gateway with your website. This plugin is based on Hesabe direct and interct Method with secure and easy configuration.To install/activate this Plugin please make sure you have installed woocommerce version 2.0 or above.
 * Version: 3.0
 * Author: HesabeTeam
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */
add_action('plugins_loaded', 'init_custom_gateway');

function init_custom_gateway() {
    if (!class_exists('WC_Payment_Gateway')) {
        return;
    }
	
	define( 'WC_HESABE_VERSION', '2.0' );
    define( 'WC_HESABE_TEST_URL', 'https://sandbox.hesabe.com' );
    define( 'WC_HESABE_LIVE_URL', 'https://api.hesabe.com' );
    define( 'WC_HESABE_INDIRECT_METHOD', true ); // Displaying Hesabe payment method(indirect)

    require_once __DIR__ . '/class-wc-hesabe-crypt.php';
    require_once __DIR__ . '/paymemt-wc-class.php';
	
	function add_my_payment_gateway( $methods ) {
    $methods[] = 'HesabePayment';
		
    return $methods;
    }
    add_filter( 'woocommerce_payment_gateways', 'add_my_payment_gateway' );
	
	add_filter('woocommerce_available_payment_gateways', 'hesabe_setting_enable_manager');
	function hesabe_setting_enable_manager($available_gateways)
    {
        return $available_gateways;
    }
}
?>