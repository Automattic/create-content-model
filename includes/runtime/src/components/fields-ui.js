import { PluginDocumentSettingPanel } from '@wordpress/editor';
import {
	Button,
	Modal,
	TextControl,
	TextareaControl,
	__experimentalVStack as VStack,
	Card,
	CardBody,
	CardFooter,
} from '@wordpress/components';
import { MediaPlaceholder } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import { useEntityProp } from '@wordpress/core-data';
import { useState } from '@wordpress/element';
import { FIELDS, POST_TYPE } from '../constants';

export const FieldsUI = function () {
	const [ isFieldsOpen, setFieldsOpen ] = useState( false );

	if ( FIELDS.filter( ( field ) => field.visible ).length === 0 ) {
		return null;
	}

	return (
		<PluginDocumentSettingPanel
			name="create-content-model-page-settings"
			title={ __( 'Custom Fields' ) }
			className="create-content-model-page-settings"
		>
			<VStack>
				<FieldsList fields={ FIELDS } />

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
					<FieldsList fields={ FIELDS } />
				</Modal>
			) }
		</PluginDocumentSettingPanel>
	);
};

const FieldsList = ( { fields } ) => {
	return (
		<VStack>
			{ fields
				.filter( ( field ) => field.visible )
				.map( ( field ) => (
					<FieldRow key={ field.slug } field={ field } />
				) ) }
		</VStack>
	);
};

const FieldRow = ( { field } ) => {
	const [ meta, setMeta ] = useEntityProp( 'postType', POST_TYPE, 'meta' );

	const value = meta[ field.slug ] ?? '';

	return (
		<div>
			<FieldInput
				field={ field }
				value={ value }
				saveChanges={ ( slug, newValue ) => {
					setMeta( {
						[ slug ]: newValue,
					} );
				} }
			/>
			<small>
				<em>{ field.description }</em>
			</small>
		</div>
	);
};

const FieldInput = ( { field, isDisabled = false, value, saveChanges } ) => {
	if ( 'image' === field.type ) {
		return (
			<>
				<span
					style={ {
						textTransform: 'uppercase',
						fontSize: '11px',
						marginBottom: 'calc(8px)',
						fontWeight: '500',
					} }
				>
					{ field.label }
				</span>
				{ ! value && (
					<MediaPlaceholder
						allowedTypes={ [ 'image' ] }
						accept="image"
						multiple={ false }
						onSelect={ ( newValue ) =>
							saveChanges( field.slug, newValue.url )
						}
					/>
				) }
				{ value && (
					<Card>
						<CardBody>
							<img
								src={ value }
								alt={ field.label }
								style={ { width: '100%' } }
							/>
						</CardBody>
						<CardFooter>
							<Button
								isDestructive
								onClick={ () => saveChanges( field.slug, '' ) }
							>
								{ __( 'Remove Image' ) }
							</Button>
						</CardFooter>
					</Card>
				) }
			</>
		);
	}

	if ( 'textarea' === field.type ) {
		return (
			<TextareaControl
				label={ field.label }
				readOnly={ isDisabled }
				value={ value }
				onChange={ ( newValue ) => saveChanges( field.slug, newValue ) }
			/>
		);
	}

	return (
		<TextControl
			label={ field.label }
			type={ field.type }
			readOnly={ isDisabled }
			value={ value }
			onChange={ ( newValue ) => saveChanges( field.slug, newValue ) }
		/>
	);
};
