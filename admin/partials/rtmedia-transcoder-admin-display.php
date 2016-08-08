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
				<img src="<?php echo esc_url( RTMEDIA_URL ); ?>app/assets/admin/img/rtcamp-logo.png" alt="rtCamp"/>
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
				<input type="submit" id="api-key-submit" name="api-key-submit" value="<?php echo esc_attr__( 'Save', 'rtmedia-transcoder' ); ?>" class="button-primary" />
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
						<?php esc_html_e( '1GB', 'rtmedia-transcoder' ); ?>
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
		</div>
		<div id="bp-media-settings-boxes" class="bp-media-settings-boxes-container rtm-transcoder-setting-container">
			<h2>
				<?php esc_html_e( 'Transcoder Settings', 'rtmedia-transcoder' ); ?>
			</h2>

			<form method="post" action="options.php">
				<?php
				settings_fields( 'rtmedia-transcoder-settings-group' );
				do_settings_sections( 'rtmedia-transcoder-settings-group' );
				?>
				<table class="form-table">
					<tr valign="top">
						<th scope="row">
							<?php esc_html_e( 'Number of thumbnails that are generated on video upload', 'rtmedia-transcoder' ); ?>
						</th>
						<td>
							<?php
							$number_of_thumbnails = get_site_option( 'number_of_thumbs', 5 );
							if ( empty( $number_of_thumbnails ) ) {
								$number_of_thumbnails = 5;
							}
							?>
							<input type="number" name="number_of_thumbs" value="<?php echo esc_attr( $number_of_thumbnails ); ?>" min="1" max="10" />
							<p class="description">
								<?php
								esc_html_e( 'Your users will be able to change the thumbnail from the Media Edit section. Maximum value is 10.' );
								?>
							</p>
						</td>
					</tr>
				</table>

				<?php submit_button(); ?>

			</form>
		</div>
	</div>
	<div class="metabox-holder bp-media-metabox-holder rtm-transcoder-sidebar">
		<?php do_action( 'rtmedia_transcoder_before_widgets' ); ?>
	</div>
</div>
