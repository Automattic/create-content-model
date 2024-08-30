import {
	Button,
	ButtonGroup,
	TextControl,
	SelectControl,
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
import { useState, useEffect } from '@wordpress/element';
import {
	trash,
	chevronUp,
	chevronDown,
	blockDefault,
	post,
} from '@wordpress/icons';

import { SUPPORTED_BLOCK_ATTRIBUTES } from '../constants';

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

	const isBlock = formData.type.startsWith( 'core/' );

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
						{ isBlock ? (
							<Button
								icon={ blockDefault }
								title={ formData.type }
							>
								{ __( 'Block Binding' ) }
							</Button>
						) : (
							<Button icon={ post } title={ formData.type }>
								{ __( 'Custom Field' ) }
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
						{ ! isBlock ?? (
							<Button
								icon={ trash }
								title={ __( 'Delete Field' ) }
								onClick={ () => {
									// eslint-disable-next-line no-alert
									const userWantsToDelete = confirm(
										__(
											'Are you sure you want to delete this field?'
										)
									);

									if ( userWantsToDelete ) {
										onDelete( formData );
									}
								} }
							/>
						) }
					</ButtonGroup>

					<Grid columns={ isBlock ? 3 : 4 }>
						<TextControl
							label={ __( 'Name' ) }
							value={ formData.label }
							disabled={ formData.type.indexOf( 'core/' ) === 0 }
							onChange={ ( value ) =>
								setFormData( { ...formData, label: value } )
							}
						/>
						<TextControl
							label={ __( 'Key' ) }
							value={ formData.slug }
							disabled={ formData.type.indexOf( 'core/' ) === 0 }
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

						{ ! isBlock && (
							<SelectControl
								label={ __( 'Field Type' ) }
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
						) }
					</Grid>
					{ isBlock ? (
						<BlockAttributes
							slug={ formData.slug }
							type={ formData.type }
						/>
					) : (
						<ItemGroup isBordered isSeparated>
							<Item>
								<Flex>
									<FlexBlock>{ formData.type }</FlexBlock>
									<FlexItem>
										<span>
											<code>{ formData.slug }</code>
										</span>
									</FlexItem>
								</Flex>
							</Item>
						</ItemGroup>
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
								{ 'post_content' === slug ? (
									<code>{ slug }</code>
								) : (
									<code>{ `${ slug }__${ attribute }` }</code>
								) }
							</span>
						</FlexItem>
					</Flex>
				</Item>
			) ) }
		</ItemGroup>
	);
};

export default EditFieldForm;
