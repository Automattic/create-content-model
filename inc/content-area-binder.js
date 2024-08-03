function registerContentAreaInspector( wp ) {
	const loadedPagePostType = wp.data
		.select( 'core/editor' )
		.getCurrentPostType();

	const createHigherOrderComponent = wp.compose.createHigherOrderComponent;
	const { Fragment } = wp.element;
	const { InspectorControls } = wp.blockEditor;
	const { TextControl } = wp.components;
	const PanelBody = wp.components.PanelBody;

	const withCustomInspectorControl = createHigherOrderComponent(
		( BlockEdit ) => {
			return ( props ) => {
				const { attributes, setAttributes, name } = props;

				if ( name !== 'core/group' ) {
					return wp.element.createElement( BlockEdit, props );
				}

				return wp.element.createElement( Fragment, null, [
					wp.element.createElement( InspectorControls, null, [
						wp.element.createElement(
							PanelBody,
							{
								title: 'Content Area',
								initialOpen: true,
							},
							[
								wp.element.createElement( TextControl, {
									key: 'location',
									label: 'location',
									readOnly:
										'data_types' !== loadedPagePostType,
									value: attributes.metadata?.[
										'data-types/binding'
									],
									onChange: ( value ) => {
										setAttributes( {
											...attributes,
											metadata: {
												...( attributes.metadata ??
													{} ),
												'data-types/binding': value,
											},
										} );
									},
								} ),
							]
						),
					] ),
					wp.element.createElement( BlockEdit, props ),
				] );
			};
		},
		'withCustomInspectorControl'
	);

	wp.hooks.addFilter(
		'editor.BlockEdit',
		'data-types/content-area-binder',
		withCustomInspectorControl
	);
}

setTimeout( registerContentAreaInspector, 800, window.wp );
