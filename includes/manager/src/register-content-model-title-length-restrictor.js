import { registerPlugin } from '@wordpress/plugins';
import { useContentModelNameLengthRestrictor } from './hooks/use-content-model-name-length-restrictor';

export const registerContentModelLengthRestrictor = () => {
	registerPlugin( 'content-model-title-length-restrictor', {
		render: () => {
			// eslint-disable-next-line react-hooks/rules-of-hooks
			useContentModelNameLengthRestrictor();
		},
	} );
};
