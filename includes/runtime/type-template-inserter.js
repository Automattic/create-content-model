function typeTemplateInserter( wp ) {
	const el = wp.element.createElement;

	wp.blocks.registerBlockType( 'data-types/type-template', {
		title: wp.i18n.__( 'Content model template' ),
		edit( props ) {
			return el( wp.editor.ServerSideRender, {
				block: 'data-types/type-template',
				attributes: props.attributes,
			} );
		},

		save: () => null,
	} );
}

setTimeout( typeTemplateInserter, 800, window.wp );
