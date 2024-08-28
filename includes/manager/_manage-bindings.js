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
	const [ originalSlug, setOriginalSlug ] = useState( defaultFormData.slug );

	const [ meta, setMeta ] = useEntityProp(
		'postType',
		contentModelFields.postType,
		'meta'
	);

	const fields = meta?.fields ? JSON.parse( meta.fields ) : [];

	const saveForm = ( e ) => {
		e.preventDefault();
		let newFields = fields;

		if ( originalSlug !== formData.slug ) {
			// If the slug has been updated, remove the old slug.
			newFields = newFields.filter(
				( field ) => field.slug !== originalSlug
			);
			newFields.push( formData );
			setMeta( {
				fields: JSON.stringify( newFields ),
			} );
		} else if ( fields.find( ( field ) => field.slug === formData.slug ) ) {
			// If the slug is the same and it exists, update the field.
			newFields = newFields.map( ( field ) => {
				if ( field.slug === formData.slug ) {
					field = formData;
				}
				return field;
			} );
			setMeta( {
				fields: JSON.stringify( newFields ),
			} );
		} else {
			setMeta( {
				fields: JSON.stringify( [ ...newFields, formData ] ),
			} );
		}
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
					label={ __( 'Binding Name' ) }
					value={ formData.label }
					onChange={ ( value ) =>
						setFormData( { ...formData, label: value } )
					}
				/>
				<TextControl
					label={ __( 'Binding Metakey' ) }
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
