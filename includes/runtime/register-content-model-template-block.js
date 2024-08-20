import { registerBlockType } from '@wordpress/blocks';
import ServerSideRender from '@wordpress/server-side-render';
import { __ } from '@wordpress/i18n';

registerBlockType( window.BLOCK_NAME, {
	title: __( 'Content model template' ),
	edit( props ) {
		return (
			<ServerSideRender
				block={ window.BLOCK_NAME }
				attributes={ props.attributes }
			/>
		);
	},

	save: () => null,
} );
