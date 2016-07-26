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

		if ( class_exists( 'RTMedia' ) ) {
			add_filter( 'attachment_fields_to_edit', array( $this, 'edit_video_thumbnail' ), 11, 2 );
			add_filter( 'attachment_fields_to_save', array( $this, 'save_video_thumbnail' ), 11, 1 );
		}

		$this->transcoder_handler = new RTMedia_Transcoder_Handler();

		if ( is_admin() ) {
			add_action( 'admin_menu', array( $this, 'menu' ) );
		}
	}

	public function menu() {
		add_menu_page( 'rtMedia Transcoder', 'rtMedia Transcoder', 'manage_options', 'rtmedia-transcoder', array( $this, 'settings_page' ), '', '40.2222' );
		add_action( 'admin_init', array( $this, 'register_rtmedia_transcoder_settings' ) );
	}

	public function register_rtmedia_transcoder_settings() {
		//register our settings
		register_setting( 'rtmedia-transcoder-settings-group', 'number_of_thumbs' );
	}

	public function settings_page() {
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

	function edit_video_thumbnail_wp( $form_fields, $post ) {
		//die($post->post_mime_type);
		if ( isset( $post->post_mime_type ) ) {
			$media_type = explode( '/', $post->post_mime_type );
			if ( is_array( $media_type ) && 'video' === $media_type[0] ) {
				$media_id         = $post->ID;
				$thumbnail_array  = get_post_meta( $media_id, '_rt_media_thumbnails', true );

				if ( empty( $thumbnail_array ) ) {
					$thumbnail_array  = get_post_meta( $media_id, 'rtmedia_media_thumbnails', true );
				}

				$featured_img_src = "";
				if (has_post_thumbnail( $post->ID ) ){
					$image = wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ), 'single-post-thumbnail' );
				  	$featured_img_src = $image[0];
				}

				$video_thumb_html = '';
				if ( is_array( $thumbnail_array ) ) {
					$video_thumb_html .= '<ul> ';

					foreach ( $thumbnail_array as $key => $thumbnail_src ) {
						$checked = false;
						if ( $featured_img_src == $thumbnail_src ){
							$checked = true;
						}
						$count   = $key + 1;
						$video_thumb_html .= '<li style="width: 150px;display: inline-block;">
								<label for="rtmedia-upload-select-thumbnail-' . esc_attr( $count ) . '">
								<input type="radio" ' . esc_attr( $checked ) . ' id="rtmedia-upload-select-thumbnail-' . esc_attr( $count ) . '" value="' . esc_url( $thumbnail_src ) . '" name="rtmedia-thumbnail" />
								<img src=" ' . esc_url( $thumbnail_src ) . '" style="max-height: 120px;max-width: 120px; vertical-align: middle;" />
								</label></li> ';
					}

					$video_thumb_html .= '  </ul>';
					$form_fields['rtmedia_video_thumbnail'] = array(
						'label' => 'Video Thumbnails',
						'input' => 'html',
						'html'  => $video_thumb_html,
					);
				}
			}
		}
		return $form_fields;
	}

	function edit_video_thumbnail( $form_fields, $post ) {
		if ( isset( $post->post_mime_type ) ) {
			$media_type = explode( '/', $post->post_mime_type );
			if ( is_array( $media_type ) && 'video' === $media_type[0] ) {
				$media_id         = $post->ID;
				$thumbnail_array  = get_post_meta( $media_id, '_rt_media_thumbnails', true );

				if ( empty( $thumbnail_array ) ) {
					$thumbnail_array  = get_post_meta( $media_id, 'rtmedia_media_thumbnails', true );
				}

				$rtmedia_model    = new RTMediaModel();
				$rtmedia_media    = $rtmedia_model->get( array( 'media_id' => $media_id ) );
				$video_thumb_html = '';
				if ( is_array( $thumbnail_array ) ) {
					$video_thumb_html .= '<ul> ';

					foreach ( $thumbnail_array as $key => $thumbnail_src ) {
						$checked = checked( $thumbnail_src, $rtmedia_media[0]->cover_art, false );
						$count   = $key + 1;
						$video_thumb_html .= '<li style="width: 150px;display: inline-block;">
								<label for="rtmedia-upload-select-thumbnail-' . esc_attr( $count ) . '">
								<input type="radio" ' . esc_attr( $checked ) . ' id="rtmedia-upload-select-thumbnail-' . esc_attr( $count ) . '" value="' . esc_url( $thumbnail_src ) . '" name="rtmedia-thumbnail" />
								<img src=" ' . esc_url( $thumbnail_src ) . '" style="max-height: 120px;max-width: 120px; vertical-align: middle;" />
								</label></li> ';
					}

					$video_thumb_html .= '  </ul>';
					$form_fields['rtmedia_video_thumbnail'] = array(
						'label' => 'Video Thumbnails',
						'input' => 'html',
						'html'  => $video_thumb_html,
					);
				}
			}
		}

		return $form_fields;
	}

	function save_video_thumbnail( $post ) {
		$rtmedia_thumbnail = filter_input( INPUT_POST, 'rtmedia-thumbnail', FILTER_SANITIZE_STRING );
		$id = filter_input( INPUT_POST, 'ID', FILTER_SANITIZE_NUMBER_INT );
		if ( isset( $rtmedia_thumbnail ) ) {
			$rtmedia_model = new RTMediaModel();
			$model         = new RTMediaModel();
			$media         = $model->get( array( 'media_id' => $id ) );
			$media_id      = $media[0]->id;
			$rtmedia_model->update( array( 'cover_art' => $rtmedia_thumbnail ), array( 'media_id' => $id ) );
			update_activity_after_thumb_set( $media_id );
		}

		return $post;
	}
}
