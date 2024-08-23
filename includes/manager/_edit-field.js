import {
	Button,
	ButtonGroup,
	TextControl,
	SelectControl,
	ToggleControl,
	__experimentalGrid as Grid,
	CardBody,
	Card,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useEntityProp } from '@wordpress/core-data';
import { useState } from '@wordpress/element';
import { trash, chevronUp, chevronDown } from '@wordpress/icons';
import { useEffect } from '@wordpress/element';

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
							label={ __( 'Label' ) }
							value={ formData.label }
							onChange={ ( value ) =>
								setFormData( { ...formData, label: value } )
							}
						/>
						<TextControl
							label={ __( 'Slug' ) }
							value={ formData.slug }
							onChange={ ( value ) =>
								setFormData( { ...formData, slug: value } )
							}
						/>
						<SelectControl
							label={ __( 'Field Type' ) }
							value={ formData.type }
							options={ [
								{ label: __( 'Text' ), value: 'text' },
								{ label: __( 'Textarea' ), value: 'textarea' },
								{ label: __( 'URL' ), value: 'url' },
								{ label: __( 'Image' ), value: 'image' },
							] }
							onChange={ ( value ) =>
								setFormData( { ...formData, type: value } )
							}
						/>
					</Grid>

					<TextControl
						label={ __( 'Field Description (optional)' ) }
						value={ formData.description }
						onChange={ ( value ) =>
							setFormData( { ...formData, description: value } )
						}
					/>
					<ToggleControl
						label={ __( 'Show Field in Custom Fields Form' ) }
						checked={ formData.visible ?? false }
						onChange={ ( value ) =>
							setFormData( { ...formData, visible: value } )
						}
					/>
				</CardBody>
			</Card>
		</>
	);
};

export default EditFieldForm;
