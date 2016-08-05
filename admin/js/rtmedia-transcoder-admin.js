/*var rtMediaAdmin = new Object();

rtMediaAdmin.templates = {
	rtm_image : wp.template( 'rtm-image' ),
	rtm_msg_div : wp.template( 'rtm-msg-div' ),
	rtm_album_favourites_importer : wp.template( 'rtm-album-favourites-importer' ),
	rtm_map_mapping_failure : wp.template( 'rtm-map-mapping-failure' ),
	rtm_p_tag : wp.template( 'rtm-p-tag' ),
	rtm_theme_overlay : wp.template( 'rtm-theme-overlay' )
};*/

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

jQuery( document ).ready( function( $ ) {
	jQuery( document ).on( 'click', "#bpm-services .encoding-try-now,#rtm-services .encoding-try-now", function ( e ) {
		e.preventDefault();
		if ( confirm( rtmedia_admin_strings.are_you_sure ) ) {
			var data = {
				src   : rtmedia_transcoder_admin_url + "images/wpspin_light.gif"
			};

			//jQuery( this ).after( rtMediaAdmin.templates.rtm_image( data ) );

			var data = {
				action: 'rtmedia_free_encoding_subscribe'
			};

			// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
			jQuery.getJSON( ajaxurl, data, function ( response ) {
				if ( response.error === undefined && response.apikey ) {
					var tempUrl = window.location.href;
					var hash = window.location.hash;
					tempUrl = tempUrl.replace( hash, '' );
					document.location.href = tempUrl + '&apikey=' + response.apikey + hash;
				} else {
					jQuery( '.encoding-try-now' ).next().remove();
					jQuery( '#settings-error-encoding-error' ).remove();

					var data = {
						id : 'settings-error-encoding-error',
						msg : response.error,
						class : 'error'
					};

					//jQuery( '#bp-media-settings-boxes' ).before( rtMediaAdmin.templates.rtm_msg_div( data ) );
				}
			} );
		}
	} );

	jQuery( document ).on( 'click', '#api-key-submit', function ( e ) {
		e.preventDefault();

		if ( jQuery( this ).next( 'img' ).length == 0 ) {
			var data = {
				src   : rtmedia_transcoder_admin_url + "images/wpspin_light.gif"
			};

			//jQuery( this ).after( rtMediaAdmin.templates.rtm_image( data ) );
		}

		var data = {
			action: 'rtmedia_enter_api_key',
			apikey: jQuery( '#new-api-key' ).val()
		};

		// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
		jQuery.getJSON( ajaxurl, data, function ( response ) {
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
				jQuery( '#settings-error-api-key-error' ).remove();

				var data = {
					id : 'settings-error-api-key-error',
					msg : response.error,
					class : 'error'
				};

				//jQuery( 'h2:first' ).after( rtMediaAdmin.templates.rtm_msg_div( data ) );
			}

			jQuery( '#api-key-submit' ).next( 'img' ).remove();
		} );
	} );

	jQuery( document ).on( 'click', '#disable-transcoding', function ( e ) {
		e.preventDefault();
		if ( confirm( disable_encoding ) ) {
			var data = {
				src   : rtmedia_transcoder_admin_url + "images/wpspin_light.gif"
			};

			//jQuery( this ).after( rtMediaAdmin.templates.rtm_image( data ) );

			var data = {
				action: 'rtmedia_disable_transcoding'
			};

			// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
			jQuery.post( ajaxurl, data, function ( response ) {
				if ( response ) {
					jQuery( '.settings-error-transcoding-disabled' ).remove();

					if ( jQuery( '#settings-transcoding-successfully-updated' ).length > 0 ) {
						jQuery( '#settings-transcoding-successfully-updated p' ).html( response );
					} else {
						var data = {
							id : 'settings-transcoding-successfully-updated',
							msg : response,
							class : 'updated'
						};

						//jQuery( 'h2:first' ).after( rtMediaAdmin.templates.rtm_msg_div( data ) );
					}

					jQuery( '#rtmedia-transcoding-usage' ).hide();
					jQuery( '#disable-transcoding' ).next( 'img' ).remove();
					jQuery( '#disable-transcoding' ).hide();
					jQuery( '#enable-transcoding' ).show();
				} else {
					jQuery( '#settings-error-transcoding-disabled' ).remove();

					var data = {
						id : 'settings-error-transcoding-disabled',
						msg : something_went_wrong,
						class : 'error'
					};

					//jQuery( 'h2:first' ).after( rtMediaAdmin.templates.rtm_msg_div( data ) );
				}
			} );
		}
	} );

	jQuery( document ).on( 'click', '#enable-transcoding', function ( e ) {
		e.preventDefault();
		if ( confirm( enable_encoding ) ) {

			var data = {
				action: 'rtmedia_enable_transcoding'
			};

			jQuery.post( ajaxurl, data, function ( response ) {
				if ( response ) {
					jQuery( '.settings-error-transcoding-enabled' ).remove();

					if ( jQuery( '#settings-transcoding-successfully-updated' ).length > 0 ) {
						jQuery( '#settings-transcoding-successfully-updated p' ).html( response );
					} else {

					}

					jQuery( '#enable-transcoding' ).next( 'img' ).remove();
					jQuery( '#enable-transcoding' ).hide();
					jQuery( '#disable-transcoding' ).show();
				} else {
					jQuery( '#settings-error-transcoding-disabled' ).remove();

				}
			} );
		}
	} );

	jQuery( '.bp-media-encoding-table' ).on( 'click', '.bpm-unsubscribe', function ( e ) {
		e.preventDefault();

		jQuery( "#bpm-unsubscribe-dialog" ).dialog( {
			dialogClass: "wp-dialog",
			modal: true,
			buttons: {
				Unsubscribe: function () {
					jQuery( this ).dialog( "close" );

					var data = {
						src   : rtmedia_transcoder_admin_url + "images/wpspin_light.gif"
					};

					//jQuery( '.bpm-unsubscribe' ).after( rtMediaAdmin.templates.rtm_image( data ) );

					var data = {
						action: 'rtmedia_unsubscribe_encoding_service',
						note: jQuery( '#bpm-unsubscribe-note' ).val(),
						plan: jQuery( '.bpm-unsubscribe' ).attr( 'data-plan' ),
						price: jQuery( '.bpm-unsubscribe' ).attr( 'data-price' )
					};

					// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
					jQuery.getJSON( ajaxurl, data, function ( response ) {
						if ( response.error === undefined && response.updated ) {
							jQuery( '.bpm-unsubscribe' ).next().remove();
							jQuery( '.bpm-unsubscribe' ).after( response.form );
							jQuery( '.bpm-unsubscribe' ).remove();
							jQuery( '#settings-unsubscribed-successfully' ).remove();
							jQuery( '#settings-unsubscribe-error' ).remove();

							var data = {
								id : 'settings-unsubscribed-successfully',
								msg : response.updated,
								class : 'updated'
							};

							//jQuery( 'h2:first' ).after( rtMediaAdmin.templates.rtm_msg_div( data ) );
							window.location.hash = '#settings-unsubscribed-successfully';
						} else {
							jQuery( '.bpm-unsubscribe' ).next().remove();
							jQuery( '#settings-unsubscribed-successfully' ).remove();
							jQuery( '#settings-unsubscribe-error' ).remove();

							var data = {
								id : 'settings-unsubscribe-error',
								msg : response.error,
								class : 'error'
							};

							//jQuery( 'h2:first' ).after( rtMediaAdmin.templates.rtm_msg_div( data ) );
							window.location.hash = '#settings-unsubscribe-error';
						}
					} );
				}
			}
		} );
	} );
} );