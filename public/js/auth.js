document.querySelectorAll('[data-password-toggle]').forEach(button => {
    const input = document.getElementById(button.getAttribute('aria-controls'));
    if (!input) return;
    button.hidden = false;
    button.addEventListener('click', () => {
        const show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        button.setAttribute('aria-pressed', String(show));
        button.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
        const icon = button.querySelector('i');
        icon.classList.toggle('fa-eye', !show);
        icon.classList.toggle('fa-eye-slash', show);
    });
});

document.querySelectorAll('form[data-submit-loading]').forEach(form => {
    const button = form.querySelector('button[type="submit"]');
    const label = button.querySelector('[data-submit-label]');
    const icon = button.querySelector('[data-submit-icon]');
    const spinner = button.querySelector('[data-submit-spinner]');
    const originalLabel = label.textContent;

    form.addEventListener('submit', event => {
        if (button.disabled) {
            event.preventDefault();
            return;
        }
        if (event.defaultPrevented) return;
        button.disabled = true;
        button.setAttribute('aria-busy', 'true');
        label.textContent = button.dataset.loadingLabel;
        icon.hidden = true;
        spinner.hidden = false;
    });

    window.addEventListener('pageshow', () => {
        button.disabled = false;
        button.removeAttribute('aria-busy');
        label.textContent = originalLabel;
        icon.hidden = false;
        spinner.hidden = true;
    });
});

const deliveryNotice = document.querySelector('[data-email-delivery]');
if (deliveryNotice?.dataset.emailDelivery === 'queued') {
    const started = Date.now();
    const checkDelivery = async () => {
        if (Date.now() - started > 180000) {
            deliveryNotice.textContent = 'Email is taking longer than expected. Check your spam folder or request another code.';
            return;
        }
        try {
            const response = await fetch(deliveryNotice.dataset.statusUrl, { headers: { Accept: 'application/json' }, cache: 'no-store' });
            if (!response.ok) return;
            const { status } = await response.json();
            const messages = {
                sent: 'Your code email has been sent. Check your inbox or spam folder.',
                failed: "We couldn't send your code. Request another code after one minute, or contact your administrator.",
                expired: 'This sign-in request has expired. Return to sign in to request a new code.',
            };
            if (messages[status]) {
                deliveryNotice.textContent = messages[status];
                deliveryNotice.classList.toggle('alert-success', status === 'sent');
                return;
            }
        } catch {
            // A temporary network failure must not interrupt code entry.
        }
        window.setTimeout(checkDelivery, 3000);
    };
    window.setTimeout(checkDelivery, 1500);
}
