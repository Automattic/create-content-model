import { Button, TextControl } from '@wordpress/components';
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
const ManageBindings = ( {
	defaultFormData = {
		label: '',
		slug: '',
		description: '',
		type: 'text',
		visible: true,
		uuid: window.crypto.randomUUID(),
	},
	onSave = () => {},
} ) => {
	const [ formData, setFormData ] = useState( defaultFormData );
	const [ isValid, setIsValid ] = useState( false );

	const [ meta, setMeta ] = useEntityProp(
		'postType',
		contentModelFields.postType,
		'meta'
	);

	const fields = meta?.fields ? JSON.parse( meta.fields ) : [];

	const saveForm = ( e ) => {
		e.preventDefault();
		if ( fields.find( ( field ) => field.slug === formData.slug ) ) {
			onSave( formData );
			return;
		}
		setMeta( {
			fields: JSON.stringify( [ ...fields, formData ] ),
		} );
		onSave( formData );
	};

	useEffect( () => {
		if ( formData.slug === '' ) {
			setIsValid( false );
		} else {
			setIsValid( true );
		}
	}, [ formData ] );

	return (
		<>
			<form onSubmit={ saveForm }>
				<TextControl
					label={ __( 'Label' ) }
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

				<Button
					variant="secondary"
					type="submit"
					disabled={ ! isValid }
				>
					{ __( 'Save Field' ) }
				</Button>
			</form>
		</>
	);
};

export default ManageBindings;
