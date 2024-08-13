// https://github.com/WordPress/WordPress/blob/master/wp-includes/class-wp-block.php#L246-L251
const SUPPORTED_BLOCK_ATTRIBUTES = {
	'core/paragraph': [ 'content' ],
	'core/heading': [ 'content' ],
	'core/image': [ 'id', 'url', 'title', 'alt' ],
	'core/button': [ 'url', 'text', 'linkTarget', 'rel' ],
};

function registerContentAreaInspector( wp ) {
	const { select } = wp.data;
	const { createHigherOrderComponent } = wp.compose;
	const { Fragment } = wp.element;
	const { InspectorControls } = wp.blockEditor;
	const { TextControl, PanelBody } = wp.components;

	const withCustomInspectorControl = createHigherOrderComponent(
		( BlockEdit ) => {
			return ( props ) => {
				const { attributes, setAttributes, name } = props;

				const getBinding = ( attribute ) => {
					return attributes.metadata?.contentModelBinding?.[
						attribute
					];
				};

				const setBinding = ( attribute ) => {
					return ( value ) => {
						const newAttributes = {
							...attributes,
							metadata: {
								...( attributes.metadata ?? {} ),
								contentModelBinding: {
									...( attributes.metadata
										?.contentModelBinding ?? {} ),
									[ attribute ]: value,
								},
							},
						};

						if ( ! value.trim() ) {
							delete newAttributes.metadata.contentModelBinding[
								attribute
							];
						}

						setAttributes( newAttributes );
					};
				};

				if ( name === 'core/group' ) {
					return wp.element.createElement( Fragment, null, [
						wp.element.createElement( InspectorControls, null, [
							wp.element.createElement(
								PanelBody,
								{
									title: 'Attribute Bindings',
									initialOpen: true,
								},
								[
									wp.element.createElement( TextControl, {
										key: 'content',
										label: 'content',
										value: getBinding( 'content' ),
										onChange: setBinding( 'content' ),
									} ),
								]
							),
						] ),
						wp.element.createElement( BlockEdit, props ),
					] );
				}

				const { getBlockType } = select( 'core/blocks' );
				const selectedBlockType = getBlockType( name );

				const supportedAttributes =
					SUPPORTED_BLOCK_ATTRIBUTES[ selectedBlockType?.name ];

				if ( ! supportedAttributes ) {
					return wp.element.createElement( BlockEdit, props );
				}

				return wp.element.createElement( Fragment, null, [
					wp.element.createElement( BlockEdit, props ),
					wp.element.createElement( InspectorControls, null, [
						wp.element.createElement(
							PanelBody,
							{ title: 'Attribute Bindings', initialOpen: true },
							[
								...supportedAttributes.map(
									( attributeKey ) => {
										return wp.element.createElement(
											TextControl,
											{
												key: attributeKey,
												label: attributeKey,
												value: getBinding(
													attributeKey
												),
												onChange:
													setBinding( attributeKey ),
											}
										);
									}
								),
							]
						),
					] ),
				] );
			};
		},
		'withCustomInspectorControl'
	);

	wp.hooks.addFilter(
		'editor.BlockEdit',
		'data-types/content-area-binder',
		withCustomInspectorControl
	);
}

setTimeout( registerContentAreaInspector, 800, window.wp );
