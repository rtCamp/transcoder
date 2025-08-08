<?php
/**
 * Transcoder functions.
 *
 * @since      1.0.0
 *
 * @package    Transcoder
 * @subpackage Transcoder/Functions
 */

/**
 * Return instance of RT_Transcoder_Admin Class.
 *
 * @since   1.0.0
 *
 * @return object
 */
function rta() {
	global $rt_transcoder_admin;
	return $rt_transcoder_admin;
}

/**
 * Builds the [rt_media] shortcode output.
 *
 * If media type is video then display transcoded video (mp4 format) if any else original video.
 *
 * If media type is audio then display transcoded audio (mp3 format) if any else original audio.
 *
 * @since 1.0.0
 *
 * @param array  $attrs {
 *     Attributes of the shortcode.
 *
 *     @type int $attachment_id     ID of attachment.
 * }
 * @param  string $content  Shortcode content.
 * @return string|void      HTML content to display video.
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
	$media_url = '';

	if ( 'video' === $mime_type[0] ) {

		$video_shortcode_attributes = '';
		$media_url                  = rtt_get_media_url( $attachment_id );

		$poster = rt_media_get_video_thumbnail( $attachment_id );

		$attrs['src']    = $media_url;
		$attrs['poster'] = $poster;

		foreach ( $attrs as $key => $value ) {
			$video_shortcode_attributes .= ' ' . $key . '="' . $value . '"';
		}

		$content = do_shortcode( "[video {$video_shortcode_attributes}]" );

	} elseif ( 'audio' === $mime_type[0] ) {

		$media_url = rtt_get_media_url( $attachment_id, 'mp3' );

		$audio_shortcode_attributes = 'src="' . $media_url . '"';

		foreach ( $attrs as $key => $value ) {
			$audio_shortcode_attributes .= ' ' . $key . '="' . $value . '"';
		}

		$content = do_shortcode( "[audio {$audio_shortcode_attributes}]" );

	} elseif ( 'image' === $mime_type[0] ) {

		$content = '<p>' . esc_html__( 'Image attachments are not handled by Transcoder plugin.', 'transcoder' ) . '</p>';

	}

	if ( is_file_being_transcoded( $attachment_id ) ) {
		$content .= '<p class="transcoding-in-progress"> ' . esc_html__( 'This file is being transcoded. Please wait.', 'transcoder' ) . '</p>';
	}

	/**
	 * Allow user to filter [rt_media] short code content.
	 *
	 * @since 1.0.0
	 *
	 * @param string $content       Activity content.
	 * @param int $attachment_id    ID of attachment.
	 * @param string $media_url     URL of the media.
	 * @param string $media_type    Mime type of the media.
	 */
	return apply_filters( 'rt_media_shortcode', $content, $attachment_id, $media_url, $mime_type[0] );
}

add_shortcode( 'rt_media', 'rt_media_shortcode' );

/**
 * Check whether the file is sent to the transcoder or not.
 *
 * @since   1.0.0
 *
 * @param  number $attachment_id    ID of attachment.
 * @return boolean
 */
function is_file_being_transcoded( $attachment_id ) {
	$job_id = get_post_meta( $attachment_id, '_rt_transcoding_job_id', true );
	if ( ! empty( $job_id ) ) {
		$transcoded_files  = get_post_meta( $attachment_id, '_rt_media_transcoded_files', true );
		$transcoded_thumbs = get_post_meta( $attachment_id, '_rt_media_thumbnails', true );
		if ( empty( $transcoded_files ) && empty( $transcoded_thumbs ) ) {
			return true;
		}
	}
	return false;
}

/**
 * Give the transcoded video's thumbnail stored in videos meta.
 *
 * @since 1.0.0
 *
 * @param  int $attachment_id   ID of attachment.
 * @return string               returns image file url on success.
 */
function rt_media_get_video_thumbnail( $attachment_id ) {

	if ( empty( $attachment_id ) ) {
		return;
	}

	$thumbnails = get_post_meta( $attachment_id, '_rt_media_video_thumbnail', true );

	if ( ! empty( $thumbnails ) ) {

		$file_url = $thumbnails;
		// for WordPress backward compatibility.
		if ( function_exists( 'wp_get_upload_dir' ) ) {
			$uploads = wp_get_upload_dir();
		} else {
			$uploads = wp_upload_dir();
		}

		if ( 0 === strpos( $file_url, $uploads['baseurl'] ) ) {
			$final_file_url = $file_url;
		} else {
			$final_file_url = $uploads['baseurl'] . '/' . $file_url;
		}

		$final_file_url = apply_filters( 'transcoded_file_url', $final_file_url, $attachment_id );

		return $final_file_url;
	}

	return false;
}

/**
 * Give the transcoded media URL of attachment.
 *
 * @since 1.0.0
 *
 * @param  int    $attachment_id     ID of attachment.
 * @param  string $media_type        Type of media i.e mp4, mp3. By default it mp4 is passed.
 * @return string                    Returns audio file url on success.
 */
function rtt_get_media_url( $attachment_id, $media_type = 'mp4' ) {

	if ( empty( $attachment_id ) ) {
		return;
	}

	$medias = get_post_meta( $attachment_id, '_rt_media_transcoded_files', true );

	if ( isset( $medias[ $media_type ] ) && is_array( $medias[ $media_type ] ) && ! empty( $medias[ $media_type ][0] ) ) {
		$file_url = $medias[ $media_type ][0];
		// for WordPress backward compatibility.
		if ( function_exists( 'wp_get_upload_dir' ) ) {
			$uploads = wp_get_upload_dir();
		} else {
			$uploads = wp_upload_dir();
		}
		if ( 0 === strpos( $file_url, $uploads['baseurl'] ) ) {
			$final_file_url = $file_url;
		} else {
			$final_file_url = $uploads['baseurl'] . '/' . $file_url;
		}
		$final_file_url = apply_filters( 'transcoded_file_url', $final_file_url, $attachment_id );
	} else {
		$final_file_url = wp_get_attachment_url( $attachment_id );
	}

	return $final_file_url;
}

if ( ! function_exists( 'rtt_update_activity_after_thumb_set' ) ) {
	/**
	 * Update the activity after thumb is set to the video.
	 *
	 * @since 1.0.0
	 *
	 * @param number $id media id.
	 *
	 * @return void
	 */
	function rtt_update_activity_after_thumb_set( $id ) {

		$model       = new RTMediaModel();
		$media_obj   = new RTMediaMedia();
		$media       = $model->get( array( 'id' => $id ) );
		$privacy     = $media[0]->privacy;
		$activity_id = rtmedia_activity_id( $id );
		if ( ! empty( $activity_id ) ) {
			$same_medias           = $media_obj->model->get( array( 'activity_id' => $activity_id ) );
			$update_activity_media = array();
			foreach ( $same_medias as $a_media ) {
				$update_activity_media[] = $a_media->id;
			}
			$obj_activity = new RTMediaActivity( $update_activity_media, $privacy, false );
			global $bp;
			$activity_old_content = bp_activity_get_meta( $activity_id, 'bp_old_activity_content' );
			$activity_text        = bp_activity_get_meta( $activity_id, 'bp_activity_text' );
			if ( ! empty( $activity_old_content ) ) {
				// get old activity content and save in activity meta.
				$activity_get  = bp_activity_get_specific( array( 'activity_ids' => $activity_id ) );
				$activity      = $activity_get['activities'][0];
				$activity_body = $activity->content;
				bp_activity_update_meta( $activity_id, 'bp_old_activity_content', $activity_body );
				// extract activity text from old content.
				$activity_text = wp_kses( $activity_body, array( '<span>' => array() ) );
				$activity_text = explode( '</span>', $activity_text );
				$activity_text = wp_strip_all_tags( $activity_text[0] );
				bp_activity_update_meta( $activity_id, 'bp_activity_text', $activity_text );
			}
			$activity_text               = bp_activity_get_meta( $activity_id, 'bp_activity_text' );
			$obj_activity->activity_text = $activity_text;
			global $wpdb;
			$wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
				$bp->activity->table_name,
				array(
					'type'    => 'rtmedia_update',
					'content' => $obj_activity->create_activity_html(),
				),
				array( 'id' => $activity_id )
			);
		}
	}
}

if ( ! function_exists( 'rtt_get_edit_post_link' ) ) {
	/**
	 * Retrieve edit posts link for post. Derived from WordPress core
	 *
	 * Can be used within the WordPress loop or outside of it. Can be used with
	 * pages, posts, attachments, and revisions.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $id      Optional. Post ID.
	 * @param string $context Optional, defaults to display.
	 * @return string|null The edit post link for the given post. null if the post type is invalid or does
	 *                     not allow an editing UI.
	 */
	function rtt_get_edit_post_link( $id = 0, $context = 'display' ) {
		$_post = get_post( $id );
		if ( empty( $_post ) ) {
			return;
		}

		if ( 'revision' === $_post->post_type ) {
			$action = '';
		} elseif ( 'display' === $context ) {
			$action = '&amp;action=edit';
		} else {
			$action = '&action=edit';
		}

		$post_type_object = get_post_type_object( $_post->post_type );
		if ( ! $post_type_object ) {
			return;
		}

		if ( $post_type_object->_edit_link ) {
			$link = admin_url( sprintf( $post_type_object->_edit_link . $action, $_post->ID ) );
		} else {
			$link = '';
		}

		return $link;
	}
}

if ( ! function_exists( 'rtt_get_job_id_by_attachment_id' ) ) {
	/**
	 * Get the job id of attachment
	 *
	 * @since 1.0.0
	 *
	 * @param number $attachment_id Attachment id.
	 *
	 * @return number On success it returns the job id otherwise it returns the false.
	 */
	function rtt_get_job_id_by_attachment_id( $attachment_id ) {

		if ( empty( $attachment_id ) ) {
			return 0;
		}

		$job_id = get_post_meta( $attachment_id, '_rt_transcoding_job_id', true );

		return $job_id ? $job_id : 0;
	}
}

/**
 * Generate the video short code when non supported media is inserted in content area
 *
 * @since  1.0
 *
 * @param string $html       Short code for the media.
 * @param number $send_id    Unique id for the short code.
 * @param array  $attachment Attachment array.
 *
 * @return string
 */
function rtt_generate_video_shortcode( $html, $send_id, $attachment ) {

	if ( empty( $attachment ) ) {
		return $html;
	}

	$post_mime_type = get_post_mime_type( $attachment['id'] );
	$mime_type      = explode( '/', $post_mime_type );

	$medias = get_post_meta( $attachment['id'], '_rt_media_transcoded_files', true );

	if ( 0 === strpos( $post_mime_type, '[audio' ) || 0 === strpos( $post_mime_type, '[video' ) ) {
		return $html;
	}

	if ( empty( $medias ) ) {
		return $html;
	}

	if ( ! empty( $mime_type ) && 0 === strpos( $post_mime_type, 'video' ) ) {
		$transcoded_file_url = rtt_get_media_url( $attachment['id'] );
		if ( empty( $transcoded_file_url ) ) {
			return $html;
		}

		$transcoded_thumb_url = rt_media_get_video_thumbnail( $attachment['id'] );

		$poster = '';
		if ( ! empty( $transcoded_thumb_url ) ) {
			$poster = 'poster="' . $transcoded_thumb_url . '"';
		}

		$html = '[video src="' . $transcoded_file_url . '" ' . $poster . ' ]';
	} elseif ( ! empty( $mime_type ) && 0 === strpos( $post_mime_type, 'audio' ) ) {
		$transcoded_file_url = rtt_get_media_url( $attachment['id'] );
		if ( empty( $transcoded_file_url ) ) {
			return $html;
		}

		$html = '[audio src="' . $transcoded_file_url . '"]';
	}

	return $html;
}

add_filter( 'media_send_to_editor', 'rtt_generate_video_shortcode', 100, 3 );

/**
 * Add the notice when file is sent for the transcoding and adds the poster thumbnail if poster tag is empty
 * This function also works as a backward compatibility for the rtAmazon S3 plugin
 *
 * @since 1.0.1
 *
 * @param string      $content  HTML contents of the activity.
 * @param object|null $activity Activity object.
 *
 * @return string
 */
function rtt_bp_get_activity_content( $content, $activity = null ) {

	if ( empty( $activity ) || empty( $content ) ) {
		return $content;
	}

	if ( class_exists( 'RTMediaModel' ) ) {
		$rt_model  = new RTMediaModel();
		$all_media = $rt_model->get( array( 'activity_id' => $activity->id ) );
		if ( empty( $all_media ) ) {
			return $content;
		}

		// Filter all video objects. So we only get video objects in $all_media array.
		foreach ( $all_media as $key => $media ) {
			if ( 'video' !== $media->media_type ) {
				unset( $all_media[ $key ] );
			}
		}

		// Reset the array keys. Changing SORT_DESC from SORT_ASC because $video_src_url is in desc order.
		array_multisort( $all_media, SORT_DESC );

		// Get all the video src.
		$search_video_url = '/<video.+(src=["]([^"]*)["])/';
		preg_match_all( $search_video_url, $content, $video_src_url );

		// Get all the poster src.
		$search_poster_url = '/<video.+(poster=["]([^"]*)["])/';
		preg_match_all( $search_poster_url, $content, $poster_url );

		$uploads = wp_upload_dir();

		// Iterate through each media.
		foreach ( $all_media as $key => $media ) {
			// Get default video thumbnail stored for this particular video in post meta.
			$wp_video_thumbnail = get_post_meta( $media->media_id, '_rt_media_video_thumbnail', true );

			if ( ! empty( $video_src_url[2] ) ) {

				$transcoded_media_url = rtt_get_media_url( $media->media_id );

				if ( ! empty( $transcoded_media_url ) ) {
					$content = preg_replace( '/' . str_replace( '/', '\/', $video_src_url[2][ $key ] ) . '/', $transcoded_media_url, $content, 1 );
				}
			}

			// Make the URL absolute.
			if ( ! empty( $wp_video_thumbnail ) ) {
				$file_url = $wp_video_thumbnail;

				if ( 0 === strpos( $file_url, $uploads['baseurl'] ) ) {
					$final_file_url = $file_url;
				} else {
					$final_file_url = $uploads['baseurl'] . '/' . $file_url;
				}
				// Thumbnail/poster URL.
				$final_file_url = apply_filters( 'transcoded_file_url', $final_file_url, $media->media_id );
				// Replace the first poster (assuming activity has multiple medias in it).
				if ( is_file_being_transcoded( $media->media_id ) ) {
					$content = preg_replace( '/' . str_replace( '/', '\/', $poster_url[1][ $key ] ) . '/', 'poster="' . $final_file_url . '"', $content, 1 );
				}
			}
			// If media is sent to the transcoder then show the message.
			if ( is_file_being_transcoded( $media->media_id ) ) {
				if ( current_user_can( 'manage_options' ) && '1' === get_option( 'rtt_client_check_status_button', false ) ) {

					$check_button_text = __( 'Check Status', 'transcoder' );

					/**
					 * Filters the text of transcoding process status check button.
					 *
					 * @since 1.2
					 *
					 * @param string $check_button_text Default text of transcoding process status check button.
					 */
					$check_button_text = apply_filters( 'rtt_transcoder_check_status_button_text', $check_button_text );

					$message = sprintf(
						'<div class="transcoding-in-progress"><button id="btn_check_status%1$s" class="btn_check_transcode_status" name="check_status_btn" data-value="%1$s">%2$s</button> <div class="transcode_status_box" id="span_status%1$s">%3$s</div></div>',
						esc_attr( $media->media_id ),
						esc_html( $check_button_text ),
						esc_html__( 'This file is converting. Please refresh the page after some time.', 'transcoder' )
					);

				} else {
					$message = sprintf(
						'<p class="transcoding-in-progress">%s</p>',
						esc_html__( 'This file is converting. Please refresh the page after some time.', 'transcoder' )
					);
				}
				/**
				 * Allow user to filter the message text.
				 *
				 * @since 1.0.2
				 *
				 * @param string $message   Message to be displayed.
				 * @param object $activity  Activity object.
				 */
				$message  = apply_filters( 'rtt_transcoding_in_progress_message', $message, $activity );
				$message .= '</div>';
				// Add this message to the particular media (there can be multiple medias in the activity).
				$search     = '/(rt_media_video_' . $media->id . ")['\"](.*?)(<\/a><\/div>)/s";
				$text_found = array();
				preg_match( $search, $content, $text_found );

				if ( ! empty( $text_found[0] ) ) {
					$content = str_replace( $text_found[0], $text_found[1] . '"' . $text_found[2] . '</a>' . $message, $content );
				}
			}
		}
		$search     = '/(<div class="rtmedia-item-title")(.*?)(>)/s';
		$text_found = array();
		global $rtmedia;
		$text_to_be_entered = " style='max-width:" . esc_attr( $rtmedia->options['defaultSizes_video_activityPlayer_width'] ) . "px;' ";
		preg_match( $search, $content, $text_found );
		if ( ! empty( $text_found[0] ) ) {
				$content = str_replace( $text_found[0], $text_found[1] . $text_to_be_entered . $text_found[3], $content );
		}
		return $content;
	} else {
		return $content;
	}
}
add_filter( 'bp_get_activity_content_body', 'rtt_bp_get_activity_content', 99, 2 );

/**
 * Parse the URL - Derived from the WordPress core
 *
 * @since 1.0.4
 *
 * @param  string $url The URL to be parsed.
 * @return array       Array containing the information about the URL.
 */
function rtt_wp_parse_url( $url ) {
	if ( function_exists( 'wp_parse_url' ) ) {
		return wp_parse_url( $url );
	}
	$parts = wp_parse_url( $url );
	if ( ! $parts ) {
		// < PHP 5.4.7 compat, trouble with relative paths including a scheme break in the path
		if ( '/' === $url[0] && false !== strpos( $url, '://' ) ) {
			// Since we know it's a relative path, prefix with a scheme/host placeholder and try again.
			$parts = wp_parse_url( 'placeholder://placeholder' . $url );
			if ( empty( $parts ) ) {
				return $parts;
			}
			// Remove the placeholder values.
			unset( $parts['scheme'], $parts['host'] );
		} else {
			return $parts;
		}
	}

	// < PHP 5.4.7 compat, doesn't detect schemeless URL's host field
	if ( '//' === substr( $url, 0, 2 ) && ! isset( $parts['host'] ) ) {
		$path_parts    = explode( '/', substr( $parts['path'], 2 ), 2 );
		$parts['host'] = $path_parts[0];
		if ( isset( $path_parts[1] ) ) {
			$parts['path'] = '/' . $path_parts[1];
		} else {
			unset( $parts['path'] );
		}
	}
	return $parts;
}

/**
 * Deletes the transcoded files related to the attachment
 *
 * @since 1.0.5
 *
 * @param  int $post_id Attachment ID.
 */
function rtt_delete_related_transcoded_files( $post_id ) {
	if ( empty( $post_id ) ) {
		return false;
	}

	$transcoded_files = get_post_meta( $post_id, '_rt_media_transcoded_files', true );

	if ( ! empty( $transcoded_files ) && is_array( $transcoded_files ) ) {
		foreach ( $transcoded_files as $files ) {
			if ( ! empty( $files ) && is_array( $files ) ) {
				rtt_delete_transcoded_files( $files );
			}
		}
	}
	delete_post_meta( $post_id, '_rt_media_transcoded_files' );

	$thumbnails = get_post_meta( $post_id, '_rt_media_thumbnails', true );
	if ( ! empty( $thumbnails ) && is_array( $thumbnails ) ) {
		rtt_delete_transcoded_files( $thumbnails );
	}
	delete_post_meta( $post_id, '_rt_media_thumbnails' );
}

add_action( 'delete_attachment', 'rtt_delete_related_transcoded_files', 99, 1 );

/**
 * Deletes/Unlinks the files given in the array
 *
 * @since 1.0.5
 *
 * @param mixed $files Files array or file path string.
 *
 * @return void
 */
function rtt_delete_transcoded_files( $files ) {

	if ( empty( $files ) ) {
		return;
	}

	if ( ! empty( $files ) && ! is_array( $files ) ) {
		$files = array( $files );
	}

	$uploadpath = rtt_get_upload_dir();

	foreach ( $files as $file ) {
		if ( ! empty( $file ) ) {
			$file_path = path_join( $uploadpath['basedir'], $file );
			\Transcoder\Inc\FileSystem::delete_file( $file_path );
		}
	}
}

/**
 * Gets the information about the upload directory
 *
 * On success, the returned array will have many indices:
 * 'path' - base directory and sub directory or full path to upload directory.
 * 'url' - base url and sub directory or absolute URL to upload directory.
 * 'subdir' - sub directory if uploads use year/month folders option is on.
 * 'basedir' - path without subdir.
 * 'baseurl' - URL path without subdir.
 * 'error' - false or error message.
 *
 * @since 1.0.5
 *
 * @return array See above for description.
 */
function rtt_get_upload_dir() {
	// for WordPress backward compatibility.
	if ( function_exists( 'wp_get_upload_dir' ) ) {
		$uploads = wp_get_upload_dir();
	} else {
		$uploads = wp_upload_dir();
	}

	return $uploads;
}

/**
 * Check if override media thumbnail setting is ON or OFF.
 *
 * @since 1.1.0
 *
 * @param int|string $attachment_id ID of attachment.
 *
 * @return boolean TRUE if override is ON, FALSE is OFF
 */
function rtt_is_override_thumbnail( $attachment_id = '' ) {

	$rtt_override_thumbnail = get_option( 'rtt_override_thumbnail', false );

	/**
	 * Allow user to override the setting.
	 *
	 * @since 1.1.0
	 *
	 * @param boolean   $rtt_override_thumbnail     Number of thumbnails set in setting.
	 * @param int       $attachment_id              ID of attachment.
	 */
	$rtt_override_thumbnail = apply_filters( 'rtt_is_override_thumbnail', $rtt_override_thumbnail, $attachment_id );

	return $rtt_override_thumbnail;
}

/**
 * Get remote IP address
 *
 * @return string Remote IP address
 */
function rtt_get_remote_ip_address() {
	$client_ip = get_server_var( 'HTTP_CLIENT_IP' );
	$xff       = get_server_var( 'HTTP_X_FORWARDED_FOR' );
	if ( ! empty( $client_ip ) ) {
		return $client_ip;
	} elseif ( ! empty( $xff ) ) {
		return $xff;
	}

	$remote_addr = get_server_var( 'REMOTE_ADDR' );
	return $remote_addr;
}

/**
 * Set status column head in media admin page
 *
 * @since 1.2
 *
 * @param array $defaults columns list.
 *
 * @return array columns list
 */
function rtt_add_status_columns_head( $defaults ) {

	$defaults['convert_status'] = __( 'Transcode Status', 'transcoder' );
	return $defaults;
}

add_filter( 'manage_media_columns', 'rtt_add_status_columns_head' );

/**
 * Set status column content in media admin page
 *
 * @since 1.2
 *
 * @param string $column_name column name.
 * @param int    $post_id Post ID.
 */
function rtt_add_status_columns_content( $column_name, $post_id ) {
	if ( 'convert_status' !== $column_name ) {
		return;
	}

	$transcoded_files  = get_post_meta( $post_id, '_rt_media_transcoded_files', true );
	$transcoded_thumbs = get_post_meta( $post_id, '_rt_media_thumbnails', true );

	if ( empty( $transcoded_files ) && is_file_being_transcoded( $post_id ) ) {
		$check_button_text = __( 'Check Status', 'transcoder' );

		/**
		 * Filters the text of transcoding process status check button.
		 *
		 * @since 1.2
		 *
		 * @param string $check_button_text Default text of transcoding process status check button.
		 */
		$check_button_text = apply_filters( 'transcoder_check_status_button_text', $check_button_text );

		?>
		<div id="span_status<?php echo esc_attr( $post_id ); ?>"></div>
		<button type="button" id="btn_check_status<?php echo esc_attr( $post_id ); ?>" name="check_status_btn" data-value='<?php echo esc_attr( $post_id ); ?>'><?php echo esc_html( $check_button_text ); ?></button>
		<?php

	} elseif ( ! empty( $transcoded_files ) && ! empty( $transcoded_thumbs ) ) {
		echo esc_html__( 'File is transcoded.', 'transcoder' );
	}
}

add_action( 'manage_media_custom_column', 'rtt_add_status_columns_content', 10, 2 );


/**
 * Set sortable status column in media admin page
 *
 * @since 1.2
 *
 * @param array $columns columns list.
 *
 * @return array columns list
 */
function rtt_status_column_register_sortable( $columns ) {

	$columns['convert_status'] = 'convert_status';
	return $columns;
}

add_filter( 'manage_upload_sortable_columns', 'rtt_status_column_register_sortable' );


/**
 * Method to add js function.
 *
 * @since 1.2
 */
function rtt_enqueue_scripts() {

	if ( current_user_can( 'manage_options' ) ) {
		wp_register_script( 'rt_transcoder_js', plugins_url( 'js/rt-transcoder.min.js', __FILE__ ), array(), RT_TRANSCODER_VERSION, false );

		$translation_array = array(
			'load_flag'      => true,
			'security_nonce' => esc_js( wp_create_nonce( 'check-transcoding-status-ajax-nonce' ) ),
		);

		wp_localize_script( 'rt_transcoder_js', 'transcoding_status', $translation_array );
		wp_enqueue_script( 'rt_transcoder_js' );

		if ( ! is_admin() ) {
			wp_enqueue_style( 'rt-transcoder-client-style', plugins_url( 'css/rt-transcoder-client.min.css', __FILE__ ), array(), RT_TRANSCODER_VERSION );
		}
	}
}

if ( '1' === get_option( 'rtt_client_check_status_button', false ) ) {
	add_action( 'wp_enqueue_scripts', 'rtt_enqueue_scripts' );
}
add_action( 'admin_enqueue_scripts', 'rtt_enqueue_scripts' );

add_action( 'enqueue_block_editor_assets', 'rt_transcoder_enqueue_block_editor_assets' );

/**
 * Enqueues script on frontend.
 *
 * @return void
 */
function rtt_enqueue_frontend_scripts() {
	$file_to_use = 'public-assets/js/build/transcoder.min.js';

	$file = path_join( RT_TRANSCODER_PATH, $file_to_use );
	if ( file_exists( $file ) && class_exists( 'RTMedia' ) ) {
		wp_enqueue_script( 'rt-transcoder-front-js', RT_TRANSCODER_URL . $file_to_use, array( 'jquery', 'rtmedia-backbone' ), filemtime( $file ), true );

		$rest_url_prefix = get_site_url() . '/' . rest_get_url_prefix();
		wp_localize_script( 'rt-transcoder-front-js', 'rtTranscoder', array( 'restURLPrefix' => $rest_url_prefix ) );
	}
}
add_action( 'wp_enqueue_scripts', 'rtt_enqueue_frontend_scripts' );

/**
 * Enqueue required script for block editor.
 */
function rt_transcoder_enqueue_block_editor_assets() {
	// Enqueue our script.
	wp_enqueue_script(
		'rt-transcoder-block-editor-support',
		esc_url( plugins_url( '/js/build/rt-transcoder-block-editor-support.build.js', __FILE__ ) ),
		array( 'wp-blocks', 'wp-i18n', 'wp-element', 'wp-editor' ),
		RT_TRANSCODER_VERSION,
		true
	);

	// Localize fallback poster image for use in our enqueued script.
	wp_localize_script(
		'rt-transcoder-block-editor-support',
		'rtTranscoderBlockEditorSupport',
		array(
			'amp_story_fallback_poster' => plugins_url( '/images/amp-story-fallback-poster.png', __FILE__ ),
			'amp_video_fallback_poster' => plugins_url( '/images/amp-story-video-fallback-poster.png', __FILE__ ),
		)
	);
}

/**
 * Method to handle AJAX request for checking status.
 *
 * @since 1.2
 */
function rtt_ajax_process_check_status_request() {

	check_ajax_referer( 'check-transcoding-status-ajax-nonce', 'security', true );
	$post_id = transcoder_filter_input( INPUT_POST, 'postid', FILTER_SANITIZE_NUMBER_INT );

	if ( ! empty( $post_id ) ) {
		echo esc_html( rtt_get_transcoding_status( $post_id ) );
	}

	wp_die();
}

// Action added to handle check_status onclick request.
add_action( 'wp_ajax_checkstatus', 'rtt_ajax_process_check_status_request' );

/**
 * To get status of transcoding process
 *
 * @since 1.2
 *
 * @param int $post_id post ID.
 *
 * @return string transcoding process status
 */
function rtt_get_transcoding_status( $post_id ) {

	require_once RT_TRANSCODER_PATH . 'admin/rt-transcoder-handler.php'; // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.UsingCustomConstant

	$obj    = new RT_Transcoder_Handler( true );
	$status = $obj->get_transcoding_status( $post_id );

	return $status;
}

/**
 * To get status of transcoding process
 *
 * @since 1.2
 *
 * @param int $rtmedia_id rtmedia ID.
 */
function rtt_add_transcoding_process_status_button_single_media_page( $rtmedia_id ) {

	global $wpdb;
	$rtmedia_media_table = $wpdb->prefix . 'rt_rtm_media';

	$post_id = wp_cache_get( 'media_' . $rtmedia_id, 'transcoder' );
	if ( empty( $post_id ) ) {
		$results = $wpdb->get_results( $wpdb->prepare( "SELECT media_id FROM $rtmedia_media_table WHERE id = %d", $rtmedia_id ), OBJECT ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- %s adds '' around table name.
		$post_id = $results[0]->media_id;
		wp_cache_set( 'media_' . $rtmedia_id, $post_id, 'transcoder', 3600 );
	}

	$check_button_text = __( 'Check Status', 'transcoder' );

	/**
	 * Filters the text of transcoding process status check button.
	 *
	 * @since 1.2
	 *
	 * @param string $check_button_text Default text of transcoding process status check button.
	 */
	$check_button_text = apply_filters( 'rtt_transcoder_check_status_button_text', $check_button_text );

	if ( is_file_being_transcoded( $post_id ) ) {

		if ( current_user_can( 'manage_options' ) && '1' === get_option( 'rtt_client_check_status_button', false ) ) {
			$message = sprintf(
				'<div class="transcoding-in-progress"><button id="btn_check_status%1$s" class="btn_check_transcode_status" name="check_status_btn" data-value="%1$s">%2$s</button> <div class="transcode_status_box" id="span_status%1$s">%3$s</div></div>',
				esc_attr( $post_id ),
				esc_html( $check_button_text ),
				esc_html__( 'This file is converting. Please click on check status button to know current status or refresh the page after some time. ', 'transcoder' )
			);
		} else {
			$message = sprintf(
				'<p class="transcoding-in-progress">%s</p>',
				esc_html__( 'This file is converting. Please refresh the page after some time.', 'transcoder' )
			);
		}

		// Message already escaped.
		echo $message; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

	}
}

// Add action to media single page.
add_action( 'rtmedia_actions_before_description', 'rtt_add_transcoding_process_status_button_single_media_page', 10, 1 );

/**
 * Do not render <video> if the video is transcoding.
 *
 * @param string $html html markup.
 * @param object $rtmedia_media rtmedia media object.
 *
 * @return string html markup
 */
function rtt_filter_no_video_on_transcoding( $html, $rtmedia_media ) {
	if ( empty( $rtmedia_media ) || empty( $rtmedia_media->media_type ) || empty( $rtmedia_media->media_id ) ) {
		return $html;
	}
	if ( 'video' === $rtmedia_media->media_type && is_file_being_transcoded( $rtmedia_media->media_id ) ) {

		$html = '<p class="transcoding-in-progress"> ' . esc_html__( 'Please wait while we\'re processing this video.', 'transcoder' ) . '</p>';
	}

	return $html;
}

add_filter( 'rtmedia_single_content_filter', 'rtt_filter_no_video_on_transcoding', 10, 2 );

/**
 *
 * Added handler to update usage if it is not updated.
 * Added one flag in transient to avoid requests when usage quota is over and it is not renewed.
 *
 * @since 1.0.0
 *
 * @param array  $wp_metadata       Metadata of the attachment.
 * @param int    $attachment_id  ID of attachment.
 * @param string $autoformat     If true then generating thumbs only else trancode video.
 */
function rtt_media_update_usage( $wp_metadata, $attachment_id, $autoformat = true ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed

	$stored_key     = get_site_option( 'rt-transcoding-api-key' );
	$transient_flag = get_transient( 'rtt_usage_update_flag' );

	if ( ! empty( $stored_key ) && empty( $transient_flag ) ) {

		$usage_info = get_site_option( 'rt-transcoding-usage' );
		$handler    = new RT_Transcoder_Handler( false );

		if ( empty( $usage_info ) || empty( $usage_info[ $handler->api_key ]->remaining ) ) {

			$handler->update_usage( $handler->api_key );
			set_transient( 'rtt_usage_update_flag', '1', HOUR_IN_SECONDS );
		}
	}

	return $wp_metadata;
}

add_filter( 'wp_generate_attachment_metadata', 'rtt_media_update_usage', 10, 2 );

/**
 * To get sanitized server variables.
 *
 * @param string $server_key Key of the $_SERVER superglobal variable.
 * @param int    $filter_type The ID of the filter to apply.
 *
 * @return string Filtered value if supports.
 */
function get_server_var( $server_key, $filter_type = FILTER_SANITIZE_FULL_SPECIAL_CHARS ) {
	$server_val = '';
	if ( function_exists( 'filter_input' ) && filter_has_var( INPUT_SERVER, $server_key ) ) {
		$server_val = transcoder_filter_input( INPUT_SERVER, $server_key, $filter_type );
	} elseif ( isset( $_SERVER[ $server_key ] ) ) {
		$server_val = $_SERVER[ $server_key ]; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	}
	return $server_val;
}

/**
 * Get local ip addresses for block.
 *
 * @return array
 */
function rtt_get_blacklist_ip_addresses() {
	// If custom API URL added then don't block local ips.
	if ( defined( 'TRANSCODER_API_URL' ) ) {
		return array();
	}

	return array( '127.0.0.1', '::1' );
}
