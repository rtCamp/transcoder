<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @since      1.0
 *
 * @package    rtmedia-trascoder
 * @subpackage rtmedia-trascoder/admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The admin-specific functionality of the plugin.
 *
 * @package    rtmedia-trascoder
 * @subpackage rtmedia-trascoder/admin
 */
class RTMedia_Transcoder_Admin {

	/**
	 * The object of RTMedia_Transcoder_Handler class.
	 *
	 * @since    1.0
	 * @access   private
	 * @var      object    $transcoder_handler    The object of RTMedia_Transcoder_Handler class.
	 */
	private $transcoder_handler;

	/**
	 * The api key of transcoding service subscription.
	 *
	 * @since    1.0
	 * @access   private
	 * @var      string    $api_key    The api key of transcoding service subscription.
	 */
	private $api_key = false;

	/**
	 * The api key of transcoding service subscription.
	 *
	 * @since    1.0
	 * @access   private
	 * @var      string    $stored_api_key    The api key of transcoding service subscription.
	 */
	private $stored_api_key = false;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0
	 */
	public function __construct() {

		$this->api_key			= get_site_option( 'rtmedia-transcoding-api-key' );
		$this->stored_api_key	= get_site_option( 'rtmedia-transcoding-api-key-stored' );

		$this->load_translation();

		if ( ! class_exists( 'rtProgress' ) ) {
			include_once( RTMEDIA_TRANSCODER_PATH . 'admin/rtmedia-transcoder-progressbar.php' );
		}

		include_once( RTMEDIA_TRANSCODER_PATH . 'admin/rtmedia-transcoder-handler.php' );

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts_styles' ) );

		add_filter( 'attachment_fields_to_edit', array( $this, 'edit_video_thumbnail' ), 11, 2 );
		add_filter( 'attachment_fields_to_save', array( $this, 'save_video_thumbnail' ), 11, 1 );
		add_filter( 'bp_get_activity_content_body', array( $this, 'rtmedia_transcoder_activity_content_body' ), 1, 2 );

		$this->transcoder_handler = new RTMedia_Transcoder_Handler();

		if ( is_admin() ) {
			add_action( 'admin_menu', array( $this, 'menu' ) );
			add_action( 'admin_init', array( $this, 'register_rtmedia_transcoder_settings' ) );
		}
	}

	/**
	 * Create menu.
	 *
	 * @since    1.0
	 */
	public function menu() {
		add_menu_page( 'rtMedia Transcoder', 'rtMedia Transcoder', 'manage_options', 'rtmedia-transcoder', array( $this, 'settings_page' ), '', '40.2222' );
	}

	/**
	 * Register transcoder settings.
	 *
	 * @since    1.0
	 */
	public function register_rtmedia_transcoder_settings() {
		register_setting( 'rtmedia-transcoder-settings-group', 'number_of_thumbs' );
	}

	/**
	 * Display settings page.
	 *
	 * @since    1.0
	 */
	public function settings_page() {
		include_once( RTMEDIA_TRANSCODER_PATH . 'admin/partials/rtmedia-transcoder-admin-display.php' );
	}

	/**
	 * Load language translation.
	 *
	 * @since    1.0
	 */
	public function load_translation() {
		load_plugin_textdomain( 'rtmedia-transcoder', false, basename( RTMEDIA_TRANSCODER_PATH ) . '/languages/' );
	}

	/**
	 * Load styles and scripts
	 *
	 * @since    1.0
	 */
	public function enqueue_scripts_styles() {

		wp_enqueue_style( 'rtmedia-transcoder-admin-css', RTMEDIA_TRANSCODER_URL . 'admin/css/rtmedia-transcoder-admin.css', array(), RTMEDIA_TRANSCODER_VERSION );
		wp_register_script( 'rtmedia-transcoder-main', RTMEDIA_TRANSCODER_URL . 'admin/js/rtmedia-transcoder-admin.js', array( 'jquery' ), RTMEDIA_TRANSCODER_VERSION, true );
		wp_localize_script( 'rtmedia-transcoder-main', 'rtmedia_transcoder_admin_url', admin_url() );
		wp_localize_script( 'rtmedia-transcoder-main', 'rtmedia_transcoder_admin_url', admin_url() );
		wp_localize_script( 'rtmedia-transcoder-main', 'disable_encoding', esc_html__( 'Are you sure you want to disable the transcoding service?', 'rtmedia-transcoder' ) );
		wp_localize_script( 'rtmedia-transcoder-main', 'enable_encoding', esc_html__( 'Are you sure you want to enable the transcoding service?', 'rtmedia-transcoder' ) );
		wp_localize_script( 'rtmedia-transcoder-main', 'something_went_wrong', esc_html__( 'Something went wrong. Please ', 'rtmedia-transcoder' ) . '<a href onclick="location.reload();">' . esc_html__( 'refresh', 'rtmedia-transcoder' ) . '</a>' . esc_html__( ' page.', 'rtmedia-transcoder' ) );

		wp_enqueue_script( 'rtmedia-transcoder-main' );
	}

	/**
	 * Create subscription form for various subscription plans.
	 *
	 * @since    1.0
	 * @param string $name	 The name of subscription plan.
	 * @param float  $price  The price of subscription plan.
	 * @param bool   $force  If true then it always show subscriobe form.
	 * @return string
	 */
	public function transcoding_subscription_button( $name = 'No Name', $price = '0', $force = false ) {
		if ( $this->api_key ) {
			$this->transcoder_handler->update_usage( $this->api_key );
		}

		$usage_details = get_site_option( 'rtmedia-transcoding-usage' );

		if ( isset( $usage_details[ $this->api_key ]->plan->name ) && ( strtolower( $usage_details[ $this->api_key ]->plan->name ) === strtolower( $name ) ) && $usage_details[ $this->api_key ]->sub_status && ! $force ) {
			$form = '<button disabled="disabled" type="submit" class="button button-primary bpm-unsubscribe">' . esc_html__( 'Current Plan', 'rtmedia-transcoder' ) . '</button>';
		} else {
			$plan_name = 'free' === $name ? 'Try Now' : 'Subscribe';
			$form = '<a href="http://edd.rtcamp.info/?transcoding-plan=' . $name . '" target="_blank" class="button button-primary">
						' . esc_html( $plan_name, 'rtmedia-transcoder' ) . '
					</a>';
		}

		return $form;
	}

	/**
	 * Display all video thumbnails on attachment edit page.
	 *
	 * @param array   $form_fields  An array of attachment form fields.
	 * @param WP_Post $post		    The WP_Post attachment object.
	 * @return array $form_fields
	 */
	function edit_video_thumbnail( $form_fields, $post ) {

		if ( isset( $post->post_mime_type ) ) {
			$media_type = explode( '/', $post->post_mime_type );
			if ( is_array( $media_type ) && 'video' === $media_type[0] ) {
				$media_id         = $post->ID;
				$thumbnail_array  = get_post_meta( $media_id, '_rt_media_thumbnails', true );

				if ( empty( $thumbnail_array ) ) {
					$thumbnail_array  = get_post_meta( $media_id, 'rtmedia_media_thumbnails', true );
				}

				$wp_video_thumbnail = get_post_meta( $media_id, '_rt_media_video_thumbnail', true );

				$video_thumb_html = '';
				if ( is_array( $thumbnail_array ) ) {
					$video_thumb_html .= '<ul> ';

					$uploads 	= wp_get_upload_dir();
		        	$base_url 	= $uploads['baseurl'];

					foreach ( $thumbnail_array as $key => $thumbnail_src ) {
						$checked = false;
						$thumbnail_src_og = $thumbnail_src;
						if ( $wp_video_thumbnail === $thumbnail_src ) {
							$checked = 'checked=checked';
						}

						$file_url = $thumbnail_src;

						if ( 0 === strpos( $file_url, $uploads['baseurl'] ) ) {
							$thumbnail_src = $file_url;
					    } else {
					    	$thumbnail_src = $uploads['baseurl'] . '/' . $file_url;
					    }

						$count   = $key + 1;
						$video_thumb_html .= '<li style="width: 150px;display: inline-block;"> ' .
							'<label for="rtmedia-upload-select-thumbnail-' . esc_attr( $count ) . '"> ' .
							'<input type="radio" ' . esc_attr( $checked ) . ' id="rtmedia-upload-select-thumbnail-' . esc_attr( $count ) . '" value="' . esc_attr( $thumbnail_src_og ) . '" name="rtmedia-thumbnail" /> ' .
							'<img src=" ' . esc_url( $thumbnail_src ) . '" style="max-height: 120px;max-width: 120px; vertical-align: middle;" /> ' .
							'</label></li>';
					}

					$video_thumb_html .= '</ul>';
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

	/**
	 * Display all video thumbnails on attachment edit page.
	 *
	 * @param array   $form_fields  An array of attachment form fields.
	 * @param WP_Post $post		    The WP_Post attachment object.
	 * @return array $form_fields
	 */
	function edit_video_thumbnail_( $form_fields, $post ) {
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

					$uploads 	= wp_get_upload_dir();
		        	$base_url 	= $uploads['baseurl'];

					$video_thumb_html .= '<ul> ';

					foreach ( $thumbnail_array as $key => $thumbnail_src ) {
						$checked = checked( $thumbnail_src, $rtmedia_media[0]->cover_art, false );
						$count   = $key + 1;
						$video_thumb_html .= '<li style="width: 150px;display: inline-block;">
								<label for="rtmedia-upload-select-thumbnail-' . esc_attr( $count ) . '">
								<input type="radio" ' . esc_attr( $checked ) . ' id="rtmedia-upload-select-thumbnail-' . esc_attr( $count ) . '" value="' . esc_attr( $thumbnail_src ) . '" name="rtmedia-thumbnail" />
								<img src=" ' . esc_url( $base_url . '/' . $thumbnail_src ) . '" style="max-height: 120px;max-width: 120px; vertical-align: middle;" />
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

	/**
	 * Save selected video thumbnail in attachment meta.
	 * Selected thumbnail use as cover art for buddypress activity if video was upload in activity.
	 *
	 * @param array $post		An array of post data.
	 * @return array $form_fields
	 */
	function save_video_thumbnail( $post ) {
		$rtmedia_thumbnail = filter_input( INPUT_POST, 'rtmedia-thumbnail', FILTER_SANITIZE_STRING );
		$id = $post['post_ID'];
		if ( isset( $rtmedia_thumbnail ) ) {
			if ( class_exists( 'rtMedia' ) ) {
				$rtmedia_model = new RTMediaModel();
				$media         = $rtmedia_model->get( array( 'media_id' => $id ) );
				$media_id      = $media[0]->id;
				$rtmedia_model->update( array( 'cover_art' => $rtmedia_thumbnail ), array( 'media_id' => $id ) );
				update_activity_after_thumb_set( $media_id );
			}

			update_post_meta( $id, '_rt_media_video_thumbnail', $rtmedia_thumbnail );
		}

		return $post;
	}

	/**
	 * Parse the short codes in the activity content
	 * @param  text 	$content
	 * @param  object 	$activity
	 * @return text
	 */
	public function rtmedia_transcoder_activity_content_body( $content, $activity ) {
		return do_shortcode( $content );
	}
}
