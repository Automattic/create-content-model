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
import { trash, chevronUp, chevronDown, post } from '@wordpress/icons';
import { cleanForSlug } from '@wordpress/url';

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
	const [ slugWasTouched, setSlugWasTouched ] = useState(
		formData.slug !== ''
	);

	useEffect( () => {
		console.log( 'formData', formData, slugWasTouched );
		if ( ! slugWasTouched ) {
			setFormData( {
				...formData,
				slug: cleanForSlug( formData.label ).replace( /-/g, '_' ),
			} );
		}
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
						<Button icon={ post } title={ formData.type }>
							{ __( 'Custom Field' ) }
						</Button>

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
					</ButtonGroup>

					<Grid columns={ 4 }>
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
							onChange={ ( value ) => {
								setSlugWasTouched( true );
								setFormData( { ...formData, slug: value } );
							} }
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

						<SelectControl
							label={ __( 'Field Type' ) }
							value={ formData.type }
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
					</Grid>
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
				</CardBody>
			</Card>
		</>
	);
};

export default EditFieldForm;
