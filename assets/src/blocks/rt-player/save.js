/**
 * React hook that is used to mark the block wrapper element.
 * It provides all the necessary props like the class name.
 *
 * @see https://developer.wordpress.org/block-editor/packages/packages-block-editor/#useBlockProps
 */
/**
 * WordPress dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';

/**
 * The save function defines the way in which the different attributes should
 * be combined into the final markup, which is then serialized by the block
 * editor into `post_content`.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#save
 *
 * @param {Object} props            Block props.
 * @param {Object} props.attributes Block's attributes.
 *
 * @return {HTMLElement} Element to render.
 */
export default function save( { attributes } ) {
	const { videoUrl, videoAlt, videoSize, videoType, videoPosterUrl, useCustomSize } = attributes;

	const videoSetupOptions = {
		controls: true,
		autoplay: false,
		preload: 'auto',
		fluid: ! useCustomSize,
		responsive: ! useCustomSize,
	};

	return (
		<div { ...useBlockProps.save() }>
			{ videoUrl && (
				<div data-vjs-player>
					<video
						className="video-js vjs-big-play-centered"
						alt={ videoAlt }
						poster={ videoPosterUrl }
						data-setup={ JSON.stringify( videoSetupOptions ) }
						width={ useCustomSize ? videoSize.width : '100%' }
						height={ useCustomSize ? videoSize.height : 'auto' }
					>
						<source src={ videoUrl } type={ videoType } />
					</video>
				</div>
			) }
		</div>
	);
}
