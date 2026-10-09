(function () {
    'use strict';

    const baseUrl = (window.FinanceConfig?.apiBaseUrl || '').replace(/\/$/, '');
    const timeoutMs = window.FinanceConfig?.requestTimeoutMs || 12000;

    async function request(path, options = {}) {
        const controller = new AbortController();
        const timeout = window.setTimeout(() => controller.abort(), timeoutMs);
        const headers = new Headers(options.headers || {});
        headers.set('Accept', 'application/json');

        if (options.body !== undefined) {
            headers.set('Content-Type', 'application/json');
        }

        try {
            const response = await fetch(`${baseUrl}${path}`, {
                ...options,
                headers,
                signal: controller.signal,
                cache: 'no-store'
            });
            const raw = await response.text();
            let data = null;

            if (raw.trim() !== '') {
                try {
                    data = JSON.parse(raw);
                } catch {
                    throw new Error('Le serveur a renvoyé une réponse illisible.');
                }
            }

            if (!response.ok) {
                const message = data && typeof data === 'object'
                    ? (response.status >= 500
                        ? 'Le serveur a rencontré un problème. Réessaie, puis vérifie les journaux PHP si cela continue.'
                        : (data.error || data.message || 'La requête a échoué.'))
                    : 'La requête a échoué.';
                const error = new Error(message);
                error.status = response.status;
                error.payload = data;
                throw error;
            }

            return data;
        } catch (error) {
            if (error.name === 'AbortError') {
                throw new Error('La requête prend trop de temps. Réessaie dans un instant.');
            }
            if (error instanceof TypeError) {
                throw new Error('Impossible de joindre l’API. Vérifie que WAMP est démarré et que le chemin du projet est correct.');
            }
            throw error;
        } finally {
            window.clearTimeout(timeout);
        }
    }

    const jsonBody = (method, body) => ({ method, body: JSON.stringify(body) });

    window.FinanceApi = Object.freeze({
        request,
        accounts: () => request('/accounts'),
        account: (id) => request(`/accounts/${encodeURIComponent(id)}`),
        createAccount: (data) => request('/accounts', jsonBody('POST', data)),
        updateAccount: (id, data) => request(`/accounts/${encodeURIComponent(id)}`, jsonBody('PATCH', data)),
        deleteAccount: (id) => request(`/accounts/${encodeURIComponent(id)}`, { method: 'DELETE' }),
        categories: async () => {
            const result = await request('/categories');
            return Array.isArray(result) ? result : (Array.isArray(result?.data) ? result.data : []);
        },
        createCategory: (data) => request('/categories', jsonBody('POST', data)),
        updateCategory: (id, data) => request(`/categories/${encodeURIComponent(id)}`, jsonBody('PATCH', data)),
        deleteCategory: (id) => request(`/categories/${encodeURIComponent(id)}`, { method: 'DELETE' }),
        transactions: () => request('/transaction'),
        transaction: (id) => request(`/transaction/${encodeURIComponent(id)}`),
        createTransaction: (data) => request('/transaction', jsonBody('POST', data)),
        updateTransaction: (id, data) => request(`/transaction/${encodeURIComponent(id)}`, jsonBody('PATCH', data)),
        deleteTransaction: (id) => request(`/transaction/${encodeURIComponent(id)}`, { method: 'DELETE' }),
        transactionsForMonth: (month) => request(`/transaction/month/${encodeURIComponent(month)}`),
        summary: (month) => request(`/summary?month=${encodeURIComponent(month)}`)
    });
}());
