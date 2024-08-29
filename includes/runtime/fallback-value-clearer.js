import { useLayoutEffect } from '@wordpress/element';
import { registerPlugin } from '@wordpress/plugins';
import { useEntityProp } from '@wordpress/core-data';

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
			}
		} );
	}, [ meta, setMeta ] );

	return null;
};

registerPlugin( 'create-content-model-fallback-value-clearer', {
	render: CreateContentModelFallbackValueClearer,
} );
