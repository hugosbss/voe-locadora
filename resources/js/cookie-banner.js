const STORAGE_KEY = 'locadora-cookie-consent';

export function initCookieBanner(root = document) {
    const banner = root.querySelector('[data-cookie-banner]');
    const accept = root.querySelector('[data-cookie-banner-accept]');
    const reject = root.querySelector('[data-cookie-banner-reject]');

    if (!banner || !accept || !reject) return;

    // Consentimento funcional: apenas cookies estritamente necessários,
    // sem rastreamento. A escolha é recordada localmente.
    if (localStorage.getItem(STORAGE_KEY)) {
        banner.hidden = true;
        return;
    }

    banner.hidden = false;

    accept.addEventListener('click', () => {
        localStorage.setItem(STORAGE_KEY, 'functional-only');
        banner.hidden = true;
    });

    reject.addEventListener('click', () => {
        localStorage.setItem(STORAGE_KEY, 'rejected');
        banner.hidden = true;
    });
}