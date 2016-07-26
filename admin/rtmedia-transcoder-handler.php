<?php

/**
 * Description of RTMedia_Transcoder_Handler
 */
class RTMedia_Transcoder_Handler {

	protected $api_url = 'http://api-rtmedia.rtcamp.info/api/v1/';
	protected $edd_api_url = 'http://edd.rtcamp.info/';
	protected $free_product_id = 71;
	protected $sandbox_testing = 0;
	protected $merchant_id = 'paypal@rtcamp.com';
	public $uploaded = array();
	public $api_key = false;
	public $stored_api_key = false;
	public $edd_api_public_key = false;
	public $edd_api_token_key = false;
	public $video_extensions = ',mov,m4v,m2v,avi,mpg,flv,wmv,mkv,webm,ogv,mxf,asf,vob,mts,qt,mpeg,x-msvideo';
	public $music_extensions = ',wma,ogg,wav,m4a';

	public function __construct( $no_init = false ) {

		$this->api_key        		= get_site_option( 'rtmedia-transcoding-api-key' );
		$this->stored_api_key 		= get_site_option( 'rtmedia-transcoding-api-key-stored' );
		$this->edd_api_public_key 	= get_site_option( 'edd-api-public-key' ) ? get_site_option( 'edd-api-public-key' ) : 'NA';
		$this->edd_api_token_key	= get_site_option( 'edd-api-token-key' ) ? get_site_option( 'edd-api-token-key' ) : 'NA';

		if ( $no_init ) {
			return;
		}
		if ( is_admin() && $this->api_key ) {
			add_action( 'rtmedia_transcoder_before_widgets', array( $this, 'usage_widget' ) );
		}
		add_action( 'admin_init', array( $this, 'save_api_key' ), 1 );
		add_action( 'admin_init', array( $this, 'transcoding_api_subscribe' ), 1 );

		if ( $this->api_key ) {
			// store api key as different db key if user disable transcoding service
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
					} elseif ( 'deluxe' == strtolower( $usage_details[ $this->api_key ]->plan->name ) ) {
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
		//add_action('wp_ajax_rtmedia_regenerate_thumbnails', array($this, 'rtmedia_regenerate_thumbnails'), 1);
	}

	/**
	 *
	 * @param type $media_ids
	 * @param type $file_object
	 * @param type $uploaded
	 * @param string $autoformat thumbnails for genrating thumbs only
	 */
	function transcoding( $media_ids, $file_object, $uploaded, $autoformat = true ) {
		remove_action( 'add_attachment', array($this, 'wp_transcoding') );
		foreach ( $file_object as $key => $single ) {

			$type_arry        = explode( '.', $single['url'] );
			$type             = strtolower( $type_arry[ count( $type_arry ) - 1 ] );
			$not_allowed_type = array( 'mp3' );
			preg_match( '/video|audio/i', $single['type'], $type_array );

			if ( preg_match( '/video|audio/i', $single['type'], $type_array ) && ! in_array( $single['type'], array( 'audio/mp3' ) ) && ! in_array( $type, $not_allowed_type ) ) {
				$options             = rtmedia_get_site_option( 'rtmedia-options' );
				$options_vedio_thumb = $options['general_videothumbs'];
				if ( '' === $options_vedio_thumb ) {
					$options_vedio_thumb = 3;
				}

				$job_type = 'video';
				/**  fORMAT * */
				if ( 'video/mp4' === $single['type'] || 'mp4' === $type ) {
					$autoformat = 'thumbnails';
					$job_type = 'thumbnail';
				}

				$query_args   = array(
					'file_url'    => urlencode( $single['url'] ),
					'callbackurl' => urlencode( trailingslashit( home_url() ) . 'index.php' ),
					'force'       => 0,
					//'size'        => filesize( $single['file'] ),
					'formats'     => ( true === $autoformat ) ? ( ( 'video' === $type_array[0] ) ? 'mp4' : 'mp3' ) : $autoformat,
					'thumb_count' => $options_vedio_thumb,
					'rt_id'       => $media_ids[ $key ],
				);
				$args = array(
				        'method' 	=> 'POST',
				        'sslverify' => false,
				        'body' 		=> array(
			                'api_token' 	=> $this->api_key,
			                'job_type' 		=> $job_type,
			                'job_for' 		=> 'rtmedia',
			                //'email'         => admin_email(),
			                'file_url'    => urlencode( $single['url'] ),
							'callback_url' => urlencode( trailingslashit( home_url() ) . 'index.php' ),
							'force'       => 0,
							//'size'        => filesize( $single['file'] ),
							'formats'     => ( true === $autoformat ) ? ( ( 'video' === $type_array[0] ) ? 'mp4' : 'mp3' ) : $autoformat,
							'thumb_count' => $options_vedio_thumb,
				        ),
				);

				$transcoding_url = $this->api_url . 'job/';
				$upload_url   = add_query_arg( $query_args, $transcoding_url . $this->api_key );
				$upload_page = wp_remote_post( $transcoding_url, $args );

				if ( ! is_wp_error( $upload_page ) && ( ( isset( $upload_page['response']['code'] ) && ( 200 === intval( $upload_page['response']['code'] ) ) ) ) ) {
					$upload_info = json_decode( $upload_page['body'] );
					if ( isset( $upload_info->status ) && $upload_info->status && isset( $upload_info->job_id ) && $upload_info->job_id ) {
						$job_id = $upload_info->job_id;
						update_rtmedia_meta( $media_ids[ $key ], 'rtmedia-transcoding-job-id', $job_id );
						update_post_meta( $media_ids[ $key ], '_rtmedia_transcoding_job_id', $job_id );
						$model = new RTMediaModel();
						$model->update( array( 'cover_art' => '0' ), array( 'id' => $media_ids[ $key ] ) );
					}
				}
				$this->update_usage( $this->api_key );
			}
		}
	}

	/**
	 *
	 * @param int $attachment_id
	 * @param string $autoformat thumbnails for genrating thumbs only
	 */
	function wp_transcoding( $attachment_id ) {

		$post_parent = wp_get_post_parent_id( $attachment_id );
		if( $post_parent !== 0 ){
			$post_type 	= get_post_type( $post_parent );
			if ( $post_type == "rtmedia_album" ){
				return;
			}
		}

		$path 		= get_attached_file( $attachment_id );
		$url 		= wp_get_attachment_url( $attachment_id );
		$metadata 	= wp_read_video_metadata( $path );
		//print_r( $metadata ); die();
		//foreach ( $file_object as $key => $single ) {

		$type_arry        = explode( '.', $url );
		$type             = strtolower( $type_arry[ count( $type_arry ) - 1 ] );
		$not_allowed_type = array( 'mp3' );
		preg_match( '/video|audio/i', $metadata['mime_type'], $type_array );

		if ( preg_match( '/video|audio/i', $metadata['mime_type'], $type_array ) && ! in_array( $metadata['mime_type'], array( 'audio/mp3' ) ) && ! in_array( $type, $not_allowed_type ) ) {
			$options_vedio_thumb = 3;
			if ( '' === $options_vedio_thumb ) {
				$options_vedio_thumb = 3;
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
			        'body' 		=> array(
		                'api_token' 	=> $this->api_key,
		                'job_type' 		=> $job_type,
		                'job_for' 		=> 'wp-media',
		                //'email'         => admin_email(),
		                'file_url'    => urlencode( $url ),
						'callback_url' => urlencode( trailingslashit( home_url() ) . 'index.php' ),
						'force'       => 0,
						//'size'        => filesize( $single['file'] ),
						'formats'     => ( true === $autoformat ) ? ( ( 'video' === $type_array[0] ) ? 'mp4' : 'mp3' ) : $autoformat,
						'thumb_count' => $options_vedio_thumb,
			        ),
			);

			$transcoding_url = $this->api_url . 'job/';
			$upload_url   = add_query_arg( $query_args, $transcoding_url . $this->api_key );
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
		//}
	}

	public function bypass_video_audio( $flag, $file ) {
		if ( isset( $file['type'] ) ) {
			$fileinfo = explode( '/', $file['type'] );
			if ( in_array( $fileinfo[0], array( 'audio', 'video' ), true ) ) {
				$flag = true;
			}
		}

		return $flag;
	}

	public function is_valid_key_old( $key ) {
		$validate_url    = trailingslashit( $this->api_url ) . 'api/validate/' . $key;
		$validation_page = wp_remote_get( $validate_url, array( 'timeout' => 20 ) );
		if ( ! is_wp_error( $validation_page ) ) {
			$validation_info = json_decode( $validation_page['body'] );
			$status          = $validation_info->status;
		} else {
			$status = false;
		}

		return $status;
	}

	public function is_valid_key( $key ) {
		$validate_url    = trailingslashit( $this->edd_api_url ) . 'rt-eddsl-api/?rt-eddsl-license-key=' . $key;
		$validation_page = wp_remote_get( $validate_url, array( 'timeout' => 20 ) );
		if ( ! is_wp_error( $validation_page ) ) {
			$validation_info = json_decode( $validation_page['body'] );
			$status          = $validation_info->status;
		} else {
			$status = false;
		}

		return $status;
	}

	public function update_usage( $key ) {
		$usage_url  = trailingslashit( $this->api_url ) . 'usage/' . $key;
		$usage_page = wp_remote_get( $usage_url, array( 'timeout' => 20 ) );

		if ( ! is_wp_error( $usage_page ) ) {
			$usage_info = json_decode( $usage_page['body'] );
		} else {
			$usage_info = null;
		}
		update_site_option( 'rtmedia-transcoding-usage', array( $key => $usage_info ) );

		return $usage_info;
	}

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
			add_filter( 'wp_mail_content_type', function(){ return 'text/html';
			} );
			wp_mail( $admin_email_ids, $subject, sprintf( $message, size_format( $usage_details[ $this->api_key ]->used, 2 ), size_format( $usage_details[ $this->api_key ]->remaining, 2 ), size_format( $usage_details[ $this->api_key ]->total, 2 ) ) );
		}
		update_site_option( 'rtmedia-transcoding-usage-limit-mail', 1 );
	}

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
				add_filter( 'wp_mail_content_type', function(){ return 'text/html';
				} );
				wp_mail( $admin_email_ids, $subject, sprintf( $message, size_format( $usage_details[ $this->api_key ]->used, 2 ), 0, size_format( $usage_details[ $this->api_key ]->total, 2 ) ) );
			}
			update_site_option( 'rtmedia-transcoding-usage-limit-mail', 1 );
		}
	}

	public function transcoding_api_subscribe() {
		if ( isset( $_GET['recurring-purchase'] ) && sanitize_text_field( wp_unslash( $_GET['recurring-purchase'] ) ) == true ) {

			$email = get_site_option( 'admin_email' );
			$edd_redirect = $this->edd_api_url . 'edd-external-api/';
			get_currentuserinfo();

			$edd_args = array(
					'key'			=> $this->edd_api_public_key,
	                'token'         => $this->edd_api_token_key,
	                'trans_type'    => 'recurring-purchase',
	                'product_id'    => $this->free_product_id,
	                'price_id'      => isset( $_GET['price-id'] ) ? $_GET['price-id'] : '2',
	                'source_name'   => 'EXTERNAL-SITE-NAME',
	                'source_url'    => 'EXTERNAL-SITE-URL',
	                'first_name'    => $current_user->user_firstname ? $current_user->user_firstname : 'Transcoder',
	                'last_name'     => $current_user->user_lastname ? $current_user->user_lastname : 'User',
	                'email'         => $email,
	                'callback' 		=> urlencode( trailingslashit( home_url() ) ),
	                'receipt'       => true,
				);

			$edd_redirect = ( add_query_arg( $edd_args, $edd_redirect ) );
			// Build query
			//$edd_redirect .= http_build_query( $edd_args );

			// Fix for some sites that encode the entities
			//$edd_redirect = str_replace( '&amp;', '&', $edd_redirect );

			// Redirect to PayPal
			wp_redirect( $edd_redirect ); exit;
		}
	}

	public function save_api_key() {
		//die();
		if ( isset( $_GET['api_key_updated'] ) && sanitize_text_field( wp_unslash( $_GET['api_key_updated'] ) ) ) {
			if ( is_multisite() ) {
				add_action( 'network_admin_notices', array( $this, 'successfully_subscribed_notice' ) );
			}

			add_action( 'admin_notices', array( $this, 'successfully_subscribed_notice' ) );
		}

		$apikey = ( isset( $_GET['apikey'] ) ) ? sanitize_text_field( wp_unslash( $_GET['apikey'] ) ) : '';
		if ( isset( $_GET['apikey'] ) && is_admin() && isset( $_GET['page'] ) && ( 'rtmedia-transcoder' === sanitize_text_field( wp_unslash( $_GET['page'] ) ) ) && $this->is_valid_key( $apikey ) ) {
			if ( $this->api_key && ! ( isset( $_GET['update'] ) && sanitize_text_field( wp_unslash( $_GET['update'] ) ) ) ) {
				$unsubscribe_url = trailingslashit( $this->edd_api_url );

				$args = array(
				        'method' 	=> 'POST',
				        'sslverify' => false,
				        'body' 		=> array(
			                'trans_type'    => 'cancel-license',
			                'license-key' 	=> $this->api_key,
				        ),
				);
				$unsubscribe = wp_remote_post( $unsubscribe_url, $args );
			}

			update_site_option( 'rtmedia-transcoding-api-key', $apikey );
			update_site_option( 'rtmedia-transcoding-api-key-stored', $apikey );
			if ( isset( $_GET['public-key'] ) && isset( $_GET['token-key'] ) ) {
				update_site_option( 'edd-api-public-key', $_GET['public-key'] );
				update_site_option( 'edd-api-token-key', $_GET['token-key'] );
			}
			$usage_info  = $this->update_usage( $apikey );
			$return_page = add_query_arg( array(
				'page'            => 'rtmedia-transcoder',
				'api_key_updated' => $usage_info->plan->name ? $usage_info->plan->name : 'free',
			), admin_url( 'admin.php' ) );
			wp_safe_redirect( esc_url_raw( $return_page ) );

			die();
		}
	}

	public function allowed_types( $types ) {
		if ( isset( $types[0] ) && isset( $types[0]['extensions'] ) ) {
			if ( is_rtmedia_upload_video_enabled() && strpos( $this->video_extensions, $types[0]['extensions'] ) ) {
				$types[0]['extensions'] .= $this->video_extensions; //Allow all types of video file to be uploded
			}
			if ( is_rtmedia_upload_music_enabled() && strpos( $this->music_extensions, $types[0]['extensions'] ) ) {
				$types[0]['extensions'] .= $this->music_extensions; //Allow all types of music file to be uploded
			}
		}

		return $types;
	}

	public function allowed_types_admin_settings( $types ) {
		$allowed_video_string   = implode( ',', $types['video']['extn'] );
		$allowed_audio_string   = implode( ',', $types['music']['extn'] );
		$allowed_video          = explode( ',', $allowed_video_string . $this->video_extensions );
		$allowed_audio          = explode( ',', $allowed_audio_string . $this->music_extensions );
		$types['video']['extn'] = array_unique( $allowed_video );
		$types['music']['extn'] = array_unique( $allowed_audio );

		return $types;
	}

	public function successfully_subscribed_notice() {
		?>
		<div class="updated">
		<p> <?php esc_html_e( 'You have successfully subscribed for the ', 'rtmedia-transcoder' ) ?>
			<strong><?php printf( '%s', esc_html( sanitize_text_field( wp_unslash( $_GET['api_key_updated'] ) ) ) ); // @codingStandardsIgnoreLine ?></strong>
			<?php esc_html_e( ' plan', 'rtmedia-transcoder' ) ?>
		</p>
		</div><?php
	}



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
				 // . ( ( $remaining_size = size_format( $usage_details[ $this->api_key ]->remaining, 2 ) ) ? esc_html( $remaining_size ) : ($remaining_size <= -1)?'Unlimited':'0MB' ) . '</p>';
				} elseif ( $usage_details[ $this->api_key ]->remaining <= -1 ) {
					$content .= 'Unlimited';
				} else {
					$content .= '0MB';
				}
			}
			if ( isset( $usage_details[ $this->api_key ]->total ) ) {
				$content .= '<p><strong>' . esc_html__( 'Total', 'rtmedia-transcoder' ) . ':</strong> '; // . ( ( $total = size_format( $usage_details[ $this->api_key ]->total, 2 ) ) ? esc_html( $total ) : ($total <= -1)?'Unlimited':'' ) . '</p>';
				// esc_html( size_format( $usage_details[ $this->api_key ]->total, 2 ) )
				if ( $usage_details[ $this->api_key ]->total >= 0 ) {
					$content .= size_format( $usage_details[ $this->api_key ]->total, 2 );
				} elseif ( $usage_details[ $this->api_key ]->total <= -1 ) {
					$content .= 'Unlimited';
				} else {
					$content .= '';
				}
			}
			$usage = new rtProgress();
			$content .= $usage->progress_ui( $usage->progress( $usage_details[ $this->api_key ]->used, $usage_details[ $this->api_key ]->total ), false );
			if ( ( $usage_details[ $this->api_key ]->remaining <= 0 ) && ( -1 != $usage_details[ $this->api_key ]->remaining ) ) {
				$content .= '<div class="error below-h2"><p>' . esc_html__( 'Your usage limit has been reached. Upgrade your plan.', 'rtmedia-transcoder' ) . '</p></div>';
			}
		} else {
			$content .= '<div class="error below-h2"><p>' . esc_html__( 'Your API key is not valid or is expired.', 'rtmedia-transcoder' ) . '</p></div>';
		}
		new RTMediaAdminWidget( 'rtmedia-transcoding-usage', esc_html__( 'Transcoding Usage', 'rtmedia-transcoder' ), $content );
	}

	public function add_media_thumbnails( $post_id ) {
		$post_info              = get_post( $post_id );
		$post_date_string       = new DateTime( $post_info->post_date );
		$post_date              = $post_date_string->format( 'Y-m-d G:i:s' );
		$post_date_thumb_string = new DateTime( $post_info->post_date );
		$post_date_thumb        = $post_date_thumb_string->format( 'Y/m/' );
		$post_thumbs            = get_post_meta( $post_id, 'rtmedia_transcode_response', true );
		$post_thumbs_array      = maybe_unserialize( $post_thumbs );
		$largest_thumb_size     = 0;
		if( $post_thumbs_array['job_for'] == 'rtmedia' ){
			$model                  = new RTMediaModel();
			$media                  = $model->get( array( 'media_id' => $post_id ) );
			$media_id               = $media[0]->id;
		}
		$largest_thumb          = false;
		$upload_thumbnail_array = array();
		foreach ( $post_thumbs_array['thumbnail'] as $thumbs => $thumbnail ) {
			$thumbresource            = wp_remote_get( $thumbnail );
			$thumbinfo                = pathinfo( $thumbnail );
			$temp_name                = $thumbinfo['basename'];
			$temp_name                = urldecode( $temp_name );
			$temp_name_array          = explode( '/', $temp_name );
			$temp_name                = $temp_name_array[ count( $temp_name_array ) - 1 ];
			$thumbinfo['basename']    = $temp_name;
			$thumb_upload_info        = wp_upload_bits( $thumbinfo['basename'], null, $thumbresource['body'] );
			$upload_thumbnail_array[] = $thumb_upload_info['url'];
			$current_thumb_size = @filesize( $thumb_upload_info['file'] );
			if ( $current_thumb_size >= $largest_thumb_size ) {
				$largest_thumb_size = $current_thumb_size;
				$largest_thumb      = $thumb_upload_info['url'];
				if( $post_thumbs_array['job_for'] == 'rtmedia' ){
					$model->update( array( 'cover_art' => $thumb_upload_info['url'] ), array( 'media_id' => $post_id ) );
				}
			}
		}
		if( $post_thumbs_array['job_for'] == 'rtmedia' ){
			update_activity_after_thumb_set( $media_id );
		}
		update_post_meta( $post_id, 'rtmedia_media_thumbnails', $upload_thumbnail_array );

		return $largest_thumb;
	}

	/**
	 * Get post id from meta key and value
	 * @param string $key
	 * @param mixed $value
	 * @return int|bool
	 */
	function get_post_id_by_meta_key_and_value($key, $value) {
		global $wpdb;
		$meta = $wpdb->get_results("SELECT * FROM `".$wpdb->postmeta."` WHERE meta_key='".$wpdb->escape($key)."' AND meta_value='".$wpdb->escape($value)."'");
		if (is_array($meta) && !empty($meta) && isset($meta[0])) {
			$meta = $meta[0];
		}
		if (is_object($meta)) {
			return $meta->post_id;
		}
		else {
			return false;
		}
	}

	/**
	 * Function to handle the callback request by the FFMPEG transcoding server
	 *
	 * @since 1.0
	 */
	public function handle_callback() {
		require_once( ABSPATH . 'wp-admin/includes/image.php' );
		//todo: nonce required
		// @codingStandardsIgnoreStart
		if ( isset( $_REQUEST['job_for'] ) && ( $_REQUEST['job_for'] == "wp-media" ) ) {
			if ( isset( $_REQUEST['job_id'] ) ) {
				$has_thumbs = isset( $_POST['thumbnail'] ) ? true : false;
				$flag       = false;
				global $wpdb;

				$id = $this->get_post_id_by_meta_key_and_value( '_rtmedia_transcoding_job_id', $_REQUEST['job_id'] );
				/*if ( ! isset( $meta_details[0] ) ) {
					$id = intval( $_REQUEST['rt_id'] );
				} else {
					$id = $meta_details[0]->media_id;
				}*/
				if ( isset( $id ) && is_numeric( $id ) ) {
					$attachment_id      = $id;
					update_post_meta( $attachment_id, 'rtmedia_transcode_response', $_POST );

					if ( $has_thumbs ) {
						$cover_art = $this->add_media_thumbnails( $attachment_id );
					}

					if ( isset( $_POST['format'] ) && 'thumbnail' === sanitize_text_field( wp_unslash( $_POST['format'] ) ) ) {
						die();
					}
					if(isset( $_REQUEST['download_url'] )){
						$attachemnt_post                = get_post( $attachment_id );
						$download_url                   = urldecode( urldecode( $_REQUEST['download_url'] ) );
						$new_wp_attached_file_pathinfo 	= pathinfo( $download_url );
						$post_mime_type                	= 'mp4' === $new_wp_attached_file_pathinfo['extension'] ? 'video/mp4' : 'audio/mp3';
						try {
							$file_bits = file_get_contents( $download_url );
						} catch ( Exception $e ) {
							$flag = $e->getMessage();
						}
						if ( $file_bits ) {

							$old_attachment_file = get_attached_file( $attachment_id );
							if( function_exists( 'wp_delete_file' ) ){  // wp_delete_file is introduced in WordPress 4.2
								wp_delete_file( $old_attachment_file );
							} else {
								unlink( $old_attachment_file );
							}

							add_filter( 'upload_dir', array( $this, 'upload_dir' ) );
							$upload_info = wp_upload_bits( $new_wp_attached_file_pathinfo['basename'], null, $file_bits );
							$wpdb->update( $wpdb->posts, array(
								'guid'           => $upload_info['url'],
								'post_mime_type' => $post_mime_type,
							), array( 'ID' => $attachment_id ) );
							$old_wp_attached_file          = get_post_meta( $attachment_id, '_wp_attached_file', true );
							$old_wp_attached_file_pathinfo = pathinfo( $old_wp_attached_file );
							update_post_meta( $attachment_id, '_wp_attached_file', str_replace( $old_wp_attached_file_pathinfo['basename'], $new_wp_attached_file_pathinfo['basename'], $old_wp_attached_file ) );

						} else {
							$flag = esc_html__( 'Could not read file.', 'rtmedia-transcoder' );
							error_log( $flag );
						}
					}
				} else {
					$flag = esc_html__( 'Something went wrong. The required attachment id does not exists. It must have been deleted.', 'rtmedia-transcoder' );
					error_log( $flag );
				}
				// @codingStandardsIgnoreEnd
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
					update_post_meta( $attachment_id, 'rtmedia_transcode_response', $_POST );

					if ( $has_thumbs ) {
						$cover_art = $this->add_media_thumbnails( $attachment_id );
					}

					if ( isset( $_POST['format'] ) && 'thumbnail' === sanitize_text_field( wp_unslash( $_POST['format'] ) ) ) {
						die();
					}
					if(isset( $_REQUEST['download_url'] )){
						$this->uploaded['context']      = $media[0]->context;
						$this->uploaded['context_id']   = $media[0]->context_id;
						$this->uploaded['media_author'] = $media[0]->media_author;
						$attachemnt_post                = get_post( $attachment_id );
						$download_url                   = urldecode( urldecode( $_REQUEST['download_url'] ) );
						$new_wp_attached_file_pathinfo = pathinfo( $download_url );
						$post_mime_type                = 'mp4' === $new_wp_attached_file_pathinfo['extension'] ? 'video/mp4' : 'audio/mp3';
						try {
							$file_bits = file_get_contents( $download_url );
						} catch ( Exception $e ) {
							$flag = $e->getMessage();
						}
						if ( $file_bits ) {

							$old_attachment_file = get_attached_file( $attachment_id );
							if( function_exists( 'wp_delete_file' ) ){  // wp_delete_file is introduced in WordPress 4.2
								wp_delete_file( $old_attachment_file );
							} else {
								unlink( $old_attachment_file );
							}

							add_filter( 'upload_dir', array( $this, 'upload_dir' ) );
							$upload_info = wp_upload_bits( $new_wp_attached_file_pathinfo['basename'], null, $file_bits );
							$wpdb->update( $wpdb->posts, array(
								'guid'           => $upload_info['url'],
								'post_mime_type' => $post_mime_type,
							), array( 'ID' => $attachment_id ) );
							$old_wp_attached_file          = get_post_meta( $attachment_id, '_wp_attached_file', true );
							$old_wp_attached_file_pathinfo = pathinfo( $old_wp_attached_file );
							update_post_meta( $attachment_id, '_wp_attached_file', str_replace( $old_wp_attached_file_pathinfo['basename'], $new_wp_attached_file_pathinfo['basename'], $old_wp_attached_file ) );

							$activity_id = $media[0]->activity_id;
							if ( $activity_id ) {
								$content          = $wpdb->get_var( $wpdb->prepare( "SELECT content FROM {$wpdb->base_prefix}bp_activity WHERE id = %d", $activity_id ) );
								$activity_content = str_replace( $attachemnt_post->guid, $upload_info['url'], $content );
								$wpdb->update( $wpdb->base_prefix . 'bp_activity', array( 'content' => $activity_content ), array( 'id' => $activity_id ) );
							}
						} else {
							$flag = esc_html__( 'Could not read file.', 'rtmedia-transcoder' );
							error_log( $flag );
						}
					}
				} else {
					$flag = esc_html__( 'Something went wrong. The required attachment id does not exists. It must have been deleted.', 'rtmedia-transcoder' );
					error_log( $flag );
				}
				// @codingStandardsIgnoreEnd
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
	}

	public function free_transcoding_subscribe() {
		global $current_user;
		$email         = get_site_option( 'admin_email' );
		$usage_details = get_site_option( 'rtmedia-transcoding-usage' );
		if ( isset( $usage_details[ $this->api_key ]->plan->name ) && ( 'free' === strtolower( $usage_details[ $this->api_key ]->plan->name ) ) ) {
			//echo wp_json_encode( array( 'error' => 'Your free subscription is already activated.' ) );
		} else {
			$free_subscription_url = esc_url_raw( add_query_arg( array( 'email' => urlencode( $email ) ), trailingslashit( $this->api_url ) . 'api/free/' ) );
			if ( $this->api_key ) {
				$free_subscription_url = esc_url_raw( add_query_arg( array(
					'email'  => urlencode( $email ),
					'apikey' => $this->api_key,
				), $free_subscription_url ) );
			}
			$edd_redirect = $this->edd_api_url . 'edd-external-api/?key=' . $this->edd_api_public_key;
			get_currentuserinfo();

			$edd_args = array(
					'key'     		=> $this->edd_api_public_key,
	                'token'         => $this->edd_api_token_key,
	                'trans_type'    => 'recurring-purchase',
	                'product_id'    => $this->free_product_id,
	                'price_id'      => '2',
	                'source_name'   => 'EXTERNAL-SITE-NAME',
	                'source_url'    => 'EXTERNAL-SITE-URL',
	                'first_name'    => $current_user->user_firstname ? $current_user->user_firstname : 'Transcoder',
	                'last_name'     => $current_user->user_lastname ? $current_user->user_lastname : 'User',
	                'email'         => $email,
	                'callback' 		=> urlencode( trailingslashit( home_url() ) . 'index.php' ),
	                'receipt'       => true,
				);

			// Build query
			$edd_redirect .= http_build_query( $edd_args );

			// Fix for some sites that encode the entities
			$edd_redirect = str_replace( '&amp;', '&', $edd_redirect );

			// Redirect to PayPal
			wp_redirect( $edd_redirect ); exit;

			//print_r($args);
			$free_subscribe_page = wp_remote_post( $edd_url, $args );
			//print_r($free_subscribe_page);
			//$body           = wp_remote_retrieve_body( $response );
			//$data           = json_decode( $body );
			//$free_subscribe_page = wp_remote_get( $free_subscription_url, array( 'timeout' => 120 ) );
			if ( ! is_wp_error( $free_subscribe_page ) && ( isset( $free_subscribe_page['response']['code'] ) && ( 200 === $free_subscribe_page['response']['code'] ) ) ) {
				$body           	= wp_remote_retrieve_body( $free_subscribe_page );
				$subscription_info 	= json_decode( $body );
				//var_dump($subscription_info->download_data[0]->license_key);
				//echo "Inside";
				if ( isset( $subscription_info->success ) && $subscription_info->success ) {
					update_site_option( 'edd-api-public-key', $subscription_info->edd_api_public_key );
					update_site_option( 'edd-api-token-key', $subscription_info->edd_api_token_key );
					echo wp_json_encode( array( 'apikey' => $subscription_info->download_data[0]->license_key ) );
				} else {
					echo wp_json_encode( array( 'error' => $subscription_info->message ) );
				}
			} else {
				echo wp_json_encode( array( 'error' => esc_html__( 'Something went wrong please try again.', 'rtmedia-transcoder' ) ) );
			}
		}
		die();
	}

	public function hide_transcoding_notice() {
		update_site_option( 'rtmedia-transcoding-service-notice', true );
		update_site_option( 'rtmedia-transcoding-expansion-notice', true );
		echo true;
		die();
	}

	public function unsubscribe_transcoding() {
		$unsubscribe_url  = trailingslashit( $this->api_url ) . 'api/cancel/' . $this->api_key;
		$unsubscribe_page = wp_remote_post( $unsubscribe_url, array(
			'timeout' => 120,
			'body'    => array( 'note' => sanitize_text_field( wp_unslash( $_GET['note'] ) ) ), // @codingStandardsIgnoreLine
		) );
		if ( ! is_wp_error( $unsubscribe_page ) && ( ! isset( $unsubscribe_page['headers']['status'] ) || ( isset( $unsubscribe_page['headers']['status'] ) && ( 200 === $unsubscribe_page['headers']['status'] ) ) ) ) {
			$subscription_info = json_decode( $unsubscribe_page['body'] );
			if ( isset( $subscription_info->status ) && $subscription_info->status ) {
				echo wp_json_encode( array(
					'updated' => esc_html__( 'Your subscription was cancelled successfully', 'rtmedia-transcoder' ),
					'form'    => $this->transcoding_subscription_form( $_GET['plan'], $_GET['price'] ), // @codingStandardsIgnoreLine
				) );
			}
		} else {
			echo wp_json_encode( array( 'error' => esc_html__( 'Something went wrong please try again.', 'rtmedia-transcoder' ) ) );
		}
		die();
	}

	public function enter_api_key() {
		if ( isset( $_GET['apikey'] ) && '' !== $_GET['apikey'] ) {
			echo wp_json_encode( array( 'apikey' => $_GET['apikey'] ) );
		} else {
			echo wp_json_encode( array( 'error' => esc_html__( 'Please enter the api key.', 'rtmedia-transcoder' ) ) );
		}
		die();
	}

	public function disable_transcoding() {
		update_site_option( 'rtmedia-transcoding-api-key', '' );
		esc_html_e( 'Transcoding disabled successfully.', 'rtmedia-transcoder' );
		die();
	}

	function enable_transcoding() {
		update_site_option( 'rtmedia-transcoding-api-key', $this->stored_api_key );
		esc_html_e( 'Transcoding enabled successfully.', 'rtmedia-transcoder' );
		die();
	}

	function upload_dir( $upload_dir ) {
		global $rtmedia_interaction, $rt_media_media;
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

		$upload_dir['path'] = trailingslashit( str_replace( $upload_dir['subdir'], '', $upload_dir['path'] ) )
		                      . $rtmedia_folder_name . '/' . $rtmedia_upload_prefix . $id . $upload_dir['subdir'];
		$upload_dir['url']  = trailingslashit( str_replace( $upload_dir['subdir'], '', $upload_dir['url'] ) )
		                      . $rtmedia_folder_name . '/' . $rtmedia_upload_prefix . $id
		                      . $upload_dir['subdir'];

		$upload_dir = apply_filters( 'rtmedia_filter_upload_dir', $upload_dir, $this->uploaded );

		return $upload_dir;
	}

	public function retranscoding( $attachment, $autoformat = true ) {
		$rtmedia_model = new RTMediaModel();
		$media_array   = $rtmedia_model->get( array( 'media_id' => $attachment ) );
		$media_id      = $media_array[0]->id;
		$attached_file = get_post_meta( $attachment, '_wp_attached_file' );
		$upload_path   = trim( get_option( 'upload_path' ) );
		if ( empty( $upload_path ) || 'wp-content/uploads' === $upload_path ) {
			$dir = WP_CONTENT_DIR . '/uploads';
		} elseif ( 0 !== strpos( $upload_path, ABSPATH ) ) {
			// $dir is absolute, $upload_path is (maybe) relative to ABSPATH
			$dir = path_join( ABSPATH, $upload_path );
		} else {
			$dir = $upload_path;
		}
		$file             = trailingslashit( $dir ) . $attached_file[0];
		$url              = wp_get_attachment_url( $attachment );
		$file_name_array  = explode( '/', $url );
		$file_name        = $file_name_array[ count( $file_name_array ) - 1 ];
		$file_object      = array();
		$media_type       = get_post_field( 'post_mime_type', $attachment );
		$media_type_array = explode( '/', $media_type );
		if ( 'video' === $media_type_array[0] ) {
			$file_object[] = array(
				'file' => $file,
				'url'  => $url,
				'name' => $file_name,
				'type' => $media_type,
			);
			$this->transcoding( array( $media_id ), $file_object, array(), $autoformat );
		}
	}

	function rtmedia_regenerate_thumbnails() {
		$this->retranscoding( intval( $_REQUEST['rtretranscoding'] ) );
		die();
	}
}


if ( isset( $_REQUEST['rtretranscoding'] ) ) {
	$rth_obj = new RTMedia_Transcoder_Handler( true );
	$rth_obj->retranscoding( intval( $_REQUEST['rtretranscoding'] ) );
}
