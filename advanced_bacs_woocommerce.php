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
		}

		/**
		 * Initialise Gateway Settings Form Fields
		 */
		function init_form_fields() {
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
            <script type="text/javascript">
                $ = jQuery;


                $(document).ready(function () {
                    // on change payment method
                    var form_id = '<?php echo $this->id; ?>';
                    var payment_method = $("input[name=payment_method]:checked").val();
                    var abacs_true = $(".wc-abacs-form-payment-receipt").is(':visible');
                    var ajax_url = '<?php echo admin_url( 'admin-ajax.php' ); ?>';

                    $("#" + form_id + "-payment-receipt").on('change', function () {
                        var pointer = $(this)
                        $(".receipt-error").remove();
                        var section = pointer.closest('.wc-abacs-form');

                        var file_data = pointer.prop('files')[0];

                        receipt_upload(file_data, section);
                    });


                    function receipt_upload(file_data, section) {

                        if (file_data) {
                            $('.abac_response').text('');
                            $(".wc-abacs-remove-file").hide();
                            section.find('.fa').addClass('fa-spinner');
                            var mine_type = file_data.type;
                            var mine_type_data = mine_type.split('/');

                            if (mine_type_data[0] == 'image' || mine_type == "application/pdf") {

                                // calling the ajax to upload file to the upload directory
                                var form_data = new FormData();

                                form_data.append('receipt_file', file_data);
                                $.ajax({
                                    url: ajax_url + '?action=abacs_receipt_upload',
                                    type: 'post',
                                    data: form_data,
                                    enctype: 'multipart/form-data',
                                    processData: false,
                                    contentType: false,
                                    cache: false,
                                    success: function (response) {
                                        section.find('.fa').removeClass('fa-spinner');
                                        $(".abacs-html").hide();
                                        $("#abac_receipt_url").val(response);
                                        $(".abac_response").append(response);
                                        $(".wc-abacs-remove-file").show();
                                    },
                                });

                            } else {
                                $("#" + form_id + '-cc-form').append('<div class="text-danger receipt-error">Please upload image or pdf file...</div>');
                                section.find('.fa').removeClass('fa-spinner');
                                return false;
                            }
                        }
                    }

                    $(".wc-abacs-remove-file").on('click', function (e) {
                        e.preventDefault();
                        var receipt_url = $('.abac_response').text();
                        var pointer = $(this);
                        var data = {
                            'action': 'abacs_unlink_receipt_file',
                            'receipt_url': receipt_url,
                        };

                        $.post(ajax_url, data, function (response) {

                            $(".abac_response").text('');
                            pointer.hide();
                            $(".abacs-html").show();
                            $("#abac_receipt_url").val('');
                            $("#" + form_id + "-payment-receipt").val('');
                        });

                    });
                });

            </script>
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

	/**
	 *  upload receipt file using ajax
	 */

	add_action( "wp_ajax_abacs_receipt_upload", "abacs_receipt_upload" );
	add_action( "wp_ajax_nopriv_abacs_receipt_upload", "abacs_receipt_upload" );

	function abacs_receipt_upload() {

		$receipt_file = ! empty( $_FILES['receipt_file']['name'] ) ? $_FILES['receipt_file'] : '';
		$ext          = pathinfo( $receipt_file['name'], PATHINFO_EXTENSION );
		if ( empty( $receipt_file ) ) {
			return false;
		}

		$file_type = $receipt_file['type'];

		$file_type_data = explode( '/', $file_type );

		$allowed_files = array( 'image', 'application/pdf' );

		$wp_upload_directory = wp_upload_dir();
		$base_dir            = $wp_upload_directory['basedir'];
		$base_url            = $wp_upload_directory['baseurl'];
		$directory           = $base_dir . "/adv_bacs_receipt";
		if ( ! file_exists( $directory ) ) {
			mkdir( $directory, 0777 );
		}

		$name            = basename( $receipt_file['tmp_name'] );
		$upload_file_url = '';
		if ( move_uploaded_file( $receipt_file['tmp_name'], $directory . '/' . $name . '.' . $ext ) ) {
			$upload_file_url = $base_url . '/adv_bacs_receipt/' . $name . '.' . $ext;
		} else {
			echo "unable to upload file to the directory";
		}

		echo $upload_file_url;

		die();
	}
}


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

/**
 *  unlik receipt file
 */
add_action( "wp_ajax_abacs_unlink_receipt_file", "abacs_unlink_receipt_file" );
add_action( "wp_ajax_nopriv_abacs_unlink_receipt_file", "abacs_unlink_receipt_file" );
function abacs_unlink_receipt_file() {

	$wp_upload_directory = wp_upload_dir();

	$base_dir       = $wp_upload_directory['basedir'];
	$base_url       = $wp_upload_directory['baseurl'];
	$directory      = $base_dir . "/adv_bacs_receipt";
	$receipt_url    = $_POST['receipt_url'];
	$directory_name = 'adv_bacs_receipt';

	$file_name   = basename( $receipt_url );
	$unlink_path = $base_dir . '/' . $directory_name . '/' . $file_name;
	unlink( $unlink_path );

	die();
}

