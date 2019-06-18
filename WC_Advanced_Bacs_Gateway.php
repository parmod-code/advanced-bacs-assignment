<?php


final Class WC_Advanced_Bacs_Gateway extends WC_Payment_Gateway {

	public function __construct() {

		$this->id                 = 'advanced_bacs_gateway';
		$this->has_fields         = true;
		$this->method_title       = 'Adanced Bacs Gateway';
		$this->method_description = 'Gateway Plugin to upload the receipt of the payment at the time of order placed';

		//load the settings
		$this->init_form_fields();
		$this->init_settings();

		// Define user set variables.
		$this->title       = $this->get_option( 'title' );
		$this->description = $this->get_option( 'description' );

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

		// We need custom JavaScript to initialize the select dropdown
		add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );

	}

	/**
	 * Initialise Gateway Settings Form Fields
	 */
	function init_form_fields() {

		global $woocommerce;

		$countries_obj   = new WC_Countries();
		$countries   = $countries_obj->__get('countries');

		$this->form_fields = array(
			'enabled'     => array(
				'title'   => __( 'Enable/Disable', 'woocommerce' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable Cheque Payment', 'woocommerce' ),
				'default' => 'yes',
			),
			'title'       => array(
				'title'       => __( 'Title', 'woocommerce' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
				'default'     => __( 'Advanced Bacs', 'woocommerce' ),
				'desc_tip'    => true,
			),
			'description' => array(
				'title'       => __( 'Description', 'woocommerce' ),
				'type'        => 'textarea',
				'default'     => '',
				'description' => __( 'Payment method description that the customer will see on the checkout page.', 'woocommerce' ),
				'desc_tip'    => true,
			),
			'available_countries' => array(
				'title'       => __( 'Gateway to specific countries', 'woocommerce' ),
				'type'        => 'select',
				'default'     => '',
				'description' => __( 'Payment Gateway available to selected countries.', 'woocommerce' ),
				'desc_tip'    => true,
				'class'       => 'select2',
				'placeholder'    => __('Enter something'),
				'options'    => $countries
			)

		);
	} // End init_form_fields();

	/**
	 *   Payment fields to be shown to the user when this payment method call
	 */

	public function payment_fields() {

		$description = $this->get_description();
		if ( $description ) {
			echo wpautop( wptexturize( $description ) );
		}
		$this->advanced_bacs_form();
	}


	/**
	 *  Advanced Bank Transfer form receipt
	 */

	public function advanced_bacs_form() {
		?>

		<fieldset id="<?php echo esc_attr( $this->id ); ?>-cc-form" class='wc-abacs-form wc-payment-form'>
			<p class="form-row form-row-first abacs-html">
				<label for="<?php echo esc_attr( $this->id ); ?>-payment-receipt"><?php echo esc_html__( 'Upload Receipt', 'woocommerce' ); ?> &nbsp;<span class="required">*</span></label>
				<input id="<?php echo esc_attr( $this->id ); ?>-payment-receipt" class="input-text wc-abacs-form-payment-receipt" type="file" autocomplete="off" name="'<?php echo esc_attr( $this->id ); ?>'-payment-receipt" accept="image/*,.pdf" required/>
				<input type="hidden" name="abac_receipt_url" id="abac_receipt_url" value=""/>
			</p>
			<i class="fa"></i>
			<div class="abac_response"></div>
			<a href="javascript:void(0);" class="wc-abacs-remove-file" style="display:none;">x</a>

			<?php echo wp_nonce_field(); ?>

			<div class="clear"></div>

		</fieldset>
		<?php
	}

	/**
	 * validate receipt field when the payemnt method is used
	 */

	public function validate_fields() {
		if ( empty( $_POST['abac_receipt_url'] ) ) {
			wc_add_notice( 'Receipt is required', 'error' );

			return false;
		}

		return true;
	}

	/**
	 *  adding scripts
	 */

	public function payment_scripts(){

		// we need JavaScript to process only on checkout page to upload receipt file regarding the payment
		if ( ! is_checkout() && ! isset( $_GET['pay_for_order'] ) ) {
			return;
		}

		wp_enqueue_script( 'abacs-front-handler', plugins_url( '/assets/js/abacs-front-handler.js', __FILE__ ), array( 'jquery' ),'',true );

		// in most payment processors you have to use PUBLIC KEY to obtain a token
		wp_localize_script( 'abacs-front-handler', 'abacs_params', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'form_id' => $this->id,
		)   );
	}

	/**
	 * @param int $order_id
	 *
	 * @return array|void
	 */
	public function process_payment( $order_id ) {

		global $woocommerce;

		// we need it to get any order detailes
		$order = wc_get_order( $order_id );

		$order->update_status( 'on-hold' );
		// Payment complete
		$order->payment_complete();

		// we received the payment

		$order->reduce_order_stock();

		// Empty cart
		$woocommerce->cart->empty_cart();

		// Return thank you page redirect
		return array(
			'result'   => 'success',
			'redirect' => $this->get_return_url( $order )
		);

	}

}