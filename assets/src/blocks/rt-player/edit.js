/**
 * React hook that is used to mark the block wrapper element.
 * It provides all the necessary props like the class name.
 *
 * @see https://developer.wordpress.org/block-editor/packages/packages-block-editor/#useBlockProps
 */

/**
 * Retrieves the translation of text.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-i18n/
 */
/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * WordPress dependencies
 */
import { useBlockProps, InspectorControls, MediaUpload, MediaUploadCheck } from '@wordpress/block-editor';
import { Button, PanelBody, PanelRow, TextControl, ToggleControl, Placeholder } from '@wordpress/components';
import { useEffect, useRef } from '@wordpress/element';

/**
 * External dependencies
 */
import videojs from 'video.js';
import 'videojs-contrib-quality-levels';
import 'videojs-contrib-quality-menu';
import 'video.js/dist/video-js.css';
import 'videojs-contrib-quality-menu/dist/videojs-contrib-quality-menu.css';

/**
 * Internal dependencies
 */
/**
 * Lets webpack process CSS, SASS or SCSS files referenced in JavaScript files.
 * Those files can contain any CSS code that gets applied to the editor.
 *
 * @see https://www.npmjs.com/package/@wordpress/scripts#using-css
 */
import './editor.scss';

/**
 * External dependencies
 */

/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
 *
 * @param {Object} props               Block props.
 * @param {Object} props.attributes    Block's attributes.
 * @param {Object} props.setAttributes Function to set block's attributes.
 *
 * @see https://developer.wordpress.org/block-editor/developers/block-api/block-edit-save/#edit
 *
 * @return {HTMLElement} Element to render.
 */
export default function Edit( { attributes, setAttributes } ) {
	const { videoId, videoUrl, videoAlt, useCustomSize, videoSize, videoType, videoPosterUrl } = attributes;
	const videoRef = useRef( null );
	const playerInstance = useRef( null );

	useEffect( () => {
		if ( ! videoUrl || ! videoRef.current || playerInstance.current ) {
			return;
		}

		// Create a new video element.
		const videoElement = document.createElement( 'video-js' );
		videoElement.classList.add( 'vjs-big-play-centered' );
		videoRef.current.appendChild( videoElement );

		// Initialize the player.
		const player = playerInstance.current = videojs( videoElement, {
			controls: true,
			responsive: ! useCustomSize,
			fluid: ! useCustomSize,
			poster: videoPosterUrl,
			sources: [ {
				src: videoUrl,
				type: videoType,
			} ],
			preload: 'auto',
		} );

		// Initialize the Quality Menu plugin after the player is ready.
		player.ready( () => {
			if ( typeof player.qualityMenu === 'function' ) {
				player.qualityMenu();
			} else {
				console.error( 'Quality Menu plugin is not available.' );
			}
		} );

		const isCustomSizeDefined = videoSize.width && videoSize.height;

		// Set initial dimensions if defined.
		if ( useCustomSize && isCustomSizeDefined ) {
			player.width( videoSize.width );
			player.height( videoSize.height );
		}

		return () => {
			if ( player && ! player.isDisposed() ) {
				player.dispose();
				playerInstance.current = null;
			}
		};
	}, [ videoUrl, videoPosterUrl ] );

	useEffect( () => {
		if ( ! playerInstance.current ) {
			return;
		}

		const isCustomSizeDefined = videoSize.width && videoSize.height;

		if ( useCustomSize && isCustomSizeDefined ) {
			// Disable responsive and fluid mode.
			playerInstance.current.fluid( false );
			playerInstance.current.responsive( false );

			// Set custom size.
			playerInstance.current.width( videoSize.width );
			playerInstance.current.height( videoSize.height );
		} else {
			// Enable responsive and fluid mode.
			playerInstance.current.fluid( true );
			playerInstance.current.responsive( true );
		}
	}, [ useCustomSize, videoSize ] );

	const onSelectVideo = ( media ) => {
		setAttributes( {
			mediaId: media.id,
			videoUrl: media.url,
			videoAlt: media.alt || media.title,
			videoSize: {
				width: media.width,
				height: media.height,
			},
			videoType: media.mime,
			videoPosterUrl: media.thumb?.src || media.image?.src,
		} );
	};

	const onSelectPoster = ( media ) => {
		setAttributes( {
			videoPosterUrl: media.url,
		} );
	};

	const onSizeChange = ( value, key ) => {
		if ( isNaN( parseInt( value ) ) ) {
			return;
		}

		setAttributes( {
			videoSize: {
				...videoSize,
				[ key ]: parseInt( value ),
			},
		} );
	};

	return (
		<div { ...useBlockProps() }>
			<InspectorControls>
				<PanelBody title="Video Settings">
					<p><strong>Video</strong></p>
					<PanelRow>
						<MediaUploadCheck>
							<MediaUpload
								title="Select Video"
								onSelect={ onSelectVideo }
								allowedTypes={ [ 'video' ] }
								accept="video/*"
								value={ videoId }
								render={ ( { open } ) => (
									<div className="upload-controls">
										<Button onClick={ open } variant="primary">
											{ ! videoUrl ? __( 'Select Video' ) : __( 'Replace Video' ) }
										</Button>
										{ videoUrl && (
											<Button onClick={ () => setAttributes( { videoUrl: null, videoPosterUrl: null } ) } variant="link" isDestructive>
												{ __( 'Remove Video' ) }
											</Button>
										) }
									</div>
								) }
							/>
						</MediaUploadCheck>
					</PanelRow>
					<p><strong>Thumbnail Image</strong></p>
					<PanelRow>
						<MediaUploadCheck>
							<MediaUpload
								title="Select Thumbnail Image"
								onSelect={ onSelectPoster }
								allowedTypes={ [ 'image' ] }
								accept="image/*"
								render={ ( { open } ) => (
									<div className="upload-controls">
										<Button onClick={ open } variant="primary">
											{ ! videoPosterUrl ? __( 'Select Thumbnail Image' ) : __( 'Replace Thumbnail Image' ) }
										</Button>
										{ videoPosterUrl && (
											<Button onClick={ () => setAttributes( { videoPosterUrl: null } ) } variant="link" isDestructive>
												{ __( 'Remove Thumbnail Image' ) }
											</Button>
										) }
									</div>
								) }
							/>
						</MediaUploadCheck>
					</PanelRow>
					<TextControl
						label="Alt Text"
						value={ videoAlt }
						onChange={ ( value ) => setAttributes( { videoAlt: value } ) }
						help="Descriptive text for screen readers and SEO."
					/>
					<ToggleControl
						label="Use Custom Size"
						checked={ useCustomSize }
						onChange={ ( value ) => setAttributes( { useCustomSize: value } ) }
					/>
					{ useCustomSize && (
						<>
							<TextControl
								label="Width"
								value={ videoSize.width }
								onChange={ ( value ) => onSizeChange( value, 'width' ) }
							/>
							<TextControl
								label="Height"
								value={ videoSize.height }
								onChange={ ( value ) => onSizeChange( value, 'height' ) }
							/>
						</>
					) }
				</PanelBody>
			</InspectorControls>
			{ videoUrl ? (
				<div data-vjs-player>
					<div ref={ videoRef } />
				</div>
			) : (
				<Placeholder
					icon="format-video"
					label="RT Player Block"
					instructions="Select a video from your media library or upload a new one."
				>
					<MediaUploadCheck>
						<MediaUpload
							onSelect={ onSelectVideo }
							allowedTypes={ [ 'video' ] }
							render={ ( { open } ) => (
								<Button onClick={ open } variant="primary">
									Select Video
								</Button>
							) }
						/>
					</MediaUploadCheck>
				</Placeholder>
			) }
		</div>
	);
}
