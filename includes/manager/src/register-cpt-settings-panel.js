import { registerPlugin } from '@wordpress/plugins';
import { CPTSettingsPanel } from './components/cpt-settings-panel';

export const registerCPTSettingsPanel = () => {
	registerPlugin( 'create-content-model-cpt-settings-pannel', {
		render: CPTSettingsPanel,
	} );
};
