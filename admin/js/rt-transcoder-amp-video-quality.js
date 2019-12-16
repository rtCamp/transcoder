import assign from 'lodash.assign';

const { createHigherOrderComponent } = wp.compose;
const { Fragment } = wp.element;
const { InspectorControls } = wp.editor;
const { PanelBody, SelectControl } = wp.components;
const { addFilter } = wp.hooks;
const { __ } = wp.i18n;

// Enable Transcoder settings on the following blocks
const enableTranscoderSettingsOnBlocks = [
	'amp/amp-story-page',
];

// Available background video quality options
const backgroundVideoQualityOptions = [ {
	label: __( 'Low' ),
	value: 'low',
},
{
	label: __( 'Medium' ),
	value: 'medium',
},
{
	label: __( 'High' ),
	value: 'high',
} ];

/**
 * Add background video quality attribute to block.
 *
 * @param {object} settings Current block settings.
 * @param {string} name Name of block.
 *
 * @returns {object} Modified block settings.
 */
const addBackgroundVideoQualityControlAttribute = ( settings, name ) => {

	if ( ! enableTranscoderSettingsOnBlocks.includes( name ) ) {

		return settings;
	}

	// Use Lodash's assign to gracefully handle if attributes are undefined
	settings.attributes = assign( settings.attributes, {
		backgroundVideoQuality: {
			type: 'string',
			default: backgroundVideoQualityOptions[ 1 ].value,
		},
	} );

	return settings;
};

addFilter( 'blocks.registerBlockType', 'transcoder/attribute/ampStoryBackgroundVideoQuality', addBackgroundVideoQualityControlAttribute );

/**
 * Create HOC to add Transcoder settings controls to inspector controls of block.
 *
 * @param {function} BlockEdit Block edit component.
 *
 * @return {function} BlockEdit Modified block edit component.
 */
const withTranscoderSettings = createHigherOrderComponent( ( BlockEdit ) => {
	return ( props ) => {

		// Do nothing if it's another block than our defined ones.
		if ( ! enableTranscoderSettingsOnBlocks.includes( props.name ) ) {
			return ( <BlockEdit { ...props } />
			);
		}

		const { backgroundVideoQuality } = props.attributes;

		// Derive the video quality from the classname.
		const qualitySavedInClassName = props.attributes.className ? ( props.attributes.className.split( '-' ) )[2] : '';
		// Set the video quality equal to the value derived from classname if the backgroundVideoQuality is undefined.
		const videoQuality = ( undefined === backgroundVideoQuality ) ? qualitySavedInClassName : backgroundVideoQuality;


		// add has-quality-xy class to block
		if ( backgroundVideoQuality ) {
			props.attributes.className = `has-quality-${ backgroundVideoQuality }`;
		}

		return (
			<Fragment>
				<BlockEdit { ...props }
				/>
				<InspectorControls>
					<PanelBody
						title={ __( 'Transcoder Settings' ) }
						initialOpen={ true }
					>
						<SelectControl
							label={ __( 'Background Video Quality' ) }
							value={ videoQuality }
							options={ backgroundVideoQualityOptions }
							onChange={
								( selectedQuality ) => {
									props.setAttributes( {
										backgroundVideoQuality: selectedQuality,
									} );
								}
							}
						/>
				</PanelBody>
				</InspectorControls>
			</Fragment>
		);
	};
}, 'withTranscoderSettings' );

addFilter( 'editor.BlockEdit', 'transcoder/with-transcoder-settings', withTranscoderSettings );

/**
 * Transcode video on save element of block.
 *
 * @param {object} saveElementProps Props of save element.
 * @param {Object} blockType Block type information.
 * @param {Object} attributes Attributes of block.
 *
 * @returns {object} Modified props of save element.
 */
const doTranscode = ( saveElementProps, blockType, attributes ) => {

	// Do nothing if it's another block than our defined ones.
	if ( ! enableTranscoderSettingsOnBlocks.includes( blockType.name ) ) {
		return saveElementProps;
	}

	// Transcoding code
	return saveElementProps;
};

addFilter( 'blocks.getSaveContent.extraProps', 'transcoder/get-save-content/do-transcode', doTranscode );
