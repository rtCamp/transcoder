<?php
/**
 * The transcoder-specific functionality of the plugin.
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
 * Handle request/response with trancoder api.
 *
 * @package    rtmedia-trascoder
 * @subpackage rtmedia-trascoder/admin
 */
class RTMedia_Transcoder_Handler {

	/**
	 * The URL of the api.
	 *
	 * @since    1.0
	 * @access   protected
	 * @var      string    $api_url    The URL of the api.
	 */
	protected $api_url = 'http://api-rtmedia.rtcamp.info/api/v1/';

	/**
	 * The URL of the transcoder api.
	 *
	 * @since    1.0
	 * @access   protected
	 * @var      string    $edd_api_url    The URL of the transcoder api.
	 */
	protected $edd_api_url = 'http://edd.rtcamp.info/';

	/**
	 * Contain uploaded media information.
	 *
	 * @since    1.0
	 * @access   public
	 * @var      array    $uploaded   Contain uploaded media information.
	 */
	public $uploaded = array();

	/**
	 * The api key of transcoding service subscription.
	 *
	 * @since    1.0
	 * @access   public
	 * @var      string    $api_key    The api key of transcoding service subscription.
	 */
	public $api_key = false;

	/**
	 * The api key of transcoding service subscription.
	 *
	 * @since    1.0
	 * @access   public
	 * @var      string    $stored_api_key    The api key of transcoding service subscription.
	 */
	public $stored_api_key = false;

	/**
	 * Video extensions with comma separated.
	 *
	 * @since    1.0
	 * @access   public
	 * @var      string    $video_extensions    Video extensions with comma separated.
	 */
	public $video_extensions = ',mov,m4v,m2v,avi,mpg,flv,wmv,mkv,webm,ogv,mxf,asf,vob,mts,qt,mpeg,x-msvideo';

	/**
	 * Audio extensions with comma separated.
	 *
	 * @since    1.0
	 * @access   public
	 * @var      string    $audio_extensions    Audio extensions with comma separated.
	 */
	public $audio_extensions = ',wma,ogg,wav,m4a';

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0
	 *
	 * @param bool $no_init  If true then do nothing else continue.
	 */
	public function __construct( $no_init = false ) {

		$this->api_key        		= get_site_option( 'rtmedia-transcoding-api-key' );
		$this->stored_api_key 		= get_site_option( 'rtmedia-transcoding-api-key-stored' );

		if ( $no_init ) {
			return;
		}
		if ( is_admin() && $this->api_key ) {
			add_action( 'rtmedia_transcoder_before_widgets', array( $this, 'usage_widget' ) );
		}
		add_action( 'admin_init', array( $this, 'save_api_key' ), 1 );
		add_action( 'admin_init', array( $this, 'transcoding_api_subscribe' ), 1 );

		if ( $this->api_key ) {
			// Store api key as different db key if user disable transcoding service.
			if ( ! $this->stored_api_key ) {
				$this->stored_api_key = $this->api_key;
				update_site_option( 'rtmedia-transcoding-api-key-stored', $this->stored_api_key );
			}
			add_filter( 'rtmedia_allowed_types', array( $this, 'allowed_types_admin_settings' ), 10, 1 );
			$usage_info = get_site_option( 'rtmedia-transcoding-usage' );
			if ( $usage_info ) {
				if ( isset( $usage_info[ $this->api_key ]->status ) && $usage_info[ $this->api_key ]->status ) {
					if ( isset( $usage_info[ $this->api_key ]->remaining ) && $usage_info[ $this->api_key ]->remaining > 0 ) {
						if ( $usage_info[ $this->api_key ]->remaining < 524288000 && ! get_site_option( 'rtmedia-transcoding-usage-limit-mail' ) ) {
							$this->nearing_usage_limit( $usage_info );
						} elseif ( $usage_info[ $this->api_key ]->remaining > 524288000 && get_site_option( 'rtmedia-transcoding-usage-limit-mail' ) ) {
							update_site_option( 'rtmedia-transcoding-usage-limit-mail', 0 );
						}
						if ( ( ! class_exists( 'RTMediaFFMPEG' ) && ! class_exists( 'RTMediaKaltura' ) ) || class_exists( 'RTMedia' ) ) {
							add_filter( 'rtmedia_after_add_media', array( $this, 'transcoding' ), 10, 3 );
						}
						add_action( 'add_attachment', array( $this, 'wp_transcoding' ), 90 );
						$blacklist = array( 'localhosts', '127.0.10.1' );
						if ( ! in_array( wp_unslash( $_SERVER['HTTP_HOST'] ), $blacklist, true ) ) { // @codingStandardsIgnoreLine
							add_filter( 'rtmedia_plupload_files_filter', array( $this, 'allowed_types' ), 10, 1 );
							add_filter( 'rtmedia_allowed_types', array(
								$this,
								'allowed_types_admin_settings',
							), 10, 1 );
							add_filter( 'rtmedia_valid_type_check', array( $this, 'bypass_video_audio' ), 10, 2 );
						}
					} elseif ( 'deluxe' == strtolower( $usage_info[ $this->api_key ]->plan->name ) ) {
						if ( ( ! class_exists( 'RTMediaFFMPEG' ) && ! class_exists( 'RTMediaKaltura' ) ) || class_exists( 'RTMedia' ) ) {
							add_filter( 'rtmedia_after_add_media', array( $this, 'transcoding' ), 10, 3 );
						}
						add_action( 'add_attachment', array( $this, 'wp_transcoding' ), 90 );
						$blacklist = array( 'localhosts', '127.0.10.1' );
						if ( ! in_array( wp_unslash( $_SERVER['HTTP_HOST'] ), $blacklist, true ) ) { // @codingStandardsIgnoreLine
							add_filter( 'rtmedia_plupload_files_filter', array( $this, 'allowed_types' ), 10, 1 );
							add_filter( 'rtmedia_allowed_types', array(
								$this,
								'allowed_types_admin_settings',
							), 10, 1 );
							add_filter( 'rtmedia_valid_type_check', array( $this, 'bypass_video_audio' ), 10, 2 );
						}
					}
				}
			}
		}

		add_action( 'init', array( $this, 'handle_callback' ), 20 );
		add_action( 'wp_ajax_rtmedia_free_transcoding_subscribe', array( $this, 'free_transcoding_subscribe' ) );
		add_action( 'wp_ajax_rtmedia_unsubscribe_transcoding_service', array( $this, 'unsubscribe_transcoding' ) );
		add_action( 'wp_ajax_rtmedia_hide_transcoding_notice', array( $this, 'hide_transcoding_notice' ), 1 );
		add_action( 'wp_ajax_rtmedia_enter_api_key', array( $this, 'enter_api_key' ), 1 );
		add_action( 'wp_ajax_rtmedia_disable_transcoding', array( $this, 'disable_transcoding' ), 1 );
		add_action( 'wp_ajax_rtmedia_enable_transcoding', array( $this, 'enable_transcoding' ), 1 );
	}

	/**
	 * Send transcoding request and save transcoding job id get in response for uploaded media in buddypress activity.
	 *
	 * @since 1.0
	 *
	 * @param array	$media_ids		Array of multiple attachment ids.
	 * @param array $file_object	Array of file objects.
	 * @param type  $uploaded
	 * @param bool  $autoformat     If true then genrating thumbs only else also trancode video.
	 */
	function transcoding( $media_ids, $file_object, $uploaded, $autoformat = true ) {
		remove_action( 'add_attachment', array( $this, 'wp_transcoding' ) );
		foreach ( $file_object as $key => $single ) {

			$attachment_id = rtmedia_media_id( $media_ids[ $key ] );
			$type_arry        = explode( '.', $single['url'] );
			$type             = strtolower( $type_arry[ count( $type_arry ) - 1 ] );
			$not_allowed_type = array( 'mp3' );
			preg_match( '/video|audio/i', $single['type'], $type_array );

			if ( preg_match( '/video|audio/i', $single['type'], $type_array ) && ! in_array( $single['type'], array( 'audio/mp3' ) ) && ! in_array( $type, $not_allowed_type ) ) {
				$options_video_thumb = $this->get_thumbnails_required( $media_ids[ $key ] );
				if ( '' === $options_video_thumb ) {
					$options_video_thumb = 5;
				}

				$job_type = 'video';

				if ( 'video/mp4' === $single['type'] || 'mp4' === $type ) {
					$autoformat = 'thumbnails';
					$job_type = 'thumbnail';
				}

				$query_args   = array(
					'file_url'    => urlencode( $single['url'] ),
					'callbackurl' => urlencode( trailingslashit( home_url() ) . 'index.php' ),
					'force'       => 0,
					'formats'     => ( true === $autoformat ) ? ( ( 'video' === $type_array[0] ) ? 'mp4' : 'mp3' ) : $autoformat,
					'thumb_count' => $options_video_thumb,
					'rt_id'       => $media_ids[ $key ],
				);
				$args = array(
					'method' 	=> 'POST',
					'sslverify' => false,
					'timeout' 	=> 60,
					'body' 		=> array(
						'api_token' 	=> $this->api_key,
						'job_type' 		=> $job_type,
						'job_for' 		=> 'rtmedia',
						'file_url'		=> urlencode( $single['url'] ),
						'callback_url'	=> urlencode( trailingslashit( home_url() ) . 'index.php' ),
						'force'			=> 0,
						'formats'		=> ( true === $autoformat ) ? ( ( 'video' === $type_array[0] ) ? 'mp4' : 'mp3' ) : $autoformat,
						'thumb_count'	=> $options_video_thumb,
					),
				);

				$transcoding_url = $this->api_url . 'job/';

				$upload_page = wp_remote_post( $transcoding_url, $args );

				if ( ! is_wp_error( $upload_page ) && ( ( isset( $upload_page['response']['code'] ) && ( 200 === intval( $upload_page['response']['code'] ) ) ) ) ) {
					$upload_info = json_decode( $upload_page['body'] );
					if ( isset( $upload_info->status ) && $upload_info->status && isset( $upload_info->job_id ) && $upload_info->job_id ) {
						$job_id = $upload_info->job_id;
						update_rtmedia_meta( $media_ids[ $key ], 'rtmedia-transcoding-job-id', $job_id );
						update_post_meta( $attachment_id, '_rtmedia_transcoding_job_id', $job_id );
						$model = new RTMediaModel();
						$model->update( array( 'cover_art' => '0' ), array( 'id' => $media_ids[ $key ] ) );
					}
				}
				$this->update_usage( $this->api_key );
			}
		}
	}

	/**
	 * Send transcoding request and save transcoding job id get in response for uploaded media in WordPress media library.
	 *
	 * @since 1.0
	 *
	 * @param int    $attachment_id		ID of attachment.
	 * @param string $autoformat		If true then genrating thumbs only else also trancode video.
	 */
	function wp_transcoding( $attachment_id, $autoformat = true ) {

		$post_parent = wp_get_post_parent_id( $attachment_id );
		if ( 0 !== $post_parent ) {
			$post_type 	= get_post_type( $post_parent );
			if ( 'rtmedia_album' === $post_type ) {
				return;
			}
		}

		$path 		= get_attached_file( $attachment_id );
		$url 		= wp_get_attachment_url( $attachment_id );
		$metadata 	= wp_read_video_metadata( $path );

		$type_arry        = explode( '.', $url );
		$type             = strtolower( $type_arry[ count( $type_arry ) - 1 ] );
		$not_allowed_type = array( 'mp3' );
		preg_match( '/video|audio/i', $metadata['mime_type'], $type_array );

		if ( preg_match( '/video|audio/i', $metadata['mime_type'], $type_array ) && ! in_array( $metadata['mime_type'], array( 'audio/mp3' ), true ) && ! in_array( $type, $not_allowed_type, true ) ) {
			$options_video_thumb = $this->get_thumbnails_required( $attachment_id );
			if ( '' === $options_video_thumb ) {
				$options_video_thumb = 5;
			}

			$job_type = 'video';
			/**  FORMAT * */
			if ( 'video/mp4' === $metadata['mime_type'] || 'mp4' === $type ) {
				$autoformat = 'thumbnails';
				$job_type = 'thumbnail';
			}

			$args = array(
				'method' 	=> 'POST',
				'sslverify' => false,
				'timeout' 	=> 60,
				'body' 		=> array(
					'api_token' 	=> $this->api_key,
					'job_type' 		=> $job_type,
					'job_for' 		=> 'wp-media',
					'file_url'		=> urlencode( $url ),
					'callback_url'	=> urlencode( trailingslashit( home_url() ) . 'index.php' ),
					'force'			=> 0,
					'formats'		=> ( true === $autoformat ) ? ( ( 'video' === $type_array[0] ) ? 'mp4' : 'mp3' ) : $autoformat,
					'thumb_count'	=> $options_video_thumb,
				),
			);

			$transcoding_url = $this->api_url . 'job/';

			$upload_page = wp_remote_post( $transcoding_url, $args );

			if ( ! is_wp_error( $upload_page ) && ( ( isset( $upload_page['response']['code'] ) && ( 200 === intval( $upload_page['response']['code'] ) ) ) ) ) {
				$upload_info = json_decode( $upload_page['body'] );
				if ( isset( $upload_info->status ) && $upload_info->status && isset( $upload_info->job_id ) && $upload_info->job_id ) {
					$job_id = $upload_info->job_id;
					update_post_meta( $attachment_id, '_rtmedia_transcoding_job_id', $job_id );
				}
			}
			$this->update_usage( $this->api_key );
		}
	}

	/**
	 * Get number of thumbnails required to generate for video.
	 *
	 * @since 1.0
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
		 * @since 1.0
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
	 * @since 1.0
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
	 * @since 1.0
	 *
	 * @param string $key    Api Key.
	 *
	 * @return boolean $status  If true then key is valid else key is not valid.
	 */
	public function is_valid_key( $key ) {
		$validate_url    = trailingslashit( $this->edd_api_url ) . 'rt-eddsl-api/?rt-eddsl-license-key=' . $key;
		if ( function_exists( 'vip_safe_wp_remote_get' ) ) {
			$validation_page = vip_safe_wp_remote_get( $validate_url );
		} else {
			$validation_page = wp_remote_get( $validate_url ); // @codingStandardsIgnoreLine 
		}
		if ( ! is_wp_error( $validation_page ) ) {
			$validation_info = json_decode( $validation_page['body'] );
			$status          = $validation_info->status;
		} else {
			$status = false;
		}

		return $status;
	}

	/**
	 * Save usage information.
	 *
	 * @since 1.0
	 *
	 * @param string $key  Api key.
	 *
	 * @return array $usage_info  An array containing usage information.
	 */
	public function update_usage( $key ) {
		$usage_url  = trailingslashit( $this->api_url ) . 'usage/' . $key;
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
		update_site_option( 'rtmedia-transcoding-usage', array( $key => $usage_info ) );

		return $usage_info;
	}

	/**
	 * Send email to admin when trancoding quota near to limit.
	 *
	 * @since 1.0
	 *
	 * @param array $usage_details Usage informataion.
	 */
	public function nearing_usage_limit( $usage_details ) {
		$subject = esc_html__( 'rtMedia Transcoding: Nearing quota limit.', 'rtmedia-transcoder' );
		$message = '<p>' . esc_html__( 'You are nearing the quota limit for your rtMedia transcoding service.', 'rtmedia-transcoder' ) . '</p><p>'
		           . esc_html__( 'Following are the details:', 'rtmedia-transcoder' ) . '</p><p><strong>Used:</strong> %s</p><p><strong>'
		           . esc_html__( 'Remaining', 'rtmedia-transcoder' ) . '</strong>: %s</p><p><strong>' . esc_html__( 'Total:', 'rtmedia-transcoder' ) . '</strong> %s</p>';
		$users   = get_users( array( 'role' => 'administrator' ) );
		if ( $users ) {
			$admin_email_ids = array();
			foreach ( $users as $user ) {
				$admin_email_ids[] = $user->user_email;
			}
			add_filter( 'wp_mail_content_type', function() { return 'text/html'; } );
			wp_mail( $admin_email_ids, $subject, sprintf( $message, size_format( $usage_details[ $this->api_key ]->used, 2 ), size_format( $usage_details[ $this->api_key ]->remaining, 2 ), size_format( $usage_details[ $this->api_key ]->total, 2 ) ) );
		}
		update_site_option( 'rtmedia-transcoding-usage-limit-mail', 1 );
	}

	/**
	 * Send email to admin when trancoding quota is over.
	 *
	 * @since 1.0
	 */
	public function usage_quota_over() {
		$usage_details = get_site_option( 'rtmedia-transcoding-usage' );
		if ( ! $usage_details[ $this->api_key ]->remaining ) {
			$subject = esc_html__( 'rtMedia Transcoding: Usage quota over.', 'rtmedia-transcoder' );
			$message = '<p>' . esc_html__( 'Your usage quota is over. Upgrade your plan' , 'rtmedia-transcoder' ) . '</p><p>' .
			           esc_html__( 'Following are the details:', 'rtmedia-transcoder' ) . '</p><p><strong>' . esc_html__( 'Used:' , 'rtmedia-transcoder' ) .
			           '</strong> %s</p><p><strong>' . esc_html__( 'Remaining' , 'rtmedia-transcoder' ) . '</strong>: %s</p><p><strong>' . esc_html__( 'Total:', 'rtmedia-transcoder' ) . '</strong> %s</p>';
			$users   = get_users( array( 'role' => 'administrator' ) );
			if ( $users ) {
				foreach ( $users as $user ) {
					$admin_email_ids[] = $user->user_email;
				}
				add_filter( 'wp_mail_content_type', function() { return 'text/html'; } );
				wp_mail( $admin_email_ids, $subject, sprintf( $message, size_format( $usage_details[ $this->api_key ]->used, 2 ), 0, size_format( $usage_details[ $this->api_key ]->total, 2 ) ) );
			}
			update_site_option( 'rtmedia-transcoding-usage-limit-mail', 1 );
		}
	}

	/**
	 * Check whether key is valid or not and save api key.
	 *
	 * @since 1.0
	 */
	public function save_api_key() {

		$is_api_key_updated = filter_input( INPUT_GET, 'api_key_updated', FILTER_SANITIZE_STRING );

		if ( $is_api_key_updated ) {
			if ( is_multisite() ) {
				add_action( 'network_admin_notices', array( $this, 'successfully_subscribed_notice' ) );
			}

			add_action( 'admin_notices', array( $this, 'successfully_subscribed_notice' ) );
		}

		$apikey		= filter_input( INPUT_GET, 'apikey', FILTER_SANITIZE_STRING );
		$page		= filter_input( INPUT_GET, 'page',	 FILTER_SANITIZE_STRING );
		$is_update	= filter_input( INPUT_GET, 'update', FILTER_SANITIZE_STRING );

		if ( ! empty( $apikey ) && is_admin() && ! empty( $page ) && ( 'rtmedia-transcoder' === $page ) && $this->is_valid_key( $apikey ) ) {
			if ( $this->api_key && ! ( isset( $is_update ) && $is_update ) ) {
				$unsubscribe_url = trailingslashit( $this->edd_api_url );

				$args = array(
				        'method' 	=> 'POST',
				        'sslverify' => false,
				        'timeout'	=> 5,
				        'body' 		=> array(
			                'trans_type'    => 'cancel-license',
			                'license-key' 	=> $this->api_key,
				        ),
				);
				$unsubscribe = wp_remote_post( $unsubscribe_url, $args );
			}

			update_site_option( 'rtmedia-transcoding-api-key', $apikey );
			update_site_option( 'rtmedia-transcoding-api-key-stored', $apikey );

			$usage_info  = $this->update_usage( $apikey );
			$return_page = add_query_arg( array(
				'page'            => 'rtmedia-transcoder',
				'api_key_updated' => $usage_info->plan->name ? $usage_info->plan->name : 'free',
			), admin_url( 'admin.php' ) );
			wp_safe_redirect( esc_url_raw( $return_page ) );

			die();
		}
	}

	/**
	 * Allow user to upload other types media files.
	 *
	 * @since 1.0
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
	 * @since 1.0
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
	 * @since 1.0
	 */
	public function successfully_subscribed_notice() {
	?>
		<div class="updated">
		<p>
			<?php esc_html_e( 'You have successfully subscribed for the ', 'rtmedia-transcoder' ) ?>
			<strong>
				<?php printf( '%s', esc_html( sanitize_text_field( wp_unslash( $_GET['api_key_updated'] ) ) ) ); // @codingStandardsIgnoreLine ?>
			</strong>
			<?php esc_html_e( ' plan', 'rtmedia-transcoder' ) ?>
		</p>
		</div>
	<?php
	}

	/**
	 * Display usage widget in sidebar on rtmedia transcoder settings page.
	 *
	 * @since 1.0
	 */
	public function usage_widget() {
		$usage_details = get_site_option( 'rtmedia-transcoding-usage' );
		$content       = '';
		if ( $usage_details && isset( $usage_details[ $this->api_key ]->status ) && $usage_details[ $this->api_key ]->status ) {
			if ( isset( $usage_details[ $this->api_key ]->plan->name ) ) {
				$content .= '<p><strong>' . esc_html__( 'Current Plan', 'rtmedia-transcoder' ) . ':</strong> ' . esc_html( $usage_details[ $this->api_key ]->plan->name ) . ( $usage_details[ $this->api_key ]->sub_status ? '' : ' (' . esc_html__( 'Unsubscribed', 'rtmedia-transcoder' ) . ')' ) . '</p>';
			}
			if ( isset( $usage_details[ $this->api_key ]->used ) ) {
				$content .= '<p><span class="transcoding-used"></span><strong>' . esc_html__( 'Used', 'rtmedia-transcoder' ) . ':</strong> ' . ( ( $used_size = size_format( $usage_details[ $this->api_key ]->used, 2 ) ) ? esc_html( $used_size ) : '0MB' ) . '</p>';
			}
			if ( isset( $usage_details[ $this->api_key ]->remaining ) ) {
				$content .= '<p><span class="transcoding-remaining"></span><strong>' . esc_html__( 'Remaining', 'rtmedia-transcoder' ) . ':</strong> ';
				if ( $usage_details[ $this->api_key ]->remaining >= 0 ) {
					$content .= size_format( $usage_details[ $this->api_key ]->remaining, 2 );
				} elseif ( $usage_details[ $this->api_key ]->remaining <= -1 ) {
					$content .= 'Unlimited';
				} else {
					$content .= '0MB';
				}
			}
			if ( isset( $usage_details[ $this->api_key ]->total ) ) {
				$content .= '<p><strong>' . esc_html__( 'Total', 'rtmedia-transcoder' ) . ':</strong> ';
				if ( $usage_details[ $this->api_key ]->total >= 0 ) {
					$content .= size_format( $usage_details[ $this->api_key ]->total, 2 );
				} elseif ( $usage_details[ $this->api_key ]->total <= -1 ) {
					$content .= 'Unlimited';
				} else {
					$content .= '';
				}
			}
			$usage = new rtProgress();

			/**
			 * If plan is deluxe/unlimited show progress bar gray all the time, to do
			 * this override `used` and `total` variable manually
			 */
			if ( ! empty( $usage_details[ $this->api_key ]->plan->name ) && ( 'deluxe' === strtolower( $usage_details[ $this->api_key ]->plan->name ) ) ) {
				$usage_details[ $this->api_key ]->used = 0;
				$usage_details[ $this->api_key ]->total = 1;
			}

			$content .= $usage->progress_ui( $usage->progress( $usage_details[ $this->api_key ]->used, $usage_details[ $this->api_key ]->total ), false );
			if ( ( 0 <= $usage_details[ $this->api_key ]->remaining ) && ( -1 !== $usage_details[ $this->api_key ]->remaining ) ) {
				$content .= '<div class="error below-h2"><p>' . esc_html__( 'Your usage limit has been reached. Upgrade your plan.', 'rtmedia-transcoder' ) . '</p></div>';
			}
		} else {
			$content .= '<div class="error below-h2"><p>' . esc_html__( 'Your API key is not valid or is expired.', 'rtmedia-transcoder' ) . '</p></div>';
		}
		?>
		<div class="postbox" id="rtmedia-transcoding-usage">
	        <h3 class="hndle">
				<span>
					<?php esc_html_e( 'Transcoding Usage', 'buddypress-media' ); ?>
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
	 * @since 1.0
	 *
	 * @param array $post_array  Attachment data.
	 *
	 * @return url
	 */
	public function add_media_thumbnails( $post_array ) {
		$post_id 				= $post_array['post_id'];
		$post_info              = get_post( $post_id );
		$post_date_string       = new DateTime( $post_info->post_date );
		$post_date              = $post_date_string->format( 'Y-m-d G:i:s' );
		$post_date_thumb_string = new DateTime( $post_info->post_date );
		$post_date_thumb        = $post_date_thumb_string->format( 'Y/m/' );
		$post_thumbs            = $post_array;
		$post_thumbs_array      = maybe_unserialize( $post_thumbs );
		$largest_thumb_size     = 0;

		if ( 'rtmedia' === $post_thumbs_array['job_for'] ) {
			$model                  = new RTMediaModel();
			$media                  = $model->get( array( 'media_id' => $post_id ) );
			$media_id               = $media[0]->id;
		}

		$largest_thumb          = false;
		$upload_thumbnail_array = array();

		foreach ( $post_thumbs_array['thumbnail'] as $thumbs => $thumbnail ) {
			$thumbresource            = function_exists( 'vip_safe_wp_remote_get' ) ? vip_safe_wp_remote_get( $thumbnail ) : wp_remote_get( $thumbnail ); // @codingStandardsIgnoreLine
			$thumbinfo                = pathinfo( $thumbnail );
			$temp_name                = $thumbinfo['basename'];
			$temp_name                = urldecode( $temp_name );
			$temp_name_array          = explode( '/', $temp_name );
			$temp_name                = $temp_name_array[ count( $temp_name_array ) - 1 ];
			$thumbinfo['basename']    = $temp_name;
			$thumb_upload_info        = wp_upload_bits( $thumbinfo['basename'], null, $thumbresource['body'] );
			$file 					  = _wp_relative_upload_path( $thumb_upload_info['file'] );

			if ( $file ) {
				$upload_thumbnail_array[] = $file;
			}

			$current_thumb_size = @filesize( $thumb_upload_info['file'] ); // @codingStandardsIgnoreLine

			if ( $current_thumb_size >= $largest_thumb_size ) {
				$largest_thumb_size = $current_thumb_size;
				$largest_thumb      = $thumb_upload_info['url'];
				$largest_thumb_url	= $file ? $file : '';
			}
		}

		update_post_meta( $post_id, '_rt_media_source', 	$post_thumbs_array['job_for'] );
		update_post_meta( $post_id, '_rt_media_thumbnails',	$upload_thumbnail_array );

		if ( $largest_thumb_url ) {
			update_post_meta( $post_id, '_rt_media_video_thumbnail', $largest_thumb_url );
		}

		return $largest_thumb;
	}

	/**
	 * Save transcoded media files.
	 *
	 * @since 1.0
	 * @param array $file_post_array	Transcoded files.
	 * @param int   $attachment_id		ID of attachment.
	 */
	public function add_transcoded_files( $file_post_array, $attachment_id ) {
		$transcoded_files = false;
		if ( isset( $file_post_array ) && is_array( $file_post_array ) && ( count( $file_post_array > 0 ) ) ) {
			foreach ( $file_post_array as $key => $format ) {
				if ( is_array( $format ) && ( count( $format > 0 ) ) ) {
					foreach ( $format as $each => $file ) {
						if ( isset( $file ) ) {
							$download_url                   = urldecode( urldecode( $file ) );
							$new_wp_attached_file_pathinfo 	= pathinfo( $download_url );
							$post_mime_type                	= 'mp4' === $new_wp_attached_file_pathinfo['extension'] ? 'video/mp4' : 'audio/mp3';
							try {
								$file_bits = function_exists( 'wpcom_vip_file_get_contents' ) ? wpcom_vip_file_get_contents( $download_url ) : file_get_contents( $download_url ); // @codingStandardsIgnoreLine
							} catch ( Exception $e ) {
								$flag = $e->getMessage();
							}
							if ( $file_bits ) {

								/* add_filter( 'upload_dir', array( $this, 'upload_dir' ) ); */
								$upload_info = wp_upload_bits( $new_wp_attached_file_pathinfo['basename'], null, $file_bits );

								$uploaded_file = _wp_relative_upload_path( $upload_info['file'] );

								if ( $uploaded_file ) {
									$transcoded_files[ $key ][] = $uploaded_file;
								}
							} else {
								$flag = esc_html__( 'Could not read file.', 'rtmedia-transcoder' );
							}
						}
					}
				}
			}
		}
		if ( ! empty( $transcoded_files ) ) {
			update_post_meta( $attachment_id, '_rt_media_transcoded_files', $transcoded_files );
		}
	}

	/**
	 * Get post id from meta key and value.
	 *
	 * @since 1.0
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
	 * @since 1.0
	 */
	public function handle_callback() {
		require_once( ABSPATH . 'wp-admin/includes/image.php' );
		// @codingStandardsIgnoreStart
		if ( isset( $_REQUEST['job_for'] ) && ( 'wp-media' == $_REQUEST['job_for'] ) ) {
			if ( isset( $_REQUEST['job_id'] ) ) {
				$has_thumbs = isset( $_POST['thumbnail'] ) ? true : false;
				$flag       = false;
				global $wpdb;

				$id = $this->get_post_id_by_meta_key_and_value( '_rtmedia_transcoding_job_id', $_REQUEST['job_id'] );

				if ( isset( $id ) && is_numeric( $id ) ) {
					$attachment_id      	= $id;

					$post_array 			= $_POST;
					$post_array['post_id'] 	= $attachment_id;

					if ( $has_thumbs ) {
						$thumbnail = $this->add_media_thumbnails( $post_array );
					}

					if ( isset( $_POST['format'] ) && 'thumbnail' === sanitize_text_field( wp_unslash( $_POST['format'] ) ) ) {
						die();
					}
					$uploded_files = $this->add_transcoded_files( $_REQUEST['files'], $attachment_id );

				} else {
					$flag = esc_html__( 'Something went wrong. The required attachment id does not exists. It must have been deleted.', 'rtmedia-transcoder' );
				}

				$this->update_usage( $this->api_key );

				if ( isset( $_SERVER['REMOTE_ADDR'] ) && ( '4.30.110.155' === $_SERVER['REMOTE_ADDR'] ) ) {
					$mail = true;
				} else {
					$mail = false;
				}

				if ( $flag && $mail ) {
					$download_link = esc_url( add_query_arg( array(
						'job_id'       => sanitize_text_field( wp_unslash( $_GET['job_id'] ) ),
						'download_url' => esc_url( $_GET['download_url'] ), // @codingStandardsIgnoreLine
					), home_url() ) );
					$subject       = esc_html__( 'rtMedia Transcoding: Download Failed', 'rtmedia-transcoder' );
					$message       = '<p><a href="' . esc_url( get_edit_post_link( $attachment_id ) ) . '">' . esc_html__( 'Media', 'rtmedia-transcoder' ) . '</a> ' .
					                 esc_html__( ' was successfully encoded but there was an error while downloading:', 'rtmedia-transcoder' ) . '</p><p><code>' .
					                 esc_html( $flag ) . '</code></p><p>' . esc_html__( 'You can ', 'rtmedia-transcoder' ) . '<a href="' . esc_url( $download_link ) . '">'
									. esc_html__( 'retry the download', 'rtmedia-transcoder' ) . '</a>.</p>';
					$users         = get_users( array( 'role' => 'administrator' ) );
					if ( $users ) {
						foreach ( $users as $user ) {
							$admin_email_ids[] = $user->user_email;
						}
						add_filter( 'wp_mail_content_type', function() { return 'text/html'; } );
						wp_mail( $admin_email_ids, $subject, $message );
					}
					echo esc_html( $flag );
				} elseif ( $flag ) {
					echo esc_html( $flag );
				} else {
					esc_html_e( 'Done', 'rtmedia-transcoder' );
				}
				die();
			}
		} else {
			if ( isset( $_REQUEST['job_id'] ) ) {
				$has_thumbs = isset( $_POST['thumbnail'] ) ? true : false;
				$flag       = false;
				global $wpdb;
				$model        = new RTDBModel( 'rtm_media_meta', false, 10, true );
				$meta_details = $model->get( array(
					'meta_value' => sanitize_text_field( wp_unslash( $_REQUEST['job_id'] ) ),
					'meta_key'   => 'rtmedia-transcoding-job-id',
				) );
				if ( ! isset( $meta_details[0] ) ) {
					$id = intval( $_REQUEST['rt_id'] );
				} else {
					$id = $meta_details[0]->media_id;
				}
				if ( isset( $id ) && is_numeric( $id ) ) {
					$model              = new RTMediaModel();
					$media              = $model->get_media( array( 'id' => $id ), 0, 1 );
					$this->media_author = $media[0]->media_author;
					$attachment_id      = $media[0]->media_id;

					$post_array 			= $_POST;
					$post_array['post_id'] 	= $attachment_id;

					if ( $has_thumbs ) {
						$cover_art = $this->add_media_thumbnails( $post_array );
					}

					if ( isset( $_POST['format'] ) && 'thumbnail' === sanitize_text_field( wp_unslash( $_POST['format'] ) ) ) {
						die();
					}

					$uploded_files = $this->add_transcoded_files( $_REQUEST['files'], $attachment_id );

				} else {
					$flag = esc_html__( 'Something went wrong. The required attachment id does not exists. It must have been deleted.', 'rtmedia-transcoder' );
				}

				$this->update_usage( $this->api_key );

				if ( isset( $_SERVER['REMOTE_ADDR'] ) && ( '4.30.110.155' === $_SERVER['REMOTE_ADDR'] ) ) {
					$mail = true;
				} else {
					$mail = false;
				}

				if ( $flag && $mail ) {
					$download_link = esc_url( add_query_arg( array(
						'job_id'       => sanitize_text_field( wp_unslash( $_GET['job_id'] ) ),
						'download_url' => esc_url( $_GET['download_url'] ), // @codingStandardsIgnoreLine
					), home_url() ) );
					$subject       = esc_html__( 'rtMedia Transcoding: Download Failed', 'rtmedia-transcoder' );
					$message       = '<p><a href="' . esc_url( get_edit_post_link( $attachment_id ) ) . '">' . esc_html__( 'Media', 'rtmedia-transcoder' ) . '</a> ' .
					                 esc_html__( ' was successfully encoded but there was an error while downloading:', 'rtmedia-transcoder' ) . '</p><p><code>' .
					                 esc_html( $flag ) . '</code></p><p>' . esc_html__( 'You can ', 'rtmedia-transcoder' ) . '<a href="' . esc_url( $download_link ) . '">'
									. esc_html__( 'retry the download', 'rtmedia-transcoder' ) . '</a>.</p>';
					$users         = get_users( array( 'role' => 'administrator' ) );
					if ( $users ) {
						foreach ( $users as $user ) {
							$admin_email_ids[] = $user->user_email;
						}
						add_filter( 'wp_mail_content_type', function() { return 'text/html'; } );
						wp_mail( $admin_email_ids, $subject, $message );
					}
					echo esc_html( $flag );
				} elseif ( $flag ) {
					echo esc_html( $flag );
				} else {
					esc_html_e( 'Done', 'rtmedia-transcoder' );
				}
				die();
			}
		}
		// @codingStandardsIgnoreEnd
	}

	/**
	 * Hide notices.
	 *
	 * @since 1.0
	 */
	public function hide_transcoding_notice() {
		update_site_option( 'rtmedia-transcoding-service-notice', true );
		update_site_option( 'rtmedia-transcoding-expansion-notice', true );
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
			echo wp_json_encode( array( 'error' => esc_html__( 'Please enter the api key.', 'rtmedia-transcoder' ) ) );
		}
		die();
	}

	/**
	 * Disable transcoding.
	 *
	 * @since 1.0
	 */
	public function disable_transcoding() {
		update_site_option( 'rtmedia-transcoding-api-key', '' );
		esc_html_e( 'Transcoding disabled successfully.', 'rtmedia-transcoder' );
		die();
	}

	/**
	 * Enable transcoding.
	 *
	 * @since 1.0
	 */
	function enable_transcoding() {
		update_site_option( 'rtmedia-transcoding-api-key', $this->stored_api_key );
		esc_html_e( 'Transcoding enabled successfully.', 'rtmedia-transcoder' );
		die();
	}

	/**
	 * Return upload path of media uploaded through rtMedia plugin.
	 *
	 * @since 1.0
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

}
