import { registerAttributeBinder } from './src/register-attribute-binder';
import { registerContentModelLengthRestrictor } from './src/register-content-model-title-length-restrictor';
import { registerCPTSettingsPanel } from './src/register-cpt-settings-panel';
import { registerDefaultValuePlaceholderChanger } from './src/register-default-value-placeholder-changer';
import { registerFieldsUI } from './src/register-fields-ui';

registerAttributeBinder();
registerCPTSettingsPanel();
registerFieldsUI();
registerContentModelLengthRestrictor();
registerDefaultValuePlaceholderChanger();
