// Frontend and API share the same WAMP domain: this relative path leads from
// /frontend/ to /public/. Adjust it if you change the folder layout.
window.FinanceConfig = Object.freeze({
    apiBaseUrl: '../public',
    currencyLabel: 'Ar',
    requestTimeoutMs: 12000
});
