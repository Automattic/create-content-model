import { registerPlugin } from '@wordpress/plugins';
import { PluginDocumentSettingPanel } from '@wordpress/editor';
import {
	Button,
	ButtonGroup,
	Modal,
	TextControl,
	TextareaControl,
	SelectControl,
	__experimentalVStack as VStack,
	Card,
	CardBody,
	__experimentalGrid as Grid,
	__experimentalItemGroup as ItemGroup,
	__experimentalItem as Item,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useEntityProp } from '@wordpress/core-data';
import { useState } from '@wordpress/element';
import { chevronUp, chevronDown, edit, trash } from '@wordpress/icons';

/**
 * Our base plugin component.
 * @returns CreateContentModelPageSettings
 */
const CreateContentModelPageSettings = function () {
	const [ isFieldsOpen, setFieldsOpen ] = useState( false );
	const [ isAddNewOpen, setAddNewOpen ] = useState( false );

	const [ meta, setMeta ] = useEntityProp(
		'postType',
		contentModelFields.postType,
		'meta'
	);

	// Saving the fields as serialized JSON because I was tired of fighting the REST API.
	const fields = meta?.fields ? JSON.parse( meta.fields ) : [];

	return (
		<PluginDocumentSettingPanel
			name="create-content-model-page-settings"
			title={ __( 'Custom Fields' ) }
			className="create-content-model-page-settings"
		>
			<VStack>
				<ItemGroup isBordered isSeparated>
					{ fields.map( ( field ) => (
						<Item key={ field.slug } size="small">
							{ field.label }
							<code>{ field.slug }</code>
						</Item>
					) ) }
				</ItemGroup>

				<Button
					variant="secondary"
					onClick={ () => setFieldsOpen( true ) }
				>
					{ __( 'Manage Fields' ) }
				</Button>
				<Button
					variant="secondary"
					onClick={ () => setAddNewOpen( true ) }
				>
					{ __( 'Add Field' ) }
				</Button>
			</VStack>
			{ isFieldsOpen && (
				<Modal
					title={ __( 'Manage Fields' ) }
					size="large"
					onRequestClose={ () => setFieldsOpen( false ) }
				>
					<FieldsList />
				</Modal>
			) }
			{ isAddNewOpen && (
				<Modal
					title={ __( 'Add New Field' ) }
					size="large"
					onRequestClose={ () => setAddNewOpen( false ) }
				>
					<EditFieldForm
						save={ ( formData ) => {
							console.log( 'Save', formData );
							setMeta( {
								fields: JSON.stringify( [
									...fields,
									formData,
								] ),
							} );
							setAddNewOpen( false );
						} }
					/>
				</Modal>
			) }
		</PluginDocumentSettingPanel>
	);
};

/**
 * Display the list of fields inside the modal.
 * @returns FieldsList
 */
const FieldsList = () => {
	const [ meta, setMeta ] = useEntityProp(
		'postType',
		contentModelFields.postType,
		'meta'
	);

	// Saving the fields as serialized JSON because I was tired of fighting the REST API.
	const fields = meta?.fields ? JSON.parse( meta.fields ) : [];

	// Save the fields back to the meta.
	const setFields = ( newFields ) => {
		console.log( 'Set Fields:', newFields );
		setMeta( { fields: JSON.stringify( newFields ) } );
	};

	const deleteField = ( field ) => {
		console.log( 'Delete Field:', field );
		const newFields = fields.filter( ( f ) => f.slug !== field.slug );
		setFields( newFields );
	};

	const editField = ( field ) => {
		console.log( 'Edit Field:', field );
	};

	return (
		<>
			<VStack spacing={ 16 }>
				{ fields.map( ( field ) => (
					<FieldRow
						key={ field.slug }
						field={ field }
						editField={ editField }
						deleteField={ deleteField }
					/>
				) ) }
			</VStack>
		</>
	);
};

/**
 * Display a row for a field.
 * @param {Object} field
 * @returns FieldRow
 */
const FieldRow = ( { field, deleteField, editField } ) => {
	const handleDeleteField = () => {
		if (
			! confirm( __( 'Are you sure you want to delete this field?' ) )
		) {
			return;
		}
		deleteField( field );
	};

	const handleEditField = () => {
		editField( field );
	};
	return (
		<>
			<Grid columns={ 4 }>
				<div style={ { gridColumn: '1/4' } }>
					<FieldInput field={ field } isDisabled />
					<small>
						<em>{ field.description }</em>
					</small>
				</div>
				<div style={ { gridColumn: '4/5' } }>
					<ButtonGroup>
						{ /* <Button
							icon={ chevronUp }
							title={ __( 'Move Field Up' ) }
							onClick={ () => console.log( 'Move Field Up' ) }
						/>
						<Button
							icon={ chevronDown }
							title={ __( 'Move Field Down' ) }
							onClick={ () => console.log( 'Move Field Down' ) }
						/> */ }
						{ /* <Button
							icon={ edit }
							title={ __( 'Edit Field' ) }
							onClick={ handleEditField }
						/> */ }
						<Button
							icon={ trash }
							title={ __( 'Delete Field' ) }
							onClick={ handleDeleteField }
						/>
					</ButtonGroup>
				</div>
			</Grid>
		</>
	);
};

/**
 * Display the input for a field.
 * @param {Object} field
 * @param {boolean} isDisabled
 * @returns FieldInput
 */
const FieldInput = ( { field, isDisabled = false } ) => {
	switch ( field.type ) {
		case 'image':
			return (
				<>
					<label
						style={ {
							textTransform: 'uppercase',
							fontSize: '11px',
							marginBottom: 'calc(8px)',
							fontWeight: '500',
						} }
					>
						{ field.label }
					</label>
					<div style={ { display: 'block' } }>
						<Button variant="secondary" disabled={ isDisabled }>
							{ __( 'Upload Image' ) }
						</Button>
					</div>
				</>
			);
			break;

		case 'textarea':
			return (
				<TextareaControl
					label={ field.label }
					readOnly={ isDisabled }
				/>
			);
			break;

		default:
			return (
				<TextControl label={ field.label } readOnly={ isDisabled } />
			);
			break;
	}
};

/**
 * Display a form to edit a field.
 * @param {Object} props
 * @param {Function} props.save
 * @param {Object} props.defaultFormData (to be updated with the field data for editing)
 * @returns EditFieldForm
 */
const EditFieldForm = ( {
	save = () => {},
	defaultFormData = {
		label: '',
		slug: '',
		description: '',
		type: 'text',
	},
} ) => {
	const [ formData, setFormData ] = useState( defaultFormData );

	const saveForm = () => {
		save( formData );
		setFormData( defaultFormData );
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

			<Button variant="secondary" onClick={ saveForm }>
				{ __( 'Save' ) }
			</Button>
		</>
	);
};

// Register the plugin.
registerPlugin( 'create-content-model-page-settings', {
	render: CreateContentModelPageSettings,
} );
