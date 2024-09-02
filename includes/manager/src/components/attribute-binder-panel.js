import { useCallback, useMemo, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
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
import { useEntityProp } from '@wordpress/core-data';

import ManageBindings from './manage-bindings';
import {
	SUPPORTED_BLOCK_ATTRIBUTES,
	BINDINGS_KEY,
	POST_TYPE_NAME,
	BLOCK_VARIATION_NAME_ATTR,
} from '../constants';

export const AttributeBinderPanel = ( { attributes, setAttributes, name } ) => {
	const supportedAttributes = SUPPORTED_BLOCK_ATTRIBUTES[ name ];
	const bindings = attributes?.metadata?.[ BINDINGS_KEY ];

	const [ editingBoundAttribute, setEditingBoundAttribute ] =
		useState( null );

	const [ meta, setMeta ] = useEntityProp(
		'postType',
		POST_TYPE_NAME,
		'meta'
	);

	const blocks = useMemo( () => {
		return meta?.blocks ? JSON.parse( meta.blocks ) : [];
	}, [ meta.blocks ] );

	const boundField = blocks.find(
		( block ) => block.slug === attributes.metadata?.slug
	);

	const removeBindings = useCallback( () => {
		const newAttributes = {
			metadata: {
				...( attributes.metadata ?? {} ),
			},
		};

		delete newAttributes.metadata[ BINDINGS_KEY ];
		delete newAttributes.metadata[ BLOCK_VARIATION_NAME_ATTR ];
		delete newAttributes.metadata.slug;

		const newBlocks = blocks.filter(
			( block ) => block.slug !== attributes.metadata.slug
		);

		setMeta( {
			blocks: JSON.stringify( newBlocks ),
		} );

		setAttributes( newAttributes );
	}, [ attributes.metadata, setAttributes, blocks, setMeta ] );

	const setBinding = useCallback(
		( block ) => {
			const newBindings = supportedAttributes.reduce(
				( acc, attribute ) => {
					acc[ attribute ] =
						'post_content' === block.slug
							? block.slug
							: `${ block.slug }__${ attribute }`;

					return acc;
				},
				{}
			);

			const newAttributes = {
				metadata: {
					...( attributes.metadata ?? {} ),
					[ BLOCK_VARIATION_NAME_ATTR ]: block.label,
					slug: block.slug,
					[ BINDINGS_KEY ]: newBindings,
				},
			};

			setAttributes( newAttributes );
		},
		[ attributes.metadata, setAttributes, supportedAttributes ]
	);

	return (
		<InspectorControls>
			<PanelBody title="Attribute Bindings" initialOpen>
				{ ! editingBoundAttribute && bindings && (
					<ItemGroup isBordered isSeparated>
						{ supportedAttributes.map( ( attribute ) => {
							return (
								<Item key={ attribute }>
									<Flex>
										<FlexBlock>{ attribute }</FlexBlock>
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
										BLOCK_VARIATION_NAME_ATTR
									)
								}
							>
								{ bindings
									? __( 'Manage Binding' )
									: __( 'Add Binding' ) }
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
										BLOCK_VARIATION_NAME_ATTR
									] ?? '',
								slug: attributes?.metadata?.slug ?? '',
								uuid: boundField?.uuid ?? crypto.randomUUID(),
								description: '',
								type: name,
								visible: false,
							} }
							typeIsDisabled={ true }
						/>
					</PanelRow>
				) }
			</PanelBody>
		</InspectorControls>
	);
};
