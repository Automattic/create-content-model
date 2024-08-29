import { useLayoutEffect } from '@wordpress/element';
import { registerPlugin } from '@wordpress/plugins';
import { useEntityProp } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';

/**
 * This allows the user to edit values that are bound to an attribute.
 * There is a bug in the Bindings API preventing this from working,
 * so here's our workaround.
 *
 * See https://github.com/Automattic/create-content-model/issues/63 for the problem.
 */
const CreateContentModelFallbackValueClearer = () => {
	const [ meta, setMeta ] = useEntityProp(
		'postType',
		window.contentModelFields.postType,
		'meta'
	);

	const blockToMetaMap = useSelect( ( select ) => {
		const blocks = select( 'core/block-editor' ).getBlocks();
		const map = {};

		blocks.forEach( ( block ) => {
			const bindings = block.attributes?.metadata?.bindings || {};
			Object.entries( bindings ).forEach( ( [ , binding ] ) => {
				if ( binding.source === 'core/post-meta' ) {
					if ( ! map[ block.clientId ] ) {
						map[ block.clientId ] = [];
					}
					map[ block.clientId ].push( {
						metaKey: binding.args.key,
						blockName: block.name,
					} );
				}
			} );
		} );

		return map;
	}, [] );

	useLayoutEffect( () => {
		Object.entries( blockToMetaMap ).forEach( ( [ , metaInfos ] ) => {
			metaInfos.forEach( ( { metaKey, blockName } ) => {
				const value = meta[ metaKey ];
				if (
					value ===
					window.contentModelFields.FALLBACK_VALUE_PLACEHOLDER
				) {
					const placeholderText = `Enter data for ${ metaKey }`;

					if ( blockName === 'core/image' ) {
						// image doesn't need placeholder text
						setMeta( { [ metaKey ]: '' } );
					} else {
						setMeta( { [ metaKey ]: placeholderText } );
					}
				}
			} );
		} );
	}, [ meta, setMeta, blockToMetaMap ] );

	return null;
};

registerPlugin( 'create-content-model-fallback-value-clearer', {
	render: CreateContentModelFallbackValueClearer,
} );
