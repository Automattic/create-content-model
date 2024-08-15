function typeTemplateInserter( wp ) {
	const el = wp.element.createElement;

	wp.blocks.registerBlockType( window.BLOCK_NAME, {
		title: wp.i18n.__( 'Content model template' ),
		edit( props ) {
			return el( wp.editor.ServerSideRender, {
				block: window.BLOCK_NAME,
				attributes: props.attributes,
			} );
		},

		save: () => null,
	} );
}

setTimeout( typeTemplateInserter, 800, window.wp );
