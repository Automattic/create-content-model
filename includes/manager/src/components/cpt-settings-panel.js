import { PluginDocumentSettingPanel } from '@wordpress/editor';
import { TextControl, Dashicon } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import {
	useLayoutEffect,
	useRef,
	createInterpolateElement,
} from '@wordpress/element';
import { useEntityProp } from '@wordpress/core-data';
import { POST_TYPE_NAME } from '../constants';

function getPlural( singular ) {
	if ( singular.endsWith( 'y' ) ) {
		return `${ singular.slice( 0, -1 ) }ies`;
	}
	if ( singular.endsWith( 's' ) || singular.endsWith( 'ch' ) ) {
		return `${ singular }es`;
	}
	return `${ singular }s`;
}

export const CPTSettingsPanel = function () {
	const [ meta, setMeta ] = useEntityProp(
		'postType',
		POST_TYPE_NAME,
		'meta'
	);

	const [ title, setTitle ] = useEntityProp(
		'postType',
		POST_TYPE_NAME,
		'title'
	);

	const lastTitle = useRef( title );

	useLayoutEffect( () => {
		if ( title !== lastTitle.current ) {
			lastTitle.current = title;
			setMeta( { ...meta, plural_label: getPlural( title ) } );
		}
	}, [ title, meta, setMeta ] );

	const dashicon = meta.icon.replace( 'dashicons-', '' );

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
					help={ __( 'This is synced with the post title' ) }
				/>
				<TextControl
					label={ __( 'Plural Label' ) }
					value={ meta.plural_label }
					onChange={ ( value ) =>
						setMeta( { ...meta, plural_label: value } )
					}
					help={ __(
						'This is the label that will be used for the plural form of the post type'
					) }
				/>
				<div style={ { position: 'relative' } }>
					<TextControl
						label={ __( 'Icon name' ) }
						value={ meta.icon }
						onChange={ ( icon ) => setMeta( { ...meta, icon } ) }
						help={ createInterpolateElement(
							__(
								'The icon for the post type. <a>See reference</a>'
							),
							{
								a: (
									// eslint-disable-next-line jsx-a11y/anchor-has-content
									<a
										target="_blank"
										href="https://developer.wordpress.org/resource/dashicons/"
										rel="noreferrer"
									/>
								),
							}
						) }
					/>
					{ dashicon && (
						<div
							style={ {
								position: 'absolute',
								top: '24px',
								right: '6px',
								height: '32px',
								display: 'flex',
								alignItems: 'center',
							} }
						>
							{ ' ' }
							<Dashicon icon={ dashicon } />{ ' ' }
						</div>
					) }
				</div>
			</PluginDocumentSettingPanel>
		</>
	);
};
