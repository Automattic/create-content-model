import { useEffect } from '@wordpress/element';
import { useEntityProp } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';
import { store as editorStore } from '@wordpress/block-editor';
import { serialize } from '@wordpress/blocks';
import { POST_TYPE } from '../constants';

export const useBoundGroupExtractor = () => {
	const blocks = useSelect( ( select ) => select( editorStore ).getBlocks() );

	const [ , setMeta ] = useEntityProp( 'postType', POST_TYPE, 'meta' );

	useEffect( () => {
		const boundBlocks = findBoundGroupBlocks( blocks );

		if ( Object.keys( boundBlocks ).length === 0 ) {
			return;
		}

		setMeta( boundBlocks );
	}, [ blocks, setMeta ] );

	return null;
};

const findBoundGroupBlocks = ( blocks, acc = {} ) => {
	for ( const block of blocks ) {
		if ( 'core/group' !== block.name ) {
			continue;
		}

		const binding = block.attributes.metadata?.bindings?.content?.args?.key;

		if ( binding && binding !== 'post_content' ) {
			acc[ binding ] = serialize( block.innerBlocks );
		}

		if ( block.innerBlocks.length > 0 ) {
			acc = findBoundGroupBlocks( block.innerBlocks, acc );
		}
	}

	return acc;
};
