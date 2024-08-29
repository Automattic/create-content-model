import { registerPlugin } from '@wordpress/plugins';
import { PluginDocumentSettingPanel } from '@wordpress/editor';
import { TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useLayoutEffect, useRef } from '@wordpress/element';
import { useEntityProp } from '@wordpress/core-data';

const CreateContentModelCptSettings = function () {
	const [ meta, setMeta ] = useEntityProp(
		'postType',
		window.contentModelFields.postType,
		'meta'
	);

	const [ title, setTitle ] = useEntityProp(
		'postType',
		window.contentModelFields.postType,
		'title'
	);

	const lastTitle = useRef( title );

	useLayoutEffect( () => {
		if ( title !== lastTitle.current ) {
			lastTitle.current = title;
			setMeta( { ...meta, plural_label: `${ title }s` } );
		}
	}, [ title, meta, setMeta ] );

	return (
		<>
			<PluginDocumentSettingPanel
				name="create-content-model-post-settings"
				title={ __( 'Post Type' ) }
				className="create-content-model-post-settings"
			>
				<TextControl
					label={ __( 'Singular Label' ) }
					value={ title }
					onChange={ setTitle }
					help={ __( 'This is synced with the post title.' ) }
				/>
				<TextControl
					label={ __( 'Plural Label' ) }
					value={ meta.plural_label }
					onChange={ ( value ) =>
						setMeta( { ...meta, plural_label: value } )
					}
					help={ __(
						'This is the label that will be used for the plural form of the post type.'
					) }
				/>
			</PluginDocumentSettingPanel>
		</>
	);
};

// Register the plugin.
registerPlugin( 'create-content-model-cpt-settings', {
	render: CreateContentModelCptSettings,
} );
