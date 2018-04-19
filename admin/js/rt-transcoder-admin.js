(function( $ ) {
	$( document ).ready( function() {

		$( document ).on( 'click', '#api-key-submit', function( e ) {
			e.preventDefault();

			if ( $( this ).next( 'img' ).length === 0 ) {
				$( this ).after( $( '<img />' ).attr( 'src', rt_transcoder_script.loader_image ).addClass( 'rtt-loader' ) );
			}

			var data = {
				action: 'rt_enter_api_key',
				apikey: $( '#new-api-key' ).val()
			};

			// Since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
			$.getJSON( ajaxurl, data, function( response ) {
				if ( response.error === undefined && response.apikey ) {
					var tempUrl = window.location.href;
					var hash = window.location.hash;
					tempUrl = tempUrl.replace( hash, '' );

					if ( tempUrl.toString().indexOf( '&apikey=' + response.apikey ) == -1 ) {
						tempUrl += '&apikey=' + response.apikey;
					}
					if ( tempUrl.toString().indexOf( '&update=true' ) == -1 ) {
						tempUrl += '&update=true';
					}

					document.location.href = tempUrl + hash;
				} else {
					$( '#api-key-error' ).remove();
					var error_div = $( '<div/>', {
						'id' : 'api-key-error',
						'class': 'error',
					});
					$( 'h1:first' ).after( error_div.html( $( '<p />' ).text( response.error ) ) );
				}

				$( '#api-key-submit' ).next( 'img' ).remove();
			} );
		} );

		$( document ).on( 'click', '#disable-transcoding', function( e ) {
			e.preventDefault();
			if ( confirm( rt_transcoder_script.disable_encoding ) ) {

				var data = {
					action: 'rt_disable_transcoding'
				};

				if ( $( this ).next( 'img' ).length === 0 ) {
					$( this ).after( $( '<img />' ).attr( 'src', rt_transcoder_script.loader_image ).addClass( 'rtt-loader' ) );
				}

				// Since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
				$.post( ajaxurl, data, function( response ) {
					if ( response ) {

						if ( $( '#rtt-settings_updated' ).length > 0 ) {
							$( '#rtt-settings_updated p' ).html( response );
							$( '#rtt-settings_updated' ).show();
						} else {
						}

						//$( '#rtmedia-transcoding-usage' ).hide();
						$( '#disable-transcoding' ).next( 'img' ).remove();
						$( '#disable-transcoding' ).hide();
						$( '#enable-transcoding' ).css( 'display', 'inline' );
					} else {
					}
				} );
			}
		} );

		$( document ).on( 'click', '#enable-transcoding', function( e ) {
			e.preventDefault();
			if ( confirm( rt_transcoder_script.enable_encoding ) ) {

				var data = {
					action: 'rt_enable_transcoding'
				};

				if ( $( this ).next( 'img' ).length === 0 ) {
					$( this ).after( $( '<img />' ).attr( 'src', rt_transcoder_script.loader_image ).addClass( 'rtt-loader' ) );
				}

				$.post( ajaxurl, data, function( response ) {
					if ( response ) {
						if ( $( '#rtt-settings_updated' ).length > 0 ) {
							$( '#rtt-settings_updated p' ).html( response );
							$( '#rtt-settings_updated' ).show();
						}

						$( '#enable-transcoding' ).next( 'img' ).remove();
						$( '#enable-transcoding' ).hide();
						$( '#disable-transcoding' ).css( 'display', 'inline' );
					} else {
						$( '#settings-error-transcoding-disabled' ).remove();

					}
				} );
			}
		} );
	} );
})( jQuery );
