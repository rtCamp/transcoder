<?php
/**
 * RTMedia Transcoder admin settings.
 *
 * @since      1.0
 *
 * @package    rtMediaTranscoder
 * @subpackage rtMediaTranscoder/Admin/Partials
 */

?>
<div class="wrap">
	<h1 class="rtm-option-title">
		<?php esc_html_e( 'rtMedia Transcoder Service Settings', 'rtmedia-transcoder' ); ?>
		<span class="alignright by">
			<a class="rt-link"
			   href="https://rtmedia.io/?utm_source=dashboard&utm_medium=plugin&utm_campaign=rtmedia-transcoder"
			   target="_blank"
			   title="rtCamp : <?php esc_attr_e( 'Empowering The Web With WordPress', 'rtmedia-transcoder' ); ?>">
				<img src="<?php echo esc_url( RTMEDIA_TRANSCODER_URL ); ?>admin/images/rtcamp-logo.png" alt="rtCamp"/>
			</a>
		</span>
	</h1>
	<div id="rtt-settings_updated" class="updated settings-error notice is-dismissible hide">
		<p></p>
		<button type="button" class="notice-dismiss"><span class="screen-reader-text"><?php echo esc_attr__( 'Dismiss this notice', 'rtmedia-transcoder' ); ?>.</span></button>
	</div>
	<div class="bp-media-settings-boxes-wrapper">
		<div id="bp-media-settings-boxes" class="bp-media-settings-boxes-container rtm-transcoder-setting-container">
			<p>
				<label for="new-api-key">
					<?php esc_html_e( 'Enter License Key', 'rtmedia-transcoder' ); ?>
				</label>
				<input id="new-api-key" type="text" name="new-api-key" value="<?php echo esc_attr( $this->stored_api_key ); ?>" size="60" />
				<input type="submit" id="api-key-submit" name="api-key-submit" value="<?php echo esc_attr__( 'Save Key', 'rtmedia-transcoder' ); ?>" class="button-primary" />
			</p>

			<p>
				<?php
				$enable_btn_style  = 'display:none;';
				$disable_btn_style = 'display:none;';
				if ( $this->api_key ) {
					$enable_btn_style = 'display:block;';
				} elseif ( $this->stored_api_key ) {
					$disable_btn_style = 'display:block;';
				}
				?>
				<input type="submit" id="disable-transcoding" name="disable-transcoding" value="Disable Transcoding" class="button-secondary" style="<?php echo esc_attr( $enable_btn_style ); ?>" />
				<input type="submit" id="enable-transcoding" name="enable-transcoding" value="Enable Transcoding" class="button-secondary" style="<?php echo esc_attr( $disable_btn_style ); ?>" />
			</p>

			<!-- Results table headers -->
			<table class="bp-media-transcoding-table fixed widefat rtm-transcoder-plan-table">
				<thead>
				<tr>
					<th>
						<?php esc_html_e( 'Feature\Plan', 'rtmedia-transcoder' ); ?>
					</th>
					<th>
						<?php esc_html_e( 'Free', 'rtmedia-transcoder' ); ?>
					</th>
					<th>
						<?php esc_html_e( 'Silver', 'rtmedia-transcoder' ); ?>
					</th>
				</tr>
				</thead>

				<tbody>
				<tr>
					<th>
						<?php esc_html_e( 'File Size Limit', 'rtmedia-transcoder' ); ?>
					</th>
					<td>
						<?php esc_html_e( '100MB', 'rtmedia-transcoder' ); ?>
					</td>
					<td>
						<?php esc_html_e( '16GB', 'rtmedia-transcoder' ); ?>
					</td>
				</tr>
				<tr>
					<th>
						<?php esc_html_e( 'Bandwidth (monthly)', 'rtmedia-transcoder' ); ?>
					</th>
					<td>
						<?php esc_html_e( '5GB', 'rtmedia-transcoder' ); ?>
					</td>
					<td>
						<?php esc_html_e( '100GB', 'rtmedia-transcoder' ); ?>
					</td>
				</tr>
				<tr>
					<th>
						<?php esc_html_e( 'Overage Bandwidth', 'rtmedia-transcoder' ); ?>
					</th>
					<td>
						<?php esc_html_e( 'Not Available', 'rtmedia-transcoder' ); ?>
					</td>
					<td>
						<?php esc_html_e( 'Currently not charged', 'rtmedia-transcoder' ); ?>
					</td>
				</tr>
				<tr>
					<th>
						<?php esc_html_e( 'Amazon S3 Support', 'rtmedia-transcoder' ); ?>
					</th>
					<td>
						<?php esc_html_e( 'Not Available', 'rtmedia-transcoder' ); ?>
					</td>
					<td>
						<?php esc_html_e( 'Coming Soon', 'rtmedia-transcoder' ); ?>
					</td>
				</tr>
				<tr>
					<th>
						<?php esc_html_e( 'HD Profile', 'rtmedia-transcoder' ); ?>
					</th>
					<td>
						<?php esc_html_e( 'Not Available', 'rtmedia-transcoder' ); ?>
					</td>
					<td>
						<?php esc_html_e( 'Coming Soon', 'rtmedia-transcoder' ); ?>
					</td>
				</tr>
				<tr>
					<th>
						<?php esc_html_e( 'Pricing', 'rtmedia-transcoder' ); ?>
					</th>
					<td>
						<?php esc_html_e( 'Free', 'rtmedia-transcoder' ); ?>
					</td>
					<td>
						<?php esc_html_e( '$9/month', 'rtmedia-transcoder' ); ?>
					</td>
				</tr>
				<tr>
					<th>
						&nbsp;
					</th>
					<td>
						<?php
							$allowed_tags = array(
								'a' => array(
									'href' => array(),
									'target' => array(),
									'title' => array(),
									'class' => array(),
								),
								'div' => array(
									'title' => array(),
									'id' => array(),
								),
								'button' => array(
									'disabled' => array(),
									'data-plan' => array(),
									'data-price' => array(),
									'type' => array(),
									'class' => array(),
								),
								'textarea' => array(
									'rows' => array(),
									'cols' => array(),
									'id' => array(),
								),
								'p' => array(),
							);

							$button = $this->transcoding_subscription_button( 'free', 0 );
							echo wp_kses( $button, $allowed_tags );
						?>
					</td>
					<td>
						<?php
							$button = $this->transcoding_subscription_button( 'silver', 9 );
							echo wp_kses( $button, $allowed_tags );
						?>
					</td>
				</tr>
				</tbody>
			</table>
			
			<div class="rtm-transcoder-setting-form">
				<form method="post" action="options.php">
					<?php
					settings_fields( 'rtmedia-transcoder-settings-group' );
					do_settings_sections( 'rtmedia-transcoder-settings-group' );
					?>
					<table class="form-table">
						<tr valign="top">
							<th scope="row">
								<?php esc_html_e( 'Number of video thumbnails generated', 'rtmedia-transcoder' ); ?>
							</th>
							<td>
								<?php
								$number_of_thumbnails = get_site_option( 'number_of_thumbs', 5 );
								if ( empty( $number_of_thumbnails ) ) {
									$number_of_thumbnails = 5;
								}
								?>
								<input type="number" name="number_of_thumbs" value="<?php echo esc_attr( $number_of_thumbnails ); ?>" min="1" max="10" />
								<span class="rtm-tooltip">
									<i class="dashicons dashicons-info rtmicon"></i>
									<span class="rtm-tip">
										<?php
										esc_html_e( 'This field specifies the number of video thumbnails that will be generated by the Transcoder. To choose from the generated thumbnails for a video, go to ​Media > Edit > Video Thumbnails. Thumbnails are only generated when the video is first uploaded. Maximum value is 10.' );
										?>
									</span>
								</span>	
							</td>
						</tr>
					</table>
					<div class="rtm-button-container">
						<div class="rtm-social-links alignleft">
							<a href="http://twitter.com/rtMediaWP" class="twitter" target="_blank">
								<span class="dashicons dashicons-twitter"></span>
							</a>
							<a href="https://www.facebook.com/rtCamp.solutions" class="facebook" target="_blank">
								<span class="dashicons dashicons-facebook"></span>
							</a>
							<a href="http://profiles.wordpress.org/rtcamp" class="wordpress" target="_blank">
								<span class="dashicons dashicons-wordpress"></span>
							</a>
							<a href="https://rtmedia.io/feed/" class="rss" target="_blank">
								<span class="dashicons dashicons-rss"></span>
							</a>
						</div>
						<input id="submit" class="button button-primary alignright" type="submit" value="Save Changes" name="submit">
					</div>
				</form>
			</div>
		</div>	
	</div>
	
	<div class="metabox-holder bp-media-metabox-holder rtm-transcoder-sidebar">
		<?php do_action( 'rtmedia_transcoder_before_widgets' ); ?>
		<div class="postbox" id="rt-spread-the-word">
			<h3 class="hndle">
				<span>
					<?php esc_html_e( 'Spread the Word', 'rtmedia-transcoder' ); ?>
				</span>
			</h3>
			<div class="inside">
				<div id="social" class="rtm-social-share">
					<?php
					$message = sprintf( esc_html__( 'I use @rtMediaWP http://rt.cx/rtmedia on %s', 'rtmedia-transcoder' ), home_url() );
					?>
					<p>
						<a href="http://twitter.com/home/?status=<?php echo esc_attr( $message ) ?>" class="button twitter" target= "_blank" title="<?php esc_attr_e( 'Post to Twitter Now', 'rtmedia-transcoder' ); ?>">
							<?php esc_html_e( 'Post to Twitter', 'rtmedia-transcoder' ); ?>
							<span class="dashicons dashicons-twitter"></span>
						</a>
					</p>
					<p>
						<a href="https://www.facebook.com/sharer/sharer.php?u=https://rtmedia.io/" class="button facebook" target="_blank" title="<?php esc_attr_e( 'Share on Facebook Now', 'rtmedia-transcoder' ); ?>">
							<?php esc_html_e( 'Share on Facebook', 'rtmedia-transcoder' ); ?>
							<span class="dashicons dashicons-facebook"></span>
						</a>
					</p>
					<p>
						<a href="http://wordpress.org/support/view/plugin-reviews/rtmedia-transcoder?rate=5#postform" class="button wordpress" target= "_blank" title="<?php esc_attr_e( 'Rate rtMedia on Wordpress.org', 'rtmedia-transcoder' ); ?>">
							<?php esc_html_e( 'Rate on Wordpress.org', 'rtmedia-transcoder' ); ?>
							<span class="dashicons dashicons-wordpress"></span>
						</a>
					</p>
					<p>
						<a href="https://rtmedia.io/feed/" class="button rss" target="_blank" title="<?php esc_attr_e( 'Subscribe to our Feeds', 'rtmedia-transcoder' ); ?>">
							<?php esc_html_e( 'Subscribe to our Feeds', 'rtmedia-transcoder' ); ?>
							<span class="dashicons dashicons-rss"></span>
						</a>
					</p>
				</div>
			</div>
		</div>
		<div class="postbox" id="rt-subscribe">
			<h3 class="hndle">
				<span>
					<?php esc_html_e( 'Subscribe', 'rtmedia-transcoder' ); ?>
				</span>
			</h3>
			<div class="inside">
				<?php $current_user = wp_get_current_user(); ?>
				<form action="http://rtcamp.us1.list-manage1.com/subscribe/post?u=85b65c9c71e2ba3fab8cb1950&amp;id=9e8ded4470" method="post" id="mc-embedded-subscribe-form" name="mc-embedded-subscribe-form" class="validate" target="_blank" novalidate>
					<div class="mc-field-group">
						<input type="email" value="<?php echo esc_attr( $current_user->user_email ); ?>" name="EMAIL" placeholder="Email" class="required email" id="mce-EMAIL" />
						<input style="display:none;" type="checkbox" checked="checked" value="1" name="group[1721][1]" id="mce-group[1721]-1721-0" />
						<input type="submit" value="<?php esc_attr_e( 'Subscribe', 'rtmedia-transcoder' ); ?>" name="subscribe" id="mc-embedded-subscribe" class="button" />
						<div id="mce-responses" class="clear">
							<div class="response" id="mce-error-response" style="display:none"></div>
							<div class="response" id="mce-success-response" style="display:none"></div>
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>
