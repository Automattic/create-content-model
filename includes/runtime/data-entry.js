import { registerBoundGroupExtractor } from './src/register-bound-group-extractor';
import { registerContentLocking } from './src/register-content-locking';
import { registerFallbackValueClearer } from './src/register-fallback-value-clearer';
import { registerFieldsUI } from './src/register-fields-ui';

registerBoundGroupExtractor();
registerFieldsUI();
registerContentLocking();
registerFallbackValueClearer();
