import { registerPlugin } from '@wordpress/plugins';
import { PluginDocumentSettingPanel } from '@wordpress/editor';
import { TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useEntityProp } from '@wordpress/core-data';

const CreateContentModelCptSettings = function () {
	const [ meta, setMeta ] = useEntityProp(
		'postType',
		window.contentModelFields.postType,
		'meta'
	);

	const [ slug, setSlug ] = useEntityProp(
		'postType',
		window.contentModelFields.postType,
		'slug'
	);

	const [ title, setTitle ] = useEntityProp(
		'postType',
		window.contentModelFields.postType,
		'title'
	);

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
			onChange: ( value ) => setTitle( value ),
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
		</>
	);
};

// Register the plugin.
registerPlugin( 'create-content-model-cpt-settings', {
	render: CreateContentModelCptSettings,
} );
