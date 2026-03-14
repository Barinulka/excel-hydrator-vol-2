import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['toggle', 'title', 'input', 'submit', 'feedback'];

    toggleChanged() {
        if (!this.hasToggleTarget) {
            return;
        }

        if (this.toggleTarget.checked) {
            this.clearFeedback();
            this.inputTarget.focus();
            this.inputTarget.select();
            return;
        }

        this.clearFeedback();
        this.inputTarget.value = this.titleTarget.textContent.trim();
    }

    async submit(event) {
        event.preventDefault();

        const title = this.inputTarget.value.trim();
        if (title.length === 0) {
            this.showFeedback('Название модели не может быть пустым.', 'error');
            this.inputTarget.focus();
            return;
        }

        this.inputTarget.value = title;
        this.clearFeedback();

        const form = event.currentTarget;
        const formData = new FormData(form);
        this.setLoading(true);

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: formData,
                credentials: 'same-origin',
            });

            const payload = await response.json().catch(() => ({}));

            if (!response.ok) {
                const validationMessage = payload?.errors?.title;
                const message = validationMessage || payload?.message || 'Не удалось обновить название модели.';
                this.showFeedback(message, 'error');
                return;
            }

            const updatedTitle = payload?.title || title;
            this.titleTarget.textContent = updatedTitle;
            this.inputTarget.value = updatedTitle;
            this.showFeedback(payload?.message || 'Название модели обновлено.', 'success');

            if (this.hasToggleTarget) {
                this.toggleTarget.checked = false;
                this.toggleChanged();
            }
        } catch (error) {
            this.showFeedback('Сетевая ошибка. Попробуйте еще раз.', 'error');
        } finally {
            this.setLoading(false);
        }
    }

    setLoading(isLoading) {
        if (this.hasSubmitTarget) {
            this.submitTarget.disabled = isLoading;
            this.submitTarget.textContent = isLoading ? 'Сохранение...' : 'Сохранить';
        }

        this.inputTarget.disabled = isLoading;
    }

    showFeedback(message, type) {
        if (!this.hasFeedbackTarget) {
            return;
        }

        this.feedbackTarget.hidden = false;
        this.feedbackTarget.textContent = message;
        this.feedbackTarget.classList.toggle('model-card__rename-feedback--error', type === 'error');
        this.feedbackTarget.classList.toggle('model-card__rename-feedback--success', type === 'success');
    }

    clearFeedback() {
        if (!this.hasFeedbackTarget) {
            return;
        }

        this.feedbackTarget.hidden = true;
        this.feedbackTarget.textContent = '';
        this.feedbackTarget.classList.remove('model-card__rename-feedback--error');
        this.feedbackTarget.classList.remove('model-card__rename-feedback--success');
    }
}
