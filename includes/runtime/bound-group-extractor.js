import { useEffect } from '@wordpress/element';
import { registerPlugin } from '@wordpress/plugins';
import { useEntityProp } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';
import { store as editorStore } from '@wordpress/block-editor';
import { serialize } from '@wordpress/blocks';

const findBoundBlocks = ( blocks, acc = {} ) => {
	for ( const block of blocks ) {
		if ( 'core/group' !== block.name ) {
			continue;
		}

		const binding = block.attributes.metadata?.bindings?.content?.args?.key;

		if ( binding && binding !== 'post_content' ) {
			acc[ binding ] = serialize( block.innerBlocks );
		}

		if ( block.innerBlocks.length > 0 ) {
			acc = findBoundBlocks( block.innerBlocks, acc );
		}
	}

	return acc;
};

const CreateContentModelBoundGroupExtractor = () => {
	const blocks = useSelect( ( select ) => select( editorStore ).getBlocks() );

	const [ , setMeta ] = useEntityProp(
		'postType',
		window.contentModelFields.postType,
		'meta'
	);

	useEffect( () => {
		const boundBlocks = findBoundBlocks( blocks );

		setMeta( boundBlocks );
	}, [ blocks, setMeta ] );

	return null;
};

registerPlugin( 'create-content-model-bound-group-extractor', {
	render: CreateContentModelBoundGroupExtractor,
} );
