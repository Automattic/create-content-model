import { registerPlugin } from '@wordpress/plugins';
import { PluginDocumentSettingPanel } from '@wordpress/editor';
import {
	Button,
	ButtonGroup,
	Modal,
	TextControl,
	TextareaControl,
	__experimentalVStack as VStack,
	__experimentalGrid as Grid,
	__experimentalItemGroup as ItemGroup,
	__experimentalItem as Item,
	Icon,
	Flex,
	FlexBlock,
	FlexItem,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useEntityProp } from '@wordpress/core-data';
import { useState } from '@wordpress/element';
import {
	seen,
	unseen,
	chevronUp,
	chevronDown,
	edit,
	trash,
	plus,
} from '@wordpress/icons';

import AddFieldForm from './_add-field';

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

	const [ slug, setSlug ] = useEntityProp(
		'postType',
		contentModelFields.postType,
		'slug'
	);

	// Saving the fields as serialized JSON because I was tired of fighting the REST API.
	const fields = meta?.fields ? JSON.parse( meta.fields ) : [];

	return (
		<>
			<PluginDocumentSettingPanel
				name="create-content-model-post-settings"
				title={ __( 'Post Type' ) }
				className="create-content-model-post-settings"
			>
				<TextControl
					label={ __( 'Slug' ) }
					value={ slug }
					onChange={ ( value ) => setSlug( value ) }
					help={ __(
						'Warning: Changing the slug will break existing content.'
					) }
				/>
			</PluginDocumentSettingPanel>
			<PluginDocumentSettingPanel
				name="create-content-model-field-settings"
				title={ __( 'Custom Fields' ) }
				className="create-content-model-field-settings"
			>
				<ItemGroup isBordered isSeparated>
					{ fields.map( ( field ) => (
						<Item key={ field.slug } size="small">
							<Flex>
								<FlexBlock>{ field.label }</FlexBlock>
								<FlexItem>
									<code>{ field.slug }</code>
								</FlexItem>

								{ field.visible && (
									<FlexItem>
										<Icon icon={ seen } />
									</FlexItem>
								) }
								{ ! field.visible && (
									<FlexItem>
										<Icon icon={ unseen } />
									</FlexItem>
								) }
							</Flex>
						</Item>
					) ) }
				</ItemGroup>
				<div
					style={ {
						textAlign: 'right',
					} }
				>
					<Button
						icon={ plus }
						onClick={ () => setAddNewOpen( true ) }
						label={ __( 'Add Field' ) }
						style={ {
							background: '#1e1e1e',
							borderRadius: '2px',
							color: '#fff',
							height: '24px',
							minWidth: '24px',
							borderRadius: '0',
						} }
					/>
				</div>

				<Button
					variant="secondary"
					onClick={ () => setFieldsOpen( true ) }
				>
					{ __( 'Manage Fields' ) }
				</Button>

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
						<AddFieldForm
							onSave={ () => {
								setAddNewOpen( false );
							} }
						/>
					</Modal>
				) }
			</PluginDocumentSettingPanel>
		</>
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
			<Grid columns={ 8 }>
				<div style={ { gridColumn: '1/8' } }>
					<FieldInput field={ field } isDisabled />
					<small>
						<em>{ field.description }</em>
					</small>
				</div>
				<div style={ { gridColumn: '8/9' } }>
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
				<TextControl
					label={ field.label }
					type={ field.type }
					readOnly={ isDisabled }
				/>
			);
			break;
	}
};

// Register the plugin.
registerPlugin( 'create-content-model-page-settings', {
	render: CreateContentModelPageSettings,
} );
