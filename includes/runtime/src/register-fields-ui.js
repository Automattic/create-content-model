import { registerPlugin } from '@wordpress/plugins';
import { FieldsUI } from './components/fields-ui';

export const registerFieldsUI = () => {
	// Register the plugin.
	registerPlugin( 'create-content-model-fields-ui', {
		render: FieldsUI,
	} );
};
