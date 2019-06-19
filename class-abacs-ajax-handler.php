<?php

defined( 'ABSPATH' ) || exit;

class Abacs_Ajax_Handler {

	private static $instance;


	private function __construct() {

		// upload receipt file
		add_action("wp_ajax_abacs_receipt_upload",array($this,"abacs_receipt_upload"));
		add_action("wp_ajax_nopriv_abacs_receipt_upload",array($this,"abacs_receipt_upload"));

		//unlink receipt file
		add_action( "wp_ajax_abacs_unlink_receipt_file", array($this,"abacs_unlink_receipt_file") );
		add_action( "wp_ajax_nopriv_abacs_unlink_receipt_file", array($this,"abacs_unlink_receipt_file") );

	}

	// The singleton method
	public static function instance()
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

		/*
		 *  check if nonce is applicable
		 */
		if ( ! isset( $_POST['receipt_upload_security'] )
			|| ! wp_verify_nonce( $_POST['receipt_upload_security'], 'abacs_receipt_upload' )
		) {

			return false;

		}

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


	/**
	 *  unlink receipt using url
	 */
	public function abacs_unlink_receipt_file() {


		/*
		 *  check if nonce is applicable
		 */
		if ( ! isset( $_POST['receipt_remove_security'] )
		     || ! wp_verify_nonce( $_POST['receipt_remove_security'], 'abacs_unlink_receipt_file' )
		) {

			return false;

		}

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
}