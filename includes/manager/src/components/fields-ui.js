import { PluginDocumentSettingPanel } from '@wordpress/editor';
import {
	Button,
	PanelRow,
	TabPanel,
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
import { seen, blockDefault } from '@wordpress/icons';

import EditFieldForm from './edit-field';
import EditBlockForm from './edit-block';
import { POST_TYPE_NAME } from '../constants';

export const FieldsUI = function () {
	const [ isFieldsOpen, setFieldsOpen ] = useState( false );

	const [ meta ] = useEntityProp( 'postType', POST_TYPE_NAME, 'meta' );

	const fields = meta?.fields ? JSON.parse( meta.fields ) : [];
	const blocks = meta?.blocks ? JSON.parse( meta.blocks ) : [];

	return (
		<>
			<PluginDocumentSettingPanel
				name="create-content-model-field-settings"
				title={ __( 'Post Meta' ) }
				className="create-content-model-field-settings"
			>
				<ItemGroup isBordered isSeparated>
					{ blocks.length > 0 && (
						<>
							{ blocks.map( ( block ) => (
								<Item key={ block.uuid }>
									<Flex>
										<FlexBlock>{ block.label }</FlexBlock>
										<FlexItem>
											<code>{ block.slug }</code>
										</FlexItem>

										<FlexItem>
											<Icon icon={ blockDefault } />
										</FlexItem>
									</Flex>
								</Item>
							) ) }
						</>
					) }
					{ fields.length > 0 && (
						<>
							{ fields.map( ( field ) => (
								<Item key={ field.uuid }>
									<Flex>
										<FlexBlock>{ field.label }</FlexBlock>
										<FlexItem>
											<code>{ field.slug }</code>
										</FlexItem>

										<FlexItem>
											<Icon icon={ seen } />
										</FlexItem>
									</Flex>
								</Item>
							) ) }
						</>
					) }
				</ItemGroup>

				<PanelRow>
					<Button
						variant="secondary"
						onClick={ () => setFieldsOpen( true ) }
					>
						{ __( 'Manage Post Meta' ) }
					</Button>
				</PanelRow>

				{ isFieldsOpen && (
					<Modal
						title={ __( 'Post Meta' ) }
						size="large"
						onRequestClose={ () => setFieldsOpen( false ) }
					>
						<TabPanel
							tabs={ [
								{
									name: 'blocks',
									title: __( 'Block Bindings' ),
									content: <BlocksList />,
								},
								{
									name: 'fields',
									title: __( 'Fields' ),
									content: <FieldsList />,
								},
							] }
						>
							{ ( tab ) => <>{ tab.content }</> }
						</TabPanel>
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
			<p>{ __( 'Custom fields show up in the post sidebar.' ) }</p>
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
					style={ { marginTop: '1rem' } }
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

const BlocksList = () => {
	const [ meta ] = useEntityProp( 'postType', POST_TYPE_NAME, 'meta' );

	const blocks = meta?.blocks ? JSON.parse( meta.blocks ) : [];

	return (
		<>
			<VStack spacing={ 2 }>
				{ blocks.map( ( block ) => (
					<EditBlockForm key={ block.uuid } block={ block } />
				) ) }
			</VStack>
		</>
	);
};
