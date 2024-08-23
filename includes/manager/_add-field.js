import {
	Button,
	TextControl,
	SelectControl,
	ToggleControl,
	__experimentalGrid as Grid,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useEntityProp } from '@wordpress/core-data';
import { useState } from '@wordpress/element';
import { v4 as uuid } from 'uuid';

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
		uuid: uuid(),
	},
	onSave = () => {},
} ) => {
	const [ formData, setFormData ] = useState( defaultFormData );

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
		onSave();
	};

	return (
		<>
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

			<Button variant="secondary" onClick={ saveForm }>
				{ __( 'Save Field' ) }
			</Button>
		</>
	);
};

export default AddFieldForm;
