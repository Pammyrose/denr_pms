(() => {
    const active = new Map();

    const stop = button => {
        const state = active.get(button);
        if (!state) return;
        button.replaceChildren(state.content);
        button.disabled = state.disabled;
        if (state.busy === null) button.removeAttribute('aria-busy');
        else button.setAttribute('aria-busy', state.busy);
        button.classList.remove('pms-button-loading');
        active.delete(button);
    };

    window.PMSButtonLoading = {
        isBusy: button => active.has(button),
        start(button, label = 'Saving…') {
            if (!button || active.has(button)) return;
            const content = document.createDocumentFragment();
            const state = { content, disabled: button.disabled, busy: button.getAttribute('aria-busy') };
            while (button.firstChild) content.appendChild(button.firstChild);
            active.set(button, state);

            const spinner = document.createElement('span');
            spinner.className = 'pms-button-spinner';
            spinner.setAttribute('aria-hidden', 'true');
            const text = document.createElement('span');
            text.textContent = label;
            button.append(spinner, text);
            button.disabled = true;
            button.setAttribute('aria-busy', 'true');
            button.classList.add('pms-button-loading');
        },
        stop,
    };

    document.addEventListener('submit', event => {
        const form = event.target;
        if (!form.matches('form[data-save-loading]')) return;
        const button = event.submitter || form.querySelector('button[type="submit"]');
        if (active.has(button)) {
            event.preventDefault();
            return;
        }
        if (!event.defaultPrevented) window.PMSButtonLoading.start(button);
    });

    window.addEventListener('pageshow', () => {
        for (const button of active.keys()) stop(button);
    });
})();
