import { registerPlugin } from '@wordpress/plugins';
import { FieldsUI } from './components/fields-ui';

export const registerFieldsUI = () => {
	registerPlugin( 'create-content-model-fields-ui', {
		render: FieldsUI,
	} );
};
