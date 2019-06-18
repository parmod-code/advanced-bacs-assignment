<?php
/**
 * Plugin Name: Advanced Bacs Woocommerce
 * Plugin Uri: ''
 * Description: Allow user or customer to upload the receipt of the payment if the order is placed through bacs payment gateway
 * Version:1.0
 * Text Domain:'advanced_bacs_woocommerce
 **/


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

	require_once plugin_dir_path(__FILE__).'WC_Advanced_Bacs_Gateway.php';

}

/**
 *  Unlink Receipt class to remove file  and Receipt uploader class file to upload
 */

require_once plugin_dir_path(__FILE__).'Ajax_Abacs_Receipt_Uploader.php';
require_once plugin_dir_path(__FILE__).'Ajax_Abacs_Unlink_Receipt.php';


$abacs_unlink_receipt = Ajax_Abacs_Unlink_Receipt::singleton();
$abacs_receipt_upload = Ajax_Abacs_Receipt_Uploader::singleton();


/**
 *  on advanced bank transfer add the receipt url to order meta
 */
add_action( 'woocommerce_checkout_update_order_meta', 'abacs_checkout_field_update_order_meta' );
function abacs_checkout_field_update_order_meta( $order_id ) {
	global $woocommerce;
	$order = wc_get_order( $order_id );
	if ( $_POST['payment_method'] == "advanced_bacs_gateway" ) {
		update_post_meta( $order_id, 'receipt_url', esc_attr( $_POST['abac_receipt_url'] ) );
	}
}

/**
 *  show the order
 */

add_action( 'woocommerce_thankyou', 'abacs_view_order_and_thankyou_page', 20 );
add_action( 'woocommerce_view_order', 'abacs_view_order_and_thankyou_page', 20 );

function abacs_view_order_and_thankyou_page( $order_id ) {
	global $woocommerce;
	$order          = wc_get_order( $order_id );
	$payment_method = $order->get_payment_method();
	if ( $payment_method == "advanced_bacs_gateway" ) {
		$receipt_url = get_post_meta( $order_id, 'receipt_url', true );
		if ( ! empty( $receipt_url ) ) {
			?>
            <table class="woocommerce-table shop_table abacs_receipt_info">
                <tbody>
                <tr>
                    <th>Receipt Url:</th>
                    <td><?php echo get_post_meta( $order_id, 'receipt_url', true ); ?></td>
                </tr>
                </tbody>
            </table>
		<?php }
	}
}




