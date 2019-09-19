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
	 * @param WP_REST_Request $request  Object of WP_REST_Request
	 *
	 * @return array|bool
	 */
	public function get_media_data( WP_REST_Request $request ) {
		$media_id = $request->get_param( 'id' );

		// Check media id.
		if ( ! empty( $media_id ) ) {
			// Check if thumbnail exists for the passed attachment.
			$thumbnail_id = get_post_thumbnail_id( $media_id );
			if ( ! empty( $thumbnail_id ) && true === (bool) get_post_meta( $thumbnail_id, 'amp_is_poster', true ) ) {
				return [
					'poster' => get_the_post_thumbnail_url( $media_id )
				];
			}
		} else {
			return false;
		}

		return false;
	}

}
