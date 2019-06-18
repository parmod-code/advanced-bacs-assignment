<?php


class Ajax_Abacs_Receipt_Uploader {

	private static $instance;


	private function __construct() {
		add_action("wp_ajax_abacs_receipt_upload",array($this,"abacs_receipt_upload"));
		add_action("wp_ajax_nopriv_abacs_receipt_upload",array($this,"abacs_receipt_upload"));
	}

	// The singleton method
	public static function singleton()
	{
		if (!isset(self::$instance)) {
			self::$instance = new self;
		}
		return self::$instance;
	}

	/**
	 * upload the receipt file to the server...
	 */

	public function abacs_receipt_upload() {

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