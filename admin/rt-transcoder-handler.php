<?php
/**
 * The transcoder-specific functionality of the plugin.
 *
 * @since	1.0.0
 *
 * @package    Transcoder
 * @subpackage Transcoder/TranscoderHandler
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handle request/response with trancoder api.
 *
 * @since	1.0.0
 *
 * @package    Transcoder
 * @subpackage Transcoder/TranscoderHandler
 */
class RT_Transcoder_Handler {

	/**
	 * The transcoder API URL.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $transcoding_api_url    The URL of the api.
	 */
	protected $transcoding_api_url = 'http://api.rtmedia.io/api/v1/';

	/**
	 * The URL of the EDD store.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $store_url    The URL of the transcoder api.
	 */
	protected $store_url = 'https://rtmedia.io/';

	/**
	 * Contain uploaded media information.
	 *
	 * @since    1.0.0
	 * @access   public
	 * @var      array    $uploaded   Contain uploaded media information.
	 */
	public $uploaded = array();

	/**
	 * The api key of transcoding service subscription.
	 *
	 * @since    1.0.0
	 * @access   public
	 * @var      string    $api_key    The api key of transcoding service subscription.
	 */
	public $api_key = false;

	/**
	 * The api key of transcoding service subscription.
	 *
	 * @since    1.0.0
	 * @access   public
	 * @var      string    $stored_api_key    The api key of transcoding service subscription.
	 */
	public $stored_api_key = false;

	/**
	 * Video extensions with comma separated.
	 *
	 * @since    1.0.0
	 * @access   public
	 * @var      string    $video_extensions    Video extensions with comma separated.
	 */
	public $video_extensions = ',mov,m4v,m2v,avi,mpg,flv,wmv,mkv,webm,ogv,mxf,asf,vob,mts,qt,mpeg,x-msvideo,3gp';

	/**
	 * Audio extensions with comma separated.
	 *
	 * @since    1.0.0
	 * @access   public
	 * @var      string    $audio_extensions    Audio extensions with comma separated.
	 */
	public $audio_extensions = ',wma,ogg,wav,m4a';

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 *
	 * @param bool $no_init  If true then do nothing else continue.
	 */
	public function __construct( $no_init = false ) {

		$this->api_key        = get_site_option( 'rt-transcoding-api-key' );
		$this->stored_api_key = get_site_option( 'rt-transcoding-api-key-stored' );

		if ( $no_init ) {
			return;
		}
		if ( is_admin() ) {
			add_action( 'rt_transcoder_before_widgets', array( $this, 'usage_widget' ) );
		}
		add_action( 'admin_init', array( $this, 'save_api_key' ), 10, 1 );

		if ( $this->api_key ) {
			// Store api key as different db key if user disable transcoding service.
			if ( ! $this->stored_api_key ) {
				$this->stored_api_key = $this->api_key;
				update_site_option( 'rt-transcoding-api-key-stored', $this->stored_api_key );
			}
			add_filter( 'rtmedia_allowed_types', array( $this, 'allowed_types_admin_settings' ), 10, 1 );
			$usage_info = get_site_option( 'rt-transcoding-usage' );

			if ( isset( $usage_info ) && is_array( $usage_info ) && array_key_exists( $this->api_key , $usage_info ) ) {
				if ( isset( $usage_info[ $this->api_key ]->plan->expires )
					&& strtotime( $usage_info[ $this->api_key ]->plan->expires ) < time() ) {
					$usage_info  = $this->update_usage( $this->api_key );
				}
				if ( array_key_exists( $this->api_key , $usage_info ) && is_object( $usage_info[ $this->api_key ] ) && isset( $usage_info[ $this->api_key ]->status ) && $usage_info[ $this->api_key ]->status ) {
					if ( isset( $usage_info[ $this->api_key ]->remaining ) && $usage_info[ $this->api_key ]->remaining > 0 ) {

						// Enable re-transcoding
						include_once( RT_TRANSCODER_PATH . 'admin/rt-retranscode-admin.php' );

						if ( $usage_info[ $this->api_key ]->remaining < 524288000 && ! get_site_option( 'rt-transcoding-usage-limit-mail' ) ) {
							$this->nearing_usage_limit( $usage_info );
						} elseif ( $usage_info[ $this->api_key ]->remaining > 524288000 && get_site_option( 'rt-transcoding-usage-limit-mail' ) ) {
							update_site_option( 'rt-transcoding-usage-limit-mail', 0 );
						}
						if ( strtotime( $usage_info[ $this->api_key ]->plan->expires ) > time() ) {
							add_filter( 'wp_generate_attachment_metadata', array( $this, 'wp_media_transcoding' ), 21, 2 );
						}

						/* Do not let the user to upload non supported media types on localhost */
						$blacklist = array( '127.0.0.1', '::1' );
						$remote_addr = rtt_get_remote_ip_address();
						if ( ! in_array( wp_unslash( $remote_addr ), $blacklist, true ) ) { // @codingStandardsIgnoreLine
							add_filter( 'rtmedia_plupload_files_filter', array( $this, 'allowed_types' ), 10, 1 );
							add_filter( 'rtmedia_allowed_types', array( $this, 'allowed_types_admin_settings' ), 10, 1 );
							add_filter( 'rtmedia_valid_type_check', array( $this, 'bypass_video_audio' ), 10, 2 );
						}
					}
				}
			}
		}

		add_action( 'init', array( $this, 'handle_callback' ), 20 );
		add_action( 'wp_ajax_rt_hide_transcoding_notice', array( $this, 'hide_transcoding_notice' ), 1 );
		add_action( 'wp_ajax_rt_enter_api_key', array( $this, 'enter_api_key' ), 1 );
		add_action( 'wp_ajax_rt_disable_transcoding', array( $this, 'disable_transcoding' ), 1 );
		add_action( 'wp_ajax_rt_enable_transcoding', array( $this, 'enable_transcoding' ), 1 );
	}

	/**
	 * Send transcoding request and save transcoding job id get in response for uploaded media in WordPress media library.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $metadata 			Metadata of the attachment.
	 * @param int    $attachment_id		ID of attachment.
	 * @param string $autoformat		If true then generating thumbs only else trancode video.
	 */
	function wp_media_transcoding( $wp_metadata, $attachment_id, $autoformat = true ) {
		if ( empty( $wp_metadata['mime_type'] ) ) {
			return $wp_metadata;
		}

		$already_sent = get_post_meta( $attachment_id, '_rt_transcoding_job_id', true );
		if ( ! empty( $already_sent ) ) {
			return $wp_metadata;
		}

		$path 	= get_attached_file( $attachment_id );
		$url 	= wp_get_attachment_url( $attachment_id );

		/**
		 * FIX WORDPRESS 3.6 METADATA
		 */
		require_once( ABSPATH . 'wp-admin/includes/media.php' );

		$metadata = $wp_metadata;

		$type_arry        = explode( '.', $url );
		$type             = strtolower( $type_arry[ count( $type_arry ) - 1 ] );
		$not_allowed_type = array( 'mp3' );
		preg_match( '/video|audio/i', $metadata['mime_type'], $type_array );

		if ( preg_match( '/video|audio/i', $metadata['mime_type'], $type_array ) && ! in_array( $metadata['mime_type'], array( 'audio/mp3' ), true ) && ! in_array( $type, $not_allowed_type, true ) ) {
			$options_video_thumb = $this->get_thumbnails_required( $attachment_id );
			if ( empty( $options_video_thumb ) ) {
				$options_video_thumb = 5;
			}

			$job_type = 'video';
			/**  FORMAT * */
			if ( 'video/mp4' === $metadata['mime_type'] && 'mp4' === $type ) {
				$autoformat = 'thumbnails';
				$job_type = 'thumbnail';
			}

			if ( 'audio' === $type_array[0] ) {
				$job_type = 'audio';
			}

			/** Figure out who is requesting this job **/
			$job_for = 'wp-media';
			$post_parent = wp_get_post_parent_id( $attachment_id );
			if ( 0 !== $post_parent ) {
				$post_type 	= get_post_type( $post_parent );
				if ( class_exists( 'RTMediaModel' ) && function_exists( 'rtmedia_id' ) ) {
					if ( 'rtmedia_album' === $post_type ) {
						$job_for = 'rtmedia';
					}
				}
			}

			$args = array(
				'method' 	=> 'POST',
				'sslverify' => false,
				'timeout' 	=> 60,
				'body' 		=> array(
					'api_token' 	=> $this->api_key,
					'job_type' 		=> $job_type,
					'job_for' 		=> $job_for,
					'file_url'		=> urlencode( $url ),
					'callback_url'	=> urlencode( trailingslashit( home_url() ) . 'index.php' ),
					'force'			=> 0,
					'formats'		=> ( true === $autoformat ) ? ( ( 'video' === $type_array[0] ) ? 'mp4' : 'mp3' ) : $autoformat,
					'thumb_count'	=> $options_video_thumb,
				),
			);

			$transcoding_url = $this->transcoding_api_url . 'job/';

			$upload_page = wp_remote_post( $transcoding_url, $args );

			if ( ! is_wp_error( $upload_page ) && ( ( isset( $upload_page['response']['code'] ) && ( 200 === intval( $upload_page['response']['code'] ) ) ) ) ) {
				$upload_info = json_decode( $upload_page['body'] );
				if ( isset( $upload_info->status ) && $upload_info->status && isset( $upload_info->job_id ) && $upload_info->job_id ) {
					$job_id = $upload_info->job_id;
					update_post_meta( $attachment_id, '_rt_transcoding_job_id', $job_id );
				}
			}
			$update_usage = $this->update_usage( $this->api_key );
		}

		return $wp_metadata;
	}

	/**
	 * Get number of thumbnails required to generate for video.
	 *
	 * @since 1.0.0
	 *
	 * @param int $attachment_id	ID of attachment.
	 *
	 * @return int $thumb_count
	 */
	public function get_thumbnails_required( $attachment_id = '' ) {

		$thumb_count = get_option( 'number_of_thumbs' );

		/**
		 * Allow user to filter number of thumbnails required to generate for video.
		 *
		 * @since 1.0.0
		 *
		 * @param int $thumb_count    Number of thumbnails set in setting.
		 * @param int $attachment_id  ID of attachment.
		 */
		$thumb_count = apply_filters( 'rt_media_total_video_thumbnails', $thumb_count, $attachment_id );

		return $thumb_count > 10 ? 10 : $thumb_count;

	}

	/**
	 * Check whether uploaded file is valid audio/video file or not.
	 *
	 * @since 1.0.0
	 *
	 * @param boolean $flag		File valid or not.
	 * @param array   $file		Media file.
	 *
	 * @return boolean
	 */
	public function bypass_video_audio( $flag, $file ) {
		if ( isset( $file['type'] ) ) {
			$fileinfo = explode( '/', $file['type'] );
			if ( in_array( $fileinfo[0], array( 'audio', 'video' ), true ) ) {
				$flag = true;
			}
		}

		return $flag;
	}

	/**
	 * Check api key is valid or not.
	 *
	 * @since	1.0.0
	 *
	 * @param string $key    Api Key.
	 *
	 * @return boolean $status  If true then key is valid else key is not valid.
	 */
	public function is_valid_key( $key ) {
		$validate_url    = trailingslashit( $this->store_url ) . 'rt-eddsl-api/?rt-eddsl-license-key=' . $key;
		if ( function_exists( 'vip_safe_wp_remote_get' ) ) {
			$validation_page = vip_safe_wp_remote_get( $validate_url );
		} else {
			$validation_page = wp_remote_get( $validate_url ); // @codingStandardsIgnoreLine
		}
		if ( ! is_wp_error( $validation_page ) ) {
			$validation_info = json_decode( $validation_page['body'] );
			if ( isset( $validation_info->status ) ) {
				$status = $validation_info->status;
			}
		} else {
			$status = false;
		}

		return $status;
	}

	/**
	 * Save usage information.
	 *
	 * @since	1.0.0
	 *
	 * @param string $key  Api key.
	 *
	 * @return array $usage_info  An array containing usage information.
	 */
	public function update_usage( $key ) {
		$usage_url  = trailingslashit( $this->transcoding_api_url ) . 'usage/' . $key;
		if ( function_exists( 'vip_safe_wp_remote_get' ) ) {
			$usage_page = vip_safe_wp_remote_get( $usage_url );
		} else {
			$usage_page = wp_remote_get( $usage_url ); // @codingStandardsIgnoreLine
		}
		if ( ! is_wp_error( $usage_page ) ) {
			$usage_info = json_decode( $usage_page['body'] );
		} else {
			$usage_info = null;
		}

		update_site_option( 'rt-transcoding-usage', array( $key => $usage_info ) );

		return $usage_info;
	}

	/**
	 * Send email to admin when trancoding quota near to limit.
	 *
	 * @since	1.0.0
	 *
	 * @param array $usage_details Usage informataion.
	 */
	public function nearing_usage_limit( $usage_details ) {
		$subject = esc_html__( 'Transcoding: Nearing quota limit.', 'transcoder' );
		$message = '<p>' . esc_html__( 'You are nearing the quota limit for your transcoding service.', 'transcoder' ) . '</p><p>'
		           . esc_html__( 'Following are the details:', 'transcoder' ) . '</p><p><strong>Used:</strong> %s</p><p><strong>'
		           . esc_html__( 'Remaining', 'transcoder' ) . '</strong>: %s</p><p><strong>' . esc_html__( 'Total:', 'transcoder' ) . '</strong> %s</p>';
		$users   = get_users( array( 'role' => 'administrator' ) );
		if ( $users ) {
			$admin_email_ids = array();
			foreach ( $users as $user ) {
				$admin_email_ids[] = $user->user_email;
			}
			add_filter( 'wp_mail_content_type', array( $this, 'wp_mail_content_type' ) );
			wp_mail( $admin_email_ids, $subject, sprintf( $message, size_format( $usage_details[ $this->api_key ]->used, 2 ), size_format( $usage_details[ $this->api_key ]->remaining, 2 ), size_format( $usage_details[ $this->api_key ]->total, 2 ) ) );
			remove_filter( 'wp_mail_content_type', array( $this, 'wp_mail_content_type' ) );
		}
		update_site_option( 'rt-transcoding-usage-limit-mail', 1 );
	}

	/**
	 * Send email to admin when trancoding quota is over.
	 *
	 * @since	1.0.0
	 */
	public function usage_quota_over() {
		$usage_details = get_site_option( 'rt-transcoding-usage' );
		if ( ! $usage_details[ $this->api_key ]->remaining ) {
			$subject = esc_html__( 'Transcoding: Usage quota over.', 'transcoder' );
			$message = '<p>' . esc_html__( 'Your usage quota is over. Upgrade your plan' , 'transcoder' ) . '</p><p>' .
			           esc_html__( 'Following are the details:', 'transcoder' ) . '</p><p><strong>' . esc_html__( 'Used:' , 'transcoder' ) .
			           '</strong> %s</p><p><strong>' . esc_html__( 'Remaining' , 'transcoder' ) . '</strong>: %s</p><p><strong>' . esc_html__( 'Total:', 'transcoder' ) . '</strong> %s</p>';
			$users   = get_users( array( 'role' => 'administrator' ) );
			if ( $users ) {
				foreach ( $users as $user ) {
					$admin_email_ids[] = $user->user_email;
				}
				add_filter( 'wp_mail_content_type', array( $this, 'wp_mail_content_type' ) );
				wp_mail( $admin_email_ids, $subject, sprintf( $message, size_format( $usage_details[ $this->api_key ]->used, 2 ), 0, size_format( $usage_details[ $this->api_key ]->total, 2 ) ) );
				remove_filter( 'wp_mail_content_type', array( $this, 'wp_mail_content_type' ) );
			}
			update_site_option( 'rt-transcoding-usage-limit-mail', 1 );
		}
	}

	/**
	 * Check whether key is valid or not and save api key.
	 *
	 * @since	1.0.0
	 */
	public function save_api_key() {
		$is_api_key_updated		= filter_input( INPUT_GET, 'api-key-updated', FILTER_SANITIZE_STRING );
		$is_invalid_license_key = filter_input( INPUT_GET, 'invalid-license-key', FILTER_SANITIZE_STRING );
		$is_localhost 			= filter_input( INPUT_GET, 'need-public-host', FILTER_SANITIZE_STRING );

		if ( $is_api_key_updated ) {
			if ( is_multisite() ) {
				add_action( 'network_admin_notices', array( $this, 'successfully_subscribed_notice' ) );
			}

			add_action( 'admin_notices', array( $this, 'successfully_subscribed_notice' ) );
		} elseif ( $is_invalid_license_key ) {
			if ( is_multisite() ) {
				add_action( 'network_admin_notices', array( $this, 'invalid_license_notice' ) );
			}

			add_action( 'admin_notices', array( $this, 'invalid_license_notice' ) );
		} elseif ( $is_localhost ) {
			if ( is_multisite() ) {
				add_action( 'network_admin_notices', array( $this, 'public_host_needed_notice' ) );
			}

			add_action( 'admin_notices', array( $this, 'public_host_needed_notice' ) );
		}

		$apikey		= trim( filter_input( INPUT_GET, 'apikey', FILTER_SANITIZE_STRING ) );
		$page		= filter_input( INPUT_GET, 'page',	 FILTER_SANITIZE_STRING );
		$is_update	= filter_input( INPUT_GET, 'update', FILTER_SANITIZE_STRING );

		if ( ! empty( $apikey ) && is_admin() && ! empty( $page ) && ( 'rt-transcoder' === $page ) ) {
			/* Do not activate transcoding service on localhost */
			$blacklist = array( '127.0.0.1', '::1' );
			$remote_addr = rtt_get_remote_ip_address();
			if ( in_array( wp_unslash( $remote_addr ), $blacklist, true ) ) {
				$return_page = add_query_arg( array(
					'page'            => 'rt-transcoder',
					'need-public-host' => '1',
				), admin_url( 'admin.php' ) );
				wp_safe_redirect( esc_url_raw( $return_page ) );
				die();
			}
			if ( $this->is_valid_key( $apikey ) ) {
				update_site_option( 'rt-transcoding-api-key', $apikey );
				update_site_option( 'rt-transcoding-api-key-stored', $apikey );

				$usage_info  = $this->update_usage( $apikey );
				$return_page = add_query_arg( array(
					'page'            => 'rt-transcoder',
					'api-key-updated' => $usage_info->plan->name ? ucfirst( strtolower( $usage_info->plan->name ) ) : 'Free',
				), admin_url( 'admin.php' ) );
				wp_safe_redirect( esc_url_raw( $return_page ) );
				die();
			} else {
				$return_page = add_query_arg( array(
					'page'					=> 'rt-transcoder',
					'invalid-license-key'	=> '1',
				), admin_url( 'admin.php' ) );
				wp_safe_redirect( esc_url_raw( $return_page ) );
				die();
			}
		}
	}

	/**
	 * Allow user to upload other types media files.
	 *
	 * @since 1.0.0
	 *
	 * @param array $types	Mime types.
	 *
	 * @return array $types Mime types.
	 */
	public function allowed_types( $types ) {
		if ( isset( $types[0] ) && isset( $types[0]['extensions'] ) ) {
			if ( is_rtmedia_upload_video_enabled() && strpos( $this->video_extensions, $types[0]['extensions'] ) ) {
				$types[0]['extensions'] .= $this->video_extensions; // Allow all types of video file to be uploded.
			}
			if ( is_rtmedia_upload_music_enabled() && strpos( $this->audio_extensions, $types[0]['extensions'] ) ) {
				$types[0]['extensions'] .= $this->audio_extensions; // Allow all types of music file to be uploded.
			}
		}

		return $types;
	}

	/**
	 * Allow user to upload other types media files.
	 *
	 * @since 1.0.0
	 *
	 * @param array $types Mime types.
	 *
	 * @return array	Mime types.
	 */
	public function allowed_types_admin_settings( $types ) {
		$allowed_video_string   = implode( ',', $types['video']['extn'] );
		$allowed_audio_string   = implode( ',', $types['music']['extn'] );
		$allowed_video          = explode( ',', $allowed_video_string . $this->video_extensions );
		$allowed_audio          = explode( ',', $allowed_audio_string . $this->audio_extensions );
		$types['video']['extn'] = array_unique( $allowed_video );
		$types['music']['extn'] = array_unique( $allowed_audio );

		return $types;
	}

	/**
	 * Display message when user subscribed successfully.
	 *
	 * @since 1.0.0
	 */
	public function successfully_subscribed_notice() {
	?>
		<div class="updated">
			<p>
				<?php
				printf(
					wp_kses(
						__( 'You have successfully subscribed for the <strong>%s</strong> plan.', 'transcoder' ),
						array(
							'strong' => array(),
						)
					),
					esc_html( sanitize_text_field( wp_unslash( $_GET['api-key-updated'] ) ) ) // @codingStandardsIgnoreLine
				);
				?>
			</p>
		</div>
	<?php
	}

	/**
	 * Display message when license key is not valid.
	 *
	 * @since 1.0.0
	 */
	public function invalid_license_notice() {
	?>
		<div class="error">
			<p>
				<?php esc_html_e( 'This license key is invalid.', 'transcoder' ); ?>
			</p>
		</div>
	<?php
	}

	/**
	 * Display message when user tries to activate license key on localhost.
	 *
	 * @since 1.0.6
	 */
	public function public_host_needed_notice() {
	?>
		<div class="error">
			<p>
				<?php esc_html_e( 'Transcoding service can not be activated on the localhost', 'transcoder' ); ?>
			</p>
		</div>
	<?php
	}

	/**
	 * Display usage widget in sidebar on rtmedia transcoder settings page.
	 *
	 * @since 1.0.0
	 */
	public function usage_widget() {
		$usage_details	= get_site_option( 'rt-transcoding-usage' );
		$content		= '';
		$api_key		= '';

		if ( ! empty( $this->api_key ) ) {
			$api_key = $this->api_key;
		} elseif ( ! empty( $this->stored_api_key ) ) {
			$api_key = $this->stored_api_key;
		}

		if ( ! empty( $api_key ) ) {
			if ( $usage_details && isset( $usage_details[ $api_key ]->status ) && $usage_details[ $api_key ]->status ) {

				if ( isset( $usage_details[ $api_key ]->plan->name ) ) {
					$plan_name = strtolower( $usage_details[ $api_key ]->plan->name );
					$content .= '<p><strong>' . esc_html__( 'Current Plan', 'transcoder' ) . ':</strong> ' . esc_html( ucfirst( $plan_name ) ) . ( $usage_details[ $api_key ]->sub_status ? '' : ' (' . esc_html__( 'Unsubscribed', 'transcoder' ) . ')' ) . '</p>';
				} else {
					$plan_name = '';
				}

				if ( isset( $usage_details[ $api_key ]->plan->expires ) && 'free' !== $plan_name ) {
					$content .= '<p><strong>' . esc_html__( 'Expires On', 'transcoder' ) . ':</strong> ' . date_i18n( 'F j, Y', strtotime( $usage_details[ $api_key ]->plan->expires ) ) . '</p>';
				}
				if ( isset( $usage_details[ $api_key ]->used ) ) {
					$content .= '<p><span class="transcoding-used"></span><strong>' . esc_html__( 'Used', 'transcoder' ) . ':</strong> ' . ( ( $used_size = size_format( $usage_details[ $api_key ]->used, 2 ) ) ? esc_html( $used_size ) : '0MB' ) . '</p>';
				}
				if ( isset( $usage_details[ $api_key ]->remaining ) ) {
					$content .= '<p><span class="transcoding-remaining"></span><strong>' . esc_html__( 'Remaining', 'transcoder' ) . ':</strong> ';
					if ( $usage_details[ $api_key ]->remaining >= 0 ) {
						$content .= size_format( $usage_details[ $api_key ]->remaining, 2 );
					} else {
						$content .= $usage_details[ $api_key ]->remaining . '0MB';
					}
				}
				if ( isset( $usage_details[ $api_key ]->total ) ) {
					$content .= '<p><strong>' . esc_html__( 'Total', 'transcoder' ) . ':</strong> ';
					if ( $usage_details[ $api_key ]->total >= 0 ) {
						$content .= size_format( $usage_details[ $api_key ]->total, 2 );
					} elseif ( $usage_details[ $api_key ]->total <= -1 ) {
						$content .= 'Unlimited';
					} else {
						$content .= '';
					}
				}
				$usage = new RT_Progress();

				if ( empty( $usage_details[ $api_key ]->used ) ) {
					$usage_details[ $api_key ]->used = 0;
				}

				if ( empty( $usage_details[ $api_key ]->total ) ) {
					$usage_details[ $api_key ]->total = 0;
				}

				if ( ! isset( $usage_details[ $api_key ]->remaining ) ) {
					$usage_details[ $api_key ]->remaining = 0;
				}

				$content .= $usage->progress_ui( $usage->progress( $usage_details[ $api_key ]->used, $usage_details[ $api_key ]->total ), false );

				$content .= '<p>' . esc_html__( 'Usage will automatically reset at the end of every month.', 'transcoder' ) . '</p>';

				if ( 'free' === $plan_name ) {
					$content .= '<p>' . esc_html__( 'Upgrade for more bandwidth.', 'transcoder' ) . '</p>';
				}

				if ( ( 0 >= $usage_details[ $api_key ]->remaining ) ) {
					$content .= '<div class="error below-h2"><p>' . esc_html__( 'Your usage limit has been reached. Upgrade your plan.', 'transcoder' ) . '</p></div>';
				}

				if ( ( isset( $usage_details[ $api_key ]->plan->expires ) && strtotime( $usage_details[ $api_key ]->plan->expires ) < time() ) ) {
					$content .= '<div class="error below-h2"><p>' . esc_html__( 'Your plan has expired. Please consider upgrading if you need more bandwidth.', 'transcoder' ) . '</p></div>';
				}
			} else {
				$content .= '<div class="error below-h2"><p>' . esc_html__( 'Your API key is not valid or is expired.', 'transcoder' ) . '</p></div>';
			}
		} else {
			$content .= '<p>' . esc_html__( 'Currently, You are not subscribed to transcoding service. Please subscribe.', 'transcoder' ) . '</p>';
		}
		?>
		<div class="postbox" id="transcoder-usage">
	        <h3 class="hndle">
				<span>
					<?php esc_html_e( 'Transcoding usage this month', 'transcoder' ); ?>
				</span>
			</h3>
	        <div class="inside">
				<?php echo $content; // @codingStandardsIgnoreLine ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Save thumbnails for transcoded video.
	 *
	 * @since 1.0.0
	 *
	 * @param array $post_array  Attachment data.
	 *
	 * @return url
	 */
	public function add_media_thumbnails( $post_array ) {

		$defaults = array(
	 		'post_id' 	=> '',
	 		'job_for' 	=> '',
		);

		// Parse incoming $post_array into an array and merge it with $defaults
		$post_array = wp_parse_args( $post_array, $defaults );

		do_action( 'rtt_before_thumbnail_store', $post_array['post_id'], $post_array );

		$post_id 				= $post_array['post_id'];
		$post_info              = get_post( $post_id );
		$post_date_string       = new DateTime( $post_info->post_date );
		$post_date              = $post_date_string->format( 'Y-m-d G:i:s' );
		$post_date_thumb_string = new DateTime( $post_info->post_date );
		$post_date_thumb        = $post_date_thumb_string->format( 'Y/m/' );
		$post_thumbs            = $post_array;
		$post_thumbs_array      = maybe_unserialize( $post_thumbs );
		$largest_thumb_size     = 0;

		if ( 'rtmedia' === $post_thumbs_array['job_for'] && class_exists( 'RTMediaModel' ) ) {
			$model           	= new RTMediaModel();
			$media             	= $model->get( array( 'media_id' => $post_id ) );
			$media_id          	= $media[0]->id;

			$this->media_author = $media[0]->media_author;
			$this->uploaded['context']      = $media[0]->context;
			$this->uploaded['context_id']   = $media[0]->context_id;
			$this->uploaded['media_author'] = $media[0]->media_author;
		}

		$largest_thumb          = false;
		$largest_thumb_url 		= false;
		$upload_thumbnail_array = array();

		foreach ( $post_thumbs_array['thumbnail'] as $thumbs => $thumbnail ) {
			$thumbresource            	= function_exists( 'vip_safe_wp_remote_get' ) ? vip_safe_wp_remote_get( $thumbnail ) : wp_remote_get( $thumbnail ); // @codingStandardsIgnoreLine
			$thumbinfo             	= pathinfo( $thumbnail );
			$temp_name             	= $thumbinfo['basename'];
			$temp_name             	= urldecode( $temp_name );
			$temp_name_array       	= explode( '/', $temp_name );
			$temp_name             	= $temp_name_array[ count( $temp_name_array ) - 1 ];
			$thumbinfo['basename'] 	= apply_filters( 'transcoded_temp_filename', $temp_name );

			if ( 'wp-media' !== $post_thumbs_array['job_for'] ) {
				add_filter( 'upload_dir', array( $this, 'upload_dir' ) );
			}

			// Create a file in the upload folder with given content.
			$thumb_upload_info = wp_upload_bits( $thumbinfo['basename'], null, $thumbresource['body'] );

			/**
			 * Allow users to filter/perform action on uploaded transcoded file.
			 *
			 * @since 1.0.5
			 *
			 * @param array $thumb_upload_info	Array contains the uploaded file url and Path
			 *                                 	i.e $thumb_upload_info['url'] contains the file URL
			 *                                 	and $thumb_upload_info['file'] contains the file physical path
			 * @param int  $post_id 			Contains the attachment ID for which transcoded file is uploaded
			 */

			$thumb_upload_info = apply_filters( 'transcoded_file_stored', $thumb_upload_info, $post_id );

			if ( 'wp-media' !== $post_thumbs_array['job_for'] ) {
				remove_filter( 'upload_dir', array( $this, 'upload_dir' ) );
			}

			$file = _wp_relative_upload_path( $thumb_upload_info['file'] );

			/**
			 * Allows users/plugins to filter the file URL
			 *
			 * @since 1.0.5
			 *
			 * @param string $thumb_upload_info['url'] 	Contains the file public URL
			 * @param int $post_id 						Contains the attachment ID for which transcoded file has been uploaded
			 */
			$thumb_upload_info['url'] = apply_filters( 'transcoded_file_url', $thumb_upload_info['url'], $post_id );

			$thumbnails_abs_url_array[] = $thumb_upload_info['url'];

			if ( $file ) {
				$upload_thumbnail_array[] = $file;
			}

			$current_thumb_size = @filesize( $thumb_upload_info['file'] ); // @codingStandardsIgnoreLine

			if ( $current_thumb_size >= $largest_thumb_size ) {
				$largest_thumb_size = $current_thumb_size;
				$largest_thumb      = $thumb_upload_info['url']; 			// Absolute URL of the thumb
				$largest_thumb_url	= $file ? $file : ''; 					// Relative URL of the thumb
			}
		}

		update_post_meta( $post_id, '_rt_media_source', 	$post_thumbs_array['job_for'] );
		update_post_meta( $post_id, '_rt_media_thumbnails',	$upload_thumbnail_array );

		do_action( 'transcoded_thumbnails_added', $post_id );

		if ( $largest_thumb_url ) {

			$is_retranscoding_job = get_post_meta( $post_id, '_rt_retranscoding_sent', true );

			if ( ! $is_retranscoding_job || rtt_is_override_thumbnail() ) {

				update_post_meta( $post_id, '_rt_media_video_thumbnail', $largest_thumb_url );

				if ( 'rtmedia' === $post_thumbs_array['job_for'] && class_exists( 'RTMediaModel' ) ) {

						$model->update( array( 'cover_art' => $largest_thumb ), array( 'media_id' => $post_id ) );
						update_activity_after_thumb_set( $media_id );
				}
			}

			/**
			 * Allow users/plugins to access the thumbnail file which is got stored as a thumbnail
			 *
			 * @since 1.0.7
			 *
			 * @param string 	$largest_thumb 	Absolute URL of the thumbnail
			 * @param int 		$post_id 		Attachment ID of the video for which thumbnail has been set
			 */
			do_action( 'transcoded_thumb_added', $largest_thumb, $post_id );
		}

		return $largest_thumb_url;
	}

	/**
	 * Save transcoded media files.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $file_post_array	Transcoded files.
	 * @param int    $attachment_id		ID of attachment.
	 * @param string $job_for			Whether media uploaded through rtmedia plugin or WordPress media.
	 */
	public function add_transcoded_files( $file_post_array, $attachment_id, $job_for = '' ) {
		$transcoded_files = false;
		$mail = true;
		global $wpdb;

		do_action( 'rtt_before_transcoded_media_store', $attachment_id, $file_post_array );

		if ( isset( $file_post_array ) && is_array( $file_post_array ) && ( count( $file_post_array > 0 ) ) ) {
			foreach ( $file_post_array as $key => $format ) {
				if ( is_array( $format ) && ( count( $format > 0 ) ) ) {
					foreach ( $format as $each => $file ) {
						$flag = false;
						if ( isset( $file ) ) {

							if ( 'rtmedia' === $job_for ) {
								$model              = new RTMediaModel();
								$media              = $model->get_media( array( 'media_id' => $attachment_id ), 0, 1 );
								$this->media_author = $media[0]->media_author;
								$this->uploaded['context']      = $media[0]->context;
								$this->uploaded['context_id']   = $media[0]->context_id;
								$this->uploaded['media_author'] = $media[0]->media_author;
							}
							$download_url                   = urldecode( urldecode( $file ) );
							$new_wp_attached_file_pathinfo 	= pathinfo( $download_url );
							$post_mime_type                	= 'mp4' === $new_wp_attached_file_pathinfo['extension'] ? 'video/mp4' : 'audio/mp3';
							try {
								$response = function_exists( 'vip_safe_wp_remote_get' ) ? vip_safe_wp_remote_get( $download_url ) : wp_remote_get( $download_url ); // @codingStandardsIgnoreLine
							} catch ( Exception $e ) {
								$flag = $e->getMessage();
							}

							$file_content = wp_remote_retrieve_body( $response );

							if ( ! empty( $file_content ) ) {

								if ( 'wp-media' !== $job_for ) {
									add_filter( 'upload_dir', array( $this, 'upload_dir' ) );
								}

								$upload_info = wp_upload_bits( $new_wp_attached_file_pathinfo['basename'], null, $file_content );

								/**
								 * Allow users to filter/perform action on uploaded transcoded file.
								 *
								 * @since 1.0.5
								 *
								 * @param array $upload_info	Array contains the uploaded file url and Path
								 *                              i.e $upload_info['url'] contains the file URL
								 *                              and $upload_info['file'] contains the file physical path
								 * @param int  $attachment_id 	Contains the attachment ID for which transcoded file is uploaded
								 */
								$upload_info = apply_filters( 'transcoded_file_stored', $upload_info, $attachment_id );

								if ( 'wp-media' !== $job_for ) {
									remove_filter( 'upload_dir', array( $this, 'upload_dir' ) );
								}

								$uploaded_file = _wp_relative_upload_path( $upload_info['file'] );

								if ( $uploaded_file ) {
									$transcoded_files[ $key ][] = $uploaded_file;
								}
							} else {
								$flag = esc_html__( 'Could not read file.', 'transcoder' );

								if ( $flag && $mail ) {
									$download_link = esc_url( add_query_arg( array(
										'job_id'    			=> rtt_get_job_id_by_attachment_id( $attachment_id ),
										'job_for'    			=> $job_for,
										'files['.$key.'][0]' 	=> esc_url( $download_url ), // @codingStandardsIgnoreLine
									), home_url() ) );
									$subject       = esc_html__( 'Transcoding: Download Failed', 'transcoder' );
									$message       = '<p><a href="' . esc_url( rtt_get_edit_post_link( $attachment_id ) ) . '">' . esc_html__( 'Media', 'transcoder' ) . '</a> ' .
									                 esc_html__( ' was successfully encoded but there was an error while downloading:', 'transcoder' ) . '</p><p><code>' .
									                 esc_html( $flag ) . '</code></p><p>' . esc_html__( 'You can ', 'transcoder' ) . '<a href="' . esc_url( $download_link ) . '">'
													. esc_html__( 'retry the download', 'transcoder' ) . '</a>.</p>';
									$users         = get_users( array( 'role' => 'administrator' ) );
									if ( $users ) {
										foreach ( $users as $user ) {
											$admin_email_ids[] = $user->user_email;
										}
										add_filter( 'wp_mail_content_type', array( $this, 'wp_mail_content_type' ) );
										wp_mail( $admin_email_ids, $subject, $message );
										remove_filter( 'wp_mail_content_type', array( $this, 'wp_mail_content_type' ) );
									}
									echo esc_html( $flag );
								} else {
									esc_html_e( 'Done', 'transcoder' );
								}
							}
						}
					}
					if ( 'rtmedia' === $job_for ) {
						$activity_id = $media[0]->activity_id;
						if ( $activity_id ) {
							$attachemnt_url  	= wp_get_attachment_url( $attachment_id );
							$content          	= $wpdb->get_var( $wpdb->prepare( "SELECT content FROM {$wpdb->base_prefix}bp_activity WHERE id = %d", $activity_id ) );

							/* for WordPress backward compatibility */
							if ( function_exists( 'wp_get_upload_dir' ) ) {
								$uploads = wp_get_upload_dir();
							} else {
								$uploads = wp_upload_dir();
							}
							if ( 'video/mp4' === $post_mime_type ) {
								$media_type = 'mp4';
							} elseif ( 'audio/mp3' === $post_mime_type ) {
								$media_type = 'mp3';
							}

							$transcoded_file_url = $uploads['baseurl'] . '/' . $transcoded_files[ $media_type ][0];
							/**
							 * Allows users/plugins to filter the file URL
							 *
							 * @since 1.0.5
							 *
							 * @param string $transcoded_file_url 	Contains the file public URL
							 * @param int $attachment_id 			Contains the attachment ID for which transcoded file has been uploaded
							 */
							$transcoded_file_url = apply_filters( 'transcoded_file_url', $transcoded_file_url, $attachment_id );

							$activity_content = str_replace( $attachemnt_url, $transcoded_file_url, $content );
							$update = $wpdb->update( $wpdb->base_prefix . 'bp_activity', array( 'content' => $activity_content ), array( 'id' => $activity_id ) );
						}
					}
				}
			}
		}
		if ( ! empty( $transcoded_files ) ) {
			update_post_meta( $attachment_id, '_rt_media_transcoded_files', $transcoded_files );
			do_action( 'transcoded_media_added', $attachment_id );
		}
	}

	/**
	 * Get post id from meta key and value.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key	Meta key.
	 * @param mixed  $value	Meta value.
	 *
	 * @return int|bool		Return post id if found else false.
	 */
	function get_post_id_by_meta_key_and_value( $key, $value ) {
		global $wpdb;
		$meta = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . $wpdb->postmeta . ' WHERE meta_key = %s AND meta_value = %s', $key, $value ) ); // @codingStandardsIgnoreLine

		if ( is_array( $meta ) && ! empty( $meta ) && isset( $meta[0] ) ) {
			$meta = $meta[0];
		}
		if ( is_object( $meta ) ) {
			return $meta->post_id;
		} else {
			return false;
		}
	}

	/**
	 * Function to handle the callback request by the FFMPEG transcoding server.
	 *
	 * @since 1.0.0
	 */
	public function handle_callback() {
		require_once( ABSPATH . 'wp-admin/includes/image.php' );

		$job_id			= filter_input( INPUT_POST, 'job_id', FILTER_SANITIZE_STRING );
		$status			= filter_input( INPUT_POST, 'status', FILTER_SANITIZE_STRING );
		$file_status 	= filter_input( INPUT_POST, 'file_status', FILTER_SANITIZE_STRING );
		$error_msg		= filter_input( INPUT_POST, 'error_msg', FILTER_SANITIZE_STRING );
		$error_code		= filter_input( INPUT_POST, 'error_code', FILTER_SANITIZE_STRING );
		$job_for		= filter_input( INPUT_POST, 'job_for', FILTER_SANITIZE_STRING );
		$thumbnail		= filter_input( INPUT_POST, 'thumbnail', FILTER_SANITIZE_STRING );
		$format			= filter_input( INPUT_POST, 'format', FILTER_SANITIZE_STRING );

		if ( ! empty( $job_id )  && ! empty( $file_status ) && ( 'error' === $file_status ) ) {
			$send_alert = $this->nofity_transcoding_failed( $job_id, $error_msg );
			echo esc_html__( 'Something went wrong. Invalid post request.', 'transcoder' );
			die();
		}

		$attachment_id = '';

		// @codingStandardsIgnoreStart
		if ( isset( $job_for ) && ( 'wp-media' === $job_for ) ) {
			if ( isset( $job_id ) ) {
				$has_thumbs = isset( $thumbnail ) ? true : false;
				$flag       = false;
				global $wpdb;

				$id = $this->get_post_id_by_meta_key_and_value( '_rt_transcoding_job_id', $job_id );

				if ( isset( $id ) && is_numeric( $id ) ) {
					$attachment_id      	= $id;

					$post_array 			= $_POST;
					$post_array['post_id'] 	= $attachment_id;

					if ( $has_thumbs ) {
						$thumbnail = $this->add_media_thumbnails( $post_array );
					}

					if ( isset( $format ) && 'thumbnail' === sanitize_text_field( wp_unslash( $format ) ) ) {
						die();
					}

					if ( ! empty( $_REQUEST['files'] ) ) {
						$uploded_files = $this->add_transcoded_files( $_REQUEST['files'], $attachment_id, $job_for );
					}


				} else {
					$flag = esc_html__( 'Something went wrong. The required attachment id does not exists. It must have been deleted.', 'transcoder' );
				}

				$this->update_usage( $this->api_key );

				if ( $flag ) {
					$subject       = esc_html__( 'Transcoding: Download Failed', 'transcoder' );
					$message       = '<p><a href="' . esc_url( rtt_get_edit_post_link( $attachment_id ) ) . '">' . esc_html__( 'Media', 'transcoder' ) . '</a> ' .
					                 esc_html__( ' was successfully encoded but there was an error while downloading:', 'transcoder' ) . '</p><p><code>' .
					                 esc_html( $flag ) . '</code></p>';
					$users         = get_users( array( 'role' => 'administrator' ) );
					if ( $users ) {
						foreach ( $users as $user ) {
							$admin_email_ids[] = $user->user_email;
						}
						add_filter( 'wp_mail_content_type', array( $this, 'wp_mail_content_type' ) );
						wp_mail( $admin_email_ids, $subject, $message );
						remove_filter( 'wp_mail_content_type', array( $this, 'wp_mail_content_type' ) );
					}
					echo esc_html( $flag );
				} else {
					esc_html_e( 'Done', 'transcoder' );
				}
				die();
			}
		} else {
			if ( isset( $job_id ) ) {
				$has_thumbs = isset( $thumbnail ) ? true : false;
				$flag       = false;
				global $wpdb;
				$model        = new RTDBModel( 'rtm_media_meta', false, 10, true );
				$meta_details = $model->get( array(
					'meta_value' => sanitize_text_field( wp_unslash( $job_id ) ),
					'meta_key'   => 'rtmedia-transcoding-job-id',
				) );
				if ( ! isset( $meta_details[0] ) ) {
					$id = $this->get_post_id_by_meta_key_and_value( '_rt_transcoding_job_id', $job_id );
				} else {
					$id = $meta_details[0]->media_id;
				}
				if ( isset( $id ) && is_numeric( $id ) ) {
					$model              = new RTMediaModel();
					$media              = $model->get_media( array( 'media_id' => $id ), 0, 1 );
					$this->media_author = $media[0]->media_author;
					$attachment_id      = $media[0]->media_id;

					$post_array 			= $_POST;
					$post_array['post_id'] 	= $attachment_id;

					if ( $has_thumbs ) {
						$cover_art = $this->add_media_thumbnails( $post_array );
					}

					if ( isset( $format ) && 'thumbnail' === sanitize_text_field( wp_unslash( $format ) ) ) {
						die();
					}

					if ( ! empty( $_REQUEST['files'] ) ) {
						$uploded_files = $this->add_transcoded_files( $_REQUEST['files'], $attachment_id, $job_for );
					}

				} else {
					$flag = esc_html__( 'Something went wrong. The required attachment id does not exists. It must have been deleted.', 'transcoder' );
				}

				$this->update_usage( $this->api_key );

				if ( $flag && $mail ) {
					$subject       = esc_html__( 'Transcoding: Download Failed', 'transcoder' );
					$message       = '<p><a href="' . esc_url( rtt_get_edit_post_link( $attachment_id ) ) . '">' . esc_html__( 'Media', 'transcoder' ) . '</a> ' .
					                 esc_html__( ' was successfully transcoded but there was an error while downloading:', 'transcoder' ) . '</p><p><code>' .
					                 esc_html( $flag ) . '</code></p><p>';
					$users         = get_users( array( 'role' => 'administrator' ) );
					if ( $users ) {
						foreach ( $users as $user ) {
							$admin_email_ids[] = $user->user_email;
						}
						add_filter( 'wp_mail_content_type', array( $this, 'wp_mail_content_type' ) );
						wp_mail( $admin_email_ids, $subject, $message );
						remove_filter( 'wp_mail_content_type', array( $this, 'wp_mail_content_type' ) );
					}
					echo esc_html( $flag );
				} else {
					esc_html_e( 'Done', 'transcoder' );
				}
				die();
			}
		}
		// @codingStandardsIgnoreEnd

		/**
		 * Allow users/plugins to perform action after response received from the transcoder is
		 * processed
		 *
		 * @since 1.0.9
		 *
		 * @param number 	$attachment_id 	Attachment ID for which the callback has sent from the transcoder
		 * @param number 	$job_id 		The transcoding job ID
		 */
		do_action( 'rtt_handle_callback_finished', $attachment_id, $job_id );
	}

	/**
	 * Hide notices.
	 *
	 * @since 1.0.0
	 */
	public function hide_transcoding_notice() {
		update_site_option( 'rt-transcoding-service-notice', true );
		update_site_option( 'rt-transcoding-expansion-notice', true );
		echo true;
		die();
	}

	/**
	 * Check whether key is entered or not.
	 *
	 * @since 1.0
	 */
	public function enter_api_key() {
		$apikey  = filter_input( INPUT_GET, 'apikey', FILTER_SANITIZE_STRING );
		if ( ! empty( $apikey ) ) {
			echo wp_json_encode( array( 'apikey' => $apikey ) );
		} else {
			echo wp_json_encode( array( 'error' => esc_html__( 'Please enter the license key.', 'transcoder' ) ) );
		}
		die();
	}

	/**
	 * Disable transcoding.
	 *
	 * @since 1.0.0
	 */
	public function disable_transcoding() {
		update_site_option( 'rt-transcoding-api-key', '' );
		esc_html_e( 'Transcoding disabled successfully.', 'transcoder' );
		die();
	}

	/**
	 * Enable transcoding.
	 *
	 * @since 1.0.0
	 */
	function enable_transcoding() {
		update_site_option( 'rt-transcoding-api-key', $this->stored_api_key );
		esc_html_e( 'Transcoding enabled successfully.', 'transcoder' );
		die();
	}

	/**
	 * Return upload path of media uploaded through rtMedia plugin.
	 *
	 * @since 1.0.0
	 *
	 * @global mixed $rtmedia_interaction
	 *
	 * @param array $upload_dir  Upload directory information.
	 *
	 * @return array $upload_dir
	 */
	function upload_dir( $upload_dir ) {
		global $rtmedia_interaction;
		if ( isset( $this->uploaded['context'] ) && isset( $this->uploaded['context_id'] ) ) {
			if ( 'group' !== $this->uploaded['context'] ) {
				$rtmedia_upload_prefix = 'users/';
				$id                    = $this->uploaded['media_author'];
			} else {
				$rtmedia_upload_prefix = 'groups/';
				$id                    = $this->uploaded['context_id'];
			}
		} else {
			if ( 'group' !== $rtmedia_interaction->context->type ) {
				$rtmedia_upload_prefix = 'users/';
				$id                    = $this->uploaded['media_author'];
			} else {
				$rtmedia_upload_prefix = 'groups/';
				$id                    = $rtmedia_interaction->context->id;
			}
		}

		if ( ! $id ) {
			$id = $this->media_author;
		}

		$rtmedia_folder_name = apply_filters( 'rtmedia_upload_folder_name', 'rtMedia' );

		$upload_dir['path'] = trailingslashit( str_replace( $upload_dir['subdir'], '', $upload_dir['path'] ) ) . $rtmedia_folder_name . '/' . $rtmedia_upload_prefix . $id . $upload_dir['subdir'];
		$upload_dir['url']  = trailingslashit( str_replace( $upload_dir['subdir'], '', $upload_dir['url'] ) ) . $rtmedia_folder_name . '/' . $rtmedia_upload_prefix . $id . $upload_dir['subdir'];

		$upload_dir = apply_filters( 'rtmedia_filter_upload_dir', $upload_dir, $this->uploaded );

		return $upload_dir;
	}

	/**
	 * Send's the email. It's the wrapper function for wp_mail
	 *
	 * @since 1.0.0
	 *
	 * @param  array   $email_ids		Email id's to send an email.
	 * @param  string  $subject			Email subject.
	 * @param  string  $message			Email message.
	 * @param  boolean $include_admin	If true then send an email to admin also else not.
	 */
	public function send_notification( $email_ids = array(), $subject, $message, $include_admin = true ) {
		if ( empty( $subject ) || empty( $message ) ) {
			return true;
		}

		/**
		 * Filter to disable the notification sent to the admins/users
		 *
		 * @param boolean 		By default it is true. If false is passed the email wont
		 *                      get sent to the any user
		 */
		$send_notification = apply_filters( 'rtt_send_notification', true );

		if ( false === $send_notification ) {
			return true;
		}

		if ( $include_admin ) {
			$users   = get_users( array( 'role' => 'administrator' ) );
			if ( $users ) {
				foreach ( $users as $user ) {
					$email_ids[] = $user->user_email;
				}
			}
		}

		add_filter( 'wp_mail_content_type', array( $this, 'wp_mail_content_type' ) );
		wp_mail( $email_ids, $subject, $message );
		remove_filter( 'wp_mail_content_type', array( $this, 'wp_mail_content_type' ) );
	}

	/**
	 * Sets the content type of mail to text/html
	 *
	 * @since  1.0.0
	 *
	 * @return string
	 */
	public function wp_mail_content_type() {
		return 'text/html';
	}

	/**
	 * Send notification about failed transcoding job
	 *
	 * @since 1.0.0
	 *
	 * @param  string $job_id		Transcoding job id.
	 * @param  string $error_msg	Error message for why transcoding of media failed.
	 */
	public function nofity_transcoding_failed( $job_id, $error_msg ) {
		if ( empty( $job_id ) ) {
			return false;
		}
		$subject = esc_html__( 'Transcoding: Something went wrong.', 'transcoder' );
		$attachment_id = $this->get_post_id_by_meta_key_and_value( '_rt_transcoding_job_id', $job_id );
		if ( ! empty( $error_msg ) ) {
			$message = '<p>' . esc_html__( ' There was unexpected error occurred while transcoding this following media.', 'transcoder' ) . '</p>';
			$message .= '<p><a href="' . esc_url( rtt_get_edit_post_link( $attachment_id ) ) . '">' . esc_html__( 'Media', 'transcoder' ) . '</a></p>';
			$message .= '<p>Error message: ' . esc_html( $error_msg ) . '</p>';
		} else {
			$message = '<p><a href="' . esc_url( rtt_get_edit_post_link( $attachment_id ) ) . '">' . esc_html__( 'Media', 'transcoder' ) . '</a> ' .
				esc_html__( ' there was unexpected error occurred while transcoding this media.', 'transcoder' ) . '</p>';
		}

		$email_ids = array();
		if ( ! empty( $attachment_id ) ) {
			$author_id 		= get_post_field( 'post_author', $attachment_id );
			$email_ids[] 	= get_the_author_meta( 'user_email', $author_id );
		}

		/**
		 * Allows users/plugins to alter the email id of a user
		 *
		 * @param array $email_ids 	Email id of the user who owns the media
		 * @param string $job_id 	Job ID sent by the transcoder
		 */
		$email_ids = apply_filters( 'rtt_nofity_transcoding_failed', $email_ids, $job_id );

		$this->send_notification( $email_ids, $subject, $message, $include_admin = true );
	}

	/**
	 * To get status of transcoding process
	 *
	 * @since 1.2
	 *
	 * @param string $post_id post ID.
	 */
	public function get_transcoding_status( $post_id ) {

		if ( empty( $post_id ) ) {
			return wp_json_encode( array(
				'status'  => 'Error',
				'message' => esc_html__( 'Something went wrong. Please try again!', 'transcoder' ),
			) );
		}

		$job_id            = get_post_meta( $post_id, '_rt_transcoding_job_id', true );
		$transcoded_files  = get_post_meta( $post_id, '_rt_media_transcoded_files', true );
		$transcoded_thumbs = get_post_meta( $post_id, '_rt_media_thumbnails', true );
		$thumbnail         = get_post_meta( $post_id, '_rt_media_video_thumbnail', true );

		$status_url = trailingslashit( $this->transcoding_api_url ) . 'job/status/' . $job_id . '/' . get_site_option( 'rt-transcoding-api-key-stored' );

		$message  = '';
		$response = array();
		$status   = 'running';

		if ( ! empty( $transcoded_files ) && ! empty( $transcoded_thumbs ) ) {

			$message    = __( 'Your file is transcoded successfully. Please refresh the page.', 'transcoder' );
			$status     = 'Success';
			$upload_dir = wp_upload_dir();

			$response['files']     = $upload_dir['baseurl'] . '/' . $transcoded_files['mp4'][0];
			$response['thumbnail'] = $upload_dir['baseurl'] . '/' . $thumbnail;

			global $wpdb;
			$results              = $wpdb->get_results( "SELECT id FROM {$wpdb->prefix}rt_rtm_media WHERE media_id = '" . $post_id . "'", OBJECT );
			$response['media_id'] = $results[0]->id;

		} else {

			if ( function_exists( 'vip_safe_wp_remote_get' ) ) {
				$status_page = vip_safe_wp_remote_get( $status_url );
			} else {
				$status_page = wp_remote_get( $status_url, array( 'timeout' => 120 ) ); // @codingStandardsIgnoreLine
			}

			if ( ! is_wp_error( $status_page ) ) {
				$status_info = json_decode( $status_page['body'] );
			} else {
				$status_info = null;
			}

			$messages = array(
				'null-response' => __( 'Looks like the server is taking too long to respond, Please try again in sometime.', 'transcoder' ),
				'failed' => __( 'Unfortunately, Transcoder failed to transcode this file.', 'transcoder' ) . $data['error_msg'],
				'running' => __( 'Your file is getting transcoded. Please refresh after some time.', 'transcoder' ),
				'in-queue' => __( 'This file is still in the queue. Please refresh after some time.', 'transcoder' ),
				'receiving-back' => __( 'Your server should be ready to receive the transcoded file.', 'transcoder' ),
				'success' => __( 'Your file is transcoded successfully. Please refresh the page.', 'transcoder' ),
			);

			/**
			 * Filters the transcoding process status message message.
			 *
			 * @since 1.2
			 *
			 * @param array $message Default transcoding process status messages.
			 */
			$messages = apply_filters( 'rtt_transcoder_status_message', $messages );

			if ( empty( $status_info ) || ! is_object( $status_info ) || empty( $status_info->job_id ) ) {

				$message = $messages['null-response'];

			} elseif ( ! empty( $status_info ) && ! empty( $status_info->error_code ) && ! empty( $status_info->error_msg ) ) {

				$message = $messages['failed'];

			} elseif ( ! empty( $status_info ) && 'processing' === $status_info->status && empty( $status_info->error_code ) && empty( $status_info->error_msg ) ) {

				$message = $messages['running'];

			} elseif ( ! empty( $status_info ) && 'processing' !== $status_info->status && '100' !== $status_info->progress && empty( $status_info->error_code ) && empty( $status_info->error_msg ) ) {

				$message = $messages['in-queue'];

			} elseif ( ! empty( $status_info ) && 'processed' === $status_info->status && 'video' === $status_info->job_type && ( empty( $transcoded_files ) || empty( $transcoded_thumbs ) ) ) {

				$message = $messages['receiving-back'];

			} elseif ( ! empty( $status_info ) && 'processed' === $status_info->status && ! empty( $transcoded_thumbs ) && ( ! empty( $transcoded_files ) || 'thumbnail' === $status_info->job_type ) ) {

				$message = $messages['success'];
				$status  = 'Success';

				$upload_dir            = wp_upload_dir();
				$response['files']     = $upload_dir['baseurl'] . '/' . $transcoded_files['mp4'][0];
				$response['thumbnail'] = $upload_dir['baseurl'] . '/' . $thumbnail;

				global $wpdb;
				$results = $wpdb->get_results( "SELECT id FROM {$wpdb->prefix}rt_rtm_media WHERE media_id = '" . $post_id . "'", OBJECT ); // @codingStandardsIgnoreLine
				$response['media_id'] = $results[0]->id;

			} elseif ( ! empty( $status_info ) ) {
				$message = $status_info->status;
			}
		}

		$response['message'] = esc_html( $message );
		$response['status']  = esc_html( $status );

		return wp_json_encode( $response );
	}
}
