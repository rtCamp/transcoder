<?php

/*
 * RTMedia Transcoder admin settings.
 */
?>

<h3 class="rtm-option-title"><?php esc_html_e( 'Audio/Video transcoding service', 'rtmedia-transcoder' ); ?></h3>
<div class="bp-media-settings-boxes-wrapper">
	<div id="bp-media-settings-boxes" class="bp-media-settings-boxes-container rtm-transcoder-setting-container">
		<p>
			<?php esc_html_e( 'rtMedia team has started offering an audio/video transcoding service.', 'rtmedia-transcoder' ); ?>
		</p>

		<p>
			<label for="new-api-key">
				<?php esc_html_e( 'Enter API KEY', 'rtmedia-transcoder' ); ?>
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
					<?php esc_html_e( 'Deluxe', 'rtmedia-transcoder' ); ?>
				</th>
			</tr>
			</thead>

			<tbody>
			<tr>
				<th>
					<?php esc_html_e( 'File Size Limit', 'rtmedia-transcoder' ); ?>
				</th>
				<td>
					200MB (<del>20MB</del>)
				</td>
				<td>
					16GB (<del>2GB</del>)
				</td>
			</tr>
			<tr>
				<th>
					<?php esc_html_e( 'Bandwidth (monthly)', 'rtmedia-transcoder' ); ?>
				</th>
				<td>
					10GB (<del>1GB</del>)
				</td>
				<td>
					100GB
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
					$0.10 per GB
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
					<?php esc_html_e( 'Webcam Recording', 'rtmedia-transcoder' ); ?>
				</th>
				<td colspan="2" class="column-posts">
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
					$action = '';
					$usage_details = get_site_option( 'rtmedia-transcoding-usage' );
					if ( isset( $usage_details[ $this->api_key ]->plan->name ) && ( strtolower( $usage_details[ $this->api_key ]->plan->name ) === 'free' ) ) {
						echo '<button disabled="disabled" type="submit" class="transcoding-try-now button button-primary">' . esc_html__( 'Current Plan', 'rtmedia-transcoder' ) . '</button>';
					} else {
						$action = '/wp-admin/?recurring-purchase=true&price-id=1';
					?>
					<!-- <form id="transcoding-try-now-form" action="<?php echo esc_attr( $action ); ?>" method="post">
						<button
							type="submit"
							class="button button-primary"><?php esc_html_e( 'Try Now', 'rtmedia-transcoder' ); ?>
						</button>
					</form> -->
					<a href="http://edd.rtcamp.info/checkout?edd_action=add_to_cart&download_id=71&edd_options[price_id]=1" target="_blank" class="button button-primary">
						Try Now
					</a>
				<?php }
					?>
				</td>
				<td>
					<?php //echo $this->transcoding_subscription_form( 'deluxe', 9.0 ); // @codingStandardsIgnoreLine ?>
					<a href="http://edd.rtcamp.info/checkout?edd_action=add_to_cart&download_id=71&edd_options[price_id]=2" target="_blank" class="button button-primary">
						Subscribe
					</a>
				</td>
			</tr>
			</tbody>
		</table>
	</div>
	<div id="bp-media-settings-boxes" class="bp-media-settings-boxes-container rtm-transcoder-setting-container">
		<h2>Transcoder Settings</h2>

		<form method="post" action="options.php">
		    <?php settings_fields( 'rtmedia-transcoder-settings-group' ); ?>
		    <?php do_settings_sections( 'rtmedia-transcoder-settings-group' ); ?>
		    <table class="form-table">
		        <tr valign="top">
			        <th scope="row">Number of Thumbnails</th>
			        <td><input type="text" name="number_of_thumbs" value="<?php echo esc_attr( get_option('number_of_thumbs')?get_option('number_of_thumbs'):5 ); ?>" /></td>
		        </tr>
		    </table>

		    <?php submit_button(); ?>

		</form>
	</div>
</div>
<div class="metabox-holder bp-media-metabox-holder rtm-transcoder-sidebar">
	<?php do_action( 'rtmedia_transcoder_before_widgets' ); ?>
</div>
