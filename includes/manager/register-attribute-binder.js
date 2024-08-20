import { addFilter } from '@wordpress/hooks';
import { createHigherOrderComponent } from '@wordpress/compose';
import { InspectorControls } from '@wordpress/block-editor';
import { TextControl, PanelBody } from '@wordpress/components';
import { select } from '@wordpress/data';

// https://github.com/WordPress/WordPress/blob/master/wp-includes/class-wp-block.php#L246-L251
const SUPPORTED_BLOCK_ATTRIBUTES = {
	'core/paragraph': [ 'content' ],
	'core/heading': [ 'content' ],
	'core/image': [ 'id', 'url', 'title', 'alt' ],
	'core/button': [ 'url', 'text', 'linkTarget', 'rel' ],
};

const withAttributeBinder = createHigherOrderComponent( ( BlockEdit ) => {
	return ( props ) => {
		const { attributes, setAttributes, name } = props;

		const getBinding = ( attribute ) => {
			return attributes.metadata?.contentModelBinding?.[ attribute ];
		};

		const setBinding = ( attribute ) => {
			return ( value ) => {
				const newAttributes = {
					...attributes,
					metadata: {
						...( attributes.metadata ?? {} ),
						contentModelBinding: {
							...( attributes.metadata?.contentModelBinding ??
								{} ),
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
			return (
				<>
					<InspectorControls>
						<PanelBody title="Attribute Bindings" initialOpen>
							<TextControl
								label="Block variation name"
								required
								value={ getBinding(
									window.BLOCK_VARIATION_NAME_ATTR
								) }
								onChange={ setBinding(
									window.BLOCK_VARIATION_NAME_ATTR
								) }
							/>
							<TextControl
								label="content"
								value={ getBinding( 'content' ) }
								onChange={ setBinding( 'content' ) }
							/>
						</PanelBody>
					</InspectorControls>
					<BlockEdit { ...props } />
				</>
			);
		}

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
							value={ getBinding(
								window.BLOCK_VARIATION_NAME_ATTR
							) }
							onChange={ setBinding(
								window.BLOCK_VARIATION_NAME_ATTR
							) }
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
