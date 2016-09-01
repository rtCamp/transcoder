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
 * @since	1.0.0
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
		$media_url 	= rtt_get_media_url( $attachment_id );

		$poster 	= rt_media_get_video_thumbnail( $attachment_id );

		$attrs['src'] 		= $media_url;
		$attrs['poster'] 	= $poster;

		foreach ( $attrs as $key => $value ) {
		    $video_shortcode_attributes .= ' ' . $key . '="' . $value . '"';
		}

		$content = do_shortcode( "[video {$video_shortcode_attributes}]" );

	} elseif ( 'audio' === $mime_type[0] ) {

		$media_url 	= rtt_get_media_url( $attachment_id, 'mp3' );

		$audio_shortcode_attributes = 'src="' . $media_url . '"';

		foreach ( $attrs as $key => $value ) {
		    $audio_shortcode_attributes .= ' ' . $key . '="' . $value . '"';
		}

		$content = do_shortcode( "[audio {$audio_shortcode_attributes}]" );

	}

	if ( is_file_being_transcoded( $attachment_id ) ) {
		$content .= '<p class="transcoding-in-progress"> ' . esc_html__( 'This file is being transcoded. Please wait.', 'transcoder' ) . '</p>';
	}

	/**
	 * Allow user to filter [rt_media] short code content.
	 *
	 * @since 1.0.0
	 *
	 * @param string $content    	Activity content.
	 * @param int $attachment_id  	ID of attachment.
	 * @param string $media_url  	URL of the media.
	 * @param string $media_type  	Mime type of the media.
	 */
	return apply_filters( 'rt_media_shortcode', $content, $attachment_id, $media_url, $mime_type[0] );
}

add_shortcode( 'rt_media', 'rt_media_shortcode' );

/**
 * Check whether the file is sent to the transcoder or not.
 *
 * @since	1.0.0
 *
 * @param  number $attachment_id	ID of attachment.
 * @return boolean
 */
function is_file_being_transcoded( $attachment_id ) {
	$job_id = get_post_meta( $attachment_id, '_rt_transcoding_job_id', true );
	if ( ! empty( $job_id ) ) {
		$transcoded_files = get_post_meta( $attachment_id, '_rt_media_transcoded_files', true );
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
 * @return string 				returns image file url on success.
 */
function rt_media_get_video_thumbnail( $attachment_id ) {

	if ( empty( $attachment_id ) ) {
	    return;
	}

	$thumbnails = get_post_meta( $attachment_id, '_rt_media_video_thumbnail', true );

	if ( ! empty( $thumbnails ) ) {

		$file_url = $thumbnails;
		/* for WordPress backward compatibility */
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

		return $final_file_url;
	}

	return false;

}

/**
 * Give the transcoded media URL of attachment.
 *
 * @since 1.0.0
 *
 * @param  int    $attachment_id	 ID of attachment.
 * @param  string $media_type        Type of media i.e mp4, mp3. By default it mp4 is passed.
 * @return string					 Returns audio file url on success.
 */
function rtt_get_media_url( $attachment_id, $media_type = 'mp4' ) {

	if ( empty( $attachment_id ) ) {
	    return;
	}

	$medias = get_post_meta( $attachment_id, '_rt_media_transcoded_files', true );

	if ( isset( $medias[ $media_type ] ) && is_array( $medias[ $media_type ] ) && ! empty( $medias[ $media_type ][0] ) ) {
		$file_url = $medias[ $media_type ][0];
		/* for WordPress backward compatibility */
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
	} else {
		$final_file_url = wp_get_attachment_url( $attachment_id );
	}

	return $final_file_url;

}

/**
 * Give the thumbnail URL for rtMedia gallery shortcode.
 *
 * @since	1.0.0
 *
 * @param  string $src			thumbnail URL.
 * @param  number $media_id		ID of attachment.
 * @param  string $media_type	media type i.e video, audio etc.
 *
 * @return string				thumbnail URL
 */
function rtt_transcoded_thumb( $src, $media_id, $media_type ) {
	if ( 'video' === $media_type ) {
		$attachment_id = rtmedia_media_id( $media_id );
		$thumb_src = rt_media_get_video_thumbnail( $attachment_id );
		if ( ! empty( $thumb_src ) ) {
			$src = $thumb_src;
		}
	}
	return $src;
}

//add_filter( 'rtmedia_media_thumb', 'rtt_transcoded_thumb', 11, 3 );

if ( ! function_exists( 'rtt_video_editor_title' ) ) {
	/**
	 * Add the video thumbnail tab on video edit page.
	 *
	 * @since	1.0.0
	 */
	function rtt_video_editor_title() {
		global $rtmedia_query;
		if ( isset( $rtmedia_query->media[0]->media_type ) && 'video' === $rtmedia_query->media[0]->media_type ) {
			$flag            = false;
			$media_id        = $rtmedia_query->media[0]->media_id;
			$thumbnail_array = get_post_meta( $media_id, 'rtmedia_media_thumbnails', true );
			if ( ! is_array( $thumbnail_array ) ) {
				$thumbnail_array = get_post_meta( $media_id, '_rt_media_thumbnails', true );
			}
			if ( is_array( $thumbnail_array ) ) {
				$flag = true;
			} else {
				global $rtmedia_media;
				$curr_cover_art = $rtmedia_media->cover_art;
				if ( ! empty( $curr_cover_art ) ) {
					$rtmedia_video_thumbs = get_rtmedia_meta( $rtmedia_query->media[0]->media_id, 'rtmedia-thumbnail-ids' );
					if ( is_array( $rtmedia_video_thumbs ) ) {
						$flag = true;
					}
				}
			}
			if ( $flag ) {
				echo '<li><a href="#panel2"><i class="dashicons dashicons-format-image rtmicon"></i>' . esc_html__( 'Video Thumbnail', 'transcoder' ) . '</a></li>';
			}
		}
	}
}

add_action( 'rtmedia_add_edit_tab_title', 'rtt_video_editor_title', 1000 );

if ( ! function_exists( 'rtt_rtmedia_vedio_editor_content' ) ) {
	/**
	 * Display the HTML to set the thumbnail for video.
	 *
	 * @since	1.0.0
	 */
	function rtt_rtmedia_vedio_editor_content() {
		global $rtmedia_query;
		if ( isset( $rtmedia_query->media ) && is_array( $rtmedia_query->media ) && isset( $rtmedia_query->media[0]->media_type ) && 'video' === $rtmedia_query->media[0]->media_type ) {
			$media_id        = $rtmedia_query->media[0]->media_id;
			$rtmedia_transcoded_video_thumbs = get_post_meta( $rtmedia_query->media[0]->media_id, '_rt_media_thumbnails', true );
			$thumbnail_array = '';
			if ( ! is_array( $rtmedia_transcoded_video_thumbs ) ) {
				$thumbnail_array = $rtmedia_transcoded_video_thumbs = get_post_meta( $media_id, 'rtmedia_media_thumbnails', true );
			}
			echo '<div class="content" id="panel2">';
			if ( is_array( $rtmedia_transcoded_video_thumbs ) ) {
				?>
				<div class="rtmedia-change-cover-arts">
					<p><?php esc_html_e( 'Video Thumbnail:', 'transcoder' ); ?></p>
					<ul>
						<?php
						/* for WordPress backward compatibility */
						if ( function_exists( 'wp_get_upload_dir' ) ) {
							$uploads = wp_get_upload_dir();
						} else {
							$uploads = wp_upload_dir();
						}
						$base_url 	= $uploads['baseurl'];
						$media_id 	= $rtmedia_query->media[0]->media_id;
						foreach ( $rtmedia_transcoded_video_thumbs as $key => $thumbnail_src ) {
							$wp_video_thumbnail = get_post_meta( $media_id, '_rt_media_video_thumbnail', true );

							if ( 0 === strpos( $thumbnail_src, $uploads['baseurl'] ) ) {
								$thumbnail_src = str_replace( $uploads['baseurl'], '', $thumbnail_src );
						    }

							if ( empty( $wp_video_thumbnail ) ) {
								$wp_video_thumbnail = $rtmedia_query->media[0]->cover_art;
								$wp_video_thumbnail = str_replace( $uploads['baseurl'], '', $wp_video_thumbnail );
							}

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

							?>
							<li<?php echo $checked ? ' class="selected"' : ''; ?>
								style="width: 150px;display: inline-block;">
								<label
									for="rtmedia-upload-select-thumbnail-<?php echo intval( sanitize_text_field( $key ) ) + 1; ?>"
									class="alignleft">
									<input type="radio"<?php echo esc_attr( $checked ); ?>
									       id="rtmedia-upload-select-thumbnail-<?php echo intval( sanitize_text_field( $key ) ) + 1; ?>"
									       value="<?php echo esc_attr( $thumbnail_src_og ); ?>"
									       name="rtmedia-thumbnail"/>
									<img src="<?php echo esc_attr( $thumbnail_src ); ?>"
									     style="max-height: 120px;max-width: 120px"/>
								</label>
							</li>
							<?php
						}
						?>
					</ul>
				</div>
			<?php
			} else { // check for array of thumbs stored as attachement ids.
				global $rtmedia_media;
				$curr_cover_art = $rtmedia_media->cover_art;
				if ( ! empty( $curr_cover_art ) ) {
					$rtmedia_video_thumbs = get_rtmedia_meta( $rtmedia_query->media[0]->media_id, 'rtmedia-thumbnail-ids' );
					if ( is_array( $rtmedia_video_thumbs ) ) {
						?>
						<div class="rtmedia-change-cover-arts">
							<p><?php esc_html_e( 'Video Thumbnail:', 'transcoder' ); ?></p>
							<ul>
								<?php
								foreach ( $rtmedia_video_thumbs as $key => $attachment_id ) {
									$thumbnail_src = wp_get_attachment_url( $attachment_id );
									?>
									<li<?php echo checked( $attachment_id, $curr_cover_art, false ) ? ' class="selected"' : ''; ?>
										style="width: 150px;display: inline-block;">
										<label
											for="rtmedia-upload-select-thumbnail-<?php echo intval( sanitize_text_field( $key ) ) + 1; ?>"
											class="alignleft">
											<input type="radio"<?php checked( $attachment_id, $curr_cover_art ); ?>
											       id="rtmedia-upload-select-thumbnail-<?php echo intval( sanitize_text_field( $key ) ) + 1; ?>"
											       value="<?php echo esc_attr( $attachment_id ); ?>"
											       name="rtmedia-thumbnail"/>
											<img src="<?php echo esc_attr( $thumbnail_src ); ?>"
											     style="max-height: 120px;max-width: 120px"/>
										</label>
									</li>
									<?php
								}
								?>
							</ul>
						</div>

						<?php
					}
				}
			}
			echo '</div>';
		}
	}
}

add_action( 'rtmedia_add_edit_tab_content', 'rtt_rtmedia_vedio_editor_content', 1000 );

if ( ! function_exists( 'rtt_set_video_thumbnail' ) ) {

	/**
	 * Set the video thumbnail
	 *
	 * @since	1.0.0
	 *
	 * @param number $id rtMedia activity ID.
	 */
	function rtt_set_video_thumbnail( $id ) {
		$media_type 	= rtmedia_type( $id );
		$attachment_id 	= rtmedia_media_id( $id );		// Get the wp attachment ID.
		$thumbnail  = filter_input( INPUT_POST, 'rtmedia-thumbnail', FILTER_SANITIZE_URL );
		if ( 'video' === $media_type && ! empty( $thumbnail ) ) {

			if ( ! is_numeric( $thumbnail ) ) {
				$file_url = $thumbnail;
				/* for WordPress backward compatibility */
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

				update_post_meta( $attachment_id, '_rt_media_video_thumbnail', $thumbnail );
			}

			$model = new RTMediaModel();
	        $model->update( array( 'cover_art' => $final_file_url ), array( 'id' => intval( $id ) ) );
	        rtt_update_activity_after_thumb_set( $id );

		}
	}
}

add_action( 'rtmedia_after_update_media', 'rtt_set_video_thumbnail', 12 );

if ( ! function_exists( 'rtt_update_activity_after_thumb_set' ) ) {
	/**
	 * Update the activity after thumb is set to the video.
	 *
	 * @since	1.0.0
	 *
	 * @param  number $id media id.
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
			global $wpdb, $bp;
			$activity_old_content = bp_activity_get_meta( $activity_id, 'bp_old_activity_content' );
			$activity_text        = bp_activity_get_meta( $activity_id, 'bp_activity_text' );
			if ( ! empty( $activity_old_content ) ) {
				// get old activity content and save in activity meta.
				$activity_get  = bp_activity_get_specific( array( 'activity_ids' => $activity_id ) );
				$activity      = $activity_get['activities'][0];
				$activity_body = $activity->content;
				bp_activity_update_meta( $activity_id, 'bp_old_activity_content', $activity_body );
				// extract activity text from old content.
				$activity_text = strip_tags( $activity_body, '<span>' );
				$activity_text = explode( '</span>', $activity_text );
				$activity_text = strip_tags( $activity_text[0] );
				bp_activity_update_meta( $activity_id, 'bp_activity_text', $activity_text );
			}
			$activity_text               = bp_activity_get_meta( $activity_id, 'bp_activity_text' );
			$obj_activity->activity_text = $activity_text;
			$wpdb->update( $bp->activity->table_name, array(
				'type'    => 'rtmedia_update',
				'content' => $obj_activity->create_activity_html(),
			), array( 'id' => $activity_id ) );
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
		if ( ! $post = get_post( $id ) ) {
			return;
		}

		if ( 'revision' === $post->post_type ) {
		    $action = '';
		} elseif ( 'display' === $context ) {
		    $action = '&amp;action=edit';
		} else {
		    $action = '&action=edit';
		}

		$post_type_object = get_post_type_object( $post->post_type );
		if ( ! $post_type_object ) {
		    return;
		}

		if ( $post_type_object->_edit_link ) {
		    $link = admin_url( sprintf( $post_type_object->_edit_link . $action, $post->ID ) );
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
	 * @since	1.0.0
	 *
	 * @param  number $attachment_id Attachment id
	 * @return number                On success it returns the job id otherwise it returns the false.
	 */
	function rtt_get_job_id_by_attachment_id( $attachment_id ) {
		if ( empty( $attachment_id ) ) {
			return false;
		}

		$job_id = get_post_meta( $attachment_id, '_rt_transcoding_job_id', true );

		return $job_id ? $job_id : false;
	}
}

/**
 * Generate the video short code when non supported media is inserted in content area
 *
 * @since  1.0
 *
 * @param  text 	$html       short code for the media
 * @param  number 	$send_id    unique id for the short code
 * @param  array 	$attachment attachment array
 * @return text
 */
function rtt_generate_video_shortcode( $html, $send_id, $attachment ) {
	if ( empty( $attachment ) ) {
		return $html;
	}

	$post_mime_type = get_post_mime_type( $attachment['id'] );
	$mime_type 		= explode( '/',  $post_mime_type );

	$medias 				= get_post_meta( $attachment['id'], '_rt_media_transcoded_files', true );

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
 *
 * @since 1.0.1
 *
 * @param  string $content  HTML contents of the activity
 * @param  object $activity Activity object
 *
 * @return string
 */
function rtt_bp_get_activity_content( $content, $activity = '' ) {
	if ( empty( $activity ) || empty( $content ) ) {
		return $content;
	}
	if ( class_exists( 'RTMediaModel' ) ) {
		$rt_model  = new RTMediaModel();
		$all_media = $rt_model->get( array( 'activity_id' => $activity->id ) );
		if ( empty( $all_media ) ) {
			return $content;
		}
		$attachement_url = wp_get_attachment_url( $all_media[0]->media_id );
		$pathinfo = rtt_wp_parse_url( $attachement_url );
		$file_extension = pathinfo( $pathinfo['path'], PATHINFO_EXTENSION );
		$message = '';

		/* Get default video thumbnail stored in attachment meta */
		$wp_video_thumbnail = get_post_meta( $all_media[0]->media_id, '_rt_media_video_thumbnail', true );

		/* Set the poster thumbnail if its empty */
		if ( ! empty( $wp_video_thumbnail ) ) {
			$file_url = $wp_video_thumbnail;
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
			$content = str_replace( 'poster=""', 'poster="' . $final_file_url . '"', $content );
		}

		/* If media is mp4 or mp3 then no need to show the message */
		if ( in_array( $file_extension, array( 'mp3', 'mp4' ), true ) ) {
			return $content;
		}

		/* If media is sent to the transcoder then show the message */
		if ( is_file_being_transcoded( $all_media[0]->media_id ) ) {
			$message = '<p class="transcoding-in-progress"> ' . esc_html__( 'This file is converting. Please refresh the page after some time.', 'transcoder' ) . '</p>';

			/**
			 * Allow user to filter the message text.
			 *
			 * @since 1.0.2
			 *
			 * @param string $message   Message to be displayed.
			 * @param object $activity  Activity object.
			 */
			$message = apply_filters( 'rtt_transcoding_in_progress_message', $message, $activity );
		}
		$message .= '</div>';

		return $content = str_replace( '</a></div>', '</a>' . $message, $content );
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
 * @param  string $url The URL to be parsed
 * @return array       Array containing the information about the URL
 */
function rtt_wp_parse_url( $url ) {
	if ( function_exists( 'wp_parse_url' ) ) {
		return wp_parse_url( $url );
	}
	$parts = @parse_url( $url );
	if ( ! $parts ) {
		// < PHP 5.4.7 compat, trouble with relative paths including a scheme break in the path
		if ( '/' == $url[0] && false !== strpos( $url, '://' ) ) {
			// Since we know it's a relative path, prefix with a scheme/host placeholder and try again
			if ( ! $parts = @parse_url( 'placeholder://placeholder' . $url ) ) {
				return $parts;
			}
			// Remove the placeholder values
			unset( $parts['scheme'], $parts['host'] );
		} else {
			return $parts;
		}
	}

	// < PHP 5.4.7 compat, doesn't detect schemeless URL's host field
	if ( '//' == substr( $url, 0, 2 ) && ! isset( $parts['host'] ) ) {
		$path_parts = explode( '/', substr( $parts['path'], 2 ), 2 );
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
 * @param  int $post_id Attachment ID
 */
function rtt_delete_related_transcoded_files( $post_id ) {
	if ( empty( $post_id ) ) {
		return false;
	}

	$transcoded_files = get_post_meta( $post_id, '_rt_media_transcoded_files', true );

	if ( ! empty( $transcoded_files ) && is_array( $transcoded_files )  ) {
		foreach ( $transcoded_files as $type => $files ) {
			if ( ! empty( $files ) && is_array( $files ) ) {
				$delete = rtt_delete_transcoded_files( $files );
			}
		}
	}
	$delete_meta = delete_post_meta( $post_id, '_rt_media_transcoded_files' );

	$thumbnails = get_post_meta( $post_id, '_rt_media_thumbnails', true );
	if ( ! empty( $thumbnails ) && is_array( $thumbnails )  ) {
		$delete = rtt_delete_transcoded_files( $thumbnails );
	}
	$delete_meta = delete_post_meta( $post_id, '_rt_media_thumbnails' );
}

add_action( 'delete_attachment', 'rtt_delete_related_transcoded_files', 99, 1 );

/**
 * Deletes/Unlinks the files given in the array
 *
 * @since 1.0.5
 *
 * @param  mixed $files 	Files array or file path string
 */
function rtt_delete_transcoded_files( $files ) {
	if ( ! is_array( $files ) ) {
		$files = array( $files );
	}
	$uploadpath = rtt_get_upload_dir();
	foreach ( $files as $key => $file ) {
		if ( ! empty( $file ) ) {
			@unlink( path_join( $uploadpath['basedir'], $file ) );
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
	/* for WordPress backward compatibility */
	if ( function_exists( 'wp_get_upload_dir' ) ) {
		$uploads = wp_get_upload_dir();
	} else {
		$uploads = wp_upload_dir();
	}

	return $uploads;
}
