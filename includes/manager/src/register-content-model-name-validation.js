import { registerPlugin } from '@wordpress/plugins';
import { useContentModelNameValidation } from './hooks/use-content-model-name-validation';

export const registerContentModelNameValidation = () => {
	registerPlugin( 'content-model-name-validation', {
		render: () => {
			// eslint-disable-next-line react-hooks/rules-of-hooks
			useContentModelNameValidation();
		},
	} );
};
