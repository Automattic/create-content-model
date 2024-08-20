import { registerPlugin } from '@wordpress/plugins';
import { PluginDocumentSettingPanel } from '@wordpress/editor';
import {
	Button,
	Modal,
	TextControl,
	TextareaControl,
	__experimentalVStack as VStack,
	Card,
	CardBody,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useEntityProp } from '@wordpress/core-data';
import { useState } from '@wordpress/element';

/**
 * Our base plugin component.
 * @returns CreateContentModelPageSettings
 */
const CreateContentModelPageSettings = function () {
	const [ isFieldsOpen, setFieldsOpen ] = useState( false );

	return (
		<PluginDocumentSettingPanel
			name="create-content-model-page-settings"
			title={ __( 'Custom Fields' ) }
			className="create-content-model-page-settings"
		>
			<VStack>
				<FieldsList />

				<Button
					variant="secondary"
					onClick={ () => setFieldsOpen( true ) }
				>
					{ __( 'Expand Fields' ) }
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
		</PluginDocumentSettingPanel>
	);
};

/**
 * Display the list of fields inside the modal.
 * @returns FieldsList
 */
const FieldsList = () => {
	const fields = contentModelFields.fields;

	return (
		<>
			<VStack spacing={ 8 }>
				{ fields.map( ( field ) => (
					<FieldRow key={ field.slug } field={ field } />
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
const FieldRow = ( { field } ) => {
	const [ meta, setMeta ] = useEntityProp(
		'postType',
		contentModelFields.postType,
		'meta'
	);

	const value = meta[ field.slug ] ?? '';

	return (
		<>
			<div>
				<FieldInput
					field={ field }
					value={ value }
					saveChanges={ ( slug, value ) => {
						setMeta( {
							[ slug ]: value,
						} );
					} }
				/>
				<small>
					<em>{ field.description }</em>
				</small>
			</div>
		</>
	);
};

/**
 * Display the input for a field.
 * @param {Object} field
 * @param {boolean} isDisabled
 * @returns FieldInput
 */
const FieldInput = ( { field, isDisabled = false, value, saveChanges } ) => {
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
					value={ value }
					onChange={ ( value ) => saveChanges( field.slug, value ) }
				/>
			);
			break;

		default:
			return (
				<TextControl
					label={ field.label }
					readOnly={ isDisabled }
					value={ value }
					onChange={ ( value ) => saveChanges( field.slug, value ) }
				/>
			);
			break;
	}
};

// Register the plugin.
registerPlugin( 'create-content-model-page-settings', {
	render: CreateContentModelPageSettings,
} );
