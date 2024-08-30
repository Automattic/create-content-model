import { addFilter } from '@wordpress/hooks';
import { useCallback, useMemo, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { createHigherOrderComponent } from '@wordpress/compose';
import { InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	PanelRow,
	Button,
	ButtonGroup,
	__experimentalItemGroup as ItemGroup,
	__experimentalItem as Item,
	Flex,
	FlexBlock,
	FlexItem,
} from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { store as blocksStore } from '@wordpress/blocks';
import { useEntityProp } from '@wordpress/core-data';

import ManageBindings from './_manage-bindings';

import SUPPORTED_BLOCK_ATTRIBUTES from './_supported-attributes';

const withAttributeBinder = createHigherOrderComponent( ( BlockEdit ) => {
	return ( props ) => {
		const { getBlockType } = useSelect( blocksStore );
		const { getBlockParentsByBlockName, getBlocksByClientId } =
			useSelect( 'core/block-editor' );
		const [ editingBoundAttribute, setEditingBoundAttribute ] =
			useState( null );

		const [ meta, setMeta ] = useEntityProp(
			'postType',
			window.contentModelFields.postType,
			'meta'
		);

		const fields = useMemo( () => {
			// Saving the fields as serialized JSON because I was tired of fighting the REST API.
			return meta?.fields ? JSON.parse( meta.fields ) : [];
		}, [ meta.fields ] );

		const { attributes, setAttributes, name } = props;

		const boundField = fields.find(
			( field ) => field.slug === attributes.metadata?.slug
		);

		const removeBindings = useCallback( () => {
			const newAttributes = {
				metadata: {
					...( attributes.metadata ?? {} ),
				},
			};

			delete newAttributes.metadata[ window.BINDINGS_KEY ];
			delete newAttributes.metadata[ window.BLOCK_VARIATION_NAME_ATTR ];
			delete newAttributes.metadata.slug;

			const newFields = fields.filter(
				( field ) => field.slug !== attributes.metadata.slug
			);

			setMeta( {
				fields: JSON.stringify( newFields ),
			} );

			setAttributes( newAttributes );
		}, [ attributes.metadata, setAttributes, fields, setMeta ] );

		const selectedBlockType = getBlockType( name );

		const blockParentsByBlockName = getBlockParentsByBlockName(
			props.clientId,
			[ 'core/group' ]
		);

		// Check if any parent blocks have bindings.
		const parentHasBindings = useMemo( () => {
			return (
				getBlocksByClientId( blockParentsByBlockName ).filter(
					( block ) =>
						Object.keys(
							block?.attributes?.metadata?.[
								window.BINDINGS_KEY
							] || {}
						).length > 0
				).length > 0
			);
		}, [ blockParentsByBlockName, getBlocksByClientId ] );

		const supportedAttributes =
			SUPPORTED_BLOCK_ATTRIBUTES[ selectedBlockType?.name ];

		const setBinding = useCallback(
			( field ) => {
				const bindings = supportedAttributes.reduce(
					( acc, attribute ) => {
						acc[ attribute ] =
							'post_content' === field.slug
								? field.slug
								: `${ field.slug }__${ attribute }`;

						return acc;
					},
					{}
				);

				const newAttributes = {
					metadata: {
						...( attributes.metadata ?? {} ),
						[ window.BLOCK_VARIATION_NAME_ATTR ]: field.label,
						slug: field.slug,
						[ window.BINDINGS_KEY ]: bindings,
					},
				};

				setAttributes( newAttributes );
			},
			[ attributes.metadata, setAttributes, supportedAttributes ]
		);

		if ( ! supportedAttributes || parentHasBindings ) {
			return <BlockEdit { ...props } />;
		}

		const bindings = attributes?.metadata?.[ window.BINDINGS_KEY ];

		return (
			<>
				<InspectorControls>
					<PanelBody title="Attribute Bindings" initialOpen>
						{ ! editingBoundAttribute && bindings && (
							<ItemGroup isBordered isSeparated>
								{ supportedAttributes.map( ( attribute ) => {
									return (
										<Item key={ attribute }>
											<Flex>
												<FlexBlock>
													{ attribute }
												</FlexBlock>
												{ bindings[ attribute ] && (
													<FlexItem>
														<span>
															<code>
																{
																	bindings[
																		attribute
																	]
																}
															</code>
														</span>
													</FlexItem>
												) }
											</Flex>
										</Item>
									);
								} ) }
							</ItemGroup>
						) }
						{ ! editingBoundAttribute && (
							<PanelRow>
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
									{ bindings && (
										<Button
											isDestructive
											onClick={ removeBindings }
										>
											{ __( 'Remove Binding' ) }
										</Button>
									) }
								</ButtonGroup>
							</PanelRow>
						) }
						{ editingBoundAttribute && (
							<PanelRow>
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
										slug: attributes?.metadata?.slug ?? '',
										uuid:
											boundField?.uuid ??
											window.crypto.randomUUID(),
										description: '',
										type: selectedBlockType?.name,
										visible: false,
									} }
									typeIsDisabled={ true }
								/>
							</PanelRow>
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
