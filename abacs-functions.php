<?php

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