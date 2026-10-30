// Compteur de caractères pour les champs qui exigent un minimum (RG-02).
document.querySelectorAll('[data-min-length]').forEach((field) => {
    const counter = document.getElementById(field.dataset.counter);
    const minimum = Number(field.dataset.minLength);

    const update = () => {
        const missing = minimum - field.value.trim().length;
        counter.textContent = missing > 0 ? `Encore ${missing} caractère(s) au minimum` : 'Longueur suffisante';
        counter.classList.toggle('text-brand-700', missing <= 0);
    };

    field.addEventListener('input', update);
    update();
});

// Demande une confirmation avant une action irréversible.
document.querySelectorAll('form[data-confirm]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        if (!window.confirm(form.dataset.confirm)) {
            event.preventDefault();
        }
    });
});

// Application installable et consultable hors connexion.
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch(() => {});
    });
}
