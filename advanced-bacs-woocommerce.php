<?php
/**
 * Plugin Name: Advanced Bacs Woocommerce
 * Plugin Uri: ''
	 * Description: Allow user or customer to upload the receipt of the payment if the order is placed through advanced bank tranfer payment gateway
 * Version:1.0
 * Text Domain:'advanced_bacs_woocommerce
 **/

defined( 'ABSPATH' ) || exit;
// Make sure WooCommerce is active
if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
	return;
}


function add_advanced_bacs_gateway( $methods ) {
	$methods[] = 'WC_Advanced_Bacs_Gateway';

	return $methods;
}

add_filter( 'woocommerce_payment_gateways', 'add_advanced_bacs_gateway' );

// added the class after plugin loaded
add_action( 'plugins_loaded', 'init_advanced_bacs_gateway_class' );


//to create the gateway class by extend from the existing WC_Payment_Gateway

function init_advanced_bacs_gateway_class() {

	require_once plugin_dir_path(__FILE__).'class-wc-advanced-bacs-gateway.php';

}

/**
 *  Unlink Receipt class to remove file  and Receipt uploader class file to upload
 */

require_once plugin_dir_path(__FILE__).'class-abacs-ajax-handler.php';


$abacs_ajax_handler = Abacs_Ajax_Handler::instance();


/**
 *  inlcuding the common functions
 */
require_once plugin_dir_path(__FILE__).'abacs-functions.php';




