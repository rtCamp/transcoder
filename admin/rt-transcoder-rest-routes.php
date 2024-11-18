<?php
/**
 * Class Transcoder_Rest_Routes
 *
 * @package transcoder
 */

/**
 * Handle REST Routes for Transcoder.
 */
class Transcoder_Rest_Routes extends WP_REST_Controller {

	/**
	 * Version of REST route.
	 *
	 * @var int
	 */
	public $version = 1;

	/**
	 * Prefix for API endpoint namespace.
	 *
	 * @var string
	 */
	public $namespace_prefix = 'transcoder/v';

	/**
	 * RT Transcoder Handler object.
	 * 
	 * @var RT_Transcoder_Handler
	 */
	public $rt_transcoder_handler;

	/**
	 * Constructor
	 *
	 * @since   1.0.0
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {
		$this->rt_transcoder_handler = new RT_Transcoder_Handler( true );
	}

	/**
	 * Function to register routes.
	 */
	public function register_routes() {

		// Register `amp-media` route to get media poster info.
		register_rest_route(
			$this->namespace_prefix . $this->version,
			'/amp-media/(?P<id>\d+)',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_media_data' ),
				'permission_callback' => '__return_true',
			)
		);

		// Register `amp-rtmedia` route to get media poster info related to rtMedia.
		register_rest_route(
			$this->namespace_prefix . $this->version,
			'/amp-rtmedia',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_rtmedia_data' ),
				'permission_callback' => '__return_true',
			)
		);

		// Register `transcoding-status` route to get transcoding status of a media.
		register_rest_route(
			$this->namespace_prefix . $this->version,
			'/transcoding-status/(?P<id>\d+)',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_transcoding_status' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Return poster url for requested media if exists.
	 *
	 * @param WP_REST_Request $request Object of WP_REST_Request.
	 *
	 * @return array|bool REST API response.
	 */
	public function get_media_data( WP_REST_Request $request ) {
		$media_id = $request->get_param( 'id' );

		return $this->get_media_data_by_id( $media_id );
	}

	/**
	 * Return poster url for requested media related to rtMedia if exists.
	 *
	 * @param WP_REST_Request $request Object of WP_REST_Request.
	 *
	 * @return array|bool REST API response.
	 */
	public function get_rtmedia_data( WP_REST_Request $request ) {
		if ( ! function_exists( 'rtmedia_media_id' ) || empty( $request['media_ids'] ) ) {
			return false;
		}
		$media_ids = explode( ',', $request['media_ids'] );

		$response = array();
		foreach ( $media_ids as $media_id ) {
			// Convert rtMedia ID to post ID.
			$attachment_id = rtmedia_media_id( $media_id );
			if ( empty( $attachment_id ) ) {
				$response[ $media_id ] = 'invalid';
				continue;
			}

			// Check if media is eligible to be transcoded.
			$rt_transcoding_job_id = get_post_meta( $attachment_id, '_rt_transcoding_job_id', true );
			if ( empty( $rt_transcoding_job_id ) ) {
				$response[ $media_id ] = 'invalid';
				continue;
			}

			$res = $this->get_media_data_by_id( $attachment_id );
			if ( false !== $res ) {
				$response[ $media_id ] = $res;
				continue;
			}

			// Get transcoding status to detect if the site doesn't have HTTP auth or some other restrictions.
			$status = json_decode( rtt_get_transcoding_status( $attachment_id ), true );
			if ( ! empty( $status['message'] ) && false !== strpos( $status['message'], 'Transcoder failed to transcode this file' ) ) {
				$response[ $media_id ] = 'invalid';
			}
		}

		return ( ! empty( $response ) ) ? $response : false;
	}

	/**
	 * Return poster URL of a media from media_id.
	 *
	 * @param int $media_id Media ID.
	 *
	 * @return array|bool Poster URL and transcoded media URL or False.
	 */
	private function get_media_data_by_id( $media_id ) {
		// Check media id.
		if ( empty( $media_id ) ) {
			return false;
		}

		// Check if thumbnail and transcoded file exist for the passed attachment.
		$thumbnail_id   = get_post_thumbnail_id( $media_id );
		$transcoded_url = get_post_meta( $media_id, '_rt_media_transcoded_files', true );

		if ( empty( $thumbnail_id ) || empty( $transcoded_url ) ) {
			return false;
		}

		// Get transcoded video path.
		$transcoded_url = empty( $transcoded_url['mp4'][0] ) ? '' : $transcoded_url['mp4'][0];
		$uploads        = wp_get_upload_dir();

		// Get URL for the transcoded video.
		if ( 0 === strpos( $transcoded_url, $uploads['baseurl'] ) ) {
			$final_file_url = $transcoded_url;
		} else {
			$final_file_url = trailingslashit( $uploads['baseurl'] ) . $transcoded_url;
		}

		if ( true !== (bool) get_post_meta( $thumbnail_id, 'amp_is_poster', true ) || empty( $final_file_url ) ) {
			return false;
		}

		return array(
			'poster'          => get_the_post_thumbnail_url( $media_id ),
			'transcodedMedia' => $final_file_url,
		);
	}

	/**
	 * Return transcoding status of a media.
	 *
	 * @since 1.2
	 *
	 * @param WP_REST_Request $request REST request object.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_transcoding_status( WP_REST_Request $request ) {
		$post_id = (int) $request['id'];

		if ( empty( $post_id ) ) {
			return new WP_Error(
				'invalid_post_id',
				__( 'Something went wrong. Please try again!', 'transcoder' ),
				array( 'status' => 400 )
			);
		}

		$job_id            = get_post_meta( $post_id, '_rt_transcoding_job_id', true );
		$transcoded_files  = get_post_meta( $post_id, '_rt_media_transcoded_files', true );
		$transcoded_thumbs = get_post_meta( $post_id, '_rt_media_thumbnails', true );
		$thumbnail         = get_post_meta( $post_id, '_rt_media_video_thumbnail', true );

		$status_url = trailingslashit( $this->rt_transcoder_handler->transcoding_api_url ) . 'job/status/' . $job_id . '/' . get_site_option( 'rt-transcoding-api-key-stored' );

		$message  = '';
		$response = array();
		$status   = 'running';
		$progress = 0;

		if ( ! empty( $transcoded_files ) && ! empty( $transcoded_thumbs ) ) {

			$message    = __( 'Your file is transcoded successfully. Please refresh the page.', 'transcoder' );
			$status     = 'Success';
			$progress   = 100;
			$upload_dir = wp_upload_dir();

			$response['files']     = $upload_dir['baseurl'] . '/' . $transcoded_files['mp4'][0];
			$response['thumbnail'] = $upload_dir['baseurl'] . '/' . $thumbnail;

			global $wpdb;
			$media_id = wp_cache_get( 'post_' . $post_id, 'transcoder' );
			if ( empty( $media_id ) ) {
				$results  = $wpdb->get_results( $wpdb->prepare( 'SELECT id FROM ' . $wpdb->prefix . 'rt_rtm_media WHERE media_id = %d', $post_id ), OBJECT ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$media_id = $results[0]->id;
				wp_cache_set( 'post_' . $post_id, $media_id, 'transcoder', 3600 );
			}
			$response['media_id'] = $media_id;

		} else {

			if ( function_exists( 'vip_safe_wp_remote_get' ) ) {
				$status_page = vip_safe_wp_remote_get( $status_url, '', 3, 3 );
			} else {
				$status_page = wp_safe_remote_get( $status_url, array( 'timeout' => 120 ) ); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.wp_remote_get_wp_remote_get, WordPressVIPMinimum.Performance.RemoteRequestTimeout.timeout_timeout
			}

			if ( ! is_wp_error( $status_page ) ) {
				$status_info = json_decode( $status_page['body'] );
			} else {
				$status_info = null;
			}

			$messages = array(
				'null-response'  => __( 'Looks like the server is taking too long to respond, Please try again in sometime.', 'transcoder' ),
				'failed'         => __( 'Unfortunately, Transcoder failed to transcode this file.', 'transcoder' ),
				'running'        => __( 'Your file is getting transcoded. Please refresh after some time.', 'transcoder' ),
				'in-queue'       => __( 'This file is still in the queue. Please refresh after some time.', 'transcoder' ),
				'receiving-back' => __( 'Your server should be ready to receive the transcoded file.', 'transcoder' ),
				'success'        => __( 'Your file is transcoded successfully. Please refresh the page.', 'transcoder' ),
			);

			/**
			 * Filters the transcoding process status messages.
			 *
			 * @since 1.2
			 *
			 * @param array $messages Default transcoding process status messages.
			 */
			$messages = apply_filters( 'rtt_transcoder_status_message', $messages );

			if ( empty( $status_info ) || ! is_object( $status_info ) || empty( $status_info->job_id ) ) {

				$message = $messages['null-response'];

			} elseif ( ! empty( $status_info->error_code ) && ! empty( $status_info->error_msg ) ) {

				$message = $messages['failed'];

			} elseif ( 'processing' === $status_info->status && empty( $status_info->error_code ) && empty( $status_info->error_msg ) ) {

				$message  = $messages['running'];
				$progress = ! empty( $status_info->progress ) ? floatval( $status_info->progress ) : 0;

			} elseif ( 'processing' !== $status_info->status && '100' !== $status_info->progress && empty( $status_info->error_code ) && empty( $status_info->error_msg ) ) {

				$message  = $messages['in-queue'];
				$progress = ! empty( $status_info->progress ) ? floatval( $status_info->progress ) : 0;

			} elseif ( 'processed' === $status_info->status && 'video' === $status_info->job_type && ( empty( $transcoded_files ) || empty( $transcoded_thumbs ) ) ) {

				$message  = $messages['receiving-back'];
				$progress = 100;

			} elseif ( 'processed' === $status_info->status && ! empty( $transcoded_thumbs ) && ( ! empty( $transcoded_files ) || 'thumbnail' === $status_info->job_type ) ) {

				$message  = $messages['success'];
				$status   = 'Success';
				$progress = 100;

				$upload_dir            = wp_upload_dir();
				$response['files']     = $upload_dir['baseurl'] . '/' . $transcoded_files['mp4'][0];
				$response['thumbnail'] = $upload_dir['baseurl'] . '/' . $thumbnail;

				global $wpdb;
				$results              = $wpdb->get_results( $wpdb->prepare( 'SELECT id FROM ' . $wpdb->prefix . 'rt_rtm_media WHERE media_id = %d', $post_id ), OBJECT ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$response['media_id'] = $results[0]->id;

			} elseif ( 'processed' === $status_info->status && 'pdf' === $status_info->job_type ) {
				$message  = $messages['success'];
				$status   = 'Success';
				$progress = 100;

			} elseif ( ! empty( $status_info ) ) {
				$message = $status_info->status;
			}
		}

		$response['message']  = esc_html( $message );
		$response['status']   = esc_html( $status );
		$response['progress'] = $progress;

		return rest_ensure_response( $response );
	}
}
