import apiFetch from '@wordpress/api-fetch';

const { rtTranscoderBlockEditorSupport } = window;

const updateAMPStoryPoster = ( BlockEdit ) => {
	return ( props ) => {
		const mediaAttributes = props.attributes;
		const isAMPStory = 'amp/amp-story-page' === props.name;
		const isVideoBlock = 'core/video' === props.name;
		const mediaId = isAMPStory ? mediaAttributes.mediaId : mediaAttributes.id;
		if ( typeof mediaId !== 'undefined' ) {
			if ( typeof mediaAttributes.poster === 'undefined' ) {
				if ( isAMPStory && typeof mediaAttributes.mediaType !== 'undefined' &&
					'video' === mediaAttributes.mediaType && ! mediaAttributes.mediaUrl.endsWith( 'mp4' ) ) {
					props.attributes.poster = rtTranscoderBlockEditorSupport.amp_story_fallback_poster;
				} else if ( isVideoBlock && typeof mediaAttributes.src !== 'undefined' &&
					mediaAttributes.src.indexOf( 'blob:' ) !== 0 && ! mediaAttributes.src.endsWith( 'mp4' ) ) {
					props.attributes.poster = rtTranscoderBlockEditorSupport.amp_video_fallback_poster;
				}
			} else if ( mediaAttributes.poster.endsWith( '-fallback-poster.png' ) ) {
				const restBase = '/wp-json/transcoder/v1/amp-media';
				apiFetch( {
					path: `${ restBase }/${ mediaId }`,
				} ).then( data => {
					if ( false !== data && null !== data ) {
						if ( data.poster.length ) {
							if ( isAMPStory && typeof mediaAttributes.mediaType !== 'undefined' && 'video' === mediaAttributes.mediaType ) {
								props.attributes.poster = data.poster;
							} else if ( isVideoBlock ) {
								props.attributes.poster = data.poster;
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

wp.hooks.addFilter( 'editor.BlockEdit', 'rt-transcoder-amp/with-inspector-controls', updateAMPStoryPoster );
