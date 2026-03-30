export function showToast(message, type = 'success') {
    if (!message) {
        return;
    }

    const existingToast = document.querySelector('.projects-toast');
    if (existingToast) {
        existingToast.remove();
    }

    const toast = document.createElement('div');
    toast.className = `projects-toast projects-toast_${type}`;
    toast.textContent = message;
    toast.dataset.controller = 'flash-alert';
    toast.dataset.flashAlertDelayValue = type === 'error' ? '5000' : '3000';

    document.body.appendChild(toast);
}

export function storePendingToast(message, type = 'success') {
    if (!message) {
        return;
    }

    sessionStorage.setItem('projects.pendingToast', JSON.stringify({
        message,
        type,
    }));
}

export function flushPendingToast() {
    const toastPayload = sessionStorage.getItem('projects.pendingToast');
    if (!toastPayload) {
        return;
    }

    sessionStorage.removeItem('projects.pendingToast');

    let toastData = null;

    try {
        toastData = JSON.parse(toastPayload);
    } catch (error) {
        return;
    }

    if (!toastData?.message) {
        return;
    }

    showToast(toastData.message, toastData.type ?? 'success');
}
