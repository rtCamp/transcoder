import { createHigherOrderComponent } from '@wordpress/compose';
import { Fragment } from '@wordpress/element';
import { PanelBody, SelectControl } from '@wordpress/components';
import { addFilter } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';
import { InspectorControls } from '@wordpress/block-editor';
import apiFetch from '@wordpress/api-fetch';
import { dispatch, select } from '@wordpress/data';
import { showNotice, showSnackBar } from './helpers';

// To get all blocks on the current page.
const {
	getBlocksByClientId,
	getClientIdsWithDescendants,
} = select( 'core/block-editor' );

// Enable Transcoder settings on the following blocks
const enableTranscoderSettingsOnBlocks = [
	'amp/amp-story-page',
	'core/video',
];

const { rtTranscoderBlockEditorSupport } = window;

// Default Video Quality for for selection.
const defaultVideoQuality = typeof rtTranscoderBlockEditorSupport.rt_default_video_quality !== 'undefined' ?
	rtTranscoderBlockEditorSupport.rt_default_video_quality : 'high';

const isTranscodingEnabled = typeof rtTranscoderBlockEditorSupport.is_transcoding_enabled !== 'undefined' ?
	rtTranscoderBlockEditorSupport.is_transcoding_enabled : 'false';

/**
 * Add background video quality and media info attribute to block.
 *
 * @param {object} settings Current block settings.
 * @param {string} name Name of block.
 *
 * @returns {object} Modified block settings.
 */
const addBackgroundVideoQualityControlAttribute = ( settings, name ) => {
	if ( ! enableTranscoderSettingsOnBlocks.includes( name ) ||
		'amp_story' !== rtTranscoderBlockEditorSupport.current_post_type ||
		'false' === isTranscodingEnabled ) {
		return settings;
	}

	//check if object exists for old Gutenberg version compatibility
	if ( typeof settings.attributes !== 'undefined' ) {
		settings.attributes = Object.assign( settings.attributes, {
			rtBackgroundVideoInfo: {
				type: 'object',
			},
			rtBackgroundVideoQuality: {
				type: 'string',
				default: defaultVideoQuality,
			},
		} );
	}

	return settings;
};

addFilter( 'blocks.registerBlockType', 'transcoder/ampStoryBackgroundVideoQuality', addBackgroundVideoQualityControlAttribute, 9 );

/**
 * Create HOC to add Transcoder settings controls to inspector controls of block.
 */
const withTranscoderSettings = createHigherOrderComponent( ( BlockEdit ) => {
	return ( props ) => {
		// Do nothing if it's another block than our defined ones.
		if ( ! enableTranscoderSettingsOnBlocks.includes( props.name ) ||
			'amp_story' !== rtTranscoderBlockEditorSupport.current_post_type ||
			'false' === isTranscodingEnabled ) {
			return ( <BlockEdit { ...props } /> );
		}

		const mediaAttributes = props.attributes;
		const isAMPStory = 'amp/amp-story-page' === props.name;
		const isVideoBlock = 'core/video' === props.name;
		const mediaType = mediaAttributes.mediaType ? mediaAttributes.mediaType : '';
		const mediaId = isAMPStory ? mediaAttributes.mediaId : mediaAttributes.id;
		const { rtBackgroundVideoQuality, rtBackgroundVideoInfo } = mediaAttributes;

		return (
			<Fragment>
				<BlockEdit { ...props }
				/>
				{ ( ( isVideoBlock || ( isAMPStory && 'video' === mediaType ) ) && typeof mediaId !== "undefined" ) && (
					<InspectorControls>
						<PanelBody
							title={ __( 'Transcoder Settings', 'transcoder' ) }
							initialOpen={ true }
							>
							{ ( typeof rtBackgroundVideoInfo === 'undefined' ) && (
								showNotice(
									'error',
									__( 'Please wait while the video is being Transcoded. Quality settings will be available once transcoding is complete.', 'transcoder' ),
									false
								)
							) }
							{ ( typeof rtBackgroundVideoInfo !== 'undefined' ) && (
								<SelectControl
									label={ __( 'Background Video Quality', 'transcoder' ) }
									value={ rtBackgroundVideoQuality }
									options={ [
												{ value: 'low', label: __( 'Low', 'transcoder' ) },
										{ value: 'medium', label: __( 'Medium', 'transcoder' ) },
										{ value: 'high', label: __( 'High', 'transcoder' ) },
									] }
									onChange={
										( selectedQuality ) => {
											props.setAttributes( {
												rtBackgroundVideoQuality: selectedQuality,
											} );
										}
									}
								/>
							) }
						</PanelBody>
					</InspectorControls>
				) }
			</Fragment>
		);
	};
}, 'withTranscoderSettings' );

addFilter( 'editor.BlockEdit', 'rt-transcoder-amp/with-transcoder-settings', withTranscoderSettings, 12 );

/**
 * Get Transcoded Media Data.
 */
const getMediaInfo = async ( mediaId ) => {
	try {
		const restBase = '/transcoder/v1/amp-media';
		const response = await apiFetch(
			{
				path: `${ restBase }/${ mediaId }`,
				method: 'GET'
			}
		);
		if ( false !== response && null !== response ) {
			return response;
		} else {
			return false;
		}
	} catch(error) {
		console.log(error);
	}
};

/**
 * Update media attributes once video is transcoded.
 */
const updateAMPStoryMedia = ( BlockEdit ) => {
	return ( props ) => {

		// Do nothing if it's another block than our defined ones.
		if ( ! enableTranscoderSettingsOnBlocks.includes( props.name ) ||
			'amp_story' !== rtTranscoderBlockEditorSupport.current_post_type ||
			'false' === isTranscodingEnabled ) {
			return ( <BlockEdit { ...props } /> );
		}

		const mediaAttributes = props.attributes;
		const isAMPStory = 'amp/amp-story-page' === props.name;
		const isVideoBlock = 'core/video' === props.name;
		const mediaId = isAMPStory ? mediaAttributes.mediaId : mediaAttributes.id;

		if ( typeof mediaId !== 'undefined' ) {
			if ( typeof mediaAttributes.poster === 'undefined' ) {
				if ( isAMPStory && typeof mediaAttributes.mediaType !== 'undefined' &&
					'video' === mediaAttributes.mediaType ) {
					if ( ! mediaAttributes.mediaUrl.endsWith( 'mp4' ) ) {
						props.setAttributes( { poster: rtTranscoderBlockEditorSupport.amp_story_fallback_poster } );
					}
					showSnackBar(  __( 'Please wait while the video is being Transcoded. Video URL and thumbnails will be updated automatically.', 'transcoder' ) );
				} else if ( isVideoBlock && typeof mediaAttributes.src !== 'undefined' &&
					mediaAttributes.src.indexOf( 'blob:' ) !== 0 ) {
					if ( ! mediaAttributes.src.endsWith( 'mp4' ) ) {
						props.setAttributes( { poster: rtTranscoderBlockEditorSupport.amp_video_fallback_poster } );
					}
					showSnackBar(  __( 'Please wait while the video is being Transcoded. Video URL and thumbnails will be updated automatically.', 'transcoder' ) );
				}
			} else {
				if ( typeof  props.attributes.rtBackgroundVideoInfo !== 'undefined' ) {
					const mediaInfo = props.attributes.rtBackgroundVideoInfo;
					const videoQuality = props.attributes.rtBackgroundVideoQuality ?
						props.attributes.rtBackgroundVideoQuality : defaultVideoQuality;
					if ( mediaInfo.poster.length && mediaInfo[ videoQuality ].transcodedMedia.length ) {
						if ( isAMPStory && typeof mediaAttributes.mediaType !== 'undefined' &&
							'video' === mediaAttributes.mediaType ) {
							props.setAttributes( {
								poster: mediaInfo.poster,
								mediaUrl: mediaInfo[ videoQuality ].transcodedMedia,
								src: mediaInfo[ videoQuality ].transcodedMedia,
								rtBackgroundVideoQuality: videoQuality,
							} );
						} else if ( isVideoBlock ) {
							props.setAttributes( {
								poster: mediaInfo.poster,
								src: mediaInfo[ videoQuality ].transcodedMedia,
								rtBackgroundVideoQuality: videoQuality,
							} );
						}
					}
				}
			}
		}

		const { rtBackgroundVideoQuality } = props.attributes;

		// add has-quality-xy class to block
		if ( rtBackgroundVideoQuality ) {
			props.setAttributes( {
				className: `has-quality-${ rtBackgroundVideoQuality }`,
			} );
		} else {
			props.setAttributes( {
				rtBackgroundVideoQuality: defaultVideoQuality,
			} );
		}

		return (
			<BlockEdit { ...props } />
		);
	};
};

addFilter( 'editor.BlockEdit', 'rt-transcoder-amp/set-media-attributes', updateAMPStoryMedia, 11 );

// Check for blocks on the page, verify transcoding status and update attributes if required.
setInterval( function () {
	// Get all blocks.
	const allBlocks = getBlocksByClientId( getClientIdsWithDescendants() );
	if ( allBlocks.length ) {
		for ( const currentBlock of allBlocks ) {
			// Verify block is of allowed type and we are on valid page.
			if ( currentBlock.name.length && enableTranscoderSettingsOnBlocks.includes( currentBlock.name ) &&
				'amp_story' === rtTranscoderBlockEditorSupport.current_post_type && 'true' === isTranscodingEnabled ) {
				const blockAttributes = currentBlock.attributes;
				const clientId = currentBlock.clientId;
				if ( typeof clientId !== 'undefined' && typeof blockAttributes.rtBackgroundVideoInfo === 'undefined' ) {
					const isAMPStory = 'amp/amp-story-page' === currentBlock.name;
					const isVideoBlock = 'core/video' === currentBlock.name;
					const mediaId = isAMPStory ? blockAttributes.mediaId : blockAttributes.id;
					if ( typeof mediaId !== 'undefined' ) {
						getMediaInfo( mediaId ).then( data => {
							if ( false !== data && null !== data ) {
								const mediaInfo = data;
								const videoQuality = blockAttributes.rtBackgroundVideoQuality ? blockAttributes.rtBackgroundVideoQuality : defaultVideoQuality;
								if ( typeof mediaInfo !== 'undefined' && mediaInfo.poster.length && mediaInfo[ videoQuality ].transcodedMedia.length ) {
									if ( isAMPStory && typeof blockAttributes.mediaType !== 'undefined' && 'video' === blockAttributes.mediaType ) {
										dispatch( 'core/block-editor' ).updateBlockAttributes( clientId,
											{
												poster: mediaInfo.poster,
												mediaUrl: mediaInfo[ videoQuality ].transcodedMedia,
												src: mediaInfo[ videoQuality ].transcodedMedia,
												rtBackgroundVideoInfo: data,
											}
										);
										showSnackBar( __( 'Video has been Transcoded, you may now select desired quality from block settings.', 'transcoder' ) );
									} else if ( isVideoBlock ) {
										dispatch( 'core/block-editor' ).updateBlockAttributes( clientId,
											{
												poster: mediaInfo.poster,
												src: mediaInfo[ videoQuality ].transcodedMedia,
												rtBackgroundVideoInfo: data,
											}
										);
										showSnackBar( __( 'Video has been Transcoded, you may now select desired quality from block settings.', 'transcoder' ) );
									}
								}
							}
						});
					}
				}
			}
		}
	}
}, 10000 );
