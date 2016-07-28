<?php
add_shortcode( 'rt_media', 'rt_media_shortcode' );

/**
 * rtMedia short code to display media file in content
 * @param  array $attrs
 * @param  string $content
 * @return string
 */
function rt_media_shortcode( $attrs, $content = '' ) {

	if ( empty( $attrs['attachment_id'] ) ) {
	    return;
	}

	$attachment_id = $attrs['attachment_id'];

	$type = get_post_mime_type( $attachment_id );

	if ( empty( $type ) ) {
		return false;
	}

	$mime_type = explode( '/', $type );

	if ( 'video' === $mime_type[0] ) {
		$media_url 	= rt_media_get_video_url( $attachment_id );

		$poster 	= rt_media_get_video_thumbnail( $attachment_id );

		$video_shortcode_attributes = 'src="' . $media_url . '"';

		$video_poster_attributes = 'poster="' . $poster . '"';

		foreach ( $attrs as $key => $value ) {
		    $video_shortcode_attributes .= ' ' . $key . '="' . $value . '"';
		}

		return do_shortcode( "[video {$video_shortcode_attributes} {$video_poster_attributes}]" );
	} elseif ( 'audio' === $mime_type[0] ) {

		/**
		 * @todo  Get transcoded file's URL
		 */
		$media_url 	= wp_get_attachment_url( $attachment_id );

		$audio_shortcode_attributes = 'src="' . $media_url . '"';

		foreach ( $attrs as $key => $value ) {
		    $audio_shortcode_attributes .= ' ' . $key . '="' . $value . '"';
		}

		return do_shortcode( "[audio {$audio_shortcode_attributes}]" );
	}
}

/**
 * Give the transcoded video's thumbnail stored in videos meta
 * @param  int $attachment_id
 * @return string 				returns image file url on success
 */
function rt_media_get_video_thumbnail( $attachment_id ) {

	if ( empty( $attachment_id ) ) {
	    return;
	}

	$thumbnails = get_post_meta( $attachment_id, '_rt_media_video_thumbnail', true );

	if ( ! empty( $thumbnails ) ) {
		$uploads = wp_get_upload_dir();
		return $uploads['baseurl'] . '/' . $thumbnails;
	}

	return false;

}

/**
 * Give the transcoded video URL of attachment
 * @param  int $attachment_id
 * @return string                returns video file url on success
 */
function rt_media_get_video_url( $attachment_id ) {

	if ( empty( $attachment_id ) ) {
	    return;
	}

	$videos = get_post_meta( $attachment_id, '_rt_media_transcoded_files', true );

	if ( isset( $videos['mp4'] ) && is_array( $videos['mp4'] ) && ! empty( $videos['mp4'][0] ) ) {
		$file_url = $videos['mp4'][0];
		$uploads = wp_get_upload_dir();
		if ( 0 === strpos( $file_url, $uploads['baseurl'] ) ) {
			$final_file_url = $file_url;
	    } else {
	    	$final_file_url = $uploads['baseurl'] . '/' . $file_url;
	    }
	} else {
		$final_file_url = wp_get_attachment_url( $attachment_id );
	}

	return $final_file_url;

}
