import { registerPlugin } from '@wordpress/plugins';
import { useContentLocking } from './hooks/use-content-locking';

export const registerContentLocking = () => {
	registerPlugin( 'create-content-model-content-locking', {
		render: () => {
			// eslint-disable-next-line react-hooks/rules-of-hooks
			useContentLocking();
		},
	} );
};
