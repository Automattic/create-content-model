import { PluginDocumentSettingPanel } from '@wordpress/editor';
import {
	Button,
	PanelRow,
	Modal,
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
import { seen, unseen, blockDefault } from '@wordpress/icons';

import EditFieldForm from './edit-field';
import { POST_TYPE_NAME } from '../constants';

export const FieldsUI = function () {
	const [ isFieldsOpen, setFieldsOpen ] = useState( false );

	const [ meta ] = useEntityProp( 'postType', POST_TYPE_NAME, 'meta' );

	// Saving the fields as serialized JSON because I was tired of fighting the REST API.
	const fields = meta?.fields ? JSON.parse( meta.fields ) : [];

	// Add UUID to fields
	fields.forEach( ( field ) => {
		if ( ! field.uuid ) {
			field.uuid = crypto.randomUUID();
		}
	} );

	return (
		<>
			<PluginDocumentSettingPanel
				name="create-content-model-field-settings"
				title={ __( 'Post Meta' ) }
				className="create-content-model-field-settings"
			>
				{ fields.length > 0 && (
					<ItemGroup isBordered isSeparated>
						{ fields.map( ( field ) => (
							<Item key={ field.uuid }>
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
									{ ! field.visible &&
										field.type.indexOf( 'core' ) > -1 && (
											<FlexItem>
												<Icon icon={ blockDefault } />
											</FlexItem>
										) }
									{ ! field.visible &&
										field.type.indexOf( 'core' ) < 0 && (
											<FlexItem>
												<Icon icon={ unseen } />
											</FlexItem>
										) }
								</Flex>
							</Item>
						) ) }
					</ItemGroup>
				) }

				<PanelRow>
					<Button
						variant="secondary"
						onClick={ () => setFieldsOpen( true ) }
					>
						{ __( 'Manage Fields' ) }
					</Button>
				</PanelRow>

				{ isFieldsOpen && (
					<Modal
						title={ __( 'Post Meta' ) }
						size="large"
						onRequestClose={ () => setFieldsOpen( false ) }
					>
						<FieldsList />
					</Modal>
				) }
			</PluginDocumentSettingPanel>
		</>
	);
};

const FieldsList = () => {
	const [ meta, setMeta ] = useEntityProp(
		'postType',
		POST_TYPE_NAME,
		'meta'
	);

	// Saving the fields as serialized JSON because I was tired of fighting the REST API.
	const fields = meta?.fields ? JSON.parse( meta.fields ) : [];

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
			<VStack spacing={ 2 }>
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
						onMoveUp={ ( movedField ) => {
							const index = fields.findIndex(
								( f ) => f.uuid === movedField.uuid
							);
							const newFields = [ ...fields ];
							newFields.splice( index, 1 );
							newFields.splice( index - 1, 0, movedField );
							setFields( newFields );
						} }
						onMoveDown={ ( movedField ) => {
							const index = fields.findIndex(
								( f ) => f.uuid === movedField.uuid
							);
							const newFields = [ ...fields ];
							newFields.splice( index, 1 );
							newFields.splice( index + 1, 0, movedField );
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
								uuid: crypto.randomUUID(),
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
