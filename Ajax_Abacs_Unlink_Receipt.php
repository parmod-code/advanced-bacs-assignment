<?php


class Ajax_Abacs_Unlink_Receipt {

	private static $instance;

	private function __construct(){
		add_action( "wp_ajax_abacs_unlink_receipt_file", array($this,"abacs_unlink_receipt_file") );
		add_action( "wp_ajax_nopriv_abacs_unlink_receipt_file", array($this,"abacs_unlink_receipt_file") );
	}

	/**
	 *  singelton method
	 *
	 */

	public static function singleton()
	{
		if (!isset(self::$instance)) {
			self::$instance = new Ajax_Abacs_Unlink_Receipt();
		}
		return self::$instance;
	}


	/**
	 *  unlink receipt using url
	 */
	public function abacs_unlink_receipt_file() {

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