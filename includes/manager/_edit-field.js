import {
	Button,
	ButtonGroup,
	TextControl,
	SelectControl,
	ToggleControl,
	__experimentalGrid as Grid,
	CardBody,
	Card,
	__experimentalItemGroup as ItemGroup,
	__experimentalItem as Item,
	Flex,
	FlexBlock,
	FlexItem,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import {
	trash,
	chevronUp,
	chevronDown,
	blockDefault,
	post,
} from '@wordpress/icons';
import { useEffect } from '@wordpress/element';

import SUPPORTED_BLOCK_ATTRIBUTES from './_supported-attributes';

/**
 * Display a form to edit a field.
 * @param {Object} props
 * @param {Function} props.onSave
 * @param {Object} props.defaultFormData (to be updated with the field data for editing)
 * @returns EditFieldForm
 */
const EditFieldForm = ( {
	field = {
		label: '',
		slug: '',
		description: '',
		type: 'text',
		visible: false,
	},
	onChange = () => {},
	onDelete = () => {},
	onMoveUp = () => {},
	onMoveDown = () => {},
	index,
	total,
} ) => {
	const [ formData, setFormData ] = useState( field );

	useEffect( () => {
		onChange( formData );
	}, [ formData ] );

	return (
		<>
			<Card>
				<CardBody>
					<ButtonGroup
						style={ {
							marginBottom: '1rem',
						} }
					>
						{ formData.type.indexOf( 'core/' ) === -1 ? (
							<Button icon={ post } title={ formData.type }>
								{ __( 'Custom Field' ) }
							</Button>
						) : (
							<Button
								icon={ blockDefault }
								title={ formData.type }
							>
								{ __( 'Block Binding' ) }
							</Button>
						) }
						{ index > 0 && (
							<Button
								icon={ chevronUp }
								title={ __( 'Move Field Up' ) }
								onClick={ () => {
									onMoveUp( formData );
								} }
							/>
						) }
						{ index < total - 1 && (
							<Button
								icon={ chevronDown }
								title={ __( 'Move Field Down' ) }
								onClick={ () => {
									onMoveDown( formData );
								} }
							/>
						) }
						<Button
							icon={ trash }
							title={ __( 'Delete Field' ) }
							onClick={ () => {
								confirm(
									__(
										'Are you sure you want to delete this field?'
									)
								) && onDelete( formData );
							} }
						/>
					</ButtonGroup>

					<Grid columns={ 3 }>
						<TextControl
							label={ __( 'Name' ) }
							value={ formData.label }
							onChange={ ( value ) =>
								setFormData( { ...formData, label: value } )
							}
						/>
						<TextControl
							label={ __( 'Key' ) }
							value={ formData.slug }
							onChange={ ( value ) =>
								setFormData( { ...formData, slug: value } )
							}
						/>
						<TextControl
							label={ __( 'Description (optional)' ) }
							value={ formData.description }
							onChange={ ( value ) =>
								setFormData( {
									...formData,
									description: value,
								} )
							}
						/>
					</Grid>
					{ formData.type.indexOf( 'core/' ) === -1 && (
						<Grid columns={ 2 } alignment="bottom">
							<SelectControl
								label={ __( 'Type' ) }
								value={ formData.type }
								disabled={
									formData.type.indexOf( 'core/' ) === 0
								}
								options={ [
									{ label: __( 'Text' ), value: 'text' },
									{
										label: __( 'Textarea' ),
										value: 'textarea',
									},
									{ label: __( 'URL' ), value: 'url' },
									{ label: __( 'Image' ), value: 'image' },
									{ label: __( 'Number' ), value: 'number' },
								] }
								onChange={ ( value ) =>
									setFormData( { ...formData, type: value } )
								}
							/>
							<ToggleControl
								label={ __(
									'Show Field in Custom Fields Form'
								) }
								checked={ formData.visible ?? false }
								onChange={ ( value ) =>
									setFormData( {
										...formData,
										visible: value,
									} )
								}
							/>
						</Grid>
					) }
					{ formData.type.indexOf( 'core/' ) === 0 && (
						<BlockAttributes
							slug={ formData.slug }
							type={ formData.type }
						/>
					) }
				</CardBody>
			</Card>
		</>
	);
};

const BlockAttributes = ( { slug, type } ) => {
	const supportedAttributes = SUPPORTED_BLOCK_ATTRIBUTES[ type ];
	return (
		<ItemGroup isBordered isSeparated>
			{ supportedAttributes.map( ( attribute ) => (
				<Item key={ attribute }>
					<Flex>
						<FlexBlock>{ attribute }</FlexBlock>
						<FlexItem>
							<span>
								<code>{ `${ slug }__${ attribute }` }</code>
							</span>
						</FlexItem>
					</Flex>
				</Item>
			) ) }
		</ItemGroup>
	);
};

export default EditFieldForm;
