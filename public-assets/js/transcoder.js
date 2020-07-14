/*global rtMediaHook, rtTranscoder*/

const mediaThumbnails = {};

( function( $ ) {
	/**
	 * Document ready method.
	 */
	$( document ).ready( () => {
		$( '#activity-stream > .activity-list > li .media-type-video > .rtmedia-item-thumbnail mediaelementwrapper > video' ).each( ( i, elem ) => {
			elem = $( elem );
			if ( ( 'undefined' !== typeof elem.attr( 'poster' ) && elem.attr( 'poster' ).length > 0 ) || 'undefined' === typeof elem.attr( 'id' ) || -1 === elem.parent().attr( 'id' ).search( 'rt_media_video_' ) ) {
				return;
			}

			const id = parseInt( elem.parent().attr( 'id' ).split( 'rt_media_video_' )[ 1 ] );
			addToMediaThumbnailQueue( id );
		} );

		/**
		 * Code for media page.
		 */
		$( '.rtmedia-container ul.rtmedia-list.rtmedia-list-media.rtm-gallery-list > li.rtmedia-list-item' ).each( ( i, elem ) => {
			elem = $( elem );
			const img = elem.find( 'a.rtmedia-list-item-a > .rtmedia-item-thumbnail > img' );
			if ( ! img.length || 'undefined' === typeof img.attr( 'src' ) || -1 === img.attr( 'src' ).search( 'video_thumb.png' ) ) {
				return;
			}

			addToMediaThumbnailQueue( parseInt( elem.attr( 'id' ) ) );
		} );

		// Request thumbnails if mediaThumbnails has any elements.
		if ( Object.entries( mediaThumbnails ).length > 0 ) {
			requestThumbnails();
		}

		if ( 'undefined' !== typeof rtMediaHook ) {
			/**
			 * Code for media page when a media is uploaded.
			 */
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

			rtMediaHook.register( 'rtmedia_js_after_files_uploaded', () => {
				requestThumbnails();

				return true;
			} );
		}
	} );

	/**
	 * Add media ID to mediaThumbnails array.
	 *
	 * @param {int} mediaID Media ID.
	 *
	 * @return {void}
	 */
	const addToMediaThumbnailQueue = ( mediaID ) => {
		if ( 'undefined' !== typeof mediaThumbnails[ mediaID ] ) {
			return;
		}
		mediaThumbnails[ mediaID ] = {};
	};

	/**
	 * Make ajax request to get thumbnail URL.
	 *
	 * @return {void}
	 */
	const requestThumbnails = () => {
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

		$.get( rtTranscoder.restURLPrefix + '/transcoder/v1/amp-rtmedia?media_ids=' + mediaIDsToRequest, ( data ) => {
			checkResponse( data );
		} );
	};

	/**
	 * Check response received from ajax request.
	 *
	 * @param {object|boolean} data Response object or false.
	 *
	 * @return {void}
	 */
	const checkResponse = ( data ) => {
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

		setTimeout( () => {
			requestThumbnails();
		}, 5000 );
	};

	/**
	 * Update video thumbnail in DOM.
	 *
	 * @param {int} mediaID Media ID.
	 *
	 * @return {void}
	 */
	const updateVideoThumbnail = ( mediaID ) => {
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
	};

	/**
	 * Check whether an object is valid or not.
	 *
	 * @param {object} obj Object contains thumbnails details.
	 *
	 * @return {boolean} Whether the object is valid or not.
	 */
	const isValidObject = ( obj ) => {
		return ( 'undefined' !== typeof obj.poster );
	};
}( jQuery ) );
