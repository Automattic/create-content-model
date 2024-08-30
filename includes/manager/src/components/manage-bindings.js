import { Button, TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useEntityProp } from '@wordpress/core-data';
import { useState, useEffect } from '@wordpress/element';
import { POST_TYPE_NAME } from '../constants';

const ManageBindings = ( {
	defaultFormData = {
		label: '',
		slug: '',
		description: '',
		type: 'text',
		visible: false,
		uuid: crypto.randomUUID(),
	},
	onSave = () => {},
} ) => {
	const [ formData, setFormData ] = useState( defaultFormData );
	const [ isValid, setIsValid ] = useState( false );

	const [ meta, setMeta ] = useEntityProp(
		'postType',
		POST_TYPE_NAME,
		'meta'
	);

	const fields = meta?.fields ? JSON.parse( meta.fields ) : [];

	const saveForm = ( e ) => {
		e.preventDefault();
		let newFields = fields;

		if ( fields.find( ( field ) => field.uuid === formData.uuid ) ) {
			// If the slug is the same and it exists, update the field.
			newFields = newFields.map( ( field ) => {
				if ( field.uuid === formData.uuid ) {
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
