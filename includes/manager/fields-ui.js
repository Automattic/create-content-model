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
	CardHeader,
	CardFooter,
	Card,
	CardBody,
	__experimentalGrid as Grid,
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
	const [ isOpen, setOpen ] = useState( false );

	const [ meta, setMeta ] = useEntityProp(
		'postType',
		'content_model',
		'meta'
	);

	// Saving the fields as serialized JSON because I was tired of fighting the REST API.
	const fields = meta?.fields ? JSON.parse( meta.fields ) : [];

	// Open and close the modal.
	const openModal = () => setOpen( true );
	const closeModal = () => setOpen( false );

	return (
		<PluginDocumentSettingPanel
			name="create-content-model-page-settings"
			title={ __( 'Custom Fields' ) }
			className="create-content-model-page-settings"
		>
			<VStack>
				<Card>
					{ fields.map( ( field ) => (
						<CardBody key={ field.slug } size="small">
							{ field.label }
						</CardBody>
					) ) }
				</Card>
				<Button variant="secondary" onClick={ openModal }>
					{ __( 'Manage Fields' ) }
				</Button>
			</VStack>
			{ isOpen && (
				<Modal
					title={ __( 'Manage Fields' ) }
					size="large"
					onRequestClose={ closeModal }
				>
					<FieldsList />
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
		'content_model',
		'meta'
	);

	// Saving the fields as serialized JSON because I was tired of fighting the REST API.
	const fields = meta?.fields ? JSON.parse( meta.fields ) : [];

	// Save the fields back to the meta.
	const setFields = ( newFields ) => {
		console.log( 'Set Fields:', newFields );
		setMeta( { fields: JSON.stringify( newFields ) } );
	};

	return (
		<>
			<VStack spacing={ 16 }>
				{ fields.map( ( field ) => (
					<FieldRow key={ field.slug } field={ field } />
				) ) }

				<EditFieldForm
					save={ ( formData ) => {
						console.log( 'Save', formData );
						setFields( [ ...fields, formData ] );
					} }
				/>
			</VStack>
		</>
	);
};

/**
 * Display a row for a field.
 * @param {Object} field
 * @returns FieldRow
 */
const FieldRow = ( { field } ) => {
	return (
		<>
			<Grid columns={ 5 }>
				<div style={ { gridColumn: '1/4' } }>
					<FieldInput field={ field } isDisabled />
					<small>
						<em>{ field.description }</em>
					</small>
				</div>
				<div style={ { gridColumn: '4/6' } }>
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
						<Button
							icon={ edit }
							title={ __( 'Edit Field' ) }
							onClick={ () => console.log( 'Edit Field' ) }
							disabled={ true }
						/>
						<Button
							icon={ trash }
							title={ __( 'Delete Field' ) }
							onClick={ () => console.log( 'Delete Field' ) }
							disabled={ true }
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
			<Card>
				<CardHeader>
					<h3>{ __( 'Add Field' ) }</h3>
				</CardHeader>
				<CardBody>
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
				</CardBody>
				<CardFooter>
					<Button variant="secondary" onClick={ saveForm }>
						{ __( 'Save' ) }
					</Button>
				</CardFooter>
			</Card>
		</>
	);
};

// Register the plugin.
registerPlugin( 'create-content-model-page-settings', {
	render: CreateContentModelPageSettings,
} );
