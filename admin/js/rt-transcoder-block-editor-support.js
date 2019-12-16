import apiFetch from '@wordpress/api-fetch';

const { rtTranscoderBlockEditorSupport } = window;

const updateAMPStoryMedia = ( BlockEdit ) => {
	return ( props ) => {

		const mediaAttributes = props.attributes;
		const isAMPStory      = 'amp/amp-story-page' === props.name;
		const isVideoBlock    = 'core/video' === props.name;
		const mediaId         = isAMPStory ? mediaAttributes.mediaId : mediaAttributes.id;

		console.warn( 'mediaId', mediaId );

		if ( typeof mediaId !== 'undefined' ) {

			if ( typeof mediaAttributes.poster === 'undefined' ) {

				if ( isAMPStory && typeof mediaAttributes.mediaType !== 'undefined' &&
					'video' === mediaAttributes.mediaType && !mediaAttributes.mediaUrl.endsWith( 'mp4' ) ) {

					props.setAttributes( { poster: rtTranscoderBlockEditorSupport.amp_story_fallback_poster } );

				} else if ( isVideoBlock && typeof mediaAttributes.src !== 'undefined' &&
					mediaAttributes.src.indexOf( 'blob:' ) !== 0 && !mediaAttributes.src.endsWith( 'mp4' ) ) {

					props.setAttributes( { poster: rtTranscoderBlockEditorSupport.amp_video_fallback_poster } );

				}

			} else  {

				const restBase = '/wp-json/transcoder/v1/amp-media';

				apiFetch( {
					path: `${ restBase }/${ mediaId }`,
				} ).then( data => {

					// Derive the video quality from the classname.
					const qualitySavedInClassName = props.attributes.className ? ( props.attributes.className.split( '-' ) )[2] : '';
					const videoQuality = qualitySavedInClassName ? qualitySavedInClassName : 'high';


					if ( false !== data && null !== data ) {

						if ( data.poster.length && data[videoQuality].transcodedMedia.length ) {

							if ( isAMPStory && typeof mediaAttributes.mediaType !== 'undefined' && 'video' === mediaAttributes.mediaType ) {

								props.setAttributes( {
									poster: data.poster,
									mediaUrl: data[videoQuality].transcodedMedia,
									src: data[videoQuality].transcodedMedia,
									backgroundVideoQuality: props.attributes.backgroundVideoQuality,
									className: props.attributes.className,
									mediaId: props.attributes.mediaId,
								} );

							} else if ( isVideoBlock ) {

								props.setAttributes( {
									poster: data.poster,
									src: data[videoQuality].transcodedMedia,
									mediaUrl: data[videoQuality].transcodedMedia,
									backgroundVideoQuality: props.attributes.backgroundVideoQuality,
									mediaId: props.attributes.mediaId,
									className: props.attributes.className,
								} );
							}
						}
					}

				} );
			}
		}

		return (
			<BlockEdit { ...props } />
		);
	};
};

wp.hooks.addFilter( 'editor.BlockEdit', 'rt-transcoder-amp/set-media-attributes', updateAMPStoryMedia );
