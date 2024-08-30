import { useEffect } from '@wordpress/element';
import { dispatch, useDispatch } from '@wordpress/data';
import { store as blockEditorStore } from '@wordpress/block-editor';
import { SUPPORTED_BLOCK_ATTRIBUTES } from '../constants';

const SUPPORTED_BLOCKS = Object.keys( SUPPORTED_BLOCK_ATTRIBUTES );

export const useContentLocking = function () {
	const blocks = wp.data.select( 'core/block-editor' ).getBlocks();

	const currentBlock = wp.data
		.select( 'core/block-editor' )
		.getSelectedBlock();

	const { setBlockEditingMode } = useDispatch( blockEditorStore );

	useEffect( () => {
		if ( blocks.length > 0 ) {
			parseBlocks( blocks, setBlockEditingMode );
		}
	}, [ blocks, setBlockEditingMode, currentBlock ] );
};

const parseBlocks = ( blocks, setEditMode, forceEnabled = false ) => {
	blocks.forEach( ( block ) => {
		if (
			block.innerBlocks.length > 0 &&
			block.attributes.metadata?.bindings
		) {
			// This is a group block with bindings, probably content.
			setEditMode( block.clientId, 'default' );

			if ( block.innerBlocks ) {
				parseBlocks( block.innerBlocks, setEditMode, true );
			}
		} else if ( block.innerBlocks.length > 0 ) {
			// This is a group block with no bindings, probably layout.

			// First check this container has a bound group block is inside.
			const boundGroup = findBoundGroup( block.innerBlocks );

			if ( ! boundGroup ) {
				// Then, lock the block.
				dispatch( 'core/block-editor' ).updateBlock( block.clientId, {
					...block,
					attributes: {
						...block.attributes,
						templateLock: 'contentOnly',
					},
				} );
				setEditMode( block.clientId, 'disabled' );
			}
			if ( block.innerBlocks ) {
				parseBlocks( block.innerBlocks, setEditMode );
			}
		} else if (
			( SUPPORTED_BLOCKS.includes( block.name ) &&
				block.attributes.metadata?.bindings ) ||
			forceEnabled
		) {
			setEditMode( block.clientId, '' );
		} else {
			setEditMode( block.clientId, 'disabled' );
		}
	} );
};

const findBoundGroup = ( blocks ) => {
	for ( const block of blocks ) {
		if (
			block.name === 'core/group' &&
			block.attributes.metadata?.bindings
		) {
			return block;
		}
		if ( block.innerBlocks.length > 0 ) {
			const boundGroup = findBoundGroup( block.innerBlocks );
			if ( boundGroup ) {
				return boundGroup;
			}
		}
	}
	return null;
};
