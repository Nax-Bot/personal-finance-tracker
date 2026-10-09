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
                    throw new Error('The server returned an unreadable response.');
                }
            }

            if (!response.ok) {
                const message = data && typeof data === 'object'
                    ? (response.status >= 500
                        ? 'The server encountered a problem. Try again, and check the PHP logs if it persists.'
                        : (data.error || data.message || 'The request failed.'))
                    : 'The request failed.';
                const error = new Error(message);
                error.status = response.status;
                error.payload = data;
                throw error;
            }

            return data;
        } catch (error) {
            if (error.name === 'AbortError') {
                throw new Error('The request is taking too long. Try again in a moment.');
            }
            if (error instanceof TypeError) {
                throw new Error('Unable to reach the API. Check that WAMP is running and that the project path is correct.');
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
