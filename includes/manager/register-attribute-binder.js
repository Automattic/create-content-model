import { addFilter } from '@wordpress/hooks';
import { createHigherOrderComponent } from '@wordpress/compose';
import { InspectorControls } from '@wordpress/block-editor';
import { TextControl, PanelBody } from '@wordpress/components';
import { select } from '@wordpress/data';

// https://github.com/WordPress/WordPress/blob/master/wp-includes/class-wp-block.php#L246-L251
const SUPPORTED_BLOCK_ATTRIBUTES = {
	'core/group': [ 'content' ],
	'core/paragraph': [ 'content' ],
	'core/heading': [ 'content' ],
	'core/image': [ 'id', 'url', 'title', 'alt' ],
	'core/button': [ 'url', 'text', 'linkTarget', 'rel' ],
};

const getBindingObject = ( { key } ) => {
	return {
		source: key === 'post_content' ? 'core/post-content' : 'core/post-meta',
		args: {
			key: key.trim(),
		},
	};
};

const withAttributeBinder = createHigherOrderComponent( ( BlockEdit ) => {
	return ( props ) => {
		const { attributes, setAttributes, name } = props;

		const getBinding = ( attribute ) => {
			return attributes.metadata?.bindings?.[ attribute ]?.args?.key;
		};

		const setBinding = ( attribute ) => {
			return ( key ) => {
				const newAttributes = {
					...attributes,
					metadata: {
						...( attributes.metadata ?? {} ),
						bindings: {
							...( attributes.metadata?.bindings ?? {} ),
							[ attribute ]: getBindingObject( { key } ),
						},
					},
				};

				if ( ! key ) {
					delete newAttributes.metadata.bindings[ attribute ];
				}

				setAttributes( newAttributes );
			};
		};

		const { getBlockType } = select( 'core/blocks' );
		const selectedBlockType = getBlockType( name );

		const supportedAttributes =
			SUPPORTED_BLOCK_ATTRIBUTES[ selectedBlockType?.name ];

		if ( ! supportedAttributes ) {
			return <BlockEdit { ...props } />;
		}

		return (
			<>
				<InspectorControls>
					<PanelBody title="Attribute Bindings" initialOpen>
						<TextControl
							label="Block variation name"
							required
							value={
								attributes.metadata?.[
									window.BLOCK_VARIATION_NAME_ATTR
								]
							}
							onChange={ ( newName ) => {
								const newAttributes = {
									metadata: {
										...( attributes.metadata ?? {} ),
										[ window.BLOCK_VARIATION_NAME_ATTR ]:
											newName,
									},
								};

								if (
									! newAttributes.metadata[
										window.BLOCK_VARIATION_NAME_ATTR
									].trim()
								) {
									delete newAttributes.metadata[
										window.BLOCK_VARIATION_NAME_ATTR
									];
								}

								setAttributes( newAttributes );
							} }
						/>
						{ supportedAttributes.map( ( attributeKey ) => {
							return (
								<TextControl
									key={ attributeKey }
									label={ attributeKey }
									value={ getBinding( attributeKey ) }
									onChange={ setBinding( attributeKey ) }
								/>
							);
						} ) }
					</PanelBody>
				</InspectorControls>
				<BlockEdit { ...props } />
			</>
		);
	};
}, 'withAttributeBinder' );

addFilter(
	'editor.BlockEdit',
	'content-model/attribute-binder',
	withAttributeBinder
);
