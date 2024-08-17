import { registerPlugin } from '@wordpress/plugins';
import { PluginDocumentSettingPanel } from '@wordpress/editor';
import {
	Button,
	Modal,
	TextControl,
	TextareaControl,
	SelectControl,
	CheckboxControl,
	__experimentalVStack as VStack,
	CardHeader,
	Card,
	CardBody,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useEntityProp } from '@wordpress/core-data';
import { useState } from '@wordpress/element';

const CreateContentModelPageSettings = function () {
	const [ isOpen, setOpen ] = useState( false );
	const openModal = () => setOpen( true );
	const closeModal = () => setOpen( false );

	// Get the current post meta.
	const [ meta, setMeta ] = useEntityProp( 'postType', 'post', 'meta' );

	console.log( meta ?? 'No meta' );

	return (
		<PluginDocumentSettingPanel
			name="create-content-model-page-settings"
			title={ __( 'Fields UI' ) }
			className="create-content-model-page-settings"
		>
			<Button variant="secondary" onClick={ openModal }>
				{ __( 'Manage Fields' ) }
			</Button>
			{ isOpen && (
				<Modal
					title={ __( 'Manage Fields' ) }
					isFullScreen
					onRequestClose={ closeModal }
				>
					<FieldsList />
				</Modal>
			) }
		</PluginDocumentSettingPanel>
	);
};

const FieldsList = () => {
	// Via https://contentmodelingp2.wordpress.com/2024/08/07/decision-time-proposals-for-json-representation-and-cpt-template/
	const [ fields, setFields ] = useState( [
		{
			slug: 'image',
			label: 'Image',
			type: 'image',
			description: 'Image of the event.',
			required: true,
		},
		{
			slug: 'location',
			label: 'Location',
			type: 'text',
			description: 'Location of the event.',
			required: true,
		},
		{
			slug: 'date',
			label: 'Date',
			type: 'text',
			description: 'Date of the event.',
			required: true,
		},
		{
			slug: 'time',
			label: 'Time',
			type: 'text',
			description: 'Start time of the event.',
			required: true,
		},
		{
			slug: 'details',
			label: 'Event Details',
			type: 'textarea',
			description: 'Detailed information about the event.',
			required: false,
		},
	] );

	return (
		<>
			<VStack>
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

const FieldRow = ( { field } ) => {
	return (
		<>
			<div>
				<FieldInput field={ field } isDisabled />
				<small>
					<em>{ field.description }</em>
				</small>
			</div>
		</>
	);
};

const EditFieldForm = ( {
	save = () => {},
	defaultFormData = {
		label: '',
		slug: '',
		description: '',
		type: '',
		required: false,
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
					<TextControl
						label={ __( 'Description' ) }
						value={ formData.description }
						onChange={ ( value ) =>
							setFormData( { ...formData, description: value } )
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
					{ /* <CheckboxControl label={ __( 'Required' ) } checked={ false } /> */ }

					<Button variant="primary" onClick={ saveForm }>
						{ __( 'Add Field' ) }
					</Button>
				</CardBody>
			</Card>
		</>
	);
};

registerPlugin( 'create-content-model-page-settings', {
	render: CreateContentModelPageSettings,
} );
