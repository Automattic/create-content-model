import { InspectorControls } from '@wordpress/block-editor';
import { getBlockVariations } from '@wordpress/blocks';
import { useMemo } from '@wordpress/element';
import { Button, PanelBody, BaseControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

const matchesMetadata = ( a, b ) => {
	return (
		a.metadata &&
		b.metadata &&
		a.metadata.name === b.metadata.name &&
		a.metadata.content_model_slug === b.metadata.content_model_slug
	);
};

export const BlockVariationUpdater = ( {
	name,
	attributes,
	setAttributes,
} ) => {
	const variation = useMemo( () => {
		const variations = getBlockVariations( name );

		return variations.find( ( tentativeVariation ) =>
			matchesMetadata( tentativeVariation.attributes, attributes )
		);
	}, [ name, attributes ] );

	return (
		<InspectorControls>
			<PanelBody title="Content Model" initialOpen>
				<BaseControl
					__nextHasNoMarginBottom
					className="edit-site-push-changes-to-global-styles-control"
					help={ __(
						'Updates this block variation to use the latest version from the content model.'
					) }
				>
					<BaseControl.VisualLabel>
						{ __( 'Sync variation' ) }
					</BaseControl.VisualLabel>
					<Button
						__next40pxDefaultSize
						variant="secondary"
						accessibleWhenDisabled
						disabled={ ! variation }
						onClick={ () => {
							setAttributes( variation.attributes );
						} }
					>
						{ __( 'Sync variation' ) }
					</Button>
				</BaseControl>
			</PanelBody>
		</InspectorControls>
	);
};
