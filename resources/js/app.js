import { initClientRegistration } from './client-registration';
import { initAdmin } from './admin';
import { initCustomSelects } from './custom-select';

document.addEventListener('DOMContentLoaded', () => {
    initCustomSelects(document);
    initClientRegistration();
    initAdmin();
});