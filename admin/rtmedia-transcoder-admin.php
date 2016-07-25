<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of RTMedia_Transcoder_Admin
 *
 * @author Mangesh
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RTMedia_Transcoder_Admin {

	public $transcoder_handler;
	public $api_key = false;
	public $stored_api_key = false;

	public function __construct() {

		$this->api_key			= get_site_option( 'rtmedia-transcoding-api-key' );
		$this->stored_api_key	= get_site_option( 'rtmedia-transcoding-api-key-stored' );

		$this->load_translation();

		if( !class_exists( 'rtProgress' ) ) {
			include_once( RTMEDIA_TRANSCODER_PATH . 'admin/rtmedia-transcoder-progressbar.php' );
		}

		include_once( RTMEDIA_TRANSCODER_PATH . 'admin/rtmedia-transcoder-handler.php' );

		// enqueue scripts
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts_styles' ) );

		$this->transcoder_handler = new RTMedia_Transcoder_Handler();

		if ( is_admin() ) {
			add_action( 'admin_menu', array( $this, 'menu' ) );
		}
	}

	public function menu() {
		add_options_page( 'rtMedia Transcoder', 'rtMedia Transcoder', 'manage_options', 'rtmedia-transcoder', array( $this, 'settings_page' ) );
	}

	function settings_page() {
		include_once( RTMEDIA_TRANSCODER_PATH . 'admin/partials/rtmedia-transcoder-admin-display.php' );
	}

	/**
	 * Loads language translation
	 */
	public function load_translation() {
		load_plugin_textdomain( 'rtmedia-transcoder', false, basename( RTMEDIA_TRANSCODER_PATH ) . '/languages/' );
	}

	/**
	 * Loads styles and scripts
	 */
	public function enqueue_scripts_styles() {

		wp_enqueue_style( 'rtmedia-transcoder-admin-css', RTMEDIA_TRANSCODER_URL . 'admin/css/rtmedia-transcoder-admin.css', array(), RTMEDIA_TRANSCODER_VERSION );
		wp_register_script( 'rtmedia-transcoder-main', RTMEDIA_TRANSCODER_URL . 'admin/js/rtmedia-transcoder-admin.js', array( 'jquery' ), RTMEDIA_TRANSCODER_VERSION, true );
		wp_localize_script( 'rtmedia-transcoder-main', 'rtmedia_transcoder_admin_ajax', $admin_ajax );
		wp_localize_script( 'rtmedia-transcoder-main', 'rtmedia_transcoder_admin_url', admin_url() );
		wp_localize_script( 'rtmedia-transcoder-main', 'rtmedia_transcoder_admin_url', admin_url() );

		wp_enqueue_script( 'rtmedia-transcoder-main' );
	}

	public function transcoding_subscription_form( $name = 'No Name', $price = '0', $force = false ) {
		if ( $this->api_key ) {
			$this->transcoder_handler->update_usage( $this->api_key );
		}
		$action      = '/wp-admin/?recurring-purchase=true&price-id=2';
		$return_page = esc_url( add_query_arg( array( 'page' => 'rtmedia-addons' ), ( is_multisite() ? network_admin_url( 'admin.php' ) : admin_url( 'admin.php' ) ) ) );

		$usage_details = get_site_option( 'rtmedia-transcoding-usage' );
		if ( isset( $usage_details[ $this->api_key ]->plan->name ) && ( strtolower( $usage_details[ $this->api_key ]->plan->name ) === strtolower( $name ) ) && $usage_details[ $this->api_key ]->sub_status && ! $force ) {
			$form = '<button data-plan="' . esc_attr( $name ) . '" data-price="' . esc_attr( $price ) . '" type="submit" class="button bpm-unsubscribe">' . esc_html__( 'Unsubscribe', 'rtmedia-transcoder' ) . '</button>';
			$form .= '<div id="bpm-unsubscribe-dialog" title="Unsubscribe">
						<p>' . esc_html__( 'Just to improve our service we would like to know the reason for you to leave us.', 'rtmedia-transcoder' ) . '</p>
						<p><textarea rows="3" cols="18" id="bpm-unsubscribe-note"></textarea></p>
						</div>';
		} else {
			$form = '<form method="post" action="' . $action . '" class="paypal-button" target="_top">
					<input type="image" src="http://www.paypal.com/en_US/i/btn/btn_subscribe_SM.gif" name="submit" alt="Make payments with PayPal - it\'s fast, free and secure!">
				</form>';
		}

		return $form;
	}
}
