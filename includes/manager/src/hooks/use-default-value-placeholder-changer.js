import { __, sprintf } from '@wordpress/i18n';
import { useEffect } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
import { store as blockEditorStore } from '@wordpress/block-editor';

export const useDefaultValuePlaceholderChanger = () => {
	const boundBlocks = useSelect( ( select ) => {
		const blocks = select( blockEditorStore ).getBlocks();
		const map = {};

		const processBlock = ( block ) => {
			const name = block.attributes?.metadata?.name;

			if ( name ) {
				map[ block.clientId ] = block.attributes.metadata.name;
			}

			// Process inner blocks if they exist, like core/button is inside core/buttons.
			if ( block.innerBlocks && block.innerBlocks.length > 0 ) {
				block.innerBlocks.forEach( processBlock );
			}
		};

		blocks.forEach( processBlock );

		return map;
	}, [] );

	const { updateBlockAttributes } = useDispatch( blockEditorStore );

	useEffect( () => {
		Object.entries( boundBlocks ).forEach( ( [ blockId, blockName ] ) => {
			updateBlockAttributes( blockId, {
				placeholder: sprintf(
					// translators: %s is the block name.
					__( 'Enter the default value for %s' ),
					blockName
				),
			} );
		} );
	}, [ boundBlocks, updateBlockAttributes ] );
};
