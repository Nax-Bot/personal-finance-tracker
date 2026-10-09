(function () {
    'use strict';

    const api = window.FinanceApi;
    const main = document.getElementById('main-content');
    const dialog = document.getElementById('app-dialog');
    const toastRegion = document.getElementById('toast-region');
    const sectionLabel = document.getElementById('topbar-section');
    const currencyLabel = window.FinanceConfig?.currencyLabel || 'Ar';

    const state = {
        view: 'dashboard',
        month: new Date().toISOString().slice(0, 7),
        accounts: [],
        categories: [],
        summary: null,
        transactions: [],
        filters: { account: '', category: '' },
        loading: true,
        error: null
    };

    const titles = {
        dashboard: 'Overview',
        transactions: 'Transactions',
        accounts: 'Accounts',
        categories: 'Categories',
        summary: 'Monthly summary'
    };

    function node(tag, className, text) {
        const item = document.createElement(tag);
        if (className) item.className = className;
        if (text !== undefined && text !== null) item.textContent = String(text);
        return item;
    }

    function append(parent, ...children) {
        children.filter(Boolean).forEach((child) => parent.append(child));
        return parent;
    }

    function makeButton(label, className, action, title) {
        const button = node('button', `button ${className || 'button-secondary'}`, label);
        button.type = 'button';
        if (action) button.addEventListener('click', action);
        if (title) button.title = title;
        return button;
    }

    function formatMoney(value) {
        const amount = Number(value);
        const safeAmount = Number.isFinite(amount) ? amount : 0;
        return `${new Intl.NumberFormat('en-US', { maximumFractionDigits: 2 }).format(safeAmount)} ${currencyLabel}`;
    }

    function monthLabel(month) {
        const date = new Date(`${month}-01T12:00:00`);
        return new Intl.DateTimeFormat('en-US', { month: 'long', year: 'numeric' }).format(date);
    }

    function dateLabel(value) {
        if (!value) return 'Unknown date';
        const date = new Date(`${String(value).slice(0, 10)}T12:00:00`);
        return Number.isNaN(date.getTime()) ? String(value) : new Intl.DateTimeFormat('en-US', { dateStyle: 'medium' }).format(date);
    }

    function accountName(id) {
        return state.accounts.find((account) => Number(account.id) === Number(id))?.name || 'Account';
    }

    function categoryName(id) {
        return state.categories.find((category) => Number(category.id) === Number(id))?.name || 'Category';
    }

    function monthInput(onChange) {
        const input = node('input', 'month-control');
        input.type = 'month';
        input.value = state.month;
        input.setAttribute('aria-label', 'Choose month');
        input.addEventListener('change', async () => {
            if (!input.value) return;
            state.month = input.value;
            try {
                await loadMonth();
                if (onChange) onChange();
                else render();
            } catch (error) {
                showToast(error.message, 'error');
                render();
            }
        });
        return input;
    }

    function heading(title, subtitle, actions, eyebrow) {
        const wrapper = node('div', 'page-heading');
        const copy = node('div');
        append(copy, node('p', 'eyebrow', eyebrow || 'PERSONAL FINANCE TRACKER'), node('h1', '', title), node('p', 'page-subtitle', subtitle));
        const actionBox = node('div', 'heading-actions');
        (actions || []).forEach((action) => actionBox.append(action));
        append(wrapper, copy, actionBox);
        return wrapper;
    }

    function panel(title, description, content, action) {
        const card = node('section', 'panel');
        const head = node('div', 'panel-heading');
        const copy = node('div');
        append(copy, node('h2', '', title));
        if (description) copy.append(node('p', 'panel-description', description));
        append(head, copy, action || null);
        append(card, head, content);
        return card;
    }

    function emptyState(title, description, buttonLabel, action) {
        const box = node('div', 'empty-state');
        append(box, node('span', 'empty-icon', '＋'), node('h3', '', title), node('p', '', description));
        if (buttonLabel && action) box.append(makeButton(buttonLabel, 'button-primary button-small', action));
        return box;
    }

    function statCard(title, value, hint, icon, tone) {
        const card = node('article', 'stat-card');
        const top = node('div', 'stat-top');
        append(top, node('span', '', title), node('span', 'stat-icon', icon));
        append(card, top, node('p', `stat-value ${tone || ''}`, value), node('p', 'stat-hint', hint));
        return card;
    }

    function showToast(message, kind) {
        const toast = node('div', `toast ${kind ? `is-${kind}` : ''}`, message);
        toast.setAttribute('role', kind === 'error' || kind === 'warning' ? 'alert' : 'status');
        toastRegion.append(toast);
        window.setTimeout(() => toast.remove(), 6500);
    }

    function showBudgetWarning(warning) {
        const limit = warning.monthly_limit ?? warning.monthy_limit;
        const exceeded = warning.exceeded_by;
        const message = Number.isFinite(Number(exceeded))
            ? `Budget exceeded: the limit of ${formatMoney(limit)} was exceeded by ${formatMoney(exceeded)}. The transaction was saved.`
            : `This category’s budget has been exceeded. The transaction was saved.`;
        showToast(message, 'warning');
    }

    function showLoading() {
        main.replaceChildren();
        const loading = node('div', 'loading-panel');
        append(loading, node('span', 'spinner'), node('p', '', 'Loading your data…'));
        main.append(loading);
    }

    function showLoadError(message) {
        main.replaceChildren();
        const error = node('div', 'error-panel panel');
        append(error, node('span', 'empty-icon', '!'), node('h2', '', 'Data is not available'), node('p', '', message));
        error.append(makeButton('Try again', 'button-primary', refreshAll));
        main.append(error);
    }

    async function loadMonth() {
        try {
            const [summary, monthlyResult] = await Promise.all([
                api.summary(state.month),
                api.transactionsForMonth(state.month)
            ]);
            state.summary = summary;
            state.transactions = Array.isArray(monthlyResult?.transactions)
                ? monthlyResult.transactions
                : (Array.isArray(monthlyResult) ? monthlyResult : []);
            state.error = null;
        } catch (error) {
            state.summary = null;
            state.transactions = [];
            state.error = error.message;
            throw error;
        }
    }

    async function refreshAll() {
        state.loading = true;
        showLoading();
        try {
            const [accounts, categories] = await Promise.all([api.accounts(), api.categories()]);
            state.accounts = Array.isArray(accounts) ? accounts : [];
            state.categories = Array.isArray(categories) ? categories : [];
            await loadMonth();
            state.loading = false;
            render();
        } catch (error) {
            state.loading = false;
            state.error = error.message;
            showLoadError(error.message);
        }
    }

    async function reloadAndRender(successMessage) {
        try {
            const [accounts, categories] = await Promise.all([api.accounts(), api.categories()]);
            state.accounts = Array.isArray(accounts) ? accounts : [];
            state.categories = Array.isArray(categories) ? categories : [];
            await loadMonth();
            render();
            if (successMessage) showToast(successMessage, 'success');
        } catch (error) {
            showToast(error.message, 'error');
        }
    }

    function monthlyExpenses() {
        return new Map((state.summary?.expenses_by_category || []).map((item) => [Number(item.id), Number(item.amount) || 0]));
    }

    function budgetProgress(category, spent) {
        const limit = Number(category.monthly_limit);
        if (!Number.isFinite(limit) || category.monthly_limit === null || category.monthly_limit === '') return null;
        const percent = limit > 0 ? (spent / limit) * 100 : (spent > 0 ? 100 : 0);
        return { limit, spent, percent, over: spent > limit, ratio: Math.min(100, Math.max(0, percent)) };
    }

    function makeBudgetItem(category, spent) {
        const row = node('div', 'budget-item');
        const progress = budgetProgress(category, spent);
        if (!progress) {
            append(row, node('div', 'budget-line', category.name), node('p', 'budget-unlimited', `Spending: ${formatMoney(spent)} · No monthly limit`));
            return row;
        }

        const top = node('div', 'budget-line');
        append(top, node('span', 'budget-name', category.name), node('span', 'budget-amount', `${formatMoney(spent)} / ${formatMoney(progress.limit)}`));
        const track = node('div', 'progress-track');
        const fill = node('div', `progress-fill ${progress.over ? 'is-over' : progress.percent >= 80 ? 'is-warning' : ''}`);
        fill.style.width = `${progress.ratio}%`;
        fill.setAttribute('role', 'progressbar');
        fill.setAttribute('aria-valuenow', String(Math.min(100, Math.round(progress.percent))));
        fill.setAttribute('aria-valuemin', '0');
        fill.setAttribute('aria-valuemax', '100');
        fill.setAttribute('aria-label', `Budget ${category.name}`);
        track.append(fill);
        const foot = node('div', `budget-foot ${progress.over ? 'is-over' : ''}`);
        const status = progress.over
            ? `Over by: ${formatMoney(progress.spent - progress.limit)}`
            : `${Math.round(progress.percent)} % used`;
        append(foot, node('span', '', status), node('span', '', progress.over ? 'Needs attention' : 'This month'));
        append(row, top, track, foot);
        return row;
    }

    function transactionRow(transaction, withActions) {
        const row = node('article', `transaction-row ${withActions ? 'has-actions' : ''}`);
        const isIncome = transaction.type === 'income';
        const symbol = node('span', `transaction-symbol ${isIncome ? '' : 'is-expense'}`, isIncome ? '↙' : '↗');
        symbol.setAttribute('aria-hidden', 'true');
        const text = node('div');
        const label = transaction.note?.trim() || categoryName(transaction.category_id);
        append(text, node('p', 'transaction-title', label), node('p', 'transaction-meta', `${dateLabel(transaction.transaction_date)} · ${accountName(transaction.account_id)} · ${categoryName(transaction.category_id)}`));
        const amount = node('div', `transaction-amount ${isIncome ? 'is-income' : 'is-expense'}`, `${isIncome ? '+' : '−'}${formatMoney(transaction.amount)}`);

        if (withActions) {
            const actions = node('div', 'row-actions');
            actions.append(makeButton('Edit', 'button-secondary', () => openTransactionDialog(transaction), 'Edit this transaction'));
            actions.append(makeButton('Delete', 'button-danger', () => removeTransaction(transaction), 'Delete this transaction'));
            const amountAndActions = node('div');
            append(amountAndActions, amount, actions);
            append(row, symbol, text, amountAndActions);
        } else {
            append(row, symbol, text, amount);
        }
        return row;
    }

    function renderDashboard() {
        const actions = [monthInput(), makeButton('＋ New transaction', 'button-primary', () => openTransactionDialog())];
        const content = node('div');
        content.append(heading('Hello, welcome', `Here is an overview of your finances for ${monthLabel(state.month)}.`, actions, 'YOUR FINANCIAL SPACE'));

        const totalBalance = state.accounts.reduce((total, account) => total + (Number(account.balance) || 0), 0);
        const summary = state.summary || {};
        const stats = node('section', 'stats-grid');
        stats.setAttribute('aria-label', 'Monthly financial indicators');
        append(stats,
            statCard('Account balance', formatMoney(totalBalance), `${state.accounts.length} account${state.accounts.length > 1 ? 's' : ''}`, '◉'),
            statCard('Income this month', formatMoney(summary.total_income), monthLabel(state.month), '↙', 'tone-positive'),
            statCard('Expenses this month', formatMoney(summary.total_expense), monthLabel(state.month), '↗', 'tone-negative'),
            statCard('Net balance this month', formatMoney(summary.net_balance), 'Income minus expenses', '＝', Number(summary.net_balance) < 0 ? 'tone-negative' : 'tone-positive')
        );
        content.append(stats);

        const dashboardGrid = node('div', 'dashboard-grid');
        const mainColumn = node('div', 'dashboard-main-column');
        const sideColumn = node('div', 'dashboard-side-column');
        const budgetList = node('div', 'budget-list');
        const expenses = monthlyExpenses();
        const budgetCategories = state.categories.filter((category) => category.monthly_limit !== null || expenses.has(Number(category.id)));
        if (budgetCategories.length) {
            budgetCategories.forEach((category) => budgetList.append(makeBudgetItem(category, expenses.get(Number(category.id)) || 0)));
        } else {
            budgetList.append(emptyState('No budgets yet', 'Create a category to start tracking expenses.', 'Create a category', () => openCategoryDialog()));
        }
        mainColumn.append(panel('Budget tracking', 'Expenses compared to monthly limits', budgetList, makeButton('Manage', 'button-quiet', () => switchView('categories'))));

        const recentList = node('div', 'list-stack');
        if (state.transactions.length) {
            state.transactions.slice(0, 5).forEach((transaction) => recentList.append(transactionRow(transaction, false)));
        } else {
            recentList.append(emptyState('No transactions this month', 'Add a transaction to see your activity here.', 'Add a transaction', () => openTransactionDialog()));
        }
        sideColumn.append(panel('Recent activity', monthLabel(state.month), recentList, makeButton('View all', 'button-quiet', () => switchView('transactions'))));

        const categoriesList = node('div', 'budget-list');
        const categoryExpenses = (summary.expenses_by_category || []).slice(0, 5);
        if (categoryExpenses.length) {
            const max = Math.max(...categoryExpenses.map((item) => Number(item.amount) || 0), 1);
            categoryExpenses.forEach((item) => {
                const wrap = node('div', 'budget-item');
                const line = node('div', 'budget-line');
                append(line, node('span', 'budget-name', item.name), node('span', 'budget-amount', formatMoney(item.amount)));
                const track = node('div', 'progress-track');
                const fill = node('div', 'progress-fill');
                fill.style.width = `${Math.max(3, (Number(item.amount) / max) * 100)}%`;
                track.append(fill);
                append(wrap, line, track);
                categoriesList.append(wrap);
            });
        } else {
            categoriesList.append(node('p', 'panel-description', 'Expenses by category will appear here after your first transaction.'));
        }
        sideColumn.append(panel('Expenses by category', 'Breakdown for the selected month', categoriesList));
        append(dashboardGrid, mainColumn, sideColumn);
        content.append(dashboardGrid);
        main.replaceChildren(content);
    }

    function renderAccounts() {
        const content = node('div');
        content.append(heading('Your accounts', 'View balances and manage your financial accounts.', [makeButton('＋ Add an account', 'button-primary', () => openAccountDialog())]));
        const grid = node('div', 'collection-grid');
        if (!state.accounts.length) {
            grid.append(emptyState('No accounts yet', 'Add an account to track your balance and record transactions.', 'Create an account', () => openAccountDialog()));
        } else {
            state.accounts.forEach((account) => {
                const card = node('article', 'account-card');
                const kind = node('span', 'account-kind', accountTypeLabel(account.type));
                append(card, kind, node('h2', 'card-title', account.name), node('p', 'card-balance', formatMoney(account.balance)));
                const actions = node('div', 'card-actions');
                actions.append(makeButton('Edit', 'button-secondary button-small', () => openAccountDialog(account)));
                actions.append(makeButton('Delete', 'button-danger button-small', () => removeAccount(account)));
                card.append(actions);
                grid.append(card);
            });
        }
        content.append(grid);
        main.replaceChildren(content);
    }

    function accountTypeLabel(type) {
        return ({ checking: 'Checking account', savings: 'Savings', cash: 'Cash' })[type] || String(type || 'Account');
    }

    function renderCategories() {
        const content = node('div');
        content.append(heading('Categories & budgets', 'Organize your expenses and keep an eye on your limits.', [monthInput(), makeButton('＋ Add a category', 'button-primary', () => openCategoryDialog())]));
        const grid = node('div', 'collection-grid');
        const expenses = monthlyExpenses();
        if (!state.categories.length) {
            grid.append(emptyState('No categories', 'Create a category to organize your transactions and track a budget.', 'Create a category', () => openCategoryDialog()));
        } else {
            state.categories.forEach((category) => {
                const card = node('article', 'category-card');
                const spent = expenses.get(Number(category.id)) || 0;
                const progress = budgetProgress(category, spent);
                const top = node('div', 'card-topline');
                append(top, node('span', 'pill', `This month: ${formatMoney(spent)}`));
                if (progress?.over) top.append(node('span', 'pill is-danger', 'Budget exceeded'));
                else if (progress && progress.percent >= 80) top.append(node('span', 'pill is-warning', 'Limit nearly reached'));
                append(card, top, node('h2', 'card-title', category.name));
                if (progress) {
                    card.append(makeBudgetItem(category, spent));
                } else {
                    card.append(node('p', 'budget-unlimited', 'No monthly limit set.'));
                }
                const actions = node('div', 'card-actions');
                actions.append(makeButton('Edit', 'button-secondary button-small', () => openCategoryDialog(category)));
                actions.append(makeButton('Delete', 'button-danger button-small', () => removeCategory(category)));
                card.append(actions);
                grid.append(card);
            });
        }
        content.append(grid);
        main.replaceChildren(content);
    }

    function renderTransactions() {
        const content = node('div');
        content.append(heading('Transactions', 'Browse and filter this month’s transactions.', [monthInput(), makeButton('＋ Add', 'button-primary', () => openTransactionDialog())]));
        const toolbar = node('div', 'toolbar');
        const accountSelect = node('select');
        accountSelect.setAttribute('aria-label', 'Filter by account');
        addOption(accountSelect, '', 'All accounts');
        state.accounts.forEach((account) => addOption(accountSelect, account.id, account.name));
        accountSelect.value = state.filters.account;
        accountSelect.addEventListener('change', () => { state.filters.account = accountSelect.value; renderTransactions(); });

        const categorySelect = node('select');
        categorySelect.setAttribute('aria-label', 'Filter by category');
        addOption(categorySelect, '', 'All categories');
        state.categories.forEach((category) => addOption(categorySelect, category.id, category.name));
        categorySelect.value = state.filters.category;
        categorySelect.addEventListener('change', () => { state.filters.category = categorySelect.value; renderTransactions(); });

        const accountField = node('div', 'field filter-field');
        append(accountField, node('label', '', 'Account'), accountSelect);
        const categoryField = node('div', 'field filter-field');
        append(categoryField, node('label', '', 'Category'), categorySelect);
        append(toolbar, accountField, categoryField);
        content.append(toolbar);

        const filtered = state.transactions.filter((transaction) => {
            const matchesAccount = !state.filters.account || String(transaction.account_id) === state.filters.account;
            const matchesCategory = !state.filters.category || String(transaction.category_id) === state.filters.category;
            return matchesAccount && matchesCategory;
        });
        content.append(node('p', 'filters-summary', `${filtered.length} transaction${filtered.length > 1 ? 's' : ''} · ${monthLabel(state.month)}`));
        const list = node('section', 'panel list-panel');
        list.setAttribute('aria-label', 'Transactions list');
        if (!filtered.length) {
            list.append(emptyState(state.transactions.length ? 'No results with these filters' : 'No transactions this month', state.transactions.length ? 'Try another account or category.' : 'Create your first transaction to start tracking.', state.transactions.length ? null : 'Create a transaction', state.transactions.length ? null : () => openTransactionDialog()));
        } else {
            filtered.forEach((transaction) => list.append(transactionRow(transaction, true)));
        }
        content.append(list);
        main.replaceChildren(content);
    }

    function renderSummary() {
        const content = node('div');
        content.append(heading('Monthly summary', `Your financial summary for ${monthLabel(state.month)}.`, [monthInput(), makeButton('＋ New transaction', 'button-primary', () => openTransactionDialog())]));
        const summary = state.summary || {};
        const stats = node('section', 'stats-grid');
        append(stats,
            statCard('Income', formatMoney(summary.total_income), monthLabel(state.month), '↙', 'tone-positive'),
            statCard('Expenses', formatMoney(summary.total_expense), monthLabel(state.month), '↗', 'tone-negative'),
            statCard('Net balance', formatMoney(summary.net_balance), 'Income minus expenses', '＝', Number(summary.net_balance) < 0 ? 'tone-negative' : 'tone-positive'),
            statCard('Transactions', String(state.transactions.length), 'For the selected month', '▤')
        );
        content.append(stats);

        const grid = node('div', 'dashboard-grid');
        const byCategory = node('div', 'budget-list');
        const rows = summary.expenses_by_category || [];
        if (rows.length) {
            const max = Math.max(...rows.map((item) => Number(item.amount) || 0), 1);
            rows.forEach((item) => {
                const row = node('div', 'budget-item');
                const line = node('div', 'budget-line');
                append(line, node('span', 'budget-name', item.name), node('span', 'budget-amount', formatMoney(item.amount)));
                const track = node('div', 'progress-track');
                const fill = node('div', 'progress-fill');
                fill.style.width = `${Math.max(3, (Number(item.amount) / max) * 100)}%`;
                track.append(fill);
                const category = state.categories.find((entry) => Number(entry.id) === Number(item.id));
                if (category && category.monthly_limit !== null) {
                    row.append(makeBudgetItem(category, Number(item.amount)));
                } else {
                    append(row, line, track);
                }
                byCategory.append(row);
            });
        } else {
            byCategory.append(emptyState('No expenses recorded', 'Expenses will appear here once a transaction is added.'));
        }
        grid.append(panel('Expenses by category', 'Category comparison for this month', byCategory));

        const history = node('div', 'list-stack');
        if (state.transactions.length) state.transactions.forEach((transaction) => history.append(transactionRow(transaction, true)));
        else history.append(emptyState('Month without activity', 'Choose another month or add a transaction.', 'Create a transaction', () => openTransactionDialog()));
        grid.append(panel('Month history', monthLabel(state.month), history, makeButton('View transactions', 'button-quiet', () => switchView('transactions'))));
        content.append(grid);
        main.replaceChildren(content);
    }

    function addOption(select, value, label) {
        const option = node('option', '', label);
        option.value = String(value);
        select.append(option);
    }

    function render() {
        sectionLabel.textContent = titles[state.view] || titles.dashboard;
        document.title = `${titles[state.view] || titles.dashboard} — Finances`;
        document.querySelectorAll('[data-view]').forEach((item) => {
            item.classList.toggle('is-active', item.dataset.view === state.view);
        });

        if (state.error && !state.summary) {
            showLoadError(state.error);
            return;
        }
        if (state.view === 'accounts') renderAccounts();
        else if (state.view === 'categories') renderCategories();
        else if (state.view === 'transactions') renderTransactions();
        else if (state.view === 'summary') renderSummary();
        else renderDashboard();
    }

    function switchView(view) {
        if (!titles[view]) return;
        state.view = view;
        render();
        main.focus({ preventScroll: true });
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function addField(form, labelText, name, type, value, options = {}) {
        const wrap = node('div', `field ${options.className || ''}`.trim());
        const id = `field-${name}`;
        const label = node('label', '', labelText);
        label.htmlFor = id;
        let input;
        if (type === 'select') {
            input = node('select');
            addOption(input, '', options.placeholder || 'Choose…');
            (options.choices || []).forEach((choice) => addOption(input, choice.value, choice.label));
            input.value = value === null || value === undefined ? '' : String(value);
        } else if (type === 'textarea') {
            input = node('textarea');
            input.value = value || '';
        } else {
            input = node('input');
            input.type = type;
            if (value !== null && value !== undefined) input.value = String(value);
        }
        input.id = id;
        input.name = name;
        if (options.required) input.required = true;
        if (options.min !== undefined) input.min = String(options.min);
        if (options.step !== undefined) input.step = String(options.step);
        if (options.maxLength) input.maxLength = options.maxLength;
        if (options.autocomplete) input.autocomplete = options.autocomplete;
        append(wrap, label, input);
        if (options.help) wrap.append(node('small', '', options.help));
        form.append(wrap);
        return input;
    }

    function openDialog(title, subtitle, fields, onSubmit) {
        dialog.replaceChildren();
        const inner = node('div', 'dialog-inner');
        const head = node('div', 'dialog-heading');
        const copy = node('div');
        const dialogTitle = node('h2', '', title);
        dialogTitle.id = 'dialog-title';
        append(copy, dialogTitle, node('p', '', subtitle));
        const close = makeButton('×', 'button-secondary dialog-close', () => dialog.close(), 'Close window');
        close.setAttribute('aria-label', 'Close window');
        append(head, copy, close);
        const form = node('form', 'dialog-form');
        form.noValidate = false;
        const formMessage = node('div', 'form-message');
        formMessage.hidden = true;
        formMessage.setAttribute('role', 'alert');

        fields(form);
        const actions = node('div', 'dialog-actions');
        actions.append(makeButton('Cancel', 'button-secondary', () => dialog.close()));
        const submit = node('button', 'button button-primary', 'Save');
        submit.type = 'submit';
        actions.append(submit);
        append(form, formMessage, actions);
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            formMessage.hidden = true;
            submit.disabled = true;
            submit.textContent = 'Saving…';
            try {
                await onSubmit(new FormData(form));
                dialog.close();
            } catch (error) {
                formMessage.textContent = error.message;
                formMessage.hidden = false;
                submit.disabled = false;
                submit.textContent = 'Save';
            }
        });
        append(inner, head, form);
        dialog.append(inner);
        dialog.showModal();
    }

    async function saveAndRefresh(successMessage, operation, responseHandler) {
        const result = await operation();
        await reloadAndRender();
        if (responseHandler) responseHandler(result);
        else showToast(successMessage, 'success');
    }

    function openAccountDialog(account) {
        const editing = Boolean(account);
        openDialog(editing ? 'Edit account' : 'New account', editing ? 'Update the account details.' : 'Add an account you want to track.', (form) => {
            addField(form, 'Account name', 'name', 'text', account?.name || '', { required: true, maxLength: 100, autocomplete: 'off' });
            addField(form, 'Account type', 'type', 'select', account?.type || 'checking', { required: true, choices: [
                { value: 'checking', label: 'Checking account' },
                { value: 'savings', label: 'Savings' },
                { value: 'cash', label: 'Cash' }
            ] });
            addField(form, editing ? 'Current balance' : 'Initial balance', 'balance', 'number', account?.balance ?? '0.00', { required: true, min: 0, step: '0.01' });
        }, async (data) => {
            const payload = { name: String(data.get('name')).trim(), type: data.get('type'), balance: Number(data.get('balance')) };
            if (!payload.name || !Number.isFinite(payload.balance) || payload.balance < 0) throw new Error('Enter a name and a balance of zero or more.');
            await saveAndRefresh(editing ? 'Account updated.' : 'Account created.', () => editing ? api.updateAccount(account.id, payload) : api.createAccount(payload));
        });
    }

    function openCategoryDialog(category) {
        const editing = Boolean(category);
        openDialog(editing ? 'Edit category' : 'New category', editing ? 'Adjust its name or monthly limit.' : 'Create a category to classify your expenses.', (form) => {
            addField(form, 'Category name', 'name', 'text', category?.name || '', { required: true, maxLength: 100, autocomplete: 'off' });
            addField(form, 'Monthly limit (Ar)', 'monthly_limit', 'number', category?.monthly_limit ?? '', {
                min: 0,
                step: '0.01',
                help: editing ? 'Leave empty to keep the current limit. The backend does not yet allow removing an existing limit.' : 'Leave empty if this category has no limit.'
            });
        }, async (data) => {
            const name = String(data.get('name')).trim();
            const rawLimit = String(data.get('monthly_limit') || '').trim();
            if (!name) throw new Error('The category name is required.');
            const payload = { name };
            if (!editing || rawLimit !== '') payload.monthly_limit = rawLimit === '' ? null : Number(rawLimit);
            if (payload.monthly_limit !== undefined && payload.monthly_limit !== null && (!Number.isFinite(payload.monthly_limit) || payload.monthly_limit < 0)) {
                throw new Error('The limit must be zero or a positive number.');
            }
            await saveAndRefresh(editing ? 'Category updated.' : 'Category created.', () => editing ? api.updateCategory(category.id, payload) : api.createCategory(payload));
        });
    }

    function openTransactionDialog(transaction) {
        if (!state.accounts.length) {
            showToast('Create an account first before adding a transaction.', 'warning');
            switchView('accounts');
            return;
        }
        if (!state.categories.length) {
            showToast('Create a category first to classify the transaction.', 'warning');
            switchView('categories');
            return;
        }
        const editing = Boolean(transaction);
        openDialog(editing ? 'Edit transaction' : 'New transaction', 'Required fields must be filled in to save the transaction.', (form) => {
            addField(form, 'Type', 'type', 'select', transaction?.type || 'expense', { required: true, choices: [
                { value: 'expense', label: 'Expense' },
                { value: 'income', label: 'Income' }
            ] });
            addField(form, 'Account', 'account_id', 'select', transaction?.account_id || '', { required: true, choices: state.accounts.map((account) => ({ value: account.id, label: `${account.name} · ${formatMoney(account.balance)}` })) });
            addField(form, 'Category', 'category_id', 'select', transaction?.category_id || '', { required: true, choices: state.categories.map((category) => ({ value: category.id, label: category.name })) });
            addField(form, 'Amount (Ar)', 'amount', 'number', transaction?.amount ?? '', { required: true, min: '0.01', step: '0.01' });
            addField(form, 'Date', 'transaction_date', 'date', transaction?.transaction_date || new Date().toISOString().slice(0, 10), { required: true });
            addField(form, 'Note (optional)', 'note', 'textarea', transaction?.note || '', { maxLength: 255 });
        }, async (data) => {
            const amount = Number(data.get('amount'));
            if (!Number.isFinite(amount) || amount <= 0) throw new Error('The amount must be greater than zero.');
            const payload = {
                account_id: Number(data.get('account_id')),
                category_id: Number(data.get('category_id')),
                type: data.get('type'),
                amount,
                transaction_date: data.get('transaction_date'),
                note: String(data.get('note') || '').trim() || null
            };
            let response;
            await saveAndRefresh(editing ? 'Transaction updated.' : 'Transaction saved.', async () => {
                response = editing ? await api.updateTransaction(transaction.id, payload) : await api.createTransaction(payload);
                return response;
            }, (result) => {
                if (result?.warning) showBudgetWarning(result.warning);
                else showToast(editing ? 'Transaction updated.' : 'Transaction saved.', 'success');
            });
        });
    }

    async function removeAccount(account) {
        if (!window.confirm(`Delete account “${account.name}”?`)) return;
        try {
            await api.deleteAccount(account.id);
            await reloadAndRender('Account deleted.');
        } catch (error) {
            showToast(error.message, 'error');
        }
    }

    async function removeCategory(category) {
        if (!window.confirm(`Delete category “${category.name}”?`)) return;
        try {
            await api.deleteCategory(category.id);
            await reloadAndRender('Category deleted.');
        } catch (error) {
            showToast(error.message, 'error');
        }
    }

    async function removeTransaction(transaction) {
        if (!window.confirm('Delete this transaction? The account balance will be recalculated by the backend.')) return;
        try {
            await api.deleteTransaction(transaction.id);
            await reloadAndRender('Transaction deleted.');
        } catch (error) {
            showToast(error.message, 'error');
        }
    }

    document.body.addEventListener('click', (event) => {
        const viewButton = event.target.closest('[data-view]');
        if (viewButton) {
            event.preventDefault();
            switchView(viewButton.dataset.view);
        }
    });

    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) dialog.close();
    });

    refreshAll();
}());
