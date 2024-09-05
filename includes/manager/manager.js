import { registerAttributeBinder } from './src/register-attribute-binder';
import { registerContentModelNameValidation } from './src/register-content-model-name-validation';
import { registerCPTSettingsPanel } from './src/register-cpt-settings-panel';
import { registerDefaultValuePlaceholderChanger } from './src/register-default-value-placeholder-changer';
import { registerFieldsUI } from './src/register-fields-ui';

registerAttributeBinder();
registerCPTSettingsPanel();
registerFieldsUI();
registerContentModelNameValidation();
registerDefaultValuePlaceholderChanger();
