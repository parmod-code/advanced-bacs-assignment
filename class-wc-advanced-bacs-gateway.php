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
		$this->title               = $this->get_option( 'title' );
		$this->description         = $this->get_option( 'description' );
		$this->available_countries = $this->get_option( 'available_countries' );

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

		// We need custom JavaScript to perform the operation of receipt files
		add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );

		// enqueue script on admin panel payment setting page
		add_action( 'admin_enqueue_scripts', array( $this, 'abacs_admin_acripts' ) );

	}

	/**
	 * Initialise Gateway Settings Form Fields
	 */
	function init_form_fields() {

		global $woocommerce;

		$countries_obj = new WC_Countries();
		$countries     = $countries_obj->__get( 'countries' );

		$this->form_fields = array(
			'enabled'             => array(
				'title'   => __( 'Enable/Disable', 'woocommerce' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable Cheque Payment', 'woocommerce' ),
				'default' => 'yes',
			),
			'title'               => array(
				'title'       => __( 'Title', 'woocommerce' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
				'default'     => __( 'Advanced Bacs', 'woocommerce' ),
				'desc_tip'    => true,
			),
			'description'         => array(
				'title'       => __( 'Description', 'woocommerce' ),
				'type'        => 'textarea',
				'default'     => '',
				'description' => __( 'Payment method description that the customer will see on the checkout page.', 'woocommerce' ),
				'desc_tip'    => true,
			),
			'available_countries' => array(
				'title'       => __( 'Gateway to specific countries', 'woocommerce' ),
				'type'        => 'multiselect',
				'default'     => '',
				'description' => __( 'Payment Gateway available to selected countries.', 'woocommerce' ),
				'desc_tip'    => true,
				'class'       => 'select2',
				'placeholder' => __( 'Enter something' ),
				'options'     => $countries
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
				<?php wp_nonce_field( 'abacs_receipt_upload', 'abacs_upload_receipt' ); ?>
            </p>
            <i class="fa"></i>
            <div class="abac_response"></div>
            <a href="javascript:void(0);" class="wc-abacs-remove-file" style="display:none;">x</a>
			<?php wp_nonce_field( 'abacs_unlink_receipt_file', 'abacs_receipt_unlink' ); ?>
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
			wc_add_notice( __( 'Receipt is required', 'advanced_bacs_woocommerce' ), 'error' );

			return false;
		}

		return true;
	}

	/**
	 *  adding scripts
	 */

	public function payment_scripts() {

		// we need JavaScript to process only on checkout page to upload receipt file regarding the payment
		if ( ! is_checkout() && ! isset( $_GET['pay_for_order'] ) ) {
			return;
		}

		wp_enqueue_script( 'abacs-front-handler', plugins_url( '/public/assets/js/abacs-front-handler.js', __FILE__ ), array( 'jquery' ), rand(), true );

		// in most payment processors you have to use PUBLIC KEY to obtain a token
		wp_localize_script( 'abacs-front-handler', 'abacs_params', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'form_id'  => $this->id,
		) );
	}

	/**
	 *  admin scripts to be loaded on the payment settings page
	 */

	function abacs_admin_acripts() {

		//only enqueue on the advanced bank transfer settings page
		if ( isset( $_GET['section'] ) && $_GET['section'] == "advanced_bacs_gateway" ) {
			wp_enqueue_style( 'abacs-select2-css', plugins_url( '/admin/assets/css/select2/select2.min.css', __FILE__ ) );
			wp_enqueue_script( 'abacs-select2-js', plugins_url( '/admin/assets/js/select2/select2.min.js', __FILE__ ), array( 'jquery' ), '', true );
			wp_enqueue_script( 'abacs-admin-js', plugins_url( '/admin/assets/js/admin.js', __FILE__ ), array( 'jquery' ), '', true );
		}
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

		wc_reduce_stock_levels( $order_id );

		// Empty cart
		$woocommerce->cart->empty_cart();

		// Return thank you page redirect
		return array(
			'result'   => 'success',
			'redirect' => $this->get_return_url( $order )
		);

	}


	/**
	 * Check if the gateway is available for use.
	 *
	 * @return bool
	 */
	public function is_available() {
		global $woocommerce;
		$is_available        = ( 'yes' === $this->enabled );
		$available_countries = $this->available_countries;
		if ( is_null( $woocommerce->customer ) ) {
			return;
		}
		$shipping_country = $woocommerce->customer->get_shipping_country();

		if ( ! empty( $available_countries ) ) {
			if ( in_array( $shipping_country, $available_countries ) ) {
				$is_available = true;
			} else {
				$is_available = false;
			}
		} else {
			$is_available = true;
		}


		if ( WC()->cart && 0 < $this->get_order_total() && 0 < $this->max_amount && $this->max_amount < $this->get_order_total() ) {
			$is_available = false;
		}

		return $is_available;
	}

}