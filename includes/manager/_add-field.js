import {
	Button,
	TextControl,
	SelectControl,
	ToggleControl,
	__experimentalGrid as Grid,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useEntityProp } from '@wordpress/core-data';
import { useState, useEffect } from '@wordpress/element';

/**
 * Display a form to edit a field.
 * @param {Object} props
 * @param {Function} props.onSave
 * @param {Object} props.defaultFormData (to be updated with the field data for editing)
 * @returns EditFieldForm
 */
const AddFieldForm = ( {
	defaultFormData = {
		label: '',
		slug: '',
		description: '',
		type: 'text',
		visible: true,
		uuid: window.crypto.randomUUID(),
	},
	onSave = () => {},
	typeIsDisabled = false,
} ) => {
	const [ formData, setFormData ] = useState( defaultFormData );
	const [ isValid, setIsValid ] = useState( false );

	const [ meta, setMeta ] = useEntityProp(
		'postType',
		contentModelFields.postType,
		'meta'
	);

	const fields = meta?.fields ? JSON.parse( meta.fields ) : [];

	const saveForm = () => {
		setMeta( {
			fields: JSON.stringify( [ ...fields, formData ] ),
		} );
		onSave( formData );
	};

	useEffect( () => {
		const hasValidationErrors = [ 'label', 'slug', 'type' ].some(
			( field ) => ! formData[ field ]
		);

		if ( hasValidationErrors ) {
			setIsValid( false );
		} else {
			setIsValid( true );
		}
	}, [ formData ] );

	return (
		<>
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
				<SelectControl
					label={ __( 'Type' ) }
					value={ formData.type }
					disabled={ typeIsDisabled }
					options={ [
						{ label: __( 'Text' ), value: 'text' },
						{ label: __( 'Textarea' ), value: 'textarea' },
						{ label: __( 'URL' ), value: 'url' },
						{ label: __( 'Image' ), value: 'image' },
						{ label: __( 'Number' ), value: 'number' },
					] }
					onChange={ ( value ) =>
						setFormData( { ...formData, type: value } )
					}
				/>
			</Grid>

			<Grid columns={ 2 } alignment="bottom">
				<TextControl
					label={ __( 'Description (optional)' ) }
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
			</Grid>

			<Button
				variant="secondary"
				onClick={ saveForm }
				disabled={ ! isValid }
			>
				{ __( 'Save Field' ) }
			</Button>
		</>
	);
};

export default AddFieldForm;
