/*global rtMediaHook, rtTranscoder*/

const mediaThumbnails = {};

( function( $ ) {
	$( document ).ready( () => {
		$( '.rtmedia-container ul.rtmedia-list.rtmedia-list-media.rtm-gallery-list > li.rtmedia-list-item' ).each( ( i, elem ) => {
			elem = $( elem );
			const img = elem.find( 'a.rtmedia-list-item-a > .rtmedia-item-thumbnail > img' );
			if ( ! img.length || 'undefined' === typeof img.attr( 'src' ) || -1 === img.attr( 'src' ).search( 'video_thumb.png' ) ) {
				return;
			}

			addToMediaThumbnailQueue( parseInt( elem.attr( 'id' ) ) );
		} );
		if ( Object.entries( mediaThumbnails ).length > 0 ) {
			requestThumbnails();
		}

		if ( 'undefined' !== typeof rtMediaHook ) {
			rtMediaHook.register( 'rtmedia_js_after_file_upload', ( data ) => {
				if ( 'undefined' === typeof data || 'undefined' === typeof data[ 1 ] || 'undefined' === typeof data[ 2 ] ) {
					return true;
				}

				const type = data[ 1 ].type.split( '/' );
				if ( 'video' !== type[ 0 ] ) {
					return true;
				}

				const rtMediaObj = JSON.parse( data[ 2 ] );
				if ( 'undefined' === typeof rtMediaObj.media_id ) {
					return true;
				}

				addToMediaThumbnailQueue( rtMediaObj.media_id );

				return true;
			} );

			rtMediaHook.register( 'rtmedia_js_after_files_uploaded', function() {
				requestThumbnails();

				return true;
			} );
		}
	} );

	function addToMediaThumbnailQueue( mediaID ) {
		if ( 'undefined' !== typeof mediaThumbnails[ mediaID ] ) {
			return;
		}
		mediaThumbnails[ mediaID ] = {};
	}

	function requestThumbnails() {
		let mediaIDsToRequest = [];
		for ( const [ mediaID, obj ] of Object.entries( mediaThumbnails ) ) {
			if ( 'undefined' === typeof mediaID || 'undefined' === typeof obj ) {
				continue;
			}

			if ( false === isValidObject( obj ) ) {
				mediaIDsToRequest.push( mediaID );
			}
		}

		if ( ! mediaIDsToRequest.length ) {
			return;
		}
		mediaIDsToRequest = mediaIDsToRequest.join();

		$.get( rtTranscoder.restURLPrefix + '/transcoder/v1/amp-rtmedia?media_ids=' + mediaIDsToRequest, function( data ) {
			checkResponse( data );
		} );
	}

	function checkResponse( data ) {
		if ( 'object' === typeof data ) {
			for ( const [ mediaID, obj ] of Object.entries( data ) ) {
				if ( 'undefined' === typeof mediaID || 'undefined' === typeof obj ) {
					continue;
				}

				if ( false !== isValidObject( obj ) ) {
					mediaThumbnails[ mediaID ] = obj;
					updateVideoThumbnail( mediaID );
				}
			}
		}

		setTimeout( function() {
			requestThumbnails();
		}, 5000 );
	}

	function updateVideoThumbnail( mediaID ) {
		if ( 'undefined' === typeof mediaThumbnails[ mediaID ] || ! isValidObject( mediaThumbnails[ mediaID ] ) ) {
			return;
		}

		const li = $( 'li#' + mediaID );
		if ( ! li.length ) {
			return;
		}

		const img = li.find( 'div.rtmedia-item-thumbnail > img' );
		if ( ! img.length ) {
			return;
		}

		img.attr( 'src', mediaThumbnails[ mediaID ].poster );
	}

	function isValidObject( obj ) {
		return ( 'undefined' !== typeof obj.poster );
	}
}( jQuery ) );
