import { addFilter } from '@wordpress/hooks';
import { useCallback, useMemo, useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { createHigherOrderComponent } from '@wordpress/compose';
import { InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	Button,
	ButtonGroup,
	Modal,
	__experimentalItemGroup as ItemGroup,
	__experimentalItem as Item,
	Flex,
	FlexBlock,
	FlexItem,
} from '@wordpress/components';
import { useSelect, useDispatch } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';
import { store as blocksStore } from '@wordpress/blocks';
import { useEntityProp } from '@wordpress/core-data';

import ManageBindings from './_manage-bindings';

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
		const [ editingBoundAttribute, setEditingBoundAttribute ] =
			useState( null );

		const [ meta, setMeta ] = useEntityProp(
			'postType',
			contentModelFields.postType,
			'meta'
		);

		// Saving the fields as serialized JSON because I was tired of fighting the REST API.
		const fields = meta?.fields ? JSON.parse( meta.fields ) : [];

		// Add a uuid to each field for React to track.
		fields.forEach( ( field ) => {
			if ( ! field.uuid ) {
				field.uuid = window.crypto.randomUUID();
			}
		} );

		const { attributes, setAttributes, name } = props;

		const getBinding = useCallback(
			( attribute ) =>
				attributes.metadata?.[ window.BINDINGS_KEY ]?.[ attribute ],
			[ attributes.metadata ]
		);

		const setBinding = useCallback(
			( field ) => {
				const newAttributes = {
					metadata: {
						...( attributes.metadata ?? {} ),
						[ window.BINDINGS_KEY ]: {
							...( attributes.metadata?.[ window.BINDINGS_KEY ] ??
								{} ),
							[ 'meta_key' ]: field.slug,
						},
						[ window.BLOCK_VARIATION_NAME_ATTR ]: field.label,
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

				supportedAttributes.forEach( ( attribute ) => {
					if (
						! newAttributes.metadata[ window.BINDINGS_KEY ][
							attribute
						]
					) {
						const slug =
							'content' === attribute
								? field.slug
								: `${ field.slug }__${ attribute }`;
						newAttributes.metadata[ window.BINDINGS_KEY ][
							attribute
						] = slug;
					}
				} );

				if ( ! field.slug.trim() ) {
					delete newAttributes.metadata[ window.BINDINGS_KEY ][
						'meta_key'
					];

					supportedAttributes.forEach( ( attribute ) => {
						delete newAttributes.metadata[ window.BINDINGS_KEY ][
							attribute
						];
					} );
				}

				setAttributes( newAttributes );
			},
			[ attributes.metadata, setAttributes ]
		);

		const removeBindings = useCallback( () => {
			const newAttributes = {
				metadata: {
					...( attributes.metadata ?? {} ),
				},
			};

			delete newAttributes.metadata[ window.BINDINGS_KEY ];
			delete newAttributes.metadata[ window.BLOCK_VARIATION_NAME_ATTR ];

			setAttributes( newAttributes );
		}, [ attributes.metadata, setAttributes ] );

		const selectedBlockType = getBlockType( name );

		const supportedAttributes =
			SUPPORTED_BLOCK_ATTRIBUTES[ selectedBlockType?.name ];

		const validations = useMemo( () => {
			const metadata = attributes.metadata ?? {};
			const bindings = metadata[ window.BINDINGS_KEY ] ?? {};

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
				const field = getBinding( attribute );

				if ( field === 'post_content' && name !== 'core/group' ) {
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
						{ attributes?.metadata?.[
							window.BLOCK_VARIATION_NAME_ATTR
						] && (
							<ItemGroup isBordered isSeparated>
								{ supportedAttributes.map( ( attribute ) => (
									<Item key={ attribute } size="small">
										<Flex>
											<FlexBlock>{ attribute }</FlexBlock>
											<FlexItem>
												<span>
													<code>
														{
															attributes
																?.metadata?.[
																window
																	.BINDINGS_KEY
															][ attribute ]
														}
													</code>
												</span>
											</FlexItem>
										</Flex>
									</Item>
								) ) }
							</ItemGroup>
						) }
						<ButtonGroup>
							<Button
								variant="secondary"
								onClick={ () =>
									setEditingBoundAttribute(
										window.BLOCK_VARIATION_NAME_ATTR
									)
								}
							>
								{ __( 'Manage Binding' ) }
							</Button>
							{ attributes?.metadata?.[
								window.BLOCK_VARIATION_NAME_ATTR
							] && (
								<Button
									isDestructive
									onClick={ removeBindings }
								>
									{ __( 'Remove Binding' ) }
								</Button>
							) }
						</ButtonGroup>
						{ editingBoundAttribute && (
							<Modal
								title={ __( 'Manage Bindings' ) }
								size="small"
								onRequestClose={ () =>
									setEditingBoundAttribute( null )
								}
							>
								<ManageBindings
									onSave={ ( formData ) => {
										setBinding( formData );
										setEditingBoundAttribute( null );
									} }
									defaultFormData={ {
										label:
											attributes?.metadata?.[
												window.BLOCK_VARIATION_NAME_ATTR
											] ?? '',
										slug:
											attributes.metadata?.[
												window.BINDINGS_KEY
											]?.[ 'meta_key' ] ?? '',
										description: '',
										type: supportedAttributes.includes(
											'content'
										)
											? 'text'
											: selectedBlockType?.name,
										visible: false,
										uuid: window.crypto.randomUUID(),
									} }
									typeIsDisabled={ true }
								/>
							</Modal>
						) }
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
