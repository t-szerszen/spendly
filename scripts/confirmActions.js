document.addEventListener('DOMContentLoaded', () => {
    document.addEventListener('click', (event) => {
        const button = event.target.closest('[data-confirm]');

        if (!button) {
            return;
        }

        const message = button.dataset.confirm || '';
        if (!message) {
            return;
        }

        if (!window.confirm(message)) {
            event.preventDefault();
        }
    });
});
