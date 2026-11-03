(() => {
    const main = document.querySelector('main');
    if (!main || !document.getElementById('dashboardFilterForm')) return;

    let activeRequest = null;
    let requestVersion = 0;
    let debounceTimer;

    const initializeResults = () => {
        window.initializeDashboardPerformance();
        window.initializeDashboardTrend();
    };
    const setStatus = (message) => {
        const status = document.getElementById('dashboardFilterStatus');
        if (status) status.textContent = message;
    };

    async function updateResults(url, pushHistory, version) {
        const controller = new AbortController();
        activeRequest = controller;
        const focusedId = document.activeElement?.id;
        try {
            const response = await fetch(url, {
                credentials: 'same-origin',
                signal: controller.signal,
                headers: { Accept: 'text/html', 'X-Dashboard-Partial': '1' },
            });
            if (version !== requestVersion) return;
            if (response.redirected) {
                window.location.assign(response.url);
                return;
            }
            if (!response.ok) throw new Error(`Dashboard request failed (${response.status})`);
            const html = await response.text();
            if (version !== requestVersion) return;
            const fragment = document.createElement('template');
            fragment.innerHTML = html;
            if (!fragment.content.querySelector('#dashboardFilterForm')) {
                throw new Error('Dashboard results were missing.');
            }
            main.querySelectorAll('.modal').forEach(modal => window.bootstrap?.Modal.getInstance(modal)?.dispose());
            main.replaceChildren(fragment.content);
            initializeResults();
            if (pushHistory) window.history.pushState(null, '', url);
            if (focusedId) document.getElementById(focusedId)?.focus({ preventScroll: true });
        } catch (error) {
            if (error.name !== 'AbortError' && version === requestVersion) {
                setStatus('Could not update results. Change a filter to retry.');
                console.warn('Dashboard filters could not be updated.', error);
            }
        } finally {
            if (version === requestVersion) {
                activeRequest = null;
                main.removeAttribute('aria-busy');
            }
        }
    }

    function scheduleUpdate(url, pushHistory = true) {
        clearTimeout(debounceTimer);
        activeRequest?.abort();
        const version = ++requestVersion;
        main.setAttribute('aria-busy', 'true');
        setStatus('Updating results…');
        debounceTimer = setTimeout(() => updateResults(url, pushHistory, version), 150);
    }

    function applyFilters(form) {
        const url = new URL(form.action, window.location.href);
        url.search = new URLSearchParams(new FormData(form)).toString();
        scheduleUpdate(url.href);
    }
    main.addEventListener('change', event => {
        const form = event.target.closest('#dashboardFilterForm');
        if (form && event.target.matches('select')) applyFilters(form);
    });
    main.addEventListener('submit', event => {
        if (event.target.id !== 'dashboardFilterForm') return;
        event.preventDefault();
        applyFilters(event.target);
    });
    window.addEventListener('popstate', () => scheduleUpdate(window.location.href, false));
    window.setInterval?.(() => {
        if (document.visibilityState === 'visible' && !activeRequest) {
            scheduleUpdate(window.location.href, false);
        }
    }, 30000);

    async function loadList(body) {
        if (!body || body.dataset.loaded === 'true' || body.dataset.loading === 'true') return;
        body.dataset.loading = 'true';
        body.setAttribute('aria-busy', 'true');
        body.innerHTML = '<p class="p-5 mb-0 text-center text-muted">Loading records...</p>';
        // Read the displayed form, including defaults absent from the address bar.
        const form = document.getElementById('dashboardFilterForm');
        const url = new URL(form.action, window.location.href);
        url.search = new URLSearchParams(new FormData(form)).toString();
        try {
            const response = await fetch(url.href, {
                credentials: 'same-origin',
                headers: { Accept: 'text/html', 'X-Dashboard-List': body.dataset.dashboardList },
            });
            if (!body.isConnected) return;
            if (response.redirected) {
                window.location.assign(response.url);
                return;
            }
            if (!response.ok) throw new Error('Could not load dashboard records.');
            const html = await response.text();
            if (!body.isConnected) return;
            body.innerHTML = html;
            body.dataset.loaded = 'true';
        } catch (error) {
            if (body.isConnected) {
                body.innerHTML = '<div class="p-5 text-center"><p>Could not load records.</p><button type="button" class="btn btn-outline-primary" data-dashboard-list-retry>Retry</button></div>';
            }
        } finally {
            body.dataset.loading = 'false';
            body.removeAttribute('aria-busy');
        }
    }
    main.addEventListener('show.bs.modal', event => {
        loadList(event.target.querySelector('[data-dashboard-list]'));
    });

    const openRow = row => {
        if (row?.dataset.href && row.dataset.href !== '#') window.location.assign(row.dataset.href);
    };
    main.addEventListener('click', event => {
        if (event.target.closest('[data-dashboard-list-retry]')) {
            loadList(event.target.closest('[data-dashboard-list]'));
            return;
        }
        const button = event.target.closest('[data-pap-sector-toggle], [data-indicator-sector-toggle]');
        if (button) {
            const type = button.hasAttribute('data-pap-sector-toggle') ? 'pap' : 'indicator';
            const groupId = button.getAttribute(`data-${type}-sector-toggle`);
            const expanded = button.getAttribute('aria-expanded') === 'true';
            main.querySelectorAll(`[data-${type}-sector-row="${groupId}"]`).forEach(row => row.classList.toggle('d-none', expanded));
            button.setAttribute('aria-expanded', String(!expanded));
            return;
        }
        if (!event.target.closest('a, button, input, select')) openRow(event.target.closest('.dashboard-link-row'));
    });
    main.addEventListener('keydown', event => {
        if (!event.target.matches('.dashboard-link-row') || !['Enter', ' '].includes(event.key)) return;
        event.preventDefault();
        openRow(event.target);
    });
    initializeResults();
})();
