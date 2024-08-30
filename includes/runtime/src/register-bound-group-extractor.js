import { registerPlugin } from '@wordpress/plugins';
import { useBoundGroupExtractor } from './hooks/use-bound-group-extractor';

export const registerBoundGroupExtractor = () => {
	registerPlugin( 'create-content-model-bound-group-extractor', {
		render: () => {
			// eslint-disable-next-line react-hooks/rules-of-hooks
			useBoundGroupExtractor();
		},
	} );
};
