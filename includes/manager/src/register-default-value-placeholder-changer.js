import { registerPlugin } from '@wordpress/plugins';
import { useDefaultValuePlaceholderChanger } from './hooks/use-default-value-placeholder-changer';

export const registerDefaultValuePlaceholderChanger = () => {
	registerPlugin( 'content-model-default-value-placeholder-changer', {
		render: () => {
			// eslint-disable-next-line react-hooks/rules-of-hooks
			useDefaultValuePlaceholderChanger();
		},
	} );
};
