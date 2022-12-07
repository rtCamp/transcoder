<?php
/**
 * The admin-specific functionality of the plugin.
 * This is the Transcoder admin first class, it will responsible for admin settings page and thumbnail selection and other 
 * @since      1.0.0
 * @package    Transcoder
 * @subpackage Transcoder/Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The admin-specific functionality of the plugin.
 * @since       1.0.0
 * @package     Transcoder
 * @subpackage  Transcoder/Admin
 */
class RT_Transcoder_Admin {

	/**
	 * The object of RT_Transcoder_Handler class.
	 * @since    1.0.0
	 * @access   private
	 * @var      object    $transcoder_handler    The object of RT_Transcoder_Handler class.
	 */
	private $transcoder_handler;

	/**
	 * The api key of transcoding service subscription.
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $api_key    The api key of transcoding service subscription.
	 */
	private $api_key = false;

	/**
	 * The api key of transcoding service subscription.
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $stored_api_key    The api key of transcoding service subscription.
	 */
	private $stored_api_key = false;

	/**
	 * Initialize the class and set its properties.
	 * @since    1.0.0
	 */
	public function __construct() {
		//  getting post site Option data that was saved Previously, option of API key 
		$this->api_key        = get_site_option( 'rt-transcoding-api-key' );
		$this->stored_api_key = get_site_option( 'rt-transcoding-api-key-stored' );
		// Plugin translation files 
		$this->load_translation();

		if ( ! class_exists( 'RT_Progress' ) ) {
			include_once RT_TRANSCODER_PATH . 'admin/rt-transcoder-progressbar.php'; // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.UsingCustomConstant
		}
		//  Including handler file
		include_once RT_TRANSCODER_PATH . 'admin/rt-transcoder-handler.php'; // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.UsingCustomConstant
		include_once RT_TRANSCODER_PATH . 'admin/rt-transcoder-actions.php'; // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.UsingCustomConstant
		// Enqueueing the scripts 
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts_styles' ) );
		// Below Hooks Will Display thumbnails in the attachment video edit page bottom
		add_filter( 'attachment_fields_to_edit', array( $this, 'show_video_thumbnail_in_attachment_edit_page' ), 11, 2 );
		// Hook for Processing the data that send by show_video_thumbnail_in_attachment_edit_page() function 
		add_filter( 'attachment_fields_to_save', array( $this, 'save_video_thumbnail' ), 11, 1 );
		// Admin Notice HOOK
		add_action( 'admin_notices', array( $this, 'add_settings_errors' ) );
		//  This Plugin HTTP request and Repose handler 
		$this->transcoder_handler = new RT_Transcoder_Handler();

		if( is_admin() ){
			// Admin menu Hook for creating admin page 
			add_action( 'admin_menu', array( $this, 'menu' ) );
			// Admin init HOOK
			add_action( 'admin_init', array( $this, 'register_transcoder_settings' ) );
			if ( class_exists( 'RTMediaEncoding' ) ) {
				$old_rtmedia_encoding_key = get_site_option( 'rtmedia-encoding-api-key' );
				if ( ! empty( $old_rtmedia_encoding_key ) ) {
					update_site_option( 'rtmedia-encoding-api-key', '' );
				}
				add_action( 'init', array( $this, 'disable_encoding' ) );
			}
			if ( is_multisite() ) {
				add_action( 'network_admin_notices', array( $this, 'subscribe_transcoder_admin_notice' ) );
			}
			// Adding admin notice hook
			add_action( 'admin_notices', array( $this, 'subscribe_transcoder_admin_notice' ) );
			// Adding footer function for JS uncheck all checkboxes except one clicked
			add_filter( 'admin_footer', array( $this,'admin_footer_for_attachment_edit_page' ) );
		}

		if ( class_exists( 'RTMedia' ) ) {
			if( ! function_exists( 'get_plugin_data' ) ){
				include_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
			$rtmedia_plugin_info = get_plugin_data( RTMEDIA_PATH . 'index.php' );

			// Show admin notice when Transcoder pluign active and user using rtMedia version 4.0.7.
			if( version_compare( $rtmedia_plugin_info['Version'], '4.0.7', '<=' ) ){
				if(is_multisite()){
					add_action( 'network_admin_notices', array( $this, 'transcoder_admin_notice' ) );
				}
				add_action( 'admin_notices', array( $this, 'transcoder_admin_notice' ) );
				add_action( 'wp_ajax_transcoder_hide_admin_notice', array( $this, 'transcoder_hide_admin_notice' ) );
			}
			add_action( 'admin_head', array( $this, 'rtmedia_hide_encoding_tab' ) );

			add_filter( 'wp_mediaelement_fallback', array( $this, 'mediaelement_add_class' ), 20, 2 );
		}
	}

	/**
	 * Display errors if any while settings are save.
	 */
	public function add_settings_errors() {
		settings_errors( 'rt-transcoder-settings-group' );
	}

	/**
	 * Create admin menu for this Plugin. ***
	 * @since    1.0.0
	 */
	public function menu() {
		add_menu_page( 'Transcoder', 'Transcoder', 'manage_options', 'rt-transcoder', array($this, 'settings_page'), RT_TRANSCODER_URL . 'admin/images/menu-icon.png', '40.2222' );
	}

	/**
	 * Register transcoder settings.
	 * @since    1.0.0
	 */
	public function register_transcoder_settings() {
		register_setting( 'rt-transcoder-settings-group', 'number_of_thumbs' );
		register_setting( 'rt-transcoder-settings-group', 'rtt_override_thumbnail' );
		register_setting( 'rt-transcoder-settings-group', 'rtt_client_check_status_button' );
	}

	/**
	 * Display settings page.
	 * @since    1.0.0
	 */
	public function settings_page() {
		include_once RT_TRANSCODER_PATH . 'admin/partials/rt-transcoder-admin-display.php'; // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.UsingCustomConstant
	}

	/**
	 * Load language translation.
	 * @since    1.0.0
	 */
	public function load_translation() {
		load_plugin_textdomain( 'transcoder', false, basename( RT_TRANSCODER_PATH ) . '/languages/' );
	}

	/**
	 * Remove actions and filters from old rtMedia (v4.0.2) plugin.
	 * @since   1.0.0
	 */
	public function disable_encoding() {
		global $rtmedia_admin;
		$rtmedia_encoding = $rtmedia_admin->rtmedia_encoding;
		if ( ! empty( $rtmedia_admin ) ) {
			remove_filter( 'media_row_actions', array( $rtmedia_admin, 'add_reencode_link' ) );
			remove_action( 'admin_head-upload.php', array( $rtmedia_admin, 'add_bulk_actions_regenerate' ) );
		}

		if ( isset( $rtmedia_admin->rtmedia_encoding ) ) {
			$rtmedia_encoding = $rtmedia_admin->rtmedia_encoding;
			remove_action( 'rtmedia_after_add_media', array( $rtmedia_encoding, 'encoding' ) );
			remove_action( 'rtmedia_before_default_admin_widgets', array( $rtmedia_encoding, 'usage_widget' ) );
			remove_action( 'admin_init', array( $rtmedia_encoding, 'save_api_key' ), 1 );
		}
	}

	/**
	 * Load styles and scripts
	 * @since    1.0.0
	 */
	public function enqueue_scripts_styles() {
		global $pagenow;

		$page = transcoder_filter_input( INPUT_GET, 'page', FILTER_SANITIZE_STRING );

		if ( 'admin.php' !== $pagenow OR 'rt-transcoder' !== $page ) {
			return;
		}
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		wp_enqueue_style( 'rt-transcoder-admin-css', RT_TRANSCODER_URL . 'admin/css/rt-transcoder-admin' . $suffix . '.css', array(), RT_TRANSCODER_VERSION );
		wp_register_script( 'rt-transcoder-main', RT_TRANSCODER_URL . 'admin/js/rt-transcoder-admin' . $suffix . '.js', array( 'jquery' ), RT_TRANSCODER_VERSION, true );

		$localize_script_data = array(
			'admin_url'            => esc_url( admin_url() ),
			'loader_image'         => esc_url( admin_url( 'images/loading.gif' ) ),
			'disable_encoding'     => esc_html__( 'Are you sure you want to disable the transcoding service?', 'transcoder' ),
			'enable_encoding'      => esc_html__( 'Are you sure you want to enable the transcoding service?', 'transcoder' ),
			'something_went_wrong' => esc_html__( 'Something went wrong. Please ', 'transcoder' ) . '<a href onclick="location.reload();">' . esc_html__( 'refresh', 'transcoder' ) . '</a>' . esc_html__( ' page.', 'transcoder' ),
			'error_empty_key'      => esc_html__( 'Please enter the license key.', 'transcoder' ),
		);

		wp_localize_script( 'rt-transcoder-main', 'rt_transcoder_script', $localize_script_data );
		wp_enqueue_script( 'rt-transcoder-main' );
	}

	/**
	 * Create subscription form for various subscription plans.
	 * @since   1.0.0
	 * @param 	string $name   The name of subscription plan.
	 * @param 	float  $price  The price of subscription plan.
	 * @param 	bool   $force  If true then it always show subscriobe form.
	 * @return 	string
	 */
	public function transcoding_subscription_button( $name = 'No Name', $price = '0', $force = false ) {
		if( $this->api_key ){
			$this->transcoder_handler->update_usage( $this->api_key );
		}
		// Getting uses data from site option
		$usage_details = get_site_option( 'rt-transcoding-usage' );
		// 
		if ( isset( $usage_details[ $this->api_key ]->plan->name ) && ( strtolower( $usage_details[ $this->api_key ]->plan->name ) === strtolower( $name ) ) && $usage_details[ $this->api_key ]->sub_status && ! $force ) {
			$form      = '<button disabled="disabled" type="submit" class="button button-primary bpm-unsubscribe">' . esc_html__( 'Current Plan', 'transcoder' ) . '</button>';
		} else {
			$plan_name = 'free' === $name ? 'Try Now' : 'Subscribe';
			$form      = '<a href="https://rtmedia.io/?transcoding-plan=' . $name . '" target="_blank" class="button button-primary">' . esc_html( $plan_name ) . '</a>';
		}
		return $form;
	}

	/**
	 * Display all video thumbnails on attachment edit page.
	 * @since   1.0.0
	 * @param 	array   $form_fields  An array of attachment form fields.
	 * @param 	WP_Post $post         The WP_Post attachment object.
	 * @return 	array   $form_fields
	 */
	public function show_video_thumbnail_in_attachment_edit_page( $form_fields, $post ) {
		// If post mime type is not video, this feature is only for videos.
		if(! isset(explode( '/', $post->post_mime_type)[0]) OR explode('/', $post->post_mime_type)[0] !== 'video' ){
            return $form_fields;
        }
		// Getting attachment thumbnail list that was created by our Plugin.
		$thumbnailArray = get_post_meta( $post->ID, '_rt_media_thumbnails', true );
		// Getting option.
		$thumbnailArray = empty( $thumbnailArray ) ? get_post_meta( $post->ID, 'rtmedia_media_thumbnails', true ) : $thumbnailArray;
		// Saved or selected thumbnail is that was saved in attachment option.
		$preSelectedThumbnail = get_post_meta($post->ID, '_rt_media_video_thumbnail', true);
		// checking thumbnail Array is array and not empty 
		if(! is_array( $thumbnailArray ) OR empty( $thumbnailArray ) ){
			return $form_fields;
		}
		// Backward compatibility.
		$uploads = function_exists( 'wp_get_upload_dir' ) ? wp_get_upload_dir() : wp_upload_dir();
		// HTML buffer holder.
		$htmlString  = "";
		// Creating HTML output buffering, starts.
		$htmlString .= "<ul>";
		// Looping the thumbnail array.
		foreach ( $thumbnailArray as $key => $thumbnailLink ) {
			// Checked status.
			$preSelectionStatus = ( $thumbnailLink === $preSelectedThumbnail ) ? 'checked=checked' : '';
			// String concatenation.
			$htmlString .= "<li style='width: 150px;display: inline-block;'>";
			$htmlString .= "<label for='rtmedia-upload-select-thumbnail-'" . esc_attr($key + 1) . "'>";
			$htmlString .= "<input type='checkbox'". $preSelectionStatus ."  onclick='yepShowAlert(this)' id='rtmedia-upload-select-thumbnail-". esc_attr($key + 1) ."' value='". esc_attr($thumbnailLink) ."' class='rtEditThumbnail' name='rtmedia-thumbnail' />";
			$htmlString .= "<img src='". esc_url($uploads['baseurl'] .'/'. $thumbnailLink) ."' style='max-height: 120px;max-width: 120px; vertical-align: middle;'/>";
			$htmlString .= "</label></li>";
			$htmlString .= "</li>";
		}
		// HTML output buffering ends.
		$htmlString .= '</ul>';
		// Custom HTML output.
		$form_fields['rtmedia_video_thumbnail'] = array(
			'label' => 'Video Thumbnails',
			'input' => 'html',
			'html'  => $htmlString,
		);
		// Return parameter array value 
		return $form_fields;
	}

	/**
	 * this is a process function of show_video_thumbnail_in_attachment_edit_page() function.
	 * This function will process show_video_thumbnail_in_attachment_edit_page() selections and create thumbnail if necessary.
	 * This Function also Save selected video thumbnail in attachment meta in attached video file.
	 * This Function will also connected to rtMedia Plugin.
	 * Selected thumbnail use as cover art for buddyPress activity if video was uploaded in activity.
	 * @since   1.0.0
	 * @param 	array $post  An array of post data.
	 * @return 	array $form_fields
	 */
	public function save_video_thumbnail( $post ) {
		// Attachment edit page selected Thumbnail file name.
		$rtMediaSelectedThumbnail = ( isset( $post['rtmedia-thumbnail'] ) AND !empty( $post['rtmedia-thumbnail'] ) ) ? sanitize_text_field( $post['rtmedia-thumbnail'] ) : NULL;
		// Video attachment ID.
		$post_id  = ( isset( $post['ID'] ) AND !empty( $post['ID'] ) ) ? intval( sanitize_text_field( $post['ID'] ) ) : NULL;
		// Empty check for thumbnail image file name and post id
		if (! $rtMediaSelectedThumbnail OR ! $post_id ) {
			return $post;
		}
		// === This is old code [legacy code starts] ===
		// if  rtMedia Plugin is exist Do this block 
		if ( class_exists( 'rtMedia' ) ){
			$uploads        = function_exists( 'wp_get_upload_dir' ) ? wp_get_upload_dir() : wp_upload_dir();
			$final_file_url = ( strpos( $rtMediaSelectedThumbnail, $uploads['baseurl'] ) === false ) ? $rtMediaSelectedThumbnail : $uploads['baseurl'] . '/' . $rtMediaSelectedThumbnail;
			$rtmedia_model  = new RTMediaModel();
			$media          = $rtmedia_model->get( array( 'media_id' => $post_id ) );
			$media_id       = $media[0]->id;
			$rtmedia_model->update( array( 'cover_art' => $final_file_url ), array( 'media_id' => $post_id) );
			rtt_update_activity_after_thumb_set( $media_id );
		}
		// Updating post meta.
		update_post_meta( $post_id, '_rt_media_video_thumbnail', $rtMediaSelectedThumbnail );
		// === This is old code [legacy code ends] ===
		# I am creating thumbnail entry because when transcoder sent the data it create only first thumbnail of the video 
		// Getting all meta File name that was created by this Plugin
		$thumbnailListArray = get_post_meta( $post_id, '_rt_media_thumbnails', true );
		//  Check is empty or not array.
		if ( ! is_array( $thumbnailListArray ) OR empty( $thumbnailListArray ) ) {
			return $post;
		}
		// Global database object 
		global $wpdb;
		// Looping the thumbnails array 
		foreach ( $thumbnailListArray as $fileLastHalfPath ) {
			// Running database query to see thumbnail already exist in the database 
			$firstPreviousEntryID = $wpdb->get_var( "SELECT * FROM ".$wpdb->prefix."posts WHERE post_type = 'attachment' AND post_title = '". pathinfo( $fileLastHalfPath, PATHINFO_FILENAME ) ."'" );
			// if Thumbnail is not in the database than insert the thumbnail to the database 
			if ( ! $firstPreviousEntryID ){
				// Getting upload directory details.
				$uploadsDir = function_exists( 'wp_get_upload_dir' ) ? wp_get_upload_dir() : wp_upload_dir();
				// File Upload path 
				$filePath = ( isset( $uploadsDir['basedir'] ) AND !empty( $uploadsDir['basedir'] ) ) ? $uploadsDir['basedir'] .'/'. $fileLastHalfPath : "";
				// sCheck to see File exist in the path if exist than create a thumbnail with that file, this file was uploaded by transcode but database was not updated
				if(file_exists( $filePath )){
					// Prepare an array of post data for the attachment.
					$attachment = array(
						'guid'           => $uploadsDir['url'] . '/' .  basename( $filePath ), 
						'post_mime_type' => ( isset( wp_check_filetype( basename( $filePath ), null )['type'] ) AND ! empty(wp_check_filetype( basename( $filePath ), null )['type'] ) ) ? wp_check_filetype( basename( $filePath ), null )['type'] : "image/jpeg",
						'post_title'     => pathinfo($filePath, PATHINFO_FILENAME),
						'post_content'   => '',
						'post_status'    => 'inherit'
					);
					// Insert the attachment to the database entry.
					$new_attachment_id = wp_insert_attachment( $attachment, $filePath, $post_id );
					// Make sure that this file is included, as wp_generate_attachment_metadata() depends on it.
					require_once( ABSPATH . 'wp-admin/includes/image.php' );
					// Generate the metadata for the attachment, and update the database record.
					$attach_data = wp_generate_attachment_metadata( $new_attachment_id, $filePath );
					// Updating attachment Information 
					wp_update_attachment_metadata( $new_attachment_id, $attach_data );
				}
			} 
		}
		// getting the selected image thumbnail database id for setting the thumbnail image
		$selectedThumbnailsId = $wpdb->get_var( "SELECT ID FROM ".$wpdb->prefix."posts WHERE post_type = 'attachment' AND post_title = '". pathinfo( $rtMediaSelectedThumbnail, PATHINFO_FILENAME ) ."'" );
		// Setting selected image to the Parent file featured image.
		if ( $selectedThumbnailsId AND ! is_float( $selectedThumbnailsId ) ) {
			$post['_thumbnail_id'] =  $selectedThumbnailsId;
		}

		return $post;
	}

	/**
	 * Display admin notice.
	 * @since   1.0.0
	*/
	public function transcoder_admin_notice() {
		//  Getting site option data thats was saved before 
		$show_notice = get_site_option( 'transcoder_admin_notice', 1 );
		// 
		if ( '1' === $show_notice OR 1 === $show_notice ) :
			?>
				<div class="notice notice-info transcoder-notice is-dismissible">
					<?php wp_nonce_field( '_transcoder_hide_notice_', 'transcoder_hide_notice_nonce' ); ?>
					<p><?php esc_html_e( 'rtMedia encoding service has been disabled because you are using Transcoder plugin.', 'transcoder' ); ?></p>
				</div>
				<script type="text/javascript">
					jQuery( document ).ready( function() {
						jQuery('.transcoder-notice.is-dismissible').on( 'click', '.notice-dismiss', function() {
							var data = {
								action: 'transcoder_hide_admin_notice',
								transcoder_notice_nonce: jQuery('#transcoder_hide_notice_nonce').val()
							};
							jQuery.post( ajaxurl, data, function ( response ) {
								jQuery('.transcoder-notice').remove();
							});
						});
					});
				</script>
			<?php
		endif;
	}

	/**
	 * Display subscribe to the transcoding service
	*/
	public function subscribe_transcoder_admin_notice() {
		if( ! empty( $this->api_key ) ){
			return false;
		}
		$settings_page_link = 'admin.php?page=rt-transcoder';
		$class              = 'notice notice-error';
		$valid_tags         = array(
			'div'    => array( 'class' => array() ),
			'p'      => array(),
			'strong' => array(),
			'a'      => array( 'href' => array() ),
		);
		// translators: Markup to show the info about plugin subscription if no API key is there.
		printf( wp_kses( __( '<div class="%1$s"><p><strong>IMPORTANT!</strong> The Transcoder plugin works with active transcoding services subscription plan. <a href="%2$s">Click here</a> to subscribe or enable.</p></div>', 'transcoder' ), $valid_tags ), esc_attr( $class ), esc_url( admin_url( $settings_page_link ) ) );
	}

	/**
	 * Set option to hide admin notice when user click on dismiss button.
	 * @since   1.0.0
	 */
	public function transcoder_hide_admin_notice() {
		if ( check_ajax_referer( '_transcoder_hide_notice_', 'transcoder_notice_nonce' ) ) {
			update_site_option( 'transcoder_admin_notice', '0' );
		}
		die();
	}

	/**
	 * Hide encoding tab in old rtMedia plugin.
	 * @since   1.0.0
	 */
	public function rtmedia_hide_encoding_tab() {
		?>
		<style>
			.rtmedia-tab-title.audiovideo-encoding {
				display: none;
			}
		</style>
		<?php
	}

	/**
	 * Filters the MediaElement fallback output to add class
	 * @since  1.0.0
	 * @param  type 	$output  Fallback output for no-JS.
	 * @param  type 	$url     Media file URL.
	 * @return string 	return 	 fallback output.
	 */
	public function mediaelement_add_class( $output, $url ) {
		return sprintf( '<a class="no-popup" href="%1$s">%1$s</a>', esc_url( $url ) );
	}

	/**
	 * This function will add JS to the admin Footer, if Thumbnail is selection is clicked then select the clicked one and deselect rest of the checkboxes.
	 * @since  1.0.0
	 * @param  type 	$output  Fallback output for no-JS.
	 * @param  type 	$url     Media file URL.
	 * @return string 	return 	 fallback output.
	 */
	public function admin_footer_for_attachment_edit_page(){
		?>
		<script type="text/javascript">
			function yepShowAlert(checkBox) {
				var get = document.getElementsByName( 'rtmedia-thumbnail' );
				for( var i=0; i<get.length; i++ ) {
					if( get[i].id == checkBox.id ){
						get[i].checked = checkBox.checked;
					} else {
						get[i].checked = '';
					}
				}
		 	}
		</script>
		<?php
	}
}
