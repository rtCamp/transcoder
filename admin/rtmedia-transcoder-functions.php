<?php
/**
 * RTMedia Transcoder functions.
 *
 * @since      1.0
 *
 * @package    rtMediaTranscoder
 * @subpackage rtMediaTranscoder/Functions
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

		$content = do_shortcode( "[video {$video_shortcode_attributes}]" );

		if ( is_file_being_transcoded( $attachment_id ) ) {
			$content .= '<p class="transcoding-in-progress"> ' . esc_html__( 'The file sent to transcoder', 'rtmedia-transcoder' ) . '</p>';
		}

		/**
		 * Allow user to filter [rt_media] short code content.
		 *
		 * @since 1.0
		 *
		 * @param string $content    	Activity content.
		 * @param int $attachment_id  	ID of attachment.
		 * @param string $media_url  	URL of the media.
		 * @param string $media_type  	Mime type of the media.
		 */
		return apply_filters( 'rt_media_shortcode', $content, $attachment_id, $media_url, $mime_type[0] );

	} elseif ( 'audio' === $mime_type[0] ) {

		$media_url 	= wp_get_attachment_url( $attachment_id );

		$audio_shortcode_attributes = 'src="' . $media_url . '"';

		foreach ( $attrs as $key => $value ) {
		    $audio_shortcode_attributes .= ' ' . $key . '="' . $value . '"';
		}

		$content = do_shortcode( "[audio {$audio_shortcode_attributes}]" );

		if ( is_file_being_transcoded( $attachment_id ) ) {
			$content .= '<p class="transcoding-in-progress"> ' . esc_html__( 'The file sent to transcoder', 'rtmedia-transcoder' ) . '</p>';
		}

		/**
		 * Allow user to filter [rt_media] short code content.
		 *
		 * @since 1.0
		 *
		 * @param string $content    	Activity content.
		 * @param int $attachment_id  	ID of attachment.
		 * @param string $media_url  	URL of the media.
		 * @param string $media_type  	Mime type of the media.
		 */
		return apply_filters( 'rt_media_shortcode', $content, $attachment_id, $media_url, $mime_type[0] );

	}
}

add_shortcode( 'rt_media', 'rt_media_shortcode' );

/**
 * Check whether the file is sent to the transcoder or not.
 *
 * @param  number  $attachment_id
 * @return boolean
 */
function is_file_being_transcoded( $attachment_id ) {
	$job_id = get_post_meta( $attachment_id, '_rtmedia_transcoding_job_id', true );
	if ( ! empty( $job_id ) ) {
		$transcoded_files = get_post_meta( $attachment_id, '_rt_media_transcoded_files', true );
		if ( empty( $transcoded_files ) ) {
			return true;
		}
	}
	return false;
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

/**
 * Parse the short codes in the activity content.
 *
 * @param  text   $content   activity body content.
 * @param  object $activity  activity object.
 *
 * @return text
 */
function rtmedia_transcoder_parse_shortcode( $content, $activity ) {
	return do_shortcode( $content );
}

add_filter( 'bp_get_activity_content_body', 'rtmedia_transcoder_parse_shortcode', 1, 2 );

if ( ! function_exists( 'rtmedia_video_editor_title' ) ) {
	/**
	 * Add the video thumbnail tab on video edit page.
	 *
	 * @return string
	 */
	function rtmedia_video_editor_title() {
		global $rtmedia_query;
		if ( isset( $rtmedia_query->media[0]->media_type ) && 'video' === $rtmedia_query->media[0]->media_type ) {
			$flag            = false;
			$media_id        = $rtmedia_query->media[0]->media_id;
			$thumbnail_array = get_post_meta( $media_id, 'rtmedia_media_thumbnails', true );
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
				echo '<li><a href="#panel2"><i class="dashicons dashicons-format-image rtmicon"></i>' . esc_html__( 'Video Thumbnail', 'rtmedia-transcoder' ) . '</a></li>';
			}
		}
	}
}

add_action( 'rtmedia_add_edit_tab_title', 'rtmedia_video_editor_title', 1000 );

if ( ! function_exists( 'rtmedia_vedio_editor_content' ) ) {
	/**
	 * Display the HTML to set the thumbnail for video.
	 *
	 * @return string HTML content
	 */
	function rtmedia_vedio_editor_content() {
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
					<p><?php esc_html_e( 'Video Thumbnail:', 'rtmedia-transcoder' ); ?></p>
					<ul>
						<?php
						$uploads 	= wp_get_upload_dir();
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
			} else { // check for array of thumbs stored as attachement ids
				global $rtmedia_media;
				$curr_cover_art = $rtmedia_media->cover_art;
				if ( ! empty( $curr_cover_art ) ) {
					$rtmedia_video_thumbs = get_rtmedia_meta( $rtmedia_query->media[0]->media_id, 'rtmedia-thumbnail-ids' );
					if ( is_array( $rtmedia_video_thumbs ) ) {
						?>
						<div class="rtmedia-change-cover-arts">
							<p><?php esc_html_e( 'Video Thumbnail:', 'rtmedia-transcoder' ); ?></p>
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

add_action( 'rtmedia_add_edit_tab_content', 'rtmedia_vedio_editor_content', 1000 );

/**
 * Set the video thumbnail
 *
 * @param number $id rtMedia activity ID
 */
if ( ! function_exists( 'set_video_thumbnail' ) ) {
	function set_video_thumbnail( $id ) {
		$media_type 	= rtmedia_type( $id );
		$attachment_id 	= rtmedia_media_id( $id );		// Get the wp attachment ID
		$thumbnail  = filter_input( INPUT_POST, 'rtmedia-thumbnail', FILTER_SANITIZE_URL );
		if ( 'video' === $media_type && ! empty( $thumbnail ) ) {
			update_post_meta( $attachment_id, '_rt_media_video_thumbnail', $thumbnail );
			if ( is_numeric( $thumbnail ) ) {
				$model = new RTMediaModel();
		        $model->update( array( 'cover_art' => $thumbnail ), array( 'id' => intval( $id ) ) );
		        update_activity_after_thumb_set( $id );
		    }
		}
	}
}

add_action( 'rtmedia_after_update_media', 'set_video_thumbnail', 12 );

if ( ! function_exists( 'update_activity_after_thumb_set' ) ) {
	/**
	 * Update the activity after thumb is set to the video
	 *
	 * @param  number $id media id
	 */
	function update_activity_after_thumb_set( $id ) {
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
				// get old activity content and save in activity meta
				$activity_get  = bp_activity_get_specific( array( 'activity_ids' => $activity_id ) );
				$activity      = $activity_get['activities'][0];
				$activity_body = $activity->content;
				bp_activity_update_meta( $activity_id, 'bp_old_activity_content', $activity_body );
				//extract activity text from old content
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
