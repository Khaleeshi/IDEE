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

// Saisie vocale en français (IA-03) : la reconnaissance vocale du navigateur, sans frais pour IDEE.
const Recognition = window.SpeechRecognition || window.webkitSpeechRecognition;
if (Recognition) {
    document.querySelectorAll('textarea[data-dictation]').forEach((field) => {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'mt-1 inline-flex min-h-10 items-center gap-1 rounded-lg px-2 text-sm font-medium text-brand-700';
        button.textContent = '🎤 Dicter';
        button.title = 'La dictée passe par le service vocal de votre navigateur.';
        field.insertAdjacentElement('afterend', button);

        let recognition = null;
        button.addEventListener('click', () => {
            if (recognition) {
                recognition.stop();
                return;
            }

            recognition = new Recognition();
            recognition.lang = 'fr-FR';
            recognition.continuous = true;
            recognition.interimResults = false;
            recognition.onresult = (event) => {
                for (let i = event.resultIndex; i < event.results.length; i++) {
                    if (event.results[i].isFinal) {
                        const text = event.results[i][0].transcript.trim();
                        field.value = (field.value.trim() ? field.value.trim() + ' ' : '') + text;
                        field.dispatchEvent(new Event('input'));
                    }
                }
            };
            recognition.onend = () => {
                recognition = null;
                button.textContent = '🎤 Dicter';
            };
            recognition.start();
            button.textContent = '⏹ Arrêter la dictée';
        });
    });
}

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
