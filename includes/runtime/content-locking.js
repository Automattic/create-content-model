import { registerPlugin } from '@wordpress/plugins';
import { useEffect } from '@wordpress/element';
import { dispatch } from '@wordpress/data';
import { useDispatch } from '@wordpress/data';
import { store as blockEditorStore } from '@wordpress/block-editor';

/**
 * Our base plugin component.
 * @returns CreateContentModelPageSettings
 */
const CreateContentModelContentLocking = function () {
	const fields = contentModelFields.fields;

	const blocks = wp.data.select( 'core/block-editor' ).getBlocks();

	const { setBlockEditingMode } = useDispatch( blockEditorStore );

	if ( ! fields ) {
		return null;
	}

	useEffect( () => {
		if ( blocks.length > 0 ) {
			parseBlocks( blocks, setBlockEditingMode );
		}
	}, [ blocks, setBlockEditingMode ] );

	return;
};
const SUPPORTED_BLOCKS = [
	'core/group',
	'core/paragraph',
	'core/heading',
	'core/image',
	'core/button',
];

const parseBlocks = ( blocks, setEditMode, forceEnabled = false ) => {
	blocks.forEach( ( block ) => {
		if (
			block.innerBlocks.length > 0 &&
			block.attributes.metadata?.bindings
		) {
			// This is a group block with bindings, probably content.
			setEditMode( block.clientId, '' );

			if ( block.innerBlocks ) {
				parseBlocks( block.innerBlocks, setEditMode, true );
			}
		} else if ( block.innerBlocks.length > 0 ) {
			// This is a group block with no bindings, probably layout.
			dispatch( 'core/block-editor' ).updateBlock( block.clientId, {
				...block,
				attributes: {
					...block.attributes,
					templateLock: 'contentOnly',
				},
			} );
			setEditMode( block.clientId, 'disabled' );

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

// Register the plugin.
registerPlugin( 'create-content-model-content-locking', {
	render: CreateContentModelContentLocking,
} );
