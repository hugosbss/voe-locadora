import { initClientRegistration } from './client-registration';
import { initAdmin } from './admin';
import { initToasts } from './toasts';
import { initCustomSelects } from './custom-select';
import { initCookieBanner } from './cookie-banner';
import { initErrorPage } from './error-page';

document.addEventListener('DOMContentLoaded', () => {
    initCustomSelects(document);
    initClientRegistration();
    initAdmin();
    initToasts(document);
    initCookieBanner();
    initErrorPage();
});