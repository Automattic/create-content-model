import { registerPlugin } from '@wordpress/plugins';
import { useFallbackValueClearer } from './hooks/use-fallback-value-clearer';

export const registerFallbackValueClearer = () => {
	registerPlugin( 'create-content-model-fallback-value-clearer', {
		render: () => {
			// eslint-disable-next-line react-hooks/rules-of-hooks
			useFallbackValueClearer();
		},
	} );
};
