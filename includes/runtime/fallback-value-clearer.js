import { useLayoutEffect } from '@wordpress/element';
import { registerPlugin } from '@wordpress/plugins';
import { useEntityProp } from '@wordpress/core-data';
import { store as blockEditorStore } from '@wordpress/block-editor';
import { useSelect, useDispatch } from '@wordpress/data';

/**
 * This allows the user to edit values that are bound to an attribute.
 * There is a bug in the Bindings API preventing this from working,
 * so here's our workaround.
 *
 * TODO Remove when Gutneberg 19.2 gets released.
 *
 * See https://github.com/Automattic/create-content-model/issues/63 for the problem.
 */
const CreateContentModelFallbackValueClearer = () => {
	const [ meta, setMeta ] = useEntityProp(
		'postType',
		window.contentModelFields.postType,
		'meta'
	);

	const dispatcher = useDispatch( blockEditorStore );

	const blockToMetaMap = useSelect( ( select ) => {
		const blocks = select( blockEditorStore ).getBlocks();
		const map = {};

		const processBlock = ( block ) => {
			const bindings = block.attributes?.metadata?.bindings || {};
			Object.entries( bindings ).forEach( ( [ , binding ] ) => {
				if ( binding.source === 'core/post-meta' ) {
					if ( ! map[ block.clientId ] ) {
						map[ block.clientId ] = [];
					}
					map[ block.clientId ].push( {
						metaKey: binding.args.key,
						blockName: block.attributes.metadata.name,
					} );
				}
			} );

			// Process inner blocks if they exist, like core/button is inside core/buttons.
			if ( block.innerBlocks && block.innerBlocks.length > 0 ) {
				block.innerBlocks.forEach( processBlock );
			}
		};

		blocks.forEach( processBlock );

		return map;
	}, [] );

	useLayoutEffect( () => {
		Object.entries( blockToMetaMap ).forEach(
			( [ blockId, metaInfos ] ) => {
				metaInfos.forEach( ( { metaKey, blockName } ) => {
					const value = meta[ metaKey ];
					if (
						value ===
						window.contentModelFields.FALLBACK_VALUE_PLACEHOLDER
					) {
						setMeta( { [ metaKey ]: '' } );

						dispatcher.updateBlockAttributes( blockId, {
							placeholder: `Enter a value for ${ blockName }`,
						} );
					}
				} );
			}
		);
	}, [ meta, setMeta, blockToMetaMap, dispatcher ] );

	return null;
};

registerPlugin( 'create-content-model-fallback-value-clearer', {
	render: CreateContentModelFallbackValueClearer,
} );
