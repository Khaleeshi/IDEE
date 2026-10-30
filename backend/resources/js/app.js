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

// Notifications push : abonnement de ce téléphone, à la demande du membre (NO-01).
const pushButton = document.querySelector('[data-push-key]');
if (pushButton && 'serviceWorker' in navigator && 'PushManager' in window && 'Notification' in window) {
    pushButton.hidden = false;
    pushButton.addEventListener('click', async () => {
        if (await Notification.requestPermission() !== 'granted') {
            pushButton.textContent = 'Notifications refusées : autorisez-les dans les réglages du navigateur';
            return;
        }

        const key = pushButton.dataset.pushKey.replace(/-/g, '+').replace(/_/g, '/');
        const raw = atob(key + '='.repeat((4 - (key.length % 4)) % 4));
        const registration = await navigator.serviceWorker.ready;
        const subscription = await registration.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: Uint8Array.from(raw, (character) => character.charCodeAt(0)),
        });

        const response = await fetch(pushButton.dataset.pushUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify(subscription),
        });

        pushButton.textContent = response.ok ? 'Notifications activées sur ce téléphone' : 'Activation impossible, réessayez plus tard';
        pushButton.disabled = response.ok;
    });
}
