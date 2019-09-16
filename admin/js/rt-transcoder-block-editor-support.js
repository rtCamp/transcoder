const { rt_transcoder_block_editor_support } = window;

const updateAMPStoryPoster = ( BlockEdit ) => {
	return ( props ) => {
		const mediaAttributes = props.attributes;
		if ( 'amp/amp-story-page' === props.name && typeof mediaAttributes.mediaType !== 'undefined' && 'video' === mediaAttributes.mediaType ) {
			if ( typeof mediaAttributes.poster === 'undefined' && typeof rt_transcoder_block_editor_support.amp_story_fallback_poster !== 'undefined' && ! mediaAttributes.mediaUrl.endsWith( 'mp4' ) ) {
				props.attributes.poster = rt_transcoder_block_editor_support.amp_story_fallback_poster;
			}
		} else if ( 'core/video' === props.name ) {
			if ( typeof mediaAttributes.poster === 'undefined' && typeof mediaAttributes.src !== 'undefined' && mediaAttributes.src.indexOf( 'blob:' ) !== 0 ) {
				if ( ! mediaAttributes.src.endsWith( 'mp4' ) ) {
					props.attributes.poster = rt_transcoder_block_editor_support.amp_video_fallback_poster;
				}
			}
		}

		return (
			<BlockEdit { ...props } />
		)
	};
};

wp.hooks.addFilter( 'editor.BlockEdit', 'rt-transcoder-amp/with-inspector-controls', updateAMPStoryPoster );
