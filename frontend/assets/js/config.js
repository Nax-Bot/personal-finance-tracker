// Frontend et API partagent le même domaine WAMP : ce chemin relatif mène de
// /frontend/ vers /public/. Adapte-le si tu changes l'organisation des dossiers.
window.FinanceConfig = Object.freeze({
    apiBaseUrl: '../public',
    currencyLabel: 'Ar',
    requestTimeoutMs: 12000
});
