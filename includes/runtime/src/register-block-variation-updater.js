/* eslint-disable camelcase */
import { addFilter } from '@wordpress/hooks';
import { createHigherOrderComponent } from '@wordpress/compose';
import { BlockVariationUpdater } from './components/block-variation-updater';

const withBlockVariationUpdater = createHigherOrderComponent( ( BlockEdit ) => {
	return ( props ) => {
		const { name, content_model_slug } = props.attributes.metadata ?? {};

		const shouldDisplayVariationUpdater = name && content_model_slug;

		return (
			<>
				{ shouldDisplayVariationUpdater && (
					<BlockVariationUpdater { ...props } />
				) }
				<BlockEdit { ...props } />
			</>
		);
	};
}, 'withBlockVariationUpdater' );

export const registerBlockVariationUpdater = () => {
	addFilter(
		'editor.BlockEdit',
		'content-model/block-variation-updater',
		withBlockVariationUpdater
	);
};
