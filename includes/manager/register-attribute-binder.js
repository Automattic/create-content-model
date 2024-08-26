import { addFilter } from '@wordpress/hooks';
import { useState, useCallback, useMemo, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { createHigherOrderComponent } from '@wordpress/compose';
import { InspectorControls } from '@wordpress/block-editor';
import { TextControl, PanelBody } from '@wordpress/components';
import { useSelect, useDispatch } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';
import { store as blocksStore } from '@wordpress/blocks';
import { debounce } from 'lodash';

// https://github.com/WordPress/WordPress/blob/master/wp-includes/class-wp-block.php#L246-L251
const SUPPORTED_BLOCK_ATTRIBUTES = {
	'core/group': [ 'content' ],
	'core/paragraph': [ 'content' ],
	'core/heading': [ 'content' ],
	'core/image': [ 'id', 'url', 'title', 'alt' ],
	'core/button': [ 'url', 'text', 'linkTarget', 'rel' ],
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

		// Use local state for input values
		const [ localBindings, setLocalBindings ] = useState(
			attributes.metadata?.[ window.BINDINGS_KEY ] || {}
		);
		const [ localVariationName, setLocalVariationName ] = useState(
			attributes.metadata?.[ window.BLOCK_VARIATION_NAME_ATTR ] || ''
		);

		// Debounce attribute updates
		const debouncedSetAttributes = useMemo(
			() =>
				debounce( ( newAttributes ) => {
					setAttributes( newAttributes );
				}, 300 ),
			[ setAttributes ]
		);

		const updateBinding = useCallback(
			( attribute, value ) => {
				setLocalBindings( ( prev ) => ( {
					...prev,
					[ attribute ]: value,
				} ) );

				const newAttributes = {
					metadata: {
						...( attributes.metadata || {} ),
						[ window.BINDINGS_KEY ]: {
							...( attributes.metadata?.[ window.BINDINGS_KEY ] ||
								{} ),
							[ attribute ]: value,
						},
					},
				};

				if ( ! value.trim() ) {
					delete newAttributes.metadata[ window.BINDINGS_KEY ][
						attribute
					];
				}

				debouncedSetAttributes( newAttributes );
			},
			[ attributes.metadata, debouncedSetAttributes ]
		);

		const updateVariationName = useCallback(
			( newName ) => {
				setLocalVariationName( newName );

				const newAttributes = {
					metadata: {
						...( attributes.metadata || {} ),
						[ window.BLOCK_VARIATION_NAME_ATTR ]: newName,
					},
				};

				if ( ! newName.trim() ) {
					delete newAttributes.metadata[
						window.BLOCK_VARIATION_NAME_ATTR
					];
				}

				debouncedSetAttributes( newAttributes );
			},
			[ attributes.metadata, debouncedSetAttributes ]
		);

		const validations = useMemo( () => {
			const _validations = {};

			const hasAtLeastOneBinding = Object.values( localBindings ).some(
				( value ) => value.trim() !== ''
			);

			if ( hasAtLeastOneBinding && ! localVariationName.trim() ) {
				_validations[ window.BLOCK_VARIATION_NAME_ATTR ] = (
					<ErrorMessage>
						{ __( 'Block variation name is required' ) }
					</ErrorMessage>
				);
			}

			if ( localVariationName.trim() && ! hasAtLeastOneBinding ) {
				_validations[ window.BLOCK_VARIATION_NAME_ATTR ] = (
					<ErrorMessage>
						{ __( 'Bind at least one attribute' ) }
					</ErrorMessage>
				);
			}

			Object.entries( localBindings ).forEach(
				( [ attribute, field ] ) => {
					if (
						field.trim() === 'post_content' &&
						name !== 'core/group'
					) {
						_validations[ attribute ] = (
							<ErrorMessage>
								{ __(
									'Only Group blocks can be bound to post_content'
								) }
							</ErrorMessage>
						);
					}
				}
			);

			return _validations;
		}, [ localBindings, localVariationName, name ] );

		useEffect( () => {
			const hasValidationErrors = Object.keys( validations ).length > 0;

			if ( hasValidationErrors ) {
				lockPostSaving();
			} else {
				unlockPostSaving();
			}
		}, [ lockPostSaving, unlockPostSaving, validations ] );

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
							value={ localVariationName }
							help={
								validations[ window.BLOCK_VARIATION_NAME_ATTR ]
							}
							onChange={ updateVariationName }
						/>
						{ supportedAttributes.map( ( attributeKey ) => (
							<TextControl
								key={ attributeKey }
								label={ attributeKey }
								help={ validations[ attributeKey ] }
								value={ localBindings[ attributeKey ] || '' }
								onChange={ ( value ) =>
									updateBinding( attributeKey, value )
								}
							/>
						) ) }
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
