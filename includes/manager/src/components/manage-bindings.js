import { Button, TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useEntityProp } from '@wordpress/core-data';
import { useState, useEffect } from '@wordpress/element';
import { POST_TYPE_NAME } from '../constants';
import { cleanForSlug } from '@wordpress/url';

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

	const blocks = meta?.blocks ? JSON.parse( meta.blocks ) : [];

	const saveForm = ( e ) => {
		e.preventDefault();
		let newBlocks = blocks;

		if ( formData.slug === '' ) {
			const slug = cleanForSlug( formData.label ).replace( /-/g, '_' );
			formData.slug = slug;
		}

		if ( blocks.find( ( block ) => block.uuid === formData.uuid ) ) {
			// If the slug is the same and it exists, update the block.
			newBlocks = newBlocks.map( ( block ) => {
				if ( block.uuid === formData.uuid ) {
					block = formData;
				}
				return block;
			} );
			setMeta( {
				blocks: JSON.stringify( newBlocks ),
			} );
		} else {
			setMeta( {
				blocks: JSON.stringify( [ ...newBlocks, formData ] ),
			} );
		}
		onSave( formData );
	};

	useEffect( () => {
		if ( formData.label === '' ) {
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
