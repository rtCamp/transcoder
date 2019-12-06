<?php

/**
 * Class Transcoder_Rest_Routes
 * Handle REST Routes for Transcoder.
 */
class Transcoder_Rest_Routes extends WP_REST_Controller {

	public $version = 1;

	public $namespace_prefix = 'transcoder/v';

	/**
	 * Function to register routes.
	 */
	public function register_routes() {

		// Register `amp-media` route to get media poster info.
		register_rest_route(
			$this->namespace_prefix . $this->version,
			'/amp-media/(?P<id>\d+)',
			array(
				'methods'  => WP_REST_Server::READABLE,
				'callback' => array( $this, 'get_media_data' ),
			) );
	}

	/**
	 * Return poster url for requested media if exists.
	 *
	 * @param WP_REST_Request $request Object of WP_REST_Request
	 *
	 * @return array|bool
	 */
	public function get_media_data( WP_REST_Request $request ) {
		$media_id = $request->get_param( 'id' );

		// Check media id.
		if ( empty( $media_id ) ) {
			return false;
		}

		// Check if thumbnail and transcoded file exist for the passed attachment.
		$thumbnail_id         = get_post_thumbnail_id( $media_id );
		$transcoded_url_data  = get_post_meta( $media_id, '_rt_media_transcoded_files' );
		$transcoded_url_array = ! empty( $transcoded_url_data[0]['mp4'] ) ? $transcoded_url_data[0]['mp4'] : [];

		// Return false if the thumbnail id or the transcoded URL is not present.
		if ( empty( $thumbnail_id ) || empty( $transcoded_url_array ) || ! is_array( $transcoded_url_array ) ) {
			return false;
		}

		$final_transcoded_urls = [
			'medium' => $this->get_full_transcoded_url( $transcoded_url_array[0] ),
			'low'    => $this->get_full_transcoded_url( $transcoded_url_array[1] ),
			'high'   => $this->get_full_transcoded_url( $transcoded_url_array[2] ),
		];


		if ( true !== (bool) get_post_meta( $thumbnail_id, 'amp_is_poster', true ) ) {
			return false;
		}

		return [
			'poster' => get_the_post_thumbnail_url( $media_id ),
			'low'    => [ 'transcodedMedia' => $final_transcoded_urls['low'] ],
			'medium' => [ 'transcodedMedia' => $final_transcoded_urls['medium'] ],
			'high'   => [ 'transcodedMedia' => $final_transcoded_urls['high'] ],
		];
	}

	/**
	 * Get the full transcoded URL with upload directory path.
	 *
	 * @param $transcoded_url Transcodes URL
	 *
	 * @return string Full Transcoded URL with upload directory path.
	 */
	public function get_full_transcoded_url( $transcoded_url ) {

		$uploads = wp_get_upload_dir();

		// Get URL for the transcoded video.
		if ( 0 === strpos( $transcoded_url, $uploads['baseurl'] ) ) {
			$final_file_url = $transcoded_url;
		} else {
			$final_file_url = trailingslashit( $uploads['baseurl'] ) . $transcoded_url;
		}

		return $final_file_url;

	}

}
