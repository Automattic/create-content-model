import { addFilter } from '@wordpress/hooks';
import { useMemo } from '@wordpress/element';
import { createHigherOrderComponent } from '@wordpress/compose';
import { useSelect } from '@wordpress/data';
import { store as blockEditorStore } from '@wordpress/block-editor';

import { SUPPORTED_BLOCK_ATTRIBUTES, BINDINGS_KEY } from './constants';
import { AttributeBinderPanel } from './components/attribute-binder-panel';

const withAttributeBinder = createHigherOrderComponent( ( BlockEdit ) => {
	return ( props ) => {
		const { getBlockParentsByBlockName, getBlocksByClientId } =
			useSelect( blockEditorStore );

		const blockParentsByBlockName = getBlockParentsByBlockName(
			props.clientId,
			[ 'core/group' ]
		);

		const parentHasBindings = useMemo( () => {
			return (
				getBlocksByClientId( blockParentsByBlockName ).filter(
					( block ) =>
						Object.keys(
							block?.attributes?.metadata?.[ BINDINGS_KEY ] || {}
						).length > 0
				).length > 0
			);
		}, [ blockParentsByBlockName, getBlocksByClientId ] );

		const shouldDisplayAttributeBinderPanel =
			SUPPORTED_BLOCK_ATTRIBUTES[ props.name ] && ! parentHasBindings;

		return (
			<>
				<BlockEdit { ...props } />
				{ shouldDisplayAttributeBinderPanel && (
					<AttributeBinderPanel { ...props } />
				) }
			</>
		);
	};
}, 'withAttributeBinder' );

export const registerAttributeBinder = () => {
	addFilter(
		'editor.BlockEdit',
		'content-model/attribute-binder',
		withAttributeBinder
	);
};
