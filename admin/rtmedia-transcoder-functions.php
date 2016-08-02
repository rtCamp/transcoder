<?php
/**
 * RTMedia Transcoder functions.
 * 
 * @since      1.0
 *
 * @package    rtmedia-transcoder
 * @subpackage rtmedia-transcoder/admin/
 */

/**
 * Return instance of rtMedia_Transcoder_Admin Class.
 * 
 * @return object
 */
function rta() {
	global $rtmedia_transcoder_admin;
	return $rtmedia_transcoder_admin;
}

add_shortcode( 'rt_media', 'rt_media_shortcode' );


/**
 * Builds the [rt_media] shortcode output.
 * 
 * If media type is video then display transcoded video (mp4 format) if any else original video.
 * 
 * If media type is audio then display transcoded audio (mp3 format) if any else original audio.
 * 
 * @since 1.0
 * 
 * @param array  $attrs {
 *     Attributes of the shortcode.
 *
 *     @type int $attachment_id     ID of attachment.
 * }
 * @param  string $content	Shortcode content.
 * @return string|void		HTML content to display video.
 */
function rt_media_shortcode( $attrs, $content = '' ) {

	if ( empty( $attrs['attachment_id'] ) ) {
	    return false;
	}

	$attachment_id = $attrs['attachment_id'];

	$type = get_post_mime_type( $attachment_id );

	if ( empty( $type ) ) {
		return false;
	}

	$mime_type = explode( '/', $type );

	if ( 'video' === $mime_type[0] ) {

		$video_shortcode_attributes = '';
		$media_url 	= rt_media_get_video_url( $attachment_id );

		$poster 	= rt_media_get_video_thumbnail( $attachment_id );

		$attrs['src'] 		= $media_url;
		$attrs['poster'] 	= $poster;

		foreach ( $attrs as $key => $value ) {
		    $video_shortcode_attributes .= ' ' . $key . '="' . $value . '"';
		}

		return do_shortcode( "[video {$video_shortcode_attributes}]" );

	} elseif ( 'audio' === $mime_type[0] ) {

		$media_url 	= wp_get_attachment_url( $attachment_id );

		$audio_shortcode_attributes = 'src="' . $media_url . '"';

		foreach ( $attrs as $key => $value ) {
		    $audio_shortcode_attributes .= ' ' . $key . '="' . $value . '"';
		}

		return do_shortcode( "[audio {$audio_shortcode_attributes}]" );
	}
}

/**
 * Give the transcoded video's thumbnail stored in videos meta.
 * 
 * @since 1.0
 * 
 * @param  int $attachment_id   ID of attachment.
 * @return string 				returns image file url on success.
 */
function rt_media_get_video_thumbnail( $attachment_id ) {

	if ( empty( $attachment_id ) ) {
	    return;
	}

	$thumbnails = get_post_meta( $attachment_id, '_rt_media_video_thumbnail', true );

	if ( ! empty( $thumbnails ) ) {

		$file_url = $thumbnails;
		$uploads = wp_get_upload_dir();
		if ( 0 === strpos( $file_url, $uploads['baseurl'] ) ) {
			$final_file_url = $file_url;
	    } else {
	    	$final_file_url = $uploads['baseurl'] . '/' . $file_url;
	    }

		return $final_file_url;
	}

	return false;

}

/**
 * Give the transcoded video URL of attachment.
 * 
 * @since 1.0
 * 
 * @param  int $attachment_id	 ID of attachment.
 * @return string                returns video file url on success.
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

add_filter( 'rtmedia_media_thumb', 'rtmedia_transcoded_thumb', 11, 3 );

/**
 * Give the thumbnail URL for rtMedia gallery shortcode.
 * 
 * @since 1.0
 * 
 * @param  string $src			thumbnail URL.
 * @param  number $media_id		ID of attachment.
 * @param  string $media_type	media type i.e video, audio etc.
 * 
 * @return string				thumbnail URL
 */
function rtmedia_transcoded_thumb( $src, $media_id, $media_type ) {
	if ( 'video' === $media_type ) {
		$attachment_id = rtmedia_media_id( $media_id );
		$thumb_src = rt_media_get_video_thumbnail( $attachment_id );
		if ( ! empty( $thumb_src ) ) {
			$src = $thumb_src;
		}
	}
	return $src;
}
