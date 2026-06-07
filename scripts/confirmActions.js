document.addEventListener('DOMContentLoaded', () => {
    // Globalna obsługa elementów wymagających potwierdzenia przed wykonaniem akcji.
    document.addEventListener('click', (event) => {
        const button = event.target.closest('[data-confirm]');

        if (!button) {
            return;
        }

        const message = button.dataset.confirm || '';
        if (!message) {
            return;
        }

        // Anulowanie okna confirm zatrzymuje domyślną akcję formularza lub linku.
        if (!window.confirm(message)) {
            event.preventDefault();
        }
    });
});
