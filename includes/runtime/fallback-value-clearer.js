import { useLayoutEffect } from '@wordpress/element';
import { registerPlugin } from '@wordpress/plugins';
import { useEntityProp } from '@wordpress/core-data';

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

	useLayoutEffect( () => {
		Object.entries( meta ).forEach( ( [ key, value ] ) => {
			if (
				value === window.contentModelFields.FALLBACK_VALUE_PLACEHOLDER
			) {
				setMeta( {
					[ key ]: '',
				} );

				// TODO: Change placeholder to block variation name or post meta key.
			}
		} );
	}, [ meta, setMeta ] );

	return null;
};

registerPlugin( 'create-content-model-fallback-value-clearer', {
	render: CreateContentModelFallbackValueClearer,
} );
