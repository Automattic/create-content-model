import { registerPlugin } from '@wordpress/plugins';
import { PluginDocumentSettingPanel } from '@wordpress/editor';
import {
	Button,
	Modal,
	TextControl,
	__experimentalVStack as VStack,
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
import { seen, unseen, plus } from '@wordpress/icons';

import AddFieldForm from './_add-field';
import EditFieldForm from './_edit-field';

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

	const [ title ] = useEntityProp(
		'postType',
		contentModelFields.postType,
		'title'
	);

	// Saving the fields as serialized JSON because I was tired of fighting the REST API.
	const fields = meta?.fields ? JSON.parse( meta.fields ) : [];

	// Add UUID to fields
	fields.forEach( ( field ) => {
		if ( ! field.uuid ) {
			field.uuid = window.crypto.randomUUID();
		}
	} );

	const textControlFields = [
		{
			key: 'slug',
			label: __( 'Slug' ),
			value: slug,
			onChange: ( value ) => setSlug( value ),
			help: __(
				'Warning: Changing the slug will break existing content.'
			),
		},
		{
			key: 'singular_label',
			label: __( 'Singular Label' ),
			value: title,
			disabled: true,
			help: __( 'Synced with the title of the post type.' ),
		},
		{
			key: 'plural_label',
			label: __( 'Plural Label' ),
			value: meta.plural_label || `${ title }s`,
			onChange: ( value ) => setMeta( { ...meta, plural_label: value } ),
			help: __(
				'This is the label that will be used for the plural form of the post type.'
			),
		},
		{
			key: 'description',
			label: __( 'Description' ),
			value: meta.description,
			onChange: ( value ) => setMeta( { ...meta, description: value } ),
			help: __( 'Description for the post type.' ),
		},
	];

	return (
		<>
			<PluginDocumentSettingPanel
				name="create-content-model-post-settings"
				title={ __( 'Post Type' ) }
				className="create-content-model-post-settings"
			>
				{ textControlFields.map( ( field ) => (
					<TextControl
						key={ field.key }
						label={ field.label }
						value={ field.value }
						onChange={ field.onChange }
						disabled={ field.disabled }
						help={ field.help }
					/>
				) ) }
			</PluginDocumentSettingPanel>
			<PluginDocumentSettingPanel
				name="create-content-model-field-settings"
				title={ __( 'Custom Fields' ) }
				className="create-content-model-field-settings"
			>
				<ItemGroup isBordered isSeparated>
					{ fields.map( ( field ) => (
						<Item key={ field.uuid } size="small">
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

	// Add a uuid to each field for React to track.
	fields.forEach( ( field ) => {
		if ( ! field.uuid ) {
			field.uuid = window.crypto.randomUUID();
		}
	} );

	// Save the fields back to the meta.
	const setFields = ( newFields ) => {
		setMeta( { fields: JSON.stringify( newFields ) } );
	};

	const deleteField = ( field ) => {
		const newFields = fields.filter( ( f ) => f.uuid !== field.uuid );
		setFields( newFields );
	};

	const editField = ( field ) => {
		const newFields = fields.map( ( f ) =>
			f.uuid === field.uuid ? field : f
		);
		setFields( newFields );
	};

	return (
		<>
			<VStack spacing={ 16 }>
				{ fields.map( ( field ) => (
					<EditFieldForm
						key={ field.uuid }
						field={ field }
						onDelete={ deleteField }
						onChange={ editField }
						total={ fields.length }
						index={ fields.findIndex(
							( f ) => f.uuid === field.uuid
						) }
						onMoveUp={ ( field ) => {
							const index = fields.findIndex(
								( f ) => f.uuid === field.uuid
							);
							const newFields = [ ...fields ];
							newFields.splice( index, 1 );
							newFields.splice( index - 1, 0, field );
							setFields( newFields );
						} }
						onMoveDown={ ( field ) => {
							const index = fields.findIndex(
								( f ) => f.uuid === field.uuid
							);
							const newFields = [ ...fields ];
							newFields.splice( index, 1 );
							newFields.splice( index + 1, 0, field );
							setFields( newFields );
						} }
					/>
				) ) }

				<Button
					variant="secondary"
					onClick={ () =>
						setFields( [
							...fields,
							{
								uuid: window.crypto.randomUUID(),
								label: '',
								slug: '',
								description: '',
								type: 'text',
								visible: true,
							},
						] )
					}
				>
					{ __( 'Add Field' ) }
				</Button>
			</VStack>
		</>
	);
};

// Register the plugin.
registerPlugin( 'create-content-model-page-settings', {
	render: CreateContentModelPageSettings,
} );
