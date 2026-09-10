import { initClientRegistration } from './client-registration';
import { initAdmin } from './admin';
import { initCustomSelects } from './custom-select';
import { initCookieBanner } from './cookie-banner';
import { initErrorPage } from './error-page';

document.addEventListener('DOMContentLoaded', () => {
    initCustomSelects(document);
    initClientRegistration();
    initAdmin();
    initCookieBanner();
    initErrorPage();
});