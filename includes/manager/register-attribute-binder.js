import { addFilter } from '@wordpress/hooks';
import { useCallback, useMemo, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { createHigherOrderComponent } from '@wordpress/compose';
import { InspectorControls } from '@wordpress/block-editor';
import { TextControl, PanelBody } from '@wordpress/components';
import { useSelect, useDispatch } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';
import { store as blocksStore } from '@wordpress/blocks';

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

const ErrorMessage = ( { children } ) => {
	return (
		<span style={ { color: 'var(--wp--preset--color--vivid-red)' } }>
			{ children }
		</span>
	);
};

const withAttributeBinder = createHigherOrderComponent( ( BlockEdit ) => {
	return ( props ) => {
		const { getBlockType } = useSelect( blocksStore );
		const { lockPostSaving, unlockPostSaving } = useDispatch( editorStore );

		const { attributes, setAttributes, name } = props;

		const getBinding = useCallback(
			( attribute ) => {
				return attributes.metadata?.bindings?.[ attribute ]?.args?.key;
			},
			[ attributes.metadata?.bindings ]
		);

		const setBinding = useCallback(
			( attribute ) => {
				return ( key ) => {
					const newAttributes = {
						metadata: {
							...( attributes.metadata ?? {} ),
							bindings: {
								...( attributes.metadata?.bindings ?? {} ),
								[ attribute ]: getBindingObject( { key } ),
							},
						},
					};

					if ( ! key.trim() ) {
						delete newAttributes.metadata.bindings[ attribute ];
					}

					setAttributes( newAttributes );
				};
			},
			[ attributes.metadata, setAttributes ]
		);

		const selectedBlockType = getBlockType( name );

		const supportedAttributes =
			SUPPORTED_BLOCK_ATTRIBUTES[ selectedBlockType?.name ];

		const validations = useMemo( () => {
			const metadata = attributes.metadata ?? {};
			const bindings = metadata.bindings ?? {};

			const _validations = {};

			const hasAtLeastOneBinding = Object.keys( bindings ).length > 0;

			if (
				hasAtLeastOneBinding &&
				! metadata[ window.BLOCK_VARIATION_NAME_ATTR ]
			) {
				_validations[ window.BLOCK_VARIATION_NAME_ATTR ] = (
					<ErrorMessage>
						{ __( 'Block variation name is required' ) }
					</ErrorMessage>
				);
			}

			if (
				metadata[ window.BLOCK_VARIATION_NAME_ATTR ] &&
				! hasAtLeastOneBinding
			) {
				_validations[ window.BLOCK_VARIATION_NAME_ATTR ] = (
					<ErrorMessage>
						{ __( 'Bind at least one attribute' ) }
					</ErrorMessage>
				);
			}

			Object.keys( bindings ).forEach( ( attribute ) => {
				const key = getBinding( attribute );

				if ( key === 'post_content' && name !== 'core/group' ) {
					_validations[ attribute ] = (
						<ErrorMessage>
							{ __(
								'Only Group blocks can be bound to post_content'
							) }
						</ErrorMessage>
					);
				}
			} );

			return _validations;
		}, [ attributes.metadata, getBinding, name ] );

		useEffect( () => {
			const hasValidationErrors = Object.keys( validations ).length > 0;

			if ( hasValidationErrors ) {
				lockPostSaving();
			} else {
				unlockPostSaving();
			}
		}, [ lockPostSaving, unlockPostSaving, validations ] );

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
							help={
								validations[ window.BLOCK_VARIATION_NAME_ATTR ]
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
									help={ validations[ attributeKey ] }
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
